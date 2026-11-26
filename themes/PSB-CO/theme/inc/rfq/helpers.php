<?php
/**
 * RFQ — small pure helpers (digits, mobile, Jalali date, HMAC tokens, client IP).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Persian / Arabic-Indic digits → Latin. */
function psb_rfq_latin( $value ) {
	return strtr(
		(string) $value,
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		)
	);
}

/** Latin → Persian digits (display). */
function psb_rfq_fa( $value ) {
	return strtr( (string) $value, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
}

/**
 * Normalise an Iranian mobile number to 09xxxxxxxxx; '' when invalid.
 * Accepts Persian digits, spaces, dashes, +98 / 0098 / 98 prefixes and the 9xxxxxxxxx short form.
 */
function psb_rfq_mobile( $value ) {
	$n = preg_replace( '/\D+/', '', psb_rfq_latin( $value ) );
	if ( 0 === strpos( $n, '0098' ) ) {
		$n = '0' . substr( $n, 4 );
	} elseif ( 0 === strpos( $n, '98' ) && 12 === strlen( $n ) ) {
		$n = '0' . substr( $n, 2 );
	} elseif ( 10 === strlen( $n ) && '9' === $n[0] ) {
		$n = '0' . $n;
	}
	return preg_match( '/^09\d{9}$/', $n ) ? $n : '';
}

/** 09121234567 → 989121234567 (wa.me / international). */
function psb_rfq_intl( $mobile ) {
	$m = psb_rfq_mobile( $mobile );
	if ( $m ) {
		return '98' . substr( $m, 1 );
	}
	return preg_replace( '/\D+/', '', psb_rfq_latin( $mobile ) );
}

/** Any phone → tel: href value (keeps leading +). */
function psb_rfq_tel( $phone ) {
	$p = preg_replace( '/[^\d+]/', '', psb_rfq_latin( $phone ) );
	return $p ? 'tel:' . $p : '';
}

/**
 * Gregorian → Jalali.
 *
 * @return int[] array( y, m, d )
 */
function psb_rfq_g2j( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
	$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];
	$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
	$days %= 12053;
	$jy   += 4 * intdiv( $days, 1461 );
	$days %= 1461;
	if ( $days > 365 ) {
		$jy  += intdiv( $days - 1, 365 );
		$days = ( $days - 1 ) % 365;
	}
	if ( $days < 186 ) {
		$jm = 1 + intdiv( $days, 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + intdiv( $days - 186, 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}
	return array( $jy, $jm, $jd );
}

/**
 * Jalali date string for a timestamp in the site timezone.
 *
 * @param int  $ts        Unix timestamp.
 * @param bool $with_time Append H:i.
 * @param bool $fa        Persian digits.
 */
function psb_rfq_jdate( $ts, $with_time = true, $fa = true ) {
	$dt = psb_rfq_local_dt( $ts );
	list( $y, $m, $d ) = psb_rfq_g2j( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );
	$out = sprintf( '%04d/%02d/%02d', $y, $m, $d );
	if ( $with_time ) {
		$out .= ' ' . $dt->format( 'H:i' );
	}
	return $fa ? psb_rfq_fa( $out ) : $out;
}

/**
 * Gregorian DateTime in the site timezone. Deliberately avoids wp_date()/date_i18n(),
 * which Persian plugins (e.g. Persian WooCommerce) filter into Jalali output.
 */
function psb_rfq_local_dt( $ts ) {
	return ( new DateTimeImmutable( '@' . (int) $ts ) )->setTimezone( wp_timezone() );
}

/** Current Jalali year. */
function psb_rfq_jyear() {
	$dt = psb_rfq_local_dt( time() );
	$j  = psb_rfq_g2j( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );
	return $j[0];
}

/** HMAC with a theme-specific key derived from the site salts. */
function psb_rfq_hmac( $data ) {
	return hash_hmac( 'sha256', (string) $data, wp_salt( 'nonce' ) . '|psb-rfq' );
}

/** URL-safe base64. */
function psb_rfq_b64e( $s ) {
	return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}
function psb_rfq_b64d( $s ) {
	return base64_decode( strtr( (string) $s, '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

/** Visitor IP (REMOTE_ADDR; filter `psb_rfq_client_ip` if the site sits behind a trusted proxy). */
function psb_rfq_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$ip = apply_filters( 'psb_rfq_client_ip', $ip );
	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Fixed-window counter per IP (transient). Returns true while under $limit.
 *
 * @param string $bucket Counter name.
 * @param int    $limit  Max hits per window.
 * @param int    $window Seconds.
 * @param bool   $hit    Increment.
 */
function psb_rfq_rate( $bucket, $limit, $window = 600, $hit = true ) {
	$key  = 'psb_rfq_rl_' . $bucket . '_' . substr( psb_rfq_hmac( psb_rfq_ip() ), 0, 20 );
	$hits = (int) get_transient( $key );
	if ( $limit > 0 && $hits >= $limit ) {
		return false;
	}
	if ( $hit ) {
		set_transient( $key, $hits + 1, $window );
	}
	return true;
}

/** Clean single-line text with a max length. */
function psb_rfq_text( $value, $max = 120 ) {
	if ( is_array( $value ) || is_object( $value ) ) {
		return '';
	}
	return mb_substr( trim( sanitize_text_field( (string) $value ) ), 0, $max );
}

/** Clean multi-line text with a max length. */
function psb_rfq_textarea( $value, $max = 2000 ) {
	if ( is_array( $value ) || is_object( $value ) ) {
		return '';
	}
	return mb_substr( trim( sanitize_textarea_field( (string) $value ) ), 0, $max );
}

/** Reset the stability.php HTTP circuit breaker for a URL (so a test/resend retries immediately). */
function psb_rfq_reset_breaker( $url ) {
	if ( $url && function_exists( 'psb_http_breaker_key' ) ) {
		$key = psb_http_breaker_key( $url );
		if ( $key ) {
			delete_transient( $key );
		}
	}
}

/** Is the stability.php breaker currently open for this URL's host? */
function psb_rfq_breaker_open( $url ) {
	if ( ! $url || ! function_exists( 'psb_http_breaker_key' ) ) {
		return false;
	}
	$key = psb_http_breaker_key( $url );
	return $key && get_transient( $key );
}

/** Human file size (Persian digits). */
function psb_rfq_size( $bytes ) {
	$bytes = (int) $bytes;
	if ( $bytes >= 1048576 ) {
		return psb_rfq_fa( number_format( $bytes / 1048576, 1 ) ) . ' مگابایت';
	}
	return psb_rfq_fa( max( 1, (int) round( $bytes / 1024 ) ) ) . ' کیلوبایت';
}
