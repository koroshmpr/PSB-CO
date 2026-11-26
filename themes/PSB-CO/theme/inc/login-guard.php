<?php
/**
 * Login guard — custom admin login URL (replaces Perfmatters › Login URL).
 *
 * A clean reimplementation of the usual "hide login" approach:
 *  - https://example.com/<slug>/ serves wp-login.php for every action
 *    (login, logout, lostpassword, rp/resetpass, confirmaction, register, interim-login …).
 *  - Guests requesting /wp-login.php, /wp-signup.php or /wp-admin/ get the theme's 404 page
 *    (no redirect, so the slug is never revealed). admin-ajax.php, admin-post.php, wp-cron.php,
 *    the REST API and xmlrpc.php are untouched.
 *  - Every generated wp-login.php link (site_url, network_site_url, wp_redirect, login/logout/
 *    lostpassword/register URLs) is rewritten to the slug, so e-mails carry the right link.
 *  - Password-protected posts keep posting to /wp-login.php?action=postpass, which stays open,
 *    so the slug does not appear in public HTML.
 *
 * Slug: constant PSB_LOGIN_SLUG (wp-config) › option `psb_login_slug` (Settings › General)
 *       › Perfmatters' saved login_url › 'secret-door'.
 *
 * SAFETY (no lockout):
 *  - Inactive while Perfmatters is active with its Login URL set (Perfmatters stays in charge;
 *    its slug is mirrored into `psb_login_slug`, so the same bookmark keeps working afterwards).
 *  - Activates by itself when Perfmatters is deactivated / deleted.
 *  - Emergency off switch: define( 'PSB_LOGIN_GUARD_DISABLE', true ); in wp-config.php
 *    (then /wp-login.php works normally again).
 *  - Switching theme also disables it (it lives in the theme).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_LOGIN_DEFAULT_SLUG = 'secret-door';

if ( ! function_exists( 'psb_perfmatters_active' ) ) {
	/** Perfmatters plugin is loaded (active) on this request. */
	function psb_perfmatters_active() {
		return defined( 'PERFMATTERS_VERSION' );
	}
}

/** Perfmatters is active AND its own Login URL feature is on → it owns the login URL. */
function psb_login_guard_perfmatters_in_charge() {
	if ( ! psb_perfmatters_active() ) {
		return false;
	}
	$pm = get_option( 'perfmatters_options' );
	return is_array( $pm ) && ! empty( $pm['login_url'] );
}

/** Words the slug may never be (WordPress / WooCommerce / theme routes). */
function psb_login_guard_reserved_slugs() {
	return apply_filters(
		'psb_login_guard_reserved_slugs',
		array( 'wp-admin', 'wp-login', 'wp-login-php', 'wp-json', 'wp-content', 'wp-includes', 'admin', 'login', 'logout', 'dashboard', 'register', 'panel', 'my-account', 'shop', 'cart', 'checkout', 'compare', 'request-quote', 'contact-us', 'blog', 'feed', 'search', 'product', 'sitemap_index-xml' )
	);
}

/** The login slug in use. */
function psb_login_guard_slug() {
	$slug = '';
	if ( defined( 'PSB_LOGIN_SLUG' ) && PSB_LOGIN_SLUG ) {
		$slug = (string) PSB_LOGIN_SLUG;
	} else {
		$slug = (string) get_option( 'psb_login_slug', '' );
		if ( '' === $slug ) {
			$pm   = get_option( 'perfmatters_options' );
			$slug = is_array( $pm ) && ! empty( $pm['login_url'] ) ? (string) $pm['login_url'] : '';
		}
	}
	$slug = sanitize_title( $slug );
	return '' !== $slug ? $slug : PSB_LOGIN_DEFAULT_SLUG;
}

/** Full URL of the login page (always with a trailing slash). */
function psb_login_guard_url( $scheme = null ) {
	if ( get_option( 'permalink_structure' ) ) {
		return home_url( '/' . psb_login_guard_slug() . '/', $scheme );
	}
	return add_query_arg( psb_login_guard_slug(), '', home_url( '/', $scheme ) );
}

/** Is the guard doing anything on this request? */
function psb_login_guard_active() {
	static $active = null;
	if ( null === $active ) {
		$active = ! ( defined( 'PSB_LOGIN_GUARD_DISABLE' ) && PSB_LOGIN_GUARD_DISABLE )
			&& ! psb_login_guard_perfmatters_in_charge()
			&& (bool) apply_filters( 'psb_login_guard_enabled', true );
	}
	return $active;
}

/**
 * Classify the current request once, before anything changes $pagenow.
 *
 * @return string 'slug' | 'direct' (wp-login.php / wp-signup.php) | 'admin' | ''
 */
function psb_login_guard_request_type() {
	static $type = null;
	if ( null !== $type ) {
		return $type;
	}
	$type = '';
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) || wp_doing_ajax() ) {
		return $type;
	}
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? rawurldecode( (string) wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$home = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	$slug = psb_login_guard_slug();
	$page = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';

	if ( untrailingslashit( $path ) === $home . '/' . $slug
		|| ( ! get_option( 'permalink_structure' ) && isset( $_GET[ $slug ] ) && '' === $_GET[ $slug ] ) // phpcs:ignore WordPress.Security.NonceVerification
	) {
		$type = 'slug';
	} elseif ( ! is_admin() && ( 'wp-login.php' === $page || false !== stripos( $path, 'wp-login.php' ) || false !== stripos( $path, 'wp-signup.php' ) || false !== stripos( $path, 'wp-register.php' ) ) ) {
		$type = 'direct';
	} elseif ( is_admin() && 'admin-post.php' !== $page && 'admin-ajax.php' !== $page ) {
		$type = 'admin';
	}
	return $type;
}

/**
 * Rewrite a wp-login.php URL to the slug, keeping the query string byte-for-byte.
 * Only URLs whose *path* ends in wp-login.php are touched (not e.g. ?redirect_to=…wp-login.php).
 */
function psb_login_guard_filter_url( $url ) {
	if ( ! is_string( $url ) || false === strpos( $url, 'wp-login.php' ) ) {
		return $url;
	}
	$hash = '';
	if ( false !== ( $p = strpos( $url, '#' ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
		$hash = substr( $url, $p );
		$url  = substr( $url, 0, $p );
	}
	$query = '';
	$base  = $url;
	if ( false !== ( $p = strpos( $url, '?' ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition
		$query = substr( $url, $p );
		$base  = substr( $url, 0, $p );
	}
	if ( 'wp-login.php' !== substr( $base, -12 ) ) {
		return $url . $hash;
	}
	// Password-protected posts keep the plain endpoint (does not reveal the slug in public HTML).
	if ( '' !== $query && preg_match( '/[?&]action=postpass(&|$)/', $query ) ) {
		return $url . $hash;
	}
	$scheme = wp_parse_url( $base, PHP_URL_SCHEME );
	$scheme = $scheme ? $scheme : ( 0 === strpos( $base, '/' ) ? 'relative' : null );
	$new    = psb_login_guard_url( $scheme );
	if ( '' !== $query && false !== strpos( $new, '?' ) ) { // Plain permalinks: ?slug + &query.
		$query = '&' . substr( $query, 1 );
	}
	return $new . $query . $hash;
}

/** Serve the theme's 404 (status 404, noindex, never cached) and stop. */
function psb_login_guard_404() {
	global $wp_query, $pagenow;
	$pagenow = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->set_404();
	}
	status_header( 404 );
	nocache_headers();
	add_filter( 'wp_robots', 'wp_robots_no_robots' );
	$template = (string) apply_filters( 'psb_login_guard_404_template', get_404_template() );
	if ( $template && is_readable( $template ) ) {
		include $template;
	} else {
		wp_die( esc_html__( 'Page not found.' ), '', array( 'response' => 404 ) );
	}
	exit;
}

/** Route the request (wp_loaded: after plugins/theme init, before wp-login.php or wp-admin run). */
function psb_login_guard_route() {
	if ( ! psb_login_guard_active() ) {
		return;
	}
	$type = psb_login_guard_request_type();

	if ( 'admin' === $type && ! is_user_logged_in() ) {
		psb_login_guard_404();
	}

	if ( 'direct' === $type && ! is_user_logged_in() ) {
		// Password-protected posts post here (see psb_login_guard_filter_url()).
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$is_wpl = 'wp-login.php' === ( isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '' );
		if ( ! ( $is_wpl && 'postpass' === $action ) ) {
			psb_login_guard_404();
		}
		return;
	}

	if ( 'slug' === $type ) {
		global $pagenow, $error, $interim_login, $action, $user_login;
		$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		// Already signed in and just opening the bookmark → straight to the dashboard (as Perfmatters did).
		if ( is_user_logged_in() && empty( $_REQUEST['action'] ) && empty( $_REQUEST['redirect_to'] ) && empty( $_REQUEST['interim-login'] ) && 'GET' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' ) ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			wp_safe_redirect( admin_url() );
			exit;
		}
		require_once ABSPATH . 'wp-login.php';
		exit;
	}
}

/*
 * Wiring.
 */
if ( psb_login_guard_active() ) {
	// Classify now (before $pagenow is overridden) and tell early code this is the login screen.
	if ( 'slug' === psb_login_guard_request_type() ) {
		$GLOBALS['pagenow'] = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	add_action( 'wp_loaded', 'psb_login_guard_route', 10 );

	foreach ( array( 'site_url', 'network_site_url', 'wp_redirect', 'login_url', 'logout_url', 'lostpassword_url', 'register_url' ) as $psb_hook ) {
		add_filter( $psb_hook, 'psb_login_guard_filter_url', 100 );
	}
	unset( $psb_hook );

	// /login, /admin, /dashboard on a 404 must not redirect to the hidden login (core's helper would reveal it).
	remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
}

/*
 * Keep `psb_login_slug` in sync with Perfmatters while Perfmatters owns the login URL,
 * so deactivating/deleting Perfmatters never changes the address the owner uses.
 */
add_action(
	'admin_init',
	static function () {
		if ( defined( 'PSB_LOGIN_SLUG' ) || ! psb_login_guard_perfmatters_in_charge() ) {
			return;
		}
		$pm   = get_option( 'perfmatters_options' );
		$slug = sanitize_title( (string) $pm['login_url'] );
		if ( '' !== $slug && get_option( 'psb_login_slug', '' ) !== $slug ) {
			update_option( 'psb_login_slug', $slug, true );
		}
	}
);

/*
 * Settings › General › «نشانی ورود مدیریت».
 */
function psb_login_guard_sanitize_slug( $value ) {
	$old  = (string) get_option( 'psb_login_slug', '' );
	$slug = sanitize_title( (string) $value );
	if ( '' === $slug ) {
		return '' !== $old ? $old : PSB_LOGIN_DEFAULT_SLUG;
	}
	if ( $slug === $old ) {
		return $slug;
	}
	$taken = in_array( $slug, psb_login_guard_reserved_slugs(), true )
		|| get_page_by_path( $slug, OBJECT, array( 'page', 'post', 'product' ) )
		|| strlen( $slug ) < 4;
	if ( $taken ) {
		if ( function_exists( 'add_settings_error' ) ) {
			add_settings_error( 'psb_login_slug', 'psb_login_slug_taken', 'این نشانی ورود قابل استفاده نیست (رزرو شده، کوتاه‌تر از ۴ حرف، یا نامک یک برگه/نوشته است). نشانی قبلی حفظ شد.' );
		}
		return '' !== $old ? $old : PSB_LOGIN_DEFAULT_SLUG;
	}
	return $slug;
}

add_action(
	'admin_init',
	static function () {
		register_setting(
			'general',
			'psb_login_slug',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'psb_login_guard_sanitize_slug',
				'show_in_rest'      => false,
				'default'           => '',
			)
		);
		add_settings_field(
			'psb_login_slug',
			'نشانی ورود مدیریت',
			'psb_login_guard_render_field',
			'general',
			'default',
			array( 'label_for' => 'psb_login_slug' )
		);
	}
);

function psb_login_guard_render_field() {
	$locked = defined( 'PSB_LOGIN_SLUG' ) || psb_login_guard_perfmatters_in_charge();
	printf(
		'<code dir="ltr">%s</code><input name="psb_login_slug" type="text" id="psb_login_slug" value="%s" class="regular-text code" dir="ltr" %s><code dir="ltr">/</code>',
		esc_html( trailingslashit( home_url() ) ),
		esc_attr( psb_login_guard_slug() ),
		$locked ? 'readonly' : ''
	);
	echo '<p class="description">';
	if ( defined( 'PSB_LOGIN_GUARD_DISABLE' ) && PSB_LOGIN_GUARD_DISABLE ) {
		echo 'محافظ ورود با PSB_LOGIN_GUARD_DISABLE در wp-config خاموش است؛ ورود از ‎/wp-login.php انجام می‌شود.';
	} elseif ( defined( 'PSB_LOGIN_SLUG' ) ) {
		echo 'این نشانی با ثابت PSB_LOGIN_SLUG در wp-config تعیین شده و از اینجا قابل تغییر نیست.';
	} elseif ( psb_login_guard_perfmatters_in_charge() ) {
		echo 'فعلاً افزونه Perfmatters نشانی ورود را کنترل می‌کند؛ پس از غیرفعال‌کردن آن، قالب همین نشانی را ادامه می‌دهد.';
	} else {
		echo 'صفحه ورود مدیریت فقط از این نشانی باز می‌شود و ‎/wp-login.php و ‎/wp-admin/ برای مهمان‌ها ۴۰۴ می‌دهند. پس از تغییر، نشانی تازه را نشانه‌گذاری (Bookmark) کنید.';
	}
	echo '</p>';
}
