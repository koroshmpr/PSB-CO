<?php
/**
 * RFQ — private file storage.
 *
 * Files go to uploads/psb-rfq/<bucket>/<random>.<ext>, where <bucket> is derived (HMAC) from the
 * visitor's session token, so every file of one request lands in the same unguessable folder.
 * The base folder gets `.htaccess` (deny all) + `index.php`; files are only served to logged-in
 * staff through a nonce'd admin-post handler. Unclaimed uploads are removed by a daily cron.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Base folder (created + protected on demand). */
function psb_rfq_upload_base( $create = true ) {
	$up   = wp_upload_dir( null, false );
	$base = trailingslashit( $up['basedir'] ) . 'psb-rfq';
	if ( $create && ! file_exists( $base . '/.htaccess' ) ) {
		wp_mkdir_p( $base );
		// Apache 2.2 + 2.4, no directory listing, never execute anything.
		@file_put_contents( $base . '/.htaccess', "Options -Indexes\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n<IfModule mod_php.c>\n\tphp_flag engine off\n</IfModule>\n" ); // phpcs:ignore
		@file_put_contents( $base . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore
		@file_put_contents( $base . '/web.config', '<?xml version="1.0" encoding="UTF-8"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>' ); // phpcs:ignore
	}
	return $base;
}

/** Bucket folder name for a session nonce (rand part of the token). */
function psb_rfq_bucket( $rand ) {
	return substr( psb_rfq_hmac( 'bucket|' . $rand ), 0, 24 );
}

/** Magic-byte check for common types (files are never executed, this is defence in depth). */
function psb_rfq_magic_ok( $path, $ext ) {
	$fh = @fopen( $path, 'rb' ); // phpcs:ignore
	if ( ! $fh ) {
		return false;
	}
	$head = (string) fread( $fh, 1024 ); // phpcs:ignore
	fclose( $fh ); // phpcs:ignore
	if ( false !== stripos( $head, '<?php' ) ) {
		return false;
	}
	$sig = array(
		'pdf'  => array( '%PDF' ),
		'png'  => array( "\x89PNG" ),
		'jpg'  => array( "\xFF\xD8\xFF" ),
		'jpeg' => array( "\xFF\xD8\xFF" ),
		'zip'  => array( "PK\x03\x04", "PK\x05\x06" ),
		'xlsx' => array( "PK\x03\x04" ),
		'docx' => array( "PK\x03\x04" ),
		'xls'  => array( "\xD0\xCF\x11\xE0" ),
		'doc'  => array( "\xD0\xCF\x11\xE0" ),
		'dwg'  => array( 'AC10', 'AC1.' ),
		'rar'  => array( 'Rar!' ),
		'7z'   => array( "7z\xBC\xAF" ),
		'webp' => array( 'RIFF' ),
	);
	if ( ! isset( $sig[ $ext ] ) ) {
		return true;
	}
	foreach ( $sig[ $ext ] as $s ) {
		if ( 'AC1.' === $s ? 0 === strpos( $head, 'AC1' ) : 0 === strpos( $head, $s ) ) {
			return true;
		}
	}
	return false;
}

/** Signed file token returned to the browser after an upload. */
function psb_rfq_file_token( $bucket, $stored, $name, $size ) {
	$payload = psb_rfq_b64e( wp_json_encode( array( 'b' => $bucket, 'f' => $stored, 'n' => $name, 's' => (int) $size ) ) );
	return $payload . '.' . substr( psb_rfq_hmac( 'file|' . $payload ), 0, 32 );
}

/**
 * Verify a file token.
 *
 * @return array|false array( bucket, file, name, size )
 */
function psb_rfq_verify_file_token( $token ) {
	$parts = explode( '.', (string) $token );
	if ( 2 !== count( $parts ) || ! hash_equals( substr( psb_rfq_hmac( 'file|' . $parts[0] ), 0, 32 ), $parts[1] ) ) {
		return false;
	}
	$d = json_decode( (string) psb_rfq_b64d( $parts[0] ), true );
	if ( ! is_array( $d ) || empty( $d['b'] ) || empty( $d['f'] ) || ! preg_match( '/^[a-f0-9]{24}$/', $d['b'] ) || ! preg_match( '/^[a-z0-9]{16}\.[a-z0-9]{1,5}$/', $d['f'] ) ) {
		return false;
	}
	$path = psb_rfq_upload_base( false ) . '/' . $d['b'] . '/' . $d['f'];
	if ( ! is_file( $path ) ) {
		return false;
	}
	return array(
		'bucket' => $d['b'],
		'file'   => $d['f'],
		'name'   => psb_rfq_text( $d['n'], 150 ),
		'size'   => (int) filesize( $path ),
	);
}

/** Absolute path of a stored file record. */
function psb_rfq_file_path( $f ) {
	if ( empty( $f['bucket'] ) || empty( $f['file'] ) || ! preg_match( '/^[a-f0-9]{24}$/', $f['bucket'] ) || ! preg_match( '/^[a-z0-9]{16}\.[a-z0-9]{1,5}$/', $f['file'] ) ) {
		return '';
	}
	$path = psb_rfq_upload_base( false ) . '/' . $f['bucket'] . '/' . $f['file'];
	return is_file( $path ) ? $path : '';
}

/** Admin download URL (nonce'd). */
function psb_rfq_file_url( $post_id, $index ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=psb_rfq_file&post=' . (int) $post_id . '&i=' . (int) $index ), 'psb_rfq_file_' . (int) $post_id );
}

/** Mark a bucket as belonging to a request (survives cleanup). */
function psb_rfq_claim_bucket( $bucket, $post_id ) {
	$dir = psb_rfq_upload_base( false ) . '/' . $bucket;
	if ( is_dir( $dir ) ) {
		@file_put_contents( $dir . '/.claimed', (string) (int) $post_id ); // phpcs:ignore
	}
}

/**
 * Total bytes stored under uploads/psb-rfq/ (cached 10 minutes; uploads add to the cached value).
 *
 * @param bool $refresh Recalculate.
 */
function psb_rfq_storage_used( $refresh = false ) {
	$cached = get_transient( 'psb_rfq_storage_bytes' );
	if ( ! $refresh && false !== $cached ) {
		return (int) $cached;
	}
	$total = 0;
	$base  = psb_rfq_upload_base( false );
	if ( is_dir( $base ) ) {
		try {
			$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $file ) {
				if ( $file->isFile() ) {
					$total += (int) $file->getSize();
				}
			}
		} catch ( Exception $e ) {
			$total = (int) $cached;
		}
	}
	set_transient( 'psb_rfq_storage_bytes', $total, 10 * MINUTE_IN_SECONDS );
	return $total;
}

/** Storage cap for uploads/psb-rfq/ in bytes (setting `upload_total_mb`, default 2 GB). */
function psb_rfq_storage_cap() {
	return max( 100, (int) psb_rfq_opt( 'upload_total_mb', 2048 ) ) * 1048576;
}

/** Per-IP upload budget: array( key, start, used bytes ) of the current 24-hour window. */
function psb_rfq_ip_budget() {
	$key = 'psb_rfq_ub_' . substr( psb_rfq_hmac( 'ub|' . psb_rfq_ip() ), 0, 20 );
	$v   = get_transient( $key );
	if ( ! is_array( $v ) || empty( $v['t'] ) || time() - (int) $v['t'] >= DAY_IN_SECONDS ) {
		$v = array( 't' => time(), 'b' => 0 );
	}
	return array( $key, (int) $v['t'], (int) $v['b'] );
}

/** Human message for PHP upload error codes. */
function psb_rfq_upload_error( $code ) {
	switch ( (int) $code ) {
		case UPLOAD_ERR_INI_SIZE:
		case UPLOAD_ERR_FORM_SIZE:
			return 'حجم فایل از حد مجاز سرور بیشتر است.';
		case UPLOAD_ERR_PARTIAL:
			return 'بارگذاری فایل ناقص ماند. دوباره تلاش کنید.';
		case UPLOAD_ERR_NO_FILE:
			return 'فایلی انتخاب نشده است.';
		default:
			return 'خطای سرور در دریافت فایل (کد ' . (int) $code . ').';
	}
}

/**
 * REST: POST /psb/v1/rfq/upload (multipart: file, token).
 */
function psb_rfq_rest_upload( WP_REST_Request $request ) {
	$sess = psb_rfq_verify_session( (string) $request->get_param( 'token' ), null, false );
	if ( is_wp_error( $sess ) ) {
		return psb_rfq_error( $sess->get_error_message(), 'token', 403 );
	}
	if ( ! psb_rfq_rate( 'up', 30, HOUR_IN_SECONDS ) ) {
		return psb_rfq_error( 'تعداد فایل‌های ارسالی زیاد است. لطفاً کمی بعد تلاش کنید.', 'files', 429 );
	}
	$files = $request->get_file_params();
	if ( empty( $files['file'] ) || ! is_array( $files['file'] ) || is_array( $files['file']['name'] ) ) {
		return psb_rfq_error( 'فایلی دریافت نشد.', 'files', 400 );
	}
	$f = $files['file'];
	if ( ! empty( $f['error'] ) ) {
		return psb_rfq_error( psb_rfq_upload_error( $f['error'] ), 'files', 400 );
	}
	$max = psb_rfq_max_bytes();
	if ( (int) $f['size'] > $max ) {
		return psb_rfq_error( 'حداکثر حجم هر فایل ' . psb_rfq_size( $max ) . ' است.', 'files', 413 );
	}
	if ( (int) $f['size'] <= 0 || ! is_uploaded_file( $f['tmp_name'] ) ) {
		return psb_rfq_error( 'فایل خالی یا نامعتبر است.', 'files', 400 );
	}
	$name = sanitize_file_name( wp_basename( (string) $f['name'] ) );
	$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, psb_rfq_allowed_ext(), true ) ) {
		return psb_rfq_error( 'این نوع فایل پذیرفته نمی‌شود. فرمت‌های مجاز: ' . implode( '، ', psb_rfq_allowed_ext() ), 'files', 415 );
	}
	if ( ! psb_rfq_magic_ok( $f['tmp_name'], $ext ) ) {
		return psb_rfq_error( 'محتوای فایل با پسوند آن مطابقت ندارد.', 'files', 415 );
	}

	// Disk-filling protection (security review M4): per-IP daily byte budget + total folder cap.
	$size                         = (int) $f['size'];
	list( $ub_key, $ub_t, $ub_b ) = psb_rfq_ip_budget();
	$ip_cap                       = max( 1, (int) psb_rfq_opt( 'upload_ip_mb', 100 ) ) * 1048576;
	if ( $ub_b + $size > $ip_cap ) {
		return psb_rfq_error( 'سقف حجم بارگذاری روزانه شما (' . psb_rfq_size( $ip_cap ) . ') پر شده است. لطفاً فردا دوباره تلاش کنید یا فایل‌ها را از طریق ایمیل یا واتساپ واحد فروش ارسال کنید.', 'files', 429 );
	}
	$used = psb_rfq_storage_used();
	if ( $used + $size > psb_rfq_storage_cap() ) {
		$used = psb_rfq_storage_used( true );
	}
	if ( $used + $size > psb_rfq_storage_cap() ) {
		return psb_rfq_error( 'فضای ذخیره فایل‌های استعلام موقتاً پر است. لطفاً درخواست را بدون فایل ثبت کنید و مدارک را از طریق ایمیل یا واتساپ واحد فروش ارسال کنید.', 'files', 507 );
	}

	$bucket = psb_rfq_bucket( $sess['rand'] );
	$dir    = psb_rfq_upload_base() . '/' . $bucket;
	if ( ! wp_mkdir_p( $dir ) ) {
		return psb_rfq_error( 'پوشه ذخیره فایل قابل ایجاد نیست.', 'files', 500 );
	}
	$existing = glob( $dir . '/*.*' );
	if ( is_array( $existing ) && count( $existing ) >= 3 * max( 1, (int) psb_rfq_opt( 'max_files', 5 ) ) ) {
		return psb_rfq_error( 'تعداد فایل‌ها بیش از حد مجاز است.', 'files', 429 );
	}
	$stored = strtolower( wp_generate_password( 16, false, false ) ) . '.' . $ext;
	if ( ! @move_uploaded_file( $f['tmp_name'], $dir . '/' . $stored ) ) { // phpcs:ignore
		return psb_rfq_error( 'ذخیره فایل روی سرور ناموفق بود.', 'files', 500 );
	}
	@chmod( $dir . '/' . $stored, 0640 ); // phpcs:ignore
	set_transient( $ub_key, array( 't' => $ub_t, 'b' => $ub_b + $size ), max( 60, DAY_IN_SECONDS - ( time() - $ub_t ) ) );
	set_transient( 'psb_rfq_storage_bytes', $used + $size, 10 * MINUTE_IN_SECONDS );

	$display = (string) $f['name'];
	$display = psb_rfq_text( wp_basename( $display ), 150 );
	if ( '' === $display ) {
		$display = $stored;
	}
	return new WP_REST_Response(
		array(
			'ok'   => true,
			'file' => array(
				'id'   => psb_rfq_file_token( $bucket, $stored, $display, (int) $f['size'] ),
				'name' => $display,
				'size' => (int) $f['size'],
			),
		),
		201
	);
}

/** Staff download handler: admin-post.php?action=psb_rfq_file&post=ID&i=N&_wpnonce=… */
add_action(
	'admin_post_psb_rfq_file',
	static function () {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$index   = isset( $_GET['i'] ) ? absint( $_GET['i'] ) : 0;
		check_admin_referer( 'psb_rfq_file_' . $post_id );
		if ( ! $post_id || PSB_RFQ_CPT !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( 'دسترسی غیرمجاز', 403 );
		}
		$files = get_post_meta( $post_id, '_psb_rfq_files', true );
		$f     = is_array( $files ) && isset( $files[ $index ] ) ? $files[ $index ] : null;
		$path  = $f ? psb_rfq_file_path( $f ) : '';
		if ( ! $path ) {
			wp_die( 'فایل پیدا نشد.', 404 );
		}
		$name  = $f['name'] ? $f['name'] : $f['file'];
		$ascii = preg_replace( '/[^A-Za-z0-9._-]+/', '_', $name );
		nocache_headers();
		header( 'Content-Type: application/octet-stream' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( "Content-Disposition: attachment; filename=\"{$ascii}\"; filename*=UTF-8''" . rawurlencode( $name ) );
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		readfile( $path ); // phpcs:ignore
		exit;
	}
);

/** Delete the files of a request when it is permanently deleted. */
add_action(
	'before_delete_post',
	static function ( $post_id ) {
		if ( PSB_RFQ_CPT !== get_post_type( $post_id ) ) {
			return;
		}
		$files = get_post_meta( $post_id, '_psb_rfq_files', true );
		$dirs  = array();
		foreach ( is_array( $files ) ? $files : array() as $f ) {
			$path = psb_rfq_file_path( $f );
			if ( $path ) {
				wp_delete_file( $path );
				$dirs[ dirname( $path ) ] = true;
			}
		}
		foreach ( array_keys( $dirs ) as $dir ) {
			psb_rfq_rmdir( $dir );
		}
		delete_transient( 'psb_rfq_storage_bytes' );
	}
);

/** Remove a bucket folder if it only contains our marker files. */
function psb_rfq_rmdir( $dir ) {
	$base = psb_rfq_upload_base( false );
	if ( 0 !== strpos( $dir, $base . '/' ) || ! is_dir( $dir ) ) {
		return;
	}
	foreach ( array( '.claimed' ) as $marker ) {
		if ( is_file( $dir . '/' . $marker ) ) {
			wp_delete_file( $dir . '/' . $marker );
		}
	}
	$left = array_diff( (array) scandir( $dir ), array( '.', '..' ) );
	if ( empty( $left ) ) {
		@rmdir( $dir ); // phpcs:ignore
	}
}

/** Daily cleanup of unclaimed uploads older than 2 days. */
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'psb_rfq_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'psb_rfq_cleanup' );
		}
	}
);
add_action(
	'psb_rfq_cleanup',
	static function () {
		$base = psb_rfq_upload_base( false );
		if ( ! is_dir( $base ) ) {
			return;
		}
		$limit = time() - 2 * DAY_IN_SECONDS;
		foreach ( (array) glob( $base . '/*', GLOB_ONLYDIR ) as $dir ) {
			if ( ! preg_match( '/\/[a-f0-9]{24}$/', $dir ) || is_file( $dir . '/.claimed' ) || filemtime( $dir ) > $limit ) {
				continue;
			}
			foreach ( (array) glob( $dir . '/*' ) as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
			psb_rfq_rmdir( $dir );
		}
		delete_transient( 'psb_rfq_storage_bytes' );
	}
);
