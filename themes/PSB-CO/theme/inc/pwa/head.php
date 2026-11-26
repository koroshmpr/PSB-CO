<?php
/**
 * PWA — <head> tags and the deferred registration / install-prompt script.
 * All output is identical for every visitor (safe for Cache Enabler).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'psb_pwa_head_tags', 2 );

/**
 * Manifest, theme colours and iOS home-screen tags.
 *
 * Note: header.php also prints an unconditional <meta name="theme-color" content="#0B1220"> before wp_head();
 * browsers use the first matching tag, so drop that line if the light/dark pair below should take effect.
 */
function psb_pwa_head_tags() {
	if ( ! psb_pwa_enabled() || is_admin() ) {
		return;
	}
	$light = apply_filters( 'psb_pwa_theme_color_light', '#FFFFFF' );
	$dark  = apply_filters( 'psb_pwa_theme_color_dark', PSB_PWA_DARK );
	// "default" keeps dark status-bar text over the white sticky header; "black-translucent" would put
	// white clock/battery icons over it (invisible). Switch with the filter if the header becomes dark.
	$bar = apply_filters( 'psb_pwa_status_bar_style', 'default' );

	echo '<link rel="manifest" href="' . esc_url( home_url( '/manifest.webmanifest' ) ) . '">' . "\n";
	echo '<meta name="theme-color" media="(prefers-color-scheme: light)" content="' . esc_attr( $light ) . '">' . "\n";
	echo '<meta name="theme-color" media="(prefers-color-scheme: dark)" content="' . esc_attr( $dark ) . '">' . "\n";
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="' . esc_attr( $bar ) . '">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="PSB">' . "\n";
	echo '<meta name="application-name" content="پارس سوئیچ بورد">' . "\n";
	echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( psb_pwa_asset( 'icons/apple-touch-icon.png' ) ) . '">' . "\n";
}

/** Our crisp 180px icon replaces the (upscaled) site-icon apple-touch-icon WordPress prints. */
add_filter(
	'site_icon_meta_tags',
	static function ( $tags ) {
		if ( ! psb_pwa_enabled() || is_admin() ) {
			return $tags;
		}
		return array_values(
			array_filter(
				(array) $tags,
				static function ( $t ) {
					return false === strpos( (string) $t, 'apple-touch-icon' );
				}
			)
		);
	}
);

/** Registration + install prompt: one small deferred file, front end only, only over HTTPS. */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! psb_pwa_enabled() || ! is_ssl() ) {
			return;
		}
		$s = psb_pwa_settings();
		wp_register_script(
			'psb-pwa',
			psb_pwa_asset( 'pwa.js' ),
			array(),
			(string) filemtime( get_template_directory() . '/pwa/pwa.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		$cfg = array(
			'sw'     => psb_pwa_home_path() . 'sw.js',
			'scope'  => psb_pwa_home_path(),
			'prompt' => (int) $s['prompt'],
			'delay'  => max( 5, (int) $s['prompt_delay'] ),
			'ios'    => (int) $s['ios'],
			'icon'   => psb_pwa_asset( 'icons/icon-192.png' ),
			'stat'   => esc_url_raw( rest_url( 'psb-pwa/v1/stat' ) ),
			// No install sheet on these paths (forms / account / checkout).
			'quiet'  => array_values(
				array_filter(
					array_merge( psb_pwa_bypass_paths(), array( psb_pwa_home_path() . 'request-quote' ) ),
					static function ( $p ) {
						return 0 !== strpos( $p, '/wp-' );
					}
				)
			),
		);
		wp_add_inline_script( 'psb-pwa', 'window.psbPWA=' . wp_json_encode( $cfg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';', 'before' );
		wp_enqueue_script( 'psb-pwa' );
	},
	20
);
