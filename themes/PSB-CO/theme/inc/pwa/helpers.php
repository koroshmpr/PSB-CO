<?php
/**
 * PWA — settings, versioning, manifest and service-worker configuration.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_PWA_OPTION = 'psb_pwa_settings';
const PSB_PWA_STATS  = 'psb_pwa_stats';

/** Brand colours (design tokens: --color-dark, --color-primary). */
const PSB_PWA_DARK    = '#0B1220';
const PSB_PWA_PRIMARY = '#1A3DA3';

function psb_pwa_defaults() {
	return array(
		'enabled'      => 1,  // 0 = serve a self-unregistering service worker (kill switch) and print no PWA tags.
		'prompt'       => 1,  // custom install bottom sheet.
		'prompt_delay' => 30, // seconds on a page before the sheet may appear (or the 2nd page view).
		'ios'          => 1,  // iOS «Share → Add to Home Screen» instructions.
	);
}

function psb_pwa_settings() {
	$s = get_option( PSB_PWA_OPTION, array() );
	return array_merge( psb_pwa_defaults(), is_array( $s ) ? $s : array() );
}

function psb_pwa_setting( $key ) {
	$s = psb_pwa_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : null;
}

function psb_pwa_enabled() {
	return (bool) apply_filters( 'psb_pwa_enabled', (bool) psb_pwa_setting( 'enabled' ) );
}

/** Site-relative home path ("/" on the live site). */
function psb_pwa_home_path() {
	return rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' ) . '/';
}

/** Public URL of a file under theme/pwa/. */
function psb_pwa_asset( $rel ) {
	return get_template_directory_uri() . '/pwa/' . ltrim( $rel, '/' );
}

/** Asset URL with ?v=filemtime (used for pwa.js). */
function psb_pwa_asset_ver( $rel ) {
	$file = get_template_directory() . '/pwa/' . ltrim( $rel, '/' );
	return psb_pwa_asset( $rel ) . '?v=' . ( file_exists( $file ) ? filemtime( $file ) : PSB_PWA_VERSION );
}

/** Theme version + mtimes of the app-shell files: any deploy changes it and so the SW bytes. */
function psb_pwa_version() {
	static $v = null;
	if ( null !== $v ) {
		return $v;
	}
	$dir = get_template_directory();
	$sig = PSB_PWA_VERSION . '|' . wp_get_theme()->get( 'Version' ) . '|' . ( defined( 'BLUEBOX_VERSION' ) ? BLUEBOX_VERSION : '' );
	foreach ( array( 'style.css', 'js/script.min.js', 'pwa/sw.js', 'pwa/pwa.js', 'inc/pwa/routes.php' ) as $f ) {
		$sig .= '|' . $f . ( file_exists( $dir . '/' . $f ) ? filemtime( $dir . '/' . $f ) : '' );
	}
	$sig .= '|' . md5( wp_json_encode( psb_pwa_settings() ) . wp_json_encode( psb_pwa_offline_phone() ) );
	$v    = substr( md5( $sig ), 0, 10 );
	return $v;
}

/** Same version string the theme enqueues its assets with (so precached URLs match the page). */
function psb_pwa_theme_ver( $rel ) {
	if ( function_exists( 'psb_asset_ver' ) ) {
		return psb_asset_ver( $rel );
	}
	$path = get_template_directory() . '/' . $rel;
	return ( defined( 'BLUEBOX_VERSION' ) ? BLUEBOX_VERSION : '1' ) . ( file_exists( $path ) ? '.' . filemtime( $path ) : '' );
}

function psb_pwa_rel( $url ) {
	return (string) wp_parse_url( $url, PHP_URL_PATH ) . ( wp_parse_url( $url, PHP_URL_QUERY ) ? '?' . wp_parse_url( $url, PHP_URL_QUERY ) : '' );
}

/** App shell precached at install (site-relative URLs). Missing files are skipped by the SW. */
function psb_pwa_precache() {
	$theme = get_template_directory_uri();
	$urls  = array(
		psb_pwa_home_path() . 'pwa-offline/',
		$theme . '/style.css?ver=' . psb_pwa_theme_ver( 'style.css' ),
		$theme . '/js/script.min.js?ver=' . psb_pwa_theme_ver( 'js/script.min.js' ),
		$theme . '/fonts/Peyda/fonts/woff2/PeydaFa-Regular-s1.woff2',
		$theme . '/fonts/Peyda/fonts/woff2/PeydaFa-Bold-s1.woff2',
		psb_pwa_asset( 'icons/icon-192.png' ),
		psb_pwa_asset( 'icons/apple-touch-icon.png' ),
	);
	$urls[] = $theme . '/images/brand/psb-logo.svg';
	$urls[] = $theme . '/images/brand/psb-mark.svg';
	$urls = array_map( 'psb_pwa_rel', $urls );
	return array_values( array_unique( apply_filters( 'psb_pwa_precache', $urls ) ) );
}

/**
 * Paths the service worker must never touch (network only, never cached).
 * Prefix match on the request path.
 */
function psb_pwa_bypass_paths() {
	$home  = psb_pwa_home_path();
	$paths = array(
		'/wp-admin', '/wp-login.php', '/wp-json', '/wp-cron.php', '/xmlrpc.php', '/wp-signup.php', '/wp-activate.php',
		$home . 'login', $home . 'my-account', $home . 'cart', $home . 'checkout', $home . 'compare',
		$home . 'panel/', $home . 'sw.js', $home . 'manifest.webmanifest', $home . 'feed', $home . 'comments/feed',
		$home . 'llms', $home . 'ai.txt', $home . 'robots.txt', $home . 'sitemap', $home . 'wp-content/uploads/psb-rfq',
	);
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $p ) {
			$id = wc_get_page_id( $p );
			if ( $id > 0 ) {
				$path = (string) wp_parse_url( get_permalink( $id ), PHP_URL_PATH );
				if ( $path && '/' !== $path ) {
					$paths[] = rtrim( urldecode( $path ), '/' );
				}
			}
		}
	}
	return array_values( array_unique( apply_filters( 'psb_pwa_bypass_paths', $paths ) ) );
}

/** Query parameters that make a request private / dynamic (never cached by the SW). */
function psb_pwa_bypass_params() {
	return apply_filters(
		'psb_pwa_bypass_params',
		array( 'preview', 'preview_id', 'preview_nonce', '_wpnonce', 'nonce', '_wp_http_referer', 'add-to-cart', 'remove_item', 'undo_item', 'wc-ajax', 'wc-api', 'customize_changeset_uuid', 'customize_theme', 'doing_wp_cron', 'action', 'key', 'token', 'redirect_to', 'loggedout', 'reauth', 's', 'rfq', 'code' )
	);
}

function psb_pwa_manifest() {
	$home  = psb_pwa_home_path();
	$icons = array(
		array( 'src' => psb_pwa_asset( 'icons/icon-192.png' ), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
		array( 'src' => psb_pwa_asset( 'icons/icon-512.png' ), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
		array( 'src' => psb_pwa_asset( 'icons/maskable-192.png' ), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable' ),
		array( 'src' => psb_pwa_asset( 'icons/maskable-512.png' ), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
	);
	$sc_icon   = array( array( 'src' => psb_pwa_asset( 'icons/shortcut-96.png' ), 'sizes' => '96x96', 'type' => 'image/png' ) );
	$shortcuts = array(
		array( 'name' => 'استعلام قیمت', 'short_name' => 'استعلام', 'description' => 'ثبت درخواست استعلام قیمت تابلو برق', 'url' => $home . 'request-quote/?source=pwa', 'icons' => $sc_icon ),
		array( 'name' => 'محصولات', 'short_name' => 'محصولات', 'url' => $home . 'shop/?source=pwa', 'icons' => $sc_icon ),
		array( 'name' => 'تماس با شرکت', 'short_name' => 'تماس', 'url' => $home . 'contact-us/?source=pwa', 'icons' => $sc_icon ),
	);
	$shots = array();
	foreach ( array( array( 'screen-narrow.png', '780x1688', 'narrow' ), array( 'screen-wide.png', '1440x900', 'wide' ) ) as $s ) {
		if ( file_exists( get_template_directory() . '/pwa/icons/' . $s[0] ) ) {
			$shots[] = array( 'src' => psb_pwa_asset( 'icons/' . $s[0] ), 'sizes' => $s[1], 'type' => 'image/png', 'form_factor' => $s[2], 'label' => 'پارس سوئیچ بورد' );
		}
	}
	$m = array(
		'id'               => $home,
		'name'             => 'پارس سوئیچ بورد',
		'short_name'       => 'PSB',
		'description'      => 'پارس سوئیچ بورد — طراحی و ساخت تابلو برق فشار ضعیف، فشار متوسط و کمپکت؛ استعلام قیمت و مشاوره فنی.',
		'lang'             => 'fa',
		'dir'              => 'rtl',
		'start_url'        => $home . '?source=pwa',
		'scope'            => $home,
		'display'          => 'standalone',
		'display_override' => array( 'standalone' ),
		'orientation'      => 'portrait',
		'background_color' => PSB_PWA_DARK,
		'theme_color'      => PSB_PWA_DARK,
		'categories'       => array( 'business', 'productivity', 'shopping' ),
		'prefer_related_applications' => false,
		'icons'            => $icons,
		'shortcuts'        => $shortcuts,
	);
	if ( $shots ) {
		$m['screenshots'] = $shots;
	}
	return apply_filters( 'psb_pwa_manifest', $m );
}

/** First public phone number for the offline page (ACF option «phones» via the design helpers). */
function psb_pwa_offline_phone() {
	if ( function_exists( 'psb_phones' ) ) {
		$p = psb_phones();
		if ( $p ) {
			return array( 'number' => $p[0]['number'], 'href' => $p[0]['href'] );
		}
	}
	return array();
}

/** Install-funnel counters. */
function psb_pwa_stats() {
	$s = get_option( PSB_PWA_STATS, array() );
	return array_merge( array( 'prompt' => 0, 'accepted' => 0, 'dismissed' => 0, 'installed' => 0, 'ios' => 0, 'standalone' => 0 ), is_array( $s ) ? $s : array() );
}
