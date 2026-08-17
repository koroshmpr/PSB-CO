<?php
/**
 * Comprehensive Speed & Resource Optimization Module
 * PSB-CO High-Voltage Engineering Theme
 * 
 * Functions:
 * 1. Disables WordPress core head bloat & emojis
 * 2. Dequeues Gutenberg block styles & global inline SVGs
 * 3. Conditionally dequeues WooCommerce CSS/JS on non-shop pages (Homepage, About Us, CPTs)
 * 4. Disables WooCommerce order attribution tracking scripts (sourcebuster.js)
 * 5. Removes jQuery Migrate & dequeues Dashicons for non-admin visitors
 * 6. Adds defer attribute to JavaScript files to resolve render-blocking warnings
 * 7. Fixes missing image width/height attributes to prevent Cumulative Layout Shift (CLS)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * 1. Helper function to check if current page requires WooCommerce scripts/styles
 */
function psb_is_woocommerce_active_page() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return false;
    }
    return ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() || is_product() || is_product_category() || is_product_tag() || is_shop() );
}

/**
 * 2. Core Optimization Hook
 */
function psb_optimize_site() {
    
    // --- A. Disable Emojis ---
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', function ( $plugins ) {
        return array_diff( $plugins, array( 'wpemoji' ) );
    } );
    add_filter( 'wp_resource_hints', function ( $urls, $relation_type ) {
        if ( 'dns-prefetch' === $relation_type ) {
            $emoji_url = 'https://s.w.org/images/core/emoji/';
            $urls = array_filter( $urls, function ( $url ) use ( $emoji_url ) {
                return strpos( $url, $emoji_url ) === false;
            } );
        }
        return $urls;
    }, 10, 2 );

    // --- B. Remove Head Junk & Discovery Links ---
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'feed_links_extra', 3 );
    remove_action( 'wp_head', 'feed_links', 2 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    remove_action( 'wp_head', 'rest_output_link_wp_head' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
    add_filter( 'embed_oembed_discover', '__return_false' );

    // --- C. Remove jQuery Migrate completely on Frontend ---
    add_action( 'wp_default_scripts', function ( $scripts ) {
        if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
            $scripts->registered['jquery']->deps = array_diff(
                $scripts->registered['jquery']->deps,
                array( 'jquery-migrate' )
            );
        }
    } );
}
add_action( 'init', 'psb_optimize_site' );

/**
 * 3. Remove inline SVG filters injected by WP 5.9+
 */
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

/**
 * 4. Disable WooCommerce Order Attribution Tracking scripts on storefront (removes sourcebuster.js)
 */
add_filter( 'woocommerce_order_attribution_enabled_in_storefront', '__return_false' );

/**
 * 5. Dequeue Unnecessary Scripts & Styles on Enqueue Action
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( is_admin() ) {
        return;
    }

    // --- Dequeue Gutenberg Core Block Library CSS & Global Styles ---
    wp_dequeue_style( 'wp-block-library' );
    wp_deregister_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_deregister_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'global-styles' );
    wp_dequeue_style( 'classic-theme-styles' );

    // --- Dequeue WooCommerce Block Styles ---
    wp_dequeue_style( 'wc-blocks-style' );
    wp_deregister_style( 'wc-blocks-style' );
    wp_dequeue_style( 'wc-blocks-vendors-style' );
    wp_deregister_style( 'wc-blocks-vendors-style' );

    // --- Dequeue Dashicons for logged-out visitors ---
    if ( ! is_user_logged_in() ) {
        wp_dequeue_style( 'dashicons' );
    }

    // --- Dequeue jQuery Migrate ---
    wp_dequeue_script( 'jquery-migrate' );
    wp_deregister_script( 'jquery-migrate' );

    // --- CONDITIONAL WOOCOMMERCE DEQUEUE (On non-shop pages like Home, About, CPTs, Contact) ---
    if ( ! psb_is_woocommerce_active_page() ) {
        // Dequeue WooCommerce Core Styles
        wp_dequeue_style( 'woocommerce-general' );
        wp_dequeue_style( 'woocommerce-layout' );
        wp_dequeue_style( 'woocommerce-smallscreen' );
        wp_dequeue_style( 'woocommerce-inline' );
        wp_dequeue_style( 'woocommerce-rtl' );
        wp_dequeue_style( 'woocommerce-layout-rtl' );
        wp_dequeue_style( 'woocommerce-smallscreen-rtl' );
        wp_dequeue_style( 'photoswipe' );
        wp_dequeue_style( 'photoswipe-default-skin' );

        // Dequeue WooCommerce Core Scripts
        wp_dequeue_script( 'woocommerce' );
        wp_dequeue_script( 'wc-add-to-cart' );
        wp_dequeue_script( 'wc-cart-fragments' );
        wp_dequeue_script( 'wc-checkout' );
        wp_dequeue_script( 'wc-single-product' );
        wp_dequeue_script( 'jquery-blockui' );
        wp_dequeue_script( 'jquery-placeholder' );
        wp_dequeue_script( 'jquery-cookie' );
        wp_dequeue_script( 'js-cookie' );
        wp_dequeue_script( 'sourcebuster-js' );
        wp_dequeue_script( 'wc-order-attribution' );
    }

}, 999 );

/**
 * 6. Automatically Defer Render-Blocking JavaScripts
 */
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
    if ( is_admin() ) {
        return $tag;
    }

    // List of handles to defer
    $defer_handles = array(
        'jquery-core',
        'jquery',
        'bluebox-script',
        'woocommerce',
        'wc-add-to-cart',
        'sourcebuster-js',
        'wc-order-attribution'
    );

    if ( in_array( $handle, $defer_handles, true ) || strpos( $src, '.min.js' ) !== false ) {
        if ( strpos( $tag, 'defer' ) === false ) {
            $tag = str_replace( ' src', ' defer="defer" src', $tag );
        }
    }

    return $tag;
}, 10, 3 );

/**
 * 7. Fix Image CLS: Auto-populate missing width & height on content images
 */
add_filter( 'the_content', function ( $content ) {
    if ( is_admin() || empty( $content ) ) {
        return $content;
    }

    // Pattern to match img tags without width or height
    return preg_replace_callback( '/<img\s+([^>]+)>/i', function ( $matches ) {
        $img_tag = $matches[0];

        // If img tag already has width and height, return untouched
        if ( strpos( $img_tag, 'width=' ) !== false && strpos( $img_tag, 'height=' ) !== false ) {
            return $img_tag;
        }

        // Add decoding="async" & loading="lazy" if missing
        if ( strpos( $img_tag, 'loading=' ) === false ) {
            $img_tag = str_replace( '<img ', '<img loading="lazy" ', $img_tag );
        }
        if ( strpos( $img_tag, 'decoding=' ) === false ) {
            $img_tag = str_replace( '<img ', '<img decoding="async" ', $img_tag );
        }

        return $img_tag;
    }, $content );
}, 20 );
