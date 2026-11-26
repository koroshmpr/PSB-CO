<?php
/**
 * Project records — admin page «ورود از اکسل/CSV»: upload → column mapping + preview → dry run / import → report.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_submenu_page(
			'edit.php?post_type=' . PSB_PR_CPT,
			'ورود از اکسل/CSV',
			'ورود از اکسل/CSV',
			'manage_options',
			'psb-pr-import',
			'psb_pr_import_page'
		);
	}
);

/** Private upload dir for import files (not web-listable). */
function psb_pr_import_dir() {
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'psb-pr-import';
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
		file_put_contents( $dir . '/index.php', "<?php // Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	// Apache 2.4 (mod_authz_core) and 2.2 rules in separate IfModule blocks: a bare "Deny from all" returns 500
	// on 2.4 without mod_access_compat (security review L5). Rewrites the old single-line file once.
	$ht = "Options -Indexes\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n";
	if ( ! is_file( $dir . '/.htaccess' ) || file_get_contents( $dir . '/.htaccess' ) !== $ht ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( $dir . '/.htaccess', $ht ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $dir;
}

/**
 * Delete import files older than $max_age seconds (abandoned uploads hold real client names).
 * Runs on every load of the import page and from a daily cron.
 *
 * @param int $max_age Seconds.
 */
function psb_pr_import_purge( $max_age = DAY_IN_SECONDS ) {
	$up  = wp_upload_dir( null, false );
	$dir = trailingslashit( $up['basedir'] ) . 'psb-pr-import';
	if ( ! is_dir( $dir ) ) {
		return 0;
	}
	$n = 0;
	foreach ( (array) glob( $dir . '/*' ) as $file ) {
		if ( is_file( $file ) && preg_match( '/\.(csv|xlsx)$/', $file ) && filemtime( $file ) < time() - $max_age ) {
			wp_delete_file( $file );
			++$n;
		}
	}
	return $n;
}
add_action( 'psb_pr_import_purge', 'psb_pr_import_purge' );
add_action(
	'admin_init',
	static function () {
		if ( ! wp_next_scheduled( 'psb_pr_import_purge' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'psb_pr_import_purge' );
		}
	}
);

/** Import options from POST. */
function psb_pr_import_args_from_post() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified by the caller.
	$p = wp_unslash( $_POST );
	return array(
		'mode'           => ( $p['mode'] ?? '' ) === 'update' ? 'update' : 'skip',
		'dry_run'        => ! empty( $p['dry_run'] ),
		'sensitive'      => in_array( $p['sensitive'] ?? '', array( 'anon', 'draft', 'skip' ), true ) ? $p['sensitive'] : 'anon',
		'do_not_publish' => ( $p['do_not_publish'] ?? '' ) === 'skip' ? 'skip' : 'draft',
		'infer_sector'   => ! empty( $p['infer_sector'] ),
		'infer_types'    => ! empty( $p['infer_types'] ),
		'status'         => ( $p['status'] ?? '' ) === 'draft' ? 'draft' : 'publish',
	);
	// phpcs:enable
}

/** Page controller. */
function psb_pr_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'دسترسی ندارید.' );
	}
	$step = isset( $_POST['psb_pr_step'] ) ? sanitize_key( wp_unslash( $_POST['psb_pr_step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	echo '<div class="wrap psb-pr-import"><h1>ورود سوابق اجرایی از اکسل / CSV</h1>';
	echo '<style>.psb-pr-import table.widefat td,.psb-pr-import table.widefat th{vertical-align:top}.psb-pr-import .pr-kv{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:8px;margin:12px 0}.psb-pr-import .pr-kv div{background:#fff;border:1px solid #dcdcde;padding:10px}.psb-pr-import .pr-kv b{display:block;font-size:20px}.psb-pr-import .pr-scroll{max-height:520px;overflow:auto}</style>';

	$error = '';
	if ( $step ) {
		check_admin_referer( 'psb_pr_import' );
	}
	psb_pr_import_purge();

	if ( 'upload' === $step ) {
		$file = $_FILES['psb_pr_file'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! $file || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$error = 'فایلی دریافت نشد.';
		} else {
			$ext = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, array( 'csv', 'txt', 'tsv', 'xlsx' ), true ) ) {
				$error = 'فقط فایل CSV یا XLSX پذیرفته می‌شود.';
			} else {
				$token = wp_generate_password( 20, false );
				$dest  = psb_pr_import_dir() . '/' . $token . '.' . ( 'xlsx' === $ext ? 'xlsx' : 'csv' );
				if ( move_uploaded_file( $file['tmp_name'], $dest ) ) {
					$prev = get_transient( 'psb_pr_imp_' . get_current_user_id() );
					if ( is_array( $prev ) && ! empty( $prev['path'] ) && is_file( $prev['path'] ) && 0 === strpos( $prev['path'], psb_pr_import_dir() . '/' ) ) {
						wp_delete_file( $prev['path'] ); // A new upload replaces the abandoned one.
					}
					set_transient( 'psb_pr_imp_' . get_current_user_id(), array( 'path' => $dest, 'name' => sanitize_file_name( $file['name'] ) ), DAY_IN_SECONDS );
					$step = 'map';
				} else {
					$error = 'ذخیره فایل ناموفق بود.';
				}
			}
		}
	}

	$job = get_transient( 'psb_pr_imp_' . get_current_user_id() );
	if ( in_array( $step, array( 'map', 'preview', 'run' ), true ) && ( ! $job || ! is_readable( $job['path'] ) ) ) {
		$error = 'فایل بارگذاری‌شده پیدا نشد؛ دوباره بارگذاری کنید.';
		$step  = '';
	}

	if ( $error ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
	}

	if ( in_array( $step, array( 'map', 'preview' ), true ) ) {
		psb_pr_import_step_map( $job, 'preview' === $step );
	} elseif ( 'run' === $step ) {
		psb_pr_import_step_run( $job );
	} else {
		psb_pr_import_step_upload();
	}
	echo '</div>';
}

/** Step 1: upload form + export. */
function psb_pr_import_step_upload() {
	$last = get_option( 'psb_pr_last_import' );
	?>
	<p>فایل CSV (UTF-8، با یا بدون BOM؛ جداکننده ویرگول، نقطه‌ویرگول یا Tab) یا XLSX را بارگذاری کنید. سطر اول باید عنوان ستون‌ها باشد؛ ستون‌ها در مرحله بعد به‌صورت خودکار شناسایی می‌شوند و قابل تغییرند.</p>
	<form method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'psb_pr_import' ); ?>
		<input type="hidden" name="psb_pr_step" value="upload">
		<p><input type="file" name="psb_pr_file" accept=".csv,.txt,.tsv,.xlsx" required></p>
		<p><button class="button button-primary">بارگذاری و تطبیق ستون‌ها</button></p>
	</form>
	<hr>
	<h2>خروجی</h2>
	<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=psb_pr_export' ), 'psb_pr_export' ) ); ?>">دریافت همه سوابق به‌صورت CSV</a>
		<span class="description">ستون‌های این فایل دوباره قابل ورود است (برای ویرایش گروهی در اکسل).</span></p>
	<?php if ( $last ) : ?>
		<h2>آخرین ورود (<?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $last['time'] ) ); ?>)</h2>
		<?php psb_pr_import_report_html( $last['report'] ); ?>
	<?php endif; ?>
	<?php
}

/** Option controls shared by the map and run steps. */
function psb_pr_import_options_html( $a ) {
	?>
	<table class="form-table" role="presentation">
		<tr><th>ردیف‌های تکراری</th><td>
			<label><input type="radio" name="mode" value="skip" <?php checked( 'skip', $a['mode'] ); ?>> رد شوند (پیش‌فرض)</label><br>
			<label><input type="radio" name="mode" value="update" <?php checked( 'update', $a['mode'] ); ?>> سابقه موجود به‌روزرسانی شود</label>
			<p class="description">تکراری = عنوان + کارفرما + سال یکسان (پس از یکسان‌سازی حروف و ارقام فارسی/عربی).</p></td></tr>
		<tr><th>ردیف‌های محرمانه</th><td>
			<select name="sensitive">
				<option value="anon" <?php selected( 'anon', $a['sensitive'] ); ?>>منتشر شوند ولی نام کارفرما، عنوان و شهر مخفی باشد</option>
				<option value="draft" <?php selected( 'draft', $a['sensitive'] ); ?>>پیش‌نویس شوند</option>
				<option value="skip" <?php selected( 'skip', $a['sensitive'] ); ?>>وارد نشوند</option>
			</select>
			<p class="description">ردیف‌هایی که در یادداشت «منتشر نشود» دارند:
				<select name="do_not_publish"><option value="draft" <?php selected( 'draft', $a['do_not_publish'] ); ?>>پیش‌نویس</option><option value="skip" <?php selected( 'skip', $a['do_not_publish'] ); ?>>وارد نشوند</option></select></p></td></tr>
		<tr><th>تکمیل خودکار</th><td>
			<label><input type="checkbox" name="infer_sector" value="1" <?php checked( $a['infer_sector'] ); ?>> حوزه خالی از روی عنوان حدس زده شود (با برچسب «حوزه حدسی» در فهرست)</label><br>
			<label><input type="checkbox" name="infer_types" value="1" <?php checked( $a['infer_types'] ); ?>> نوع تابلوی خالی از روی عنوان تشخیص داده شود</label></td></tr>
		<tr><th>وضعیت ردیف‌های جدید</th><td>
			<select name="status"><option value="publish" <?php selected( 'publish', $a['status'] ); ?>>منتشرشده</option><option value="draft" <?php selected( 'draft', $a['status'] ); ?>>پیش‌نویس</option></select></td></tr>
		<tr><th>اجرای آزمایشی</th><td>
			<label><input type="checkbox" name="dry_run" value="1" <?php checked( $a['dry_run'] ); ?>> فقط گزارش بده، چیزی ذخیره نکن</label></td></tr>
	</table>
	<?php
}

/** Step 2: mapping + preview of the first 20 normalised rows. */
function psb_pr_import_step_map( $job, $from_post ) {
	$table = psb_pr_read_table( $job['path'] );
	if ( is_wp_error( $table ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $table->get_error_message() ) . '</p></div>';
		psb_pr_import_step_upload();
		return;
	}
	$headers = array_map( 'psb_pr_clean', $table[0] ?? array() );
	$map     = psb_pr_detect_map( $headers );
	$args    = psb_pr_import_defaults();
	$args['dry_run'] = true;
	if ( $from_post ) {
		$map  = psb_pr_map_from_post();
		$args = array_merge( $args, psb_pr_import_args_from_post() );
	}
	$mapped  = psb_pr_map_table( $table, $map );
	$preview = psb_pr_import_rows( $mapped['rows'], array_merge( $args, array( 'dry_run' => true, 'limit' => 20 ) ) );
	$fields  = psb_pr_import_fields();
	?>
	<p>فایل: <strong><?php echo esc_html( $job['name'] ); ?></strong> — <?php echo esc_html( count( $table ) - 1 ); ?> ردیف داده، <?php echo esc_html( count( $headers ) ); ?> ستون.</p>
	<form method="post">
		<?php wp_nonce_field( 'psb_pr_import' ); ?>
		<h2>تطبیق ستون‌ها</h2>
		<table class="widefat striped" style="max-width:760px"><thead><tr><th>فیلد</th><th>ستون فایل</th></tr></thead><tbody>
		<?php foreach ( $fields as $f => $def ) : ?>
			<tr><td><?php echo esc_html( $def[0] ); ?></td><td>
				<select name="map[<?php echo esc_attr( $f ); ?>]">
					<option value="-1">— نادیده —</option>
					<?php foreach ( $headers as $i => $h ) : ?>
						<option value="<?php echo (int) $i; ?>" <?php selected( isset( $map[ $f ] ) && (int) $map[ $f ] === $i ); ?>><?php echo esc_html( ( '' !== $h ? $h : 'ستون ' . ( $i + 1 ) ) ); ?></option>
					<?php endforeach; ?>
				</select></td></tr>
		<?php endforeach; ?>
		</tbody></table>
		<h2>تنظیمات</h2>
		<?php psb_pr_import_options_html( $args ); ?>
		<p>
			<button class="button" name="psb_pr_step" value="preview">به‌روزرسانی پیش‌نمایش</button>
			<button class="button button-primary" name="psb_pr_step" value="run">اجرا</button>
		</p>
		<h2>پیش‌نمایش ۲۰ ردیف اول (پس از یکسان‌سازی)</h2>
		<?php psb_pr_preview_table( $preview['preview'] ); ?>
	</form>
	<?php
}

/** field => column from POST. */
function psb_pr_map_from_post() {
	$map = array();
	$in  = isset( $_POST['map'] ) ? (array) wp_unslash( $_POST['map'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput
	foreach ( array_keys( psb_pr_import_fields() ) as $f ) {
		if ( isset( $in[ $f ] ) && (int) $in[ $f ] >= 0 ) {
			$map[ $f ] = (int) $in[ $f ];
		}
	}
	return $map;
}

/** Preview table. */
function psb_pr_preview_table( $recs ) {
	$names = psb_pr_sector_names();
	$tl    = psb_pr_type_labels();
	echo '<div class="pr-scroll"><table class="widefat striped"><thead><tr><th>عنوان</th><th>کارفرما</th><th>استان / شهر</th><th>سال</th><th>حوزه</th><th>نوع تابلو</th><th>نمایش</th></tr></thead><tbody>';
	foreach ( $recs as $r ) {
		$types = array();
		foreach ( $r['types'] as $t ) {
			$types[] = $tl[ $t ] ?? $t;
		}
		$vis = 'publish' === $r['status'] ? ( $r['show_client'] ? 'عمومی' : 'ناشناس: ' . psb_pr_generic_title( $r['types'], $r['sector'], $r['province'] ) ) : 'پیش‌نویس';
		if ( 'skip_sensitive' === $r['action'] ) {
			$vis = 'وارد نمی‌شود';
		}
		printf(
			'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s%s</td><td>%s</td><td>%s</td></tr>',
			esc_html( $r['title'] ),
			esc_html( $r['client'] ),
			esc_html( trim( $r['province'] . ' / ' . $r['city'], ' /' ) ),
			$r['year'] ? esc_html( $r['year'] ) : '—',
			esc_html( $names[ $r['sector'] ] ?? $r['sector'] ),
			$r['inferred'] ? ' <em>(حدسی)</em>' : '',
			esc_html( implode( '، ', $types ) ),
			esc_html( $vis )
		);
	}
	echo '</tbody></table></div>';
}

/** Step 3: run + report. */
function psb_pr_import_step_run( $job ) {
	$args        = psb_pr_import_args_from_post();
	$args['map'] = psb_pr_map_from_post();
	$rep         = psb_pr_import_csv( $job['path'], $args );
	if ( is_wp_error( $rep ) ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $rep->get_error_message() ) . '</p></div>';
		return;
	}
	if ( $args['dry_run'] ) {
		echo '<div class="notice notice-info"><p>اجرای آزمایشی: هیچ تغییری ذخیره نشد.</p></div>';
	} else {
		echo '<div class="notice notice-success"><p>ورود انجام شد.</p></div>';
		unset( $rep['preview'] );
		update_option( 'psb_pr_last_import', array( 'time' => time(), 'report' => $rep ), false );
		wp_delete_file( $job['path'] );
		delete_transient( 'psb_pr_imp_' . get_current_user_id() );
	}
	psb_pr_import_report_html( $rep );
	if ( $args['dry_run'] ) {
		?>
		<form method="post">
			<?php wp_nonce_field( 'psb_pr_import' ); ?>
			<?php foreach ( $args['map'] as $f => $c ) : ?>
				<input type="hidden" name="map[<?php echo esc_attr( $f ); ?>]" value="<?php echo (int) $c; ?>">
			<?php endforeach; ?>
			<?php
			$real            = $args;
			$real['dry_run'] = false;
			psb_pr_import_options_html( $real );
			?>
			<p><button class="button button-primary" name="psb_pr_step" value="run">اجرای واقعی با همین تنظیمات</button>
				<button class="button" name="psb_pr_step" value="preview">بازگشت به تطبیق ستون‌ها</button></p>
		</form>
		<?php
	} else {
		echo '<p><a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=' . PSB_PR_CPT ) ) . '">مشاهده سوابق</a></p>';
	}
}

/** Report markup. */
function psb_pr_import_report_html( $rep ) {
	$names = psb_pr_sector_names();
	$kv    = array(
		'rows'               => 'ردیف خوانده‌شده',
		'created'            => $rep['dry_run'] ? 'ایجاد می‌شود' : 'ایجاد شد',
		'updated'            => $rep['dry_run'] ? 'به‌روزرسانی می‌شود' : 'به‌روزرسانی شد',
		'skipped_existing'   => 'رد شد (موجود)',
		'duplicates_in_file' => 'تکراری در فایل',
		'empty'              => 'بدون عنوان',
		'anonymised'         => 'منتشر با نام مخفی',
		'drafts'             => 'پیش‌نویس',
		'skipped_sensitive'  => 'محرمانه، وارد نشد',
		'inferred_sector'    => 'حوزه حدسی',
		'no_province'        => 'بدون استان',
		'with_year'          => 'دارای سال',
	);
	echo '<div class="pr-kv">';
	foreach ( $kv as $k => $l ) {
		printf( '<div><b>%s</b>%s</div>', esc_html( (int) ( $rep[ $k ] ?? 0 ) ), esc_html( $l ) );
	}
	echo '</div>';
	if ( ! empty( $rep['errors'] ) ) {
		echo '<div class="notice notice-error"><p>' . implode( '<br>', array_map( 'esc_html', $rep['errors'] ) ) . '</p></div>';
	}
	if ( ! empty( $rep['by_sector'] ) ) {
		echo '<h3>منتشرشده‌ها بر اساس حوزه</h3><p>';
		$parts = array();
		foreach ( $rep['by_sector'] as $s => $n ) {
			$parts[] = esc_html( ( $names[ $s ] ?? $s ) . ': ' . $n );
		}
		echo implode( ' · ', $parts ) . '</p>'; // phpcs:ignore
	}
	if ( ! empty( $rep['unmapped_locations'] ) ) {
		echo '<h3>محل‌هایی که استانشان شناسایی نشد</h3><p>' . esc_html( implode( '، ', $rep['unmapped_locations'] ) ) . '</p><p class="description">استان این ردیف‌ها را از ویرایش سریع تنظیم کنید.</p>';
	}
	if ( ! empty( $rep['unknown_types'] ) ) {
		$parts = array();
		foreach ( array_slice( $rep['unknown_types'], 0, 30, true ) as $t => $n ) {
			$parts[] = $t . ' (' . $n . ')';
		}
		echo '<h3>عبارت‌های نوع تابلو که شناخته نشد</h3><p>' . esc_html( implode( '، ', $parts ) ) . '</p>';
	}
	if ( ! empty( $rep['unknown_sectors'] ) ) {
		echo '<h3>حوزه‌های ناشناخته (به «سایر» رفتند)</h3><p>' . esc_html( implode( '، ', array_keys( $rep['unknown_sectors'] ) ) ) . '</p>';
	}
}
