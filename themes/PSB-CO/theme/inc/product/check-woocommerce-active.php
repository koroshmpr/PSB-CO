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
