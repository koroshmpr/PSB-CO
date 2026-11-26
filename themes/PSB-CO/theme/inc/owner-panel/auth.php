<?php
/**
 * Owner panel — long-lived logins.
 *
 * How it works
 *  - WordPress keeps a "remember me" login for 14 days. Only logins made through the /panel/ login form
 *    (psb_panel_handle_login() raises a request flag) get the configured number of days (default 180), and
 *    their session token is marked `psb_panel`. Every other login (wp-login.php, WooCommerce, theme OTP or
 *    classic AJAX login) keeps the WordPress default, even for panel users (security review M1).
 *  - Each visit to /panel/ silently extends a panel session that has fewer than `refresh_days` left
 *    (default 30), but only while the session's original login time is less than `session_days` old.
 *    After that the session simply runs out and the manager signs in again (no indefinite extension).
 *  - «خروج از همه دستگاه‌ها» destroys every session token of the user (WP_Session_Tokens::destroy_all()),
 *    which immediately invalidates the cookies on every phone/computer.
 *
 * Trade-off: a stolen/unlocked phone keeps access for at most about 2 × `session_days` − `refresh_days` after the
 * original sign-in (one last refresh can happen just before `session_days`). Mitigations: HttpOnly + Secure
 * cookies (WordPress default over HTTPS), sessions are revocable per user, the panel is limited to chosen roles,
 * and the settings page lets you shorten the period. Wordfence's login protection (rate limits, 2FA) stays in
 * charge of the actual sign-in because the panel uses wp_signon().
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'auth_cookie_expiration', 'psb_panel_cookie_expiration', 20, 3 );
add_filter( 'attach_session_information', 'psb_panel_mark_session', 10, 2 );

/**
 * Request flag: true only while the /panel/ login form (or the panel refresh) issues a cookie.
 *
 * @param bool|null $set Set the flag (null = read).
 */
function psb_panel_login_flow( $set = null ) {
	static $flag = false;
	if ( null !== $set ) {
		$flag = (bool) $set;
	}
	return $flag;
}

/**
 * @param int  $length  Seconds.
 * @param int  $user_id User ID.
 * @param bool $remember Remember-me login.
 */
function psb_panel_cookie_expiration( $length, $user_id, $remember ) {
	if ( ! psb_panel_login_flow() || ! $remember || ! $user_id || ! psb_panel_can( (int) $user_id ) ) {
		return $length;
	}
	return max( (int) $length, psb_panel_settings()['session_days'] * DAY_IN_SECONDS );
}

/** Mark session tokens created by the panel login form, so only those are refreshed later. */
function psb_panel_mark_session( $info, $user_id ) {
	if ( psb_panel_login_flow() && psb_panel_can( (int) $user_id ) ) {
		$info['psb_panel'] = 1;
	}
	return $info;
}

/** Extend a panel session that is getting close to its end (called on panel visits, before output). */
function psb_panel_maybe_refresh_cookie() {
	$set  = psb_panel_settings();
	$days = $set['refresh_days'];
	if ( $days <= 0 || headers_sent() || ! class_exists( 'WP_Session_Tokens' ) ) {
		return;
	}
	$uid   = get_current_user_id();
	$token = wp_get_session_token();
	if ( ! $uid || ! $token || ! psb_panel_can() ) {
		return;
	}
	$mgr  = WP_Session_Tokens::get_instance( $uid );
	$sess = $mgr->get( $token );
	if ( ! is_array( $sess ) || empty( $sess['expiration'] ) || empty( $sess['psb_panel'] ) ) {
		return; // Only sessions started from the /panel/ login form.
	}
	$login = isset( $sess['login'] ) ? (int) $sess['login'] : 0;
	if ( ! $login || ( $sess['expiration'] - $login ) <= 3 * DAY_IN_SECONDS ) {
		return; // Not a remembered login: leave it alone.
	}
	if ( time() - $login > $set['session_days'] * DAY_IN_SECONDS ) {
		return; // Absolute limit reached: no more extensions, the session runs out.
	}
	if ( $sess['expiration'] - time() > $days * DAY_IN_SECONDS ) {
		return;
	}
	psb_panel_login_flow( true );
	$len                = (int) apply_filters( 'auth_cookie_expiration', 14 * DAY_IN_SECONDS, $uid, true );
	$sess['expiration'] = time() + $len;
	$mgr->update( $token, $sess );
	wp_set_auth_cookie( $uid, true, is_ssl(), $token );
	psb_panel_login_flow( false );
}

/** Failed panel logins per IP (in addition to Wordfence). */
function psb_panel_login_rate_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return 'psb_panel_lf_' . substr( md5( $ip . wp_salt( 'auth' ) ), 0, 16 );
}

/**
 * Handle the panel login POST. Redirects and exits on success; returns an error message otherwise.
 *
 * @return string
 */
function psb_panel_handle_login() {
	if ( ! isset( $_POST['psb_panel_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['psb_panel_nonce'] ) ), 'psb_panel_login' ) ) {
		return 'نشست فرم منقضی شده است؛ دوباره تلاش کنید.';
	}
	$rk    = psb_panel_login_rate_key();
	$fails = (int) get_transient( $rk );
	if ( $fails >= 8 ) {
		return 'تعداد تلاش‌های ناموفق زیاد بود. ۱۵ دقیقه دیگر دوباره تلاش کنید.';
	}
	$login = isset( $_POST['log'] ) ? trim( psb_panel_latin( sanitize_text_field( wp_unslash( $_POST['log'] ) ) ) ) : '';
	$pass  = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- passwords are not sanitized.
	if ( '' === $login || '' === $pass ) {
		return 'نام کاربری (یا ایمیل) و رمز عبور را وارد کنید.';
	}
	$remember = ! empty( $_POST['rememberme'] );
	psb_panel_login_flow( true ); // Long lifetime only for this form (security review M1).
	$user = wp_signon(
		array(
			'user_login'    => $login,
			'user_password' => $pass,
			'remember'      => $remember,
		),
		is_ssl()
	);
	psb_panel_login_flow( false );
	if ( is_wp_error( $user ) ) {
		set_transient( $rk, $fails + 1, 15 * MINUTE_IN_SECONDS );
		$code = $user->get_error_code();
		// Wordfence Login Security wants something this form cannot provide (security review L9).
		$wfls = psb_panel_wfls_blocks_login( $code, $login, $pass );
		if ( 'twofactor' === $wfls ) {
			psb_panel_login_needs_wp_login( true );
			return 'برای این حساب ورود دومرحله‌ای (Wordfence 2FA) فعال است و این فرم نمی‌تواند کد آن را بپذیرد. از صفحه ورود وردپرس وارد شوید؛ پس از ورود به همین پنل برمی‌گردید.';
		}
		if ( 'captcha' === $wfls ) {
			return 'Wordfence برای این ورود تأیید امنیتی (CAPTCHA یا ایمیل تأیید) خواسته است که این فرم پشتیبانی نمی‌کند. لطفاً از صفحه ورود اصلی وردپرس وارد شوید.';
		}
		if ( in_array( $code, array( 'invalid_username', 'incorrect_password', 'invalid_email', 'empty_username', 'empty_password' ), true ) ) {
			return 'نام کاربری یا رمز عبور درست نیست.';
		}
		$msg = trim( wp_strip_all_tags( $user->get_error_message() ) );
		return $msg ? $msg : 'ورود انجام نشد. از صفحه ورود وردپرس امتحان کنید.';
	}
	delete_transient( $rk );
	$to = isset( $_POST['to'] ) ? preg_replace( '#[^A-Za-z0-9/_-]#', '', (string) wp_unslash( $_POST['to'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- whitelisted characters.
	$to = ( $to && 0 === strpos( $to, psb_panel_base() ) && false === strpos( $to, '//' ) ) ? $to : psb_panel_base();
	wp_safe_redirect( $to, 303 );
	exit;
}

/**
 * Request flag: the last panel login failed because Wordfence needs its own login screen.
 *
 * @param bool|null $set Set the flag (null = read).
 */
function psb_panel_login_needs_wp_login( $set = null ) {
	static $flag = false;
	if ( null !== $set ) {
		$flag = (bool) $set;
	}
	return $flag;
}

/**
 * Did Wordfence Login Security refuse this login for a reason the panel form cannot satisfy?
 * Guarded: Wordfence may be inactive (no WordfenceLS classes).
 *
 *  'twofactor' — the password was right but WFLS wants a 2FA code (or 2FA is mandatory for the account).
 *                Detected from the wfls_twofactor_* error, or from WordfenceLS\Controller_Users::has_2fa_active()
 *                plus a correct password, so the (hidden) login URL is only shown to someone who knows it.
 *  'captcha'   — WFLS reCAPTCHA / e-mail verification (no link: that error can come before the password check).
 *  false       — not a Wordfence refusal.
 *
 * @param string $code  WP_Error code from wp_signon().
 * @param string $login Submitted user name or email.
 * @param string $pass  Submitted password.
 * @return string|false
 */
function psb_panel_wfls_blocks_login( $code, $login, $pass ) {
	$code = (string) $code;
	if ( 0 === strpos( $code, 'wfls_twofactor' ) ) {
		return 'twofactor';
	}
	if ( 0 !== strpos( $code, 'wfls_' ) ) {
		return false;
	}
	if ( class_exists( 'WordfenceLS\\Controller_Users' ) && method_exists( 'WordfenceLS\\Controller_Users', 'shared' ) ) {
		$user = is_email( $login ) ? get_user_by( 'email', $login ) : get_user_by( 'login', $login );
		try {
			$ctl = \WordfenceLS\Controller_Users::shared();
			if ( $user && method_exists( $ctl, 'has_2fa_active' ) && $ctl->has_2fa_active( $user ) && wp_check_password( $pass, $user->user_pass, $user->ID ) ) {
				return 'twofactor';
			}
		} catch ( Throwable $e ) {
			unset( $e ); // Fall through.
		}
	}
	return 'captcha';
}

/** Is the theme's Kavenegar OTP login (inc/otp-login-form.php) usable? */
function psb_panel_otp_available() {
	if ( ! function_exists( 'handle_kavenegar_send_otp' ) || ! function_exists( 'handle_kavenegar_verify_otp' ) ) {
		return false;
	}
	$key = defined( 'KAVENEGAR_API_KEY' ) ? KAVENEGAR_API_KEY : ( function_exists( 'get_field' ) ? get_field( 'kavenegar_api_key', 'option' ) : '' );
	return is_string( $key ) && '' !== trim( $key ) && 'YOUR_KAVENEGAR_API_KEY' !== $key;
}

/**
 * Sign out of every device (or every other device).
 *
 * @param bool $others_only Keep the current session.
 */
function psb_panel_destroy_sessions( $others_only = false ) {
	$uid = get_current_user_id();
	if ( ! $uid || ! class_exists( 'WP_Session_Tokens' ) ) {
		return false;
	}
	$mgr = WP_Session_Tokens::get_instance( $uid );
	if ( $others_only ) {
		$mgr->destroy_others( wp_get_session_token() );
	} else {
		$mgr->destroy_all();
		wp_clear_auth_cookie();
	}
	return true;
}
