<?php
/**
 * Front-end performance module — PSB-CO theme.
 *
 * 1. Head clean-up: emojis, oEmbed/embeds, generator, RSD, WLW, shortlink, REST link, feeds (extra).
 * 2. Dequeues block-library / global styles / classic-theme styles (classic templates, no blocks).
 * 3. WooCommerce assets only on Woo pages (+ compare page); order attribution disabled.
 * 4. jQuery: migrate removed; moved to the footer on pages that do not need it in <head>
 *    (no WooCommerce, no Gravity Forms). jQuery is never `defer`red, so inline
 *    jQuery from plugins keeps working.
 * 5. Heartbeat & dashicons off for anonymous visitors.
 * 6. Content images: loading=lazy + decoding=async when missing.
 * 7. Speculation Rules: prerender with "moderate" eagerness (hover ~200ms / pointer-down) for instant navigation.
 *
 * @package bluebox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pages that need WooCommerce front-end assets.
 */
function psb_is_woocommerce_active_page() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}
	return ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() || is_page( 'compare' ) );
}

/**
 * Pages that render a Gravity Form (contact / form templates or a [gravityform] shortcode/block).
 */
function psb_page_has_gravity_form() {
	if ( is_page_template( array( 'contact-us.php', 'form.php' ) ) || is_page( array( 'contact-us', 'login' ) ) ) {
		return true;
	}
	if ( is_singular() ) {
		$post = get_post();
		if ( $post && ( has_shortcode( $post->post_content, 'gravityform' ) || has_block( 'gravityforms/form', $post ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * 1. Head clean-up.
 */
function psb_optimize_site() {
	// Emojis.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter(
		'tiny_mce_plugins',
		function ( $plugins ) {
			return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
		}
	);
	add_filter( 'emoji_svg_url', '__return_false' );

	// Discovery links & generator.
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'rest_output_link_wp_head' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

	// oEmbed / embeds (this site does not embed other WP posts).
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	add_filter( 'embed_oembed_discover', '__return_false' );

	// jQuery Migrate.
	add_action(
		'wp_default_scripts',
		function ( $scripts ) {
			if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
				$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
			}
		}
	);
}
add_action( 'init', 'psb_optimize_site' );

// Inline SVG duotone filters (WP 5.9+).
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

// WooCommerce order attribution (sourcebuster.js).
add_filter( 'woocommerce_order_attribution_enabled_in_storefront', '__return_false' );

/**
 * 2–5. Dequeue what this classic theme never uses.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_admin() ) {
			return;
		}

		// Block styles (classic templates).
		foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles', 'core-block-supports', 'wc-blocks-style', 'wc-blocks-style-rtl', 'wc-blocks-vendors-style' ) as $handle ) {
			wp_dequeue_style( $handle );
		}

		wp_deregister_script( 'wp-embed' );

		if ( ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
			wp_deregister_script( 'heartbeat' );
		}

		wp_dequeue_script( 'jquery-migrate' );

		// WooCommerce only where needed.
		if ( ! psb_is_woocommerce_active_page() ) {
			foreach ( array( 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-inline', 'woocommerce-rtl', 'woocommerce-layout-rtl', 'woocommerce-smallscreen-rtl', 'photoswipe', 'photoswipe-default-skin' ) as $handle ) {
				wp_dequeue_style( $handle );
			}
			foreach ( array( 'woocommerce', 'wc-add-to-cart', 'wc-cart-fragments', 'wc-checkout', 'wc-single-product', 'jquery-blockui', 'jquery-placeholder', 'jquery-cookie', 'js-cookie', 'sourcebuster-js', 'wc-order-attribution' ) as $handle ) {
				wp_dequeue_script( $handle );
			}
		}

		// jQuery to the footer where nothing in <head>/<body> needs it early.
		if ( ! psb_is_woocommerce_active_page() && ! psb_page_has_gravity_form() ) {
			$wp_scripts = wp_scripts();
			foreach ( array( 'jquery', 'jquery-core' ) as $handle ) {
				if ( isset( $wp_scripts->registered[ $handle ] ) ) {
					$wp_scripts->add_data( $handle, 'group', 1 );
				}
			}
		}
	},
	999
);

// Remove the global-styles inline CSS that classic themes still receive.
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );

/**
 * 6. Content images: lazy + async decoding when missing (WP adds width/height itself
 * via wp_img_tag_add_width_and_height_attr for attachment images).
 */
add_filter(
	'the_content',
	function ( $content ) {
		if ( is_admin() || empty( $content ) || false === stripos( $content, '<img' ) ) {
			return $content;
		}
		return preg_replace_callback(
			'/<img\s[^>]*>/i',
			function ( $m ) {
				$tag = $m[0];
				if ( false === stripos( $tag, 'loading=' ) ) {
					$tag = preg_replace( '/^<img\s/i', '<img loading="lazy" ', $tag );
				}
				if ( false === stripos( $tag, 'decoding=' ) ) {
					$tag = preg_replace( '/^<img\s/i', '<img decoding="async" ', $tag );
				}
				return $tag;
			},
			$content
		);
	},
	20
);

/**
 * 7. Speculation Rules (Chrome/Edge): prerender same-site pages with "moderate"
 * eagerness so navigation (plus the CSS view transition) feels instant.
 * Never for admin/ajax, login/logout, account, cart/checkout, compare,
 * add-to-cart, nonce URLs or RFQ submit endpoints. Logged-in users are skipped.
 * Note: prerendered pages run PHP, so the post-view counter may count hovers.
 */
function psb_speculation_excludes() {
	return array(
		'/wp-admin/*',
		'/wp-login.php*',
		'/wp-json/*',
		'/*\\?*add-to-cart=*',
		'/*\\?*_wpnonce=*',
		'/cart/*',
		'/checkout/*',
		'/my-account/*',
		'/login/*',
		'/compare/*',
		'/feed/*',
		'/*\\?*s=*',
		'/search/*',
		'/*\\?*action=*',
		'/*logout*',
		'/request-quote/*\\?*',
		'/wp-content/*',
	);
}

/** Main destinations prefetched eagerly (nav + tab bar). */
function psb_speculation_eager_urls() {
	$urls = array( home_url( '/' ), psb_shop_url(), psb_quote_url(), psb_archive_url( 'portfolio', '/portfolios/' ) );
	$blog = (int) get_option( 'page_for_posts' );
	$urls[] = $blog ? get_permalink( $blog ) : home_url( '/blog/' );
	$here   = home_url( add_query_arg( array() ) );
	return array_values(
		array_filter(
			array_unique( array_map( 'esc_url_raw', $urls ) ),
			function ( $u ) use ( $here ) {
				return untrailingslashit( $u ) !== untrailingslashit( $here );
			}
		)
	);
}

if ( function_exists( 'wp_get_speculation_rules_configuration' ) ) {
	// WP 6.8+: add an eager prefetch list next to core's moderate prerender rule.
	add_action(
		'wp_load_speculation_rules',
		function ( $rules ) {
			if ( is_user_logged_in() || ! is_object( $rules ) || ! method_exists( $rules, 'add_rule' ) ) {
				return;
			}
			$rules->add_rule(
				'prefetch',
				'psb-main-destinations',
				array(
					'source'    => 'list',
					'urls'      => psb_speculation_eager_urls(),
					'eagerness' => 'eager',
				)
			);
		}
	);
	// WordPress 6.8+: configure core's implementation.
	add_filter(
		'wp_speculation_rules_configuration',
		function ( $config ) {
			if ( is_user_logged_in() ) {
				return $config;
			}
			return array(
				'mode'      => 'prerender',
				'eagerness' => 'moderate',
			);
		}
	);
	add_filter(
		'wp_speculation_rules_href_exclude_paths',
		function ( $paths ) {
			return array_merge( (array) $paths, psb_speculation_excludes() );
		}
	);
} else {
	add_action(
		'wp_footer',
		function () {
			if ( is_user_logged_in() || is_admin() ) {
				return;
			}
			$not = array();
			foreach ( psb_speculation_excludes() as $path ) {
				$not[] = array( 'href_matches' => $path );
			}
			$not[] = array( 'selector_matches' => '[rel~=nofollow], [target=_blank], .no-prerender' );
			$where = array(
				'and' => array_merge( array( array( 'href_matches' => '/*' ) ), array( array( 'not' => array( 'or' => $not ) ) ) ),
			);
			$rules = array(
				'prerender' => array(
					array(
						'source'    => 'document',
						'where'     => $where,
						'eagerness' => 'moderate',
					),
				),
				'prefetch'  => array(
					array(
						'source'    => 'list',
						'urls'      => psb_speculation_eager_urls(),
						'eagerness' => 'eager',
					),
				),
			);
			echo '<script type="speculationrules">' . wp_json_encode( $rules, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
		},
		99
	);
}

/**
 * WooCommerce Blocks styles are (re-)enqueued late by block rendering; drop them
 * right before printing on pages that do not render Woo blocks.
 */
function psb_dequeue_late_block_styles() {
	if ( is_admin() || psb_is_woocommerce_active_page() ) {
		return;
	}
	foreach ( array( 'wc-blocks-style', 'wc-blocks-style-rtl', 'wc-blocks-vendors-style' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_print_styles', 'psb_dequeue_late_block_styles', 1 );
// WP 6.9+ hoists styles enqueued while rendering the body into <head>; catch those too.
add_action( 'wp_footer', 'psb_dequeue_late_block_styles', 1 );
add_action( 'wp_print_footer_scripts', 'psb_dequeue_late_block_styles', 1 );

/**
 * 8. Light HTML whitespace trim (front-end HTML only): removes leading indentation
 * on each line outside <script>/<style>/<pre>/<textarea> (those blocks are left untouched). Typically −8–10% page weight.
 */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || is_customize_preview() ) {
			return;
		}
		ob_start(
			function ( $html ) {
				if ( ! is_string( $html ) || false === stripos( $html, '<html' ) ) {
					return $html;
				}
				$parts = preg_split( '/(<script\b.*?<\/script>|<style\b.*?<\/style>|<pre\b.*?<\/pre>|<textarea\b.*?<\/textarea>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
				if ( ! is_array( $parts ) ) {
					return $html;
				}
				foreach ( $parts as $i => $part ) {
					if ( 0 === $i % 2 ) {
						$parts[ $i ] = preg_replace( '/\n[\t ]+/', "\n", preg_replace( '/\n\s*\n+/', "\n", $part ) );
					}
				}
				return implode( '', $parts );
			}
		);
	},
	0
);
