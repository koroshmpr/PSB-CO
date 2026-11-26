<?php
/**
 * Stability & server-load guards.
 *
 * The host cannot reach wordpress.org (DNS resolution times out after 10s), and
 * every blocked outbound request stalls a PHP worker. On a CloudLinux/LVE shared
 * host that pile-up shows up as slow pages and intermittent 500/508 errors.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Circuit breaker for outbound HTTP.
 *
 * When a host fails (timeout / DNS / connect error), further requests to it are
 * short-circuited for an hour instead of blocking a worker again and again.
 * Applies only to the allowlisted non-essential hosts below.
 */
const PSB_HTTP_BREAKER_TTL = HOUR_IN_SECONDS;

/**
 * Only non-essential update/licence/telemetry hosts may be short-circuited.
 * Business-critical hosts (SMS gateway, Bale/Telegram, payment, Wordfence) are never skipped.
 */
function psb_http_breaker_applies( $url ) {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	if ( '' === $host ) {
		return false;
	}
	$hosts = apply_filters(
		'psb_http_breaker_hosts',
		array( 'wordpress.org', 'w.org', 'wp.com', 'woocommerce.com', 'rankmath.com', 'gravityforms.com', 'gravityhelp.com', 'perfmatters.io', 'updraftplus.com', 'teamupdraft.com', 'advancedcustomfields.com', 'wpengine.com' )
	);
	foreach ( $hosts as $h ) {
		if ( $host === $h || substr( $host, -strlen( '.' . $h ) ) === '.' . $h ) {
			return true;
		}
	}
	return false;
}

/**
 * An administrator explicitly installing/updating something (plugins/themes/core screens or
 * their AJAX actions). Those requests are never short-circuited — the breaker only protects
 * visitors and background checks.
 */
function psb_http_breaker_user_action() {
	if ( ! is_admin() || ! function_exists( 'current_user_can' ) || ! current_user_can( 'update_plugins' ) ) {
		return false;
	}
	global $pagenow;
	if ( in_array( $pagenow, array( 'update.php', 'update-core.php', 'plugin-install.php', 'theme-install.php' ), true ) ) {
		return true;
	}
	$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return wp_doing_ajax() && in_array( $action, array( 'update-plugin', 'install-plugin', 'update-theme', 'install-theme', 'search-install-plugins' ), true );
}

function psb_http_breaker_key( $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	return $host ? 'psb_http_down_' . md5( strtolower( $host ) ) : '';
}

add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) {
		if ( false !== $pre ) {
			return $pre;
		}
		if ( ! psb_http_breaker_applies( $url ) ) {
			return $pre;
		}
		$key = psb_http_breaker_key( $url );
		if ( psb_http_breaker_user_action() ) {
			// Manual update/install: always try for real, and start from a clean slate.
			if ( $key ) {
				delete_transient( $key );
				delete_transient( $key . '_n' );
			}
			return $pre;
		}
		if ( $key && get_transient( $key ) ) {
			return new WP_Error( 'psb_http_breaker', 'Host temporarily skipped after a recent connection failure.' );
		}
		return $pre;
	},
	1,
	3
);

add_action(
	'http_api_debug',
	static function ( $response, $context, $class, $args, $url ) {
		if ( ! is_wp_error( $response ) || 'psb_http_breaker' === $response->get_error_code() ) {
			return;
		}
		$msg = strtolower( $response->get_error_message() );
		if ( preg_match( '/timed out|resolv|could not connect|connection refused|operation timed|curl error (6|7|28)/', $msg ) ) {
			$key = psb_http_breaker_key( $url );
			if ( $key && psb_http_breaker_applies( $url ) ) {
				// Trip only after 2 failures within 10 minutes.
				$strikes = (int) get_transient( $key . '_n' ) + 1;
				set_transient( $key . '_n', $strikes, 10 * MINUTE_IN_SECONDS );
				if ( $strikes >= 2 ) {
					set_transient( $key, 1, PSB_HTTP_BREAKER_TTL );
					delete_transient( $key . '_n' );
				}
			}
		}
	},
	10,
	5
);

/**
 * Never let a single outbound request hold a worker for long.
 */
add_filter(
	'http_request_args',
	static function ( $args ) {
		// File downloads (plugin/theme/core packages stream to disk) and manual updates keep their own timeout.
		if ( ! empty( $args['stream'] ) || ! empty( $args['filename'] ) || psb_http_breaker_user_action() ) {
			return $args;
		}
		if ( empty( $args['timeout'] ) || $args['timeout'] > 8 ) {
			$args['timeout'] = 8;
		}
		return $args;
	},
	99
);

/**
 * Heartbeat: off on the front end, slowed down in the admin.
 */
add_action(
	'init',
	static function () {
		if ( ! is_admin() ) {
			wp_deregister_script( 'heartbeat' );
		}
	},
	1
);
add_filter(
	'heartbeat_settings',
	static function ( $settings ) {
		$settings['interval'] = 60;
		return $settings;
	}
);

/**
 * Fewer revisions = smaller posts table and faster saves.
 */
add_filter(
	'wp_revisions_to_keep',
	static function ( $num ) {
		return ( $num < 0 || $num > 15 ) ? 15 : $num;
	}
);

/**
 * Page-cache exclusions (Cache Enabler honours DONOTCACHEPAGE).
 * WooCommerce already flags cart/checkout/my-account itself.
 */
add_action(
	'template_redirect',
	static function () {
		$nocache = is_user_logged_in()
			|| is_search()
			|| is_page_template( array( 'login.php', 'page-compare.php' ) )
			|| is_page( array( 'login', 'compare' ) )
			|| ( function_exists( 'is_account_page' ) && ( is_account_page() || is_cart() || is_checkout() ) );
		if ( $nocache && ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	},
	0
);

/**
 * WP-Cron: a real server cron hits wp-cron.php every 5 minutes (cPanel).
 * While that heartbeat is fresh, stop spawning cron on visitor requests.
 * If the server cron stops (no hit for 15 minutes), spawning resumes automatically,
 * so scheduled posts are never missed.
 */
if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
	$psb_last = (int) get_option( 'psb_real_cron_seen', 0 );
	if ( time() - $psb_last > 120 ) {
		update_option( 'psb_real_cron_seen', time(), false );
	}
} elseif ( ! defined( 'DISABLE_WP_CRON' ) && time() - (int) get_option( 'psb_real_cron_seen', 0 ) < 15 * MINUTE_IN_SECONDS ) {
	remove_action( 'init', 'wp_cron' );
	remove_action( 'wp_loaded', '_wp_cron' );
}
