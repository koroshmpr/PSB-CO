<?php
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

// Check if WooCommerce plugin is active
$active_plugins = get_option('active_plugins');
$woo_active = in_array('woocommerce/woocommerce.php', $active_plugins);

/**
 * Prevent Rank Math Schema fatal error on WooCommerce product snippet if product object is boolean/invalid
 */
add_filter('rank_math/snippet/rich_snippet_product_entity', function ($entity) {
    if (!function_exists('wc_get_product')) {
        return false;
    }
    $product_id = get_the_ID();
    if (!$product_id) {
        return false;
    }
    $product = wc_get_product($product_id);
    if (!$product || !is_a($product, 'WC_Product')) {
        return false;
    }
    return $entity;
}, 5, 1);

/**
 * Declare WooCommerce support so WooCommerce uses the theme templates (single-product.php,
 * woocommerce/archive-product.php). Without it WooCommerce runs in "unsupported theme" mode and
 * injects its own summary (price, add to cart, related products) into the_content.
 * No zoom/lightbox/slider scripts: the catalogue gallery is plain markup + the theme lightbox.
 */
add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'woocommerce' );
	}
);

/**
 * Catalogue mode (B2B, prices on request): products cannot be purchased and no price HTML is shown.
 * Turn off with add_filter( 'psb_catalogue_mode', '__return_false' ).
 */
add_action(
	'init',
	function () {
		if ( ! apply_filters( 'psb_catalogue_mode', true ) ) {
			return;
		}
		add_filter( 'woocommerce_is_purchasable', '__return_false' );
		add_filter( 'woocommerce_get_price_html', '__return_empty_string' );
	}
);

/** No product reviews tab / form (B2B catalogue). */
add_filter(
	'woocommerce_product_tabs',
	function ( $tabs ) {
		unset( $tabs['reviews'] );
		return $tabs;
	},
	98
);
