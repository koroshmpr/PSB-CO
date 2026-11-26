<?php
/**
 * RFQ — REST API (namespace psb/v1).
 *
 *   GET  /rfq/session  fresh signed session token (+ optional math question). Never cached.
 *   POST /rfq/upload   one file (multipart: file, token) → signed file id.
 *   POST /rfq          submit (JSON).
 *
 * No WP nonce is used on purpose: pages are served from the page cache, so a nonce printed in
 * the HTML would go stale. Protection instead: HMAC session token fetched at runtime (time-trap
 * from its issue time, 12h max age, single use), honeypot, per-IP rate limits, optional math
 * challenge, strict server-side validation (mirrors the client).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_RFQ_SESSION_TTL = 12 * HOUR_IN_SECONDS;

add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'psb/v1',
			'/rfq/session',
			array(
				'methods'             => 'GET',
				'callback'            => 'psb_rfq_rest_session',
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'psb/v1',
			'/rfq/upload',
			array(
				'methods'             => 'POST',
				'callback'            => 'psb_rfq_rest_upload',
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'psb/v1',
			'/rfq',
			array(
				'methods'             => 'POST',
				'callback'            => 'psb_rfq_rest_submit',
				'permission_callback' => '__return_true',
			)
		);
	}
);

/** Error response in the shape the client expects. */
function psb_rfq_error( $message, $field = '', $status = 422, $extra = array() ) {
	return new WP_REST_Response( array_merge( array( 'ok' => false, 'message' => $message, 'field' => $field ), $extra ), $status );
}

/** Never let a proxy / page cache store RFQ API responses. */
add_filter(
	'rest_post_dispatch',
	static function ( $response, $server, $request ) {
		if ( 0 === strpos( $request->get_route(), '/psb/v1/rfq' ) && $response instanceof WP_HTTP_Response ) {
			$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
			$response->header( 'X-Robots-Tag', 'noindex' );
		}
		return $response;
	},
	10,
	3
);

/**
 * GET /rfq/session
 */
function psb_rfq_rest_session() {
	$ts   = time();
	$rand = strtolower( wp_generate_password( 20, false, false ) );
	$c    = psb_rfq_opt( 'challenge' ) ? 1 : 0;
	$q    = '';
	$sig2 = '-';
	if ( $c ) {
		$a    = wp_rand( 2, 9 );
		$b    = wp_rand( 1, 9 );
		$q    = psb_rfq_fa( $a ) . ' + ' . psb_rfq_fa( $b );
		$sig2 = substr( psb_rfq_hmac( "ans|$ts|$rand|" . ( $a + $b ) ), 0, 24 );
	}
	$sig1 = substr( psb_rfq_hmac( "sess|$ts|$rand|$c" ), 0, 32 );
	return new WP_REST_Response(
		array(
			'token'     => "$ts.$rand.$c.$sig1.$sig2",
			'challenge' => (bool) $c,
			'question'  => $q,
			'minWait'   => (int) psb_rfq_opt( 'min_seconds', 4 ),
		),
		200
	);
}

/**
 * Verify a session token.
 *
 * @param string      $token    Token.
 * @param string|null $answer   Challenge answer (null = do not check the challenge).
 * @param bool        $timetrap Enforce the minimum fill time.
 * @return array|WP_Error array( ts, rand )
 */
function psb_rfq_verify_session( $token, $answer = null, $timetrap = true ) {
	$p = explode( '.', (string) $token );
	if ( 5 !== count( $p ) || ! ctype_digit( $p[0] ) || ! preg_match( '/^[a-z0-9]{20}$/', $p[1] ) ) {
		return new WP_Error( 'token', 'نشست فرم نامعتبر است. صفحه را تازه کنید.' );
	}
	list( $ts, $rand, $c, $sig1, $sig2 ) = $p;
	if ( ! hash_equals( substr( psb_rfq_hmac( "sess|$ts|$rand|$c" ), 0, 32 ), $sig1 ) ) {
		return new WP_Error( 'token', 'نشست فرم نامعتبر است. صفحه را تازه کنید.' );
	}
	$age = time() - (int) $ts;
	if ( $age > PSB_RFQ_SESSION_TTL || $age < -60 ) {
		return new WP_Error( 'token', 'نشست فرم منقضی شده است.' );
	}
	if ( $timetrap ) {
		$min = max( 0, (int) psb_rfq_opt( 'min_seconds', 4 ) );
		if ( $age < $min ) {
			return new WP_Error( 'too_fast', 'ارسال بیش از حد سریع بود.', array( 'retry_after' => $min - $age ) );
		}
	}
	if ( null !== $answer ) {
		if ( psb_rfq_opt( 'challenge' ) && '1' !== $c ) {
			return new WP_Error( 'token', 'نشست فرم منقضی شده است.' ); // challenge was switched on meanwhile.
		}
		if ( '1' === $c ) {
			$ans = (int) preg_replace( '/\D+/', '', psb_rfq_latin( $answer ) );
			if ( ! hash_equals( substr( psb_rfq_hmac( "ans|$ts|$rand|$ans" ), 0, 24 ), $sig2 ) ) {
				return new WP_Error( 'challenge', 'پاسخ سؤال امنیتی درست نیست.' );
			}
		}
	}
	return array(
		'ts'   => (int) $ts,
		'rand' => $rand,
	);
}

/**
 * Sanitize + validate one value of a field definition.
 *
 * @return string|array
 */
function psb_rfq_clean_value( $def, $opts, $value ) {
	switch ( $def['type'] ) {
		case 'select':
		case 'radio':
			$raw = is_scalar( $value ) ? trim( (string) $value ) : '';
			if ( '' !== $raw && isset( $opts[ $raw ] ) ) {
				return $raw; // Exact key (also non-slug keys such as province names).
			}
			$v = sanitize_key( $raw );
			return ( '' !== $v && isset( $opts[ $v ] ) ) ? $v : '';
		case 'chips':
			$vals = is_array( $value ) ? array_map( 'sanitize_key', $value ) : array();
			return array_values( array_unique( array_intersect( $vals, array_map( 'strval', array_keys( $opts ) ) ) ) );
		case 'textarea':
			return psb_rfq_textarea( $value, isset( $def['max'] ) ? $def['max'] : 1000 );
		default:
			$v = psb_rfq_text( $value, isset( $def['max'] ) ? $def['max'] : 120 );
			if ( ! empty( $def['unit'] ) ) {
				$v = psb_rfq_latin( $v );
			}
			return $v;
	}
}

/**
 * Validate a submission. Mirrors the client rules in js/rfq.js.
 *
 * @param array $p Raw JSON params.
 * @return array|WP_Error Clean data, or error with data['field'].
 */
function psb_rfq_validate( $p ) {
	$mode  = ( isset( $p['mode'] ) && 'quick' === $p['mode'] ) ? 'quick' : 'full';
	$types = psb_rfq_types();
	$specs = psb_rfq_spec_defs();
	$defs  = psb_rfq_field_defs();
	$err   = static function ( $msg, $field ) {
		return new WP_Error( 'invalid', $msg, array( 'field' => $field ) );
	};

	// Line items.
	$items = array();
	$raw   = isset( $p['items'] ) && is_array( $p['items'] ) ? array_slice( array_values( $p['items'] ), 0, 30 ) : array();
	foreach ( $raw as $i => $it ) {
		if ( ! is_array( $it ) ) {
			continue;
		}
		$type = isset( $it['type'] ) ? sanitize_key( $it['type'] ) : '';
		if ( ! isset( $types[ $type ] ) ) {
			if ( 'quick' === $mode ) {
				$type = isset( $types['other'] ) ? 'other' : (string) key( $types );
			} else {
				return $err( 'نوع تابلو ردیف ' . psb_rfq_fa( $i + 1 ) . ' معتبر نیست.', 'items' );
			}
		}
		$qty = isset( $it['qty'] ) ? (int) preg_replace( '/\D+/', '', psb_rfq_latin( $it['qty'] ) ) : 1;
		if ( $qty < 1 || $qty > 9999 ) {
			return $err( 'تعداد باید بین ۱ تا ۹۹۹۹ باشد.', "items.$i.qty" );
		}
		$group = $types[ $type ]['group'];
		$clean = array();
		foreach ( $specs as $k => $def ) {
			$vis  = psb_rfq_vis( 'spec.' . $k );
			$opts = psb_rfq_spec_options( $def, $group );
			if ( 'hide' === $vis || null === $opts ) {
				continue;
			}
			$v = isset( $it['specs'][ $k ] ) ? psb_rfq_clean_value( $def, $opts, $it['specs'][ $k ] ) : '';
			if ( 'required' === $vis && 'full' === $mode && ( '' === $v || array() === $v ) ) {
				return $err( '«' . $def['label'] . '» را برای ' . $types[ $type ]['label'] . ' مشخص کنید (یا «نمی‌دانم» را انتخاب کنید).', "items.$i.$k" );
			}
			if ( '' !== $v && array() !== $v ) {
				$clean[ $k ] = $v;
			}
		}
		$items[] = array(
			'type'  => $type,
			'qty'   => $qty,
			'specs' => $clean,
		);
	}
	if ( ! $items ) {
		if ( 'quick' === $mode ) {
			$items[] = array( 'type' => isset( $types['other'] ) ? 'other' : (string) key( $types ), 'qty' => 1, 'specs' => array() );
		} else {
			return $err( 'حداقل یک نوع تابلو انتخاب کنید.', 'items' );
		}
	}

	// Project + contact.
	$out = array( 'project' => array(), 'contact' => array() );
	foreach ( $defs as $key => $def ) {
		if ( 'files' === $def['type'] ) {
			continue;
		}
		list( $sec, $name ) = explode( '.', $key, 2 );
		$vis = psb_rfq_vis( $key );
		if ( 'hide' === $vis ) {
			continue;
		}
		if ( 'quick' === $mode && ! in_array( $key, array( 'contact.name', 'contact.mobile', 'contact.company', 'project.description' ), true ) ) {
			continue;
		}
		$value = isset( $p[ $sec ][ $name ] ) ? $p[ $sec ][ $name ] : '';
		if ( 'contact.mobile' === $key ) {
			$v = psb_rfq_mobile( $value );
			if ( ! $v ) {
				return $err( 'شماره موبایل معتبر نیست (مثال: ۰۹۱۲۱۲۳۴۵۶۷).', $key );
			}
		} elseif ( 'contact.email' === $key ) {
			$v = sanitize_email( is_scalar( $value ) ? (string) $value : '' );
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) && ! is_email( $v ) ) {
				return $err( 'ایمیل معتبر نیست.', $key );
			}
		} elseif ( 'contact.username' === $key || 'contact.bale' === $key ) {
			$v = preg_replace( '/[^A-Za-z0-9_.+]/', '', ltrim( psb_rfq_text( psb_rfq_latin( $value ), 40 ), '@' ) );
		} else {
			$v = psb_rfq_clean_value( $def, psb_rfq_field_options( $def ), $value );
		}
		if ( 'contact.name' === $key && mb_strlen( $v ) < 2 ) {
			return $err( 'نام خود را وارد کنید.', $key );
		}
		if ( 'required' === $vis && 'full' === $mode && '' === $v ) {
			return $err( '«' . $def['label'] . '» را تکمیل کنید.', $key );
		}
		$out[ $sec ][ $name ] = $v;
	}
	if ( 'full' === $mode && isset( $out['contact']['channel'] ) && 'email' === $out['contact']['channel'] && empty( $out['contact']['email'] ) ) {
		return $err( 'برای ارتباط از طریق ایمیل، آدرس ایمیل را وارد کنید.', 'contact.email' );
	}

	// Files.
	$files = array();
	if ( 'hide' !== psb_rfq_vis( 'files' ) && ! empty( $p['files'] ) && is_array( $p['files'] ) ) {
		$max = max( 1, (int) psb_rfq_opt( 'max_files', 5 ) );
		foreach ( array_slice( $p['files'], 0, $max ) as $tok ) {
			$f = psb_rfq_verify_file_token( is_scalar( $tok ) ? (string) $tok : '' );
			if ( $f ) {
				$files[ $f['bucket'] . $f['file'] ] = $f;
			}
		}
		$files = array_values( $files );
	}
	if ( 'full' === $mode && 'required' === psb_rfq_vis( 'files' ) && ! $files ) {
		return $err( 'لطفاً حداقل یک فایل (نقشه تک‌خطی، BOQ یا مشخصات فنی) بارگذاری کنید.', 'files' );
	}

	// Source / context.
	$src    = isset( $p['source'] ) && is_array( $p['source'] ) ? $p['source'] : array();
	$source = array(
		'page'     => esc_url_raw( mb_substr( isset( $src['page'] ) ? (string) $src['page'] : '', 0, 400 ) ),
		'referrer' => esc_url_raw( mb_substr( isset( $src['referrer'] ) ? (string) $src['referrer'] : '', 0, 400 ) ),
		'product'  => psb_rfq_text( isset( $src['product'] ) ? $src['product'] : '', 150 ),
		'utm'      => psb_rfq_text( isset( $src['utm'] ) ? $src['utm'] : '', 200 ),
	);

	return array(
		'mode'    => $mode,
		'items'   => $items,
		'project' => $out['project'],
		'contact' => $out['contact'],
		'files'   => $files,
		'source'  => $source,
	);
}

/**
 * POST /rfq
 */
function psb_rfq_rest_submit( WP_REST_Request $request ) {
	$p = $request->get_json_params();
	if ( ! is_array( $p ) ) {
		$p = $request->get_body_params();
	}
	if ( ! is_array( $p ) ) {
		return psb_rfq_error( 'درخواست نامعتبر است.', '', 400 );
	}

	// Honeypot: pretend success, store nothing.
	if ( ! empty( $p['website'] ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'code' => strtoupper( psb_rfq_opt( 'prefix', 'PSB' ) ) . '-' . psb_rfq_jyear() . '-' . wp_rand( 1000, 9999 ) ), 201 );
	}

	$sess = psb_rfq_verify_session( isset( $p['token'] ) ? (string) $p['token'] : '', isset( $p['answer'] ) ? (string) $p['answer'] : '', true );
	if ( is_wp_error( $sess ) ) {
		$code = $sess->get_error_code();
		if ( 'challenge' === $code && ! psb_rfq_rate( 'ch', 6, 600 ) ) {
			return psb_rfq_error( 'تلاش‌های ناموفق زیاد بود. چند دقیقه دیگر دوباره تلاش کنید.', 'challenge', 429 );
		}
		$data = $sess->get_error_data();
		return psb_rfq_error(
			$sess->get_error_message(),
			'challenge' === $code ? 'challenge' : ( 'too_fast' === $code ? '' : 'token' ),
			'too_fast' === $code ? 429 : 403,
			array(
				'code'        => $code,
				'retry_after' => is_array( $data ) && isset( $data['retry_after'] ) ? (int) $data['retry_after'] : 0,
			)
		);
	}

	// Idempotency: the same session token never creates two requests (double taps, retries).
	$used_key = 'psb_rfq_used_' . md5( $sess['rand'] );
	$used     = (int) get_transient( $used_key );
	if ( $used && PSB_RFQ_CPT === get_post_type( $used ) ) {
		return new WP_REST_Response( psb_rfq_success_payload( $used ), 200 );
	}

	$data = psb_rfq_validate( $p );
	if ( is_wp_error( $data ) ) {
		$d = $data->get_error_data();
		return psb_rfq_error( $data->get_error_message(), is_array( $d ) && isset( $d['field'] ) ? $d['field'] : '', 422 );
	}

	if ( ! psb_rfq_rate( 'sub', max( 1, (int) psb_rfq_opt( 'rate_limit', 5 ) ), 600 ) ) {
		return psb_rfq_error( 'تعداد درخواست‌های ثبت‌شده از این اتصال زیاد است. لطفاً چند دقیقه دیگر تلاش کنید یا با واحد فروش تماس بگیرید.', '', 429 );
	}

	$post_id = psb_rfq_store( $data );
	if ( is_wp_error( $post_id ) ) {
		return psb_rfq_error( 'خطای داخلی در ثبت درخواست. لطفاً با واحد فروش تماس بگیرید.', '', 500 );
	}
	set_transient( $used_key, $post_id, PSB_RFQ_SESSION_TTL );

	foreach ( $data['files'] as $f ) {
		psb_rfq_claim_bucket( $f['bucket'], $post_id );
	}

	psb_rfq_queue_notifications( $post_id );

	return new WP_REST_Response( psb_rfq_success_payload( $post_id ), 201 );
}

/** Insert the post + meta. */
function psb_rfq_store( $data ) {
	$code    = psb_rfq_next_code();
	$c       = $data['contact'];
	$name    = isset( $c['name'] ) ? $c['name'] : '';
	$company = isset( $c['company'] ) ? $c['company'] : '';
	$city    = trim( ( isset( $data['project']['city'] ) ? $data['project']['city'] : '' ) . ( ! empty( $data['project']['province'] ) ? ' (' . $data['project']['province'] . ')' : '' ) );
	$summary = psb_rfq_items_summary( $data['items'] );
	$data['code'] = $code;

	$post_id = wp_insert_post(
		array(
			'post_type'    => PSB_RFQ_CPT,
			'post_status'  => 'publish',
			'post_title'   => wp_strip_all_tags( $name . ( $company ? ' — ' . $company : '' ) ),
			// Search index: admin search hits code / mobile / name / company / city.
			'post_excerpt' => implode( ' ', array_filter( array( $code, isset( $c['mobile'] ) ? $c['mobile'] : '', $name, $company, $city, isset( $c['email'] ) ? $c['email'] : '', isset( $data['project']['name'] ) ? $data['project']['name'] : '', $summary ) ) ),
			'post_content' => '',
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}
	update_post_meta( $post_id, '_psb_rfq_code', $code );
	update_post_meta( $post_id, '_psb_rfq_status', 'new' );
	update_post_meta( $post_id, '_psb_rfq_mode', $data['mode'] );
	update_post_meta( $post_id, '_psb_rfq_data', $data );
	update_post_meta( $post_id, '_psb_rfq_files', $data['files'] );
	update_post_meta( $post_id, '_psb_rfq_name', $name );
	update_post_meta( $post_id, '_psb_rfq_mobile', isset( $c['mobile'] ) ? $c['mobile'] : '' );
	update_post_meta( $post_id, '_psb_rfq_company', $company );
	update_post_meta( $post_id, '_psb_rfq_city', $city );
	foreach ( array_unique( wp_list_pluck( $data['items'], 'type' ) ) as $t ) {
		add_post_meta( $post_id, '_psb_rfq_type', $t );
	}
	update_post_meta( $post_id, '_psb_rfq_ip', psb_rfq_ip() );
	update_post_meta( $post_id, '_psb_rfq_ua', isset( $_SERVER['HTTP_USER_AGENT'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '' );

	do_action( 'psb_rfq_created', $post_id, $data );
	return $post_id;
}

/** Response for a stored request. */
function psb_rfq_success_payload( $post_id ) {
	return array(
		'ok'           => true,
		'code'         => get_post_meta( $post_id, '_psb_rfq_code', true ),
		'title'        => psb_rfq_opt( 'success_title' ),
		'message'      => psb_rfq_opt( 'success_message' ),
		'responseTime' => psb_rfq_opt( 'response_time' ),
	);
}
