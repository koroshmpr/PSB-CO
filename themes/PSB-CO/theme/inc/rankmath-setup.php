<?php
/**
 * One-time Rank Math settings (2026-11-27). The remote API cannot write Rank Math's option arrays,
 * so the agreed values live here. Only the listed keys are written; everything else in Rank Math
 * stays as the owner set it. Previous values are kept in the option psb_rankmath_setup_backup.
 * Versioned by psb_rankmath_setup; bump PSB_RANKMATH_SETUP_VERSION to apply a changed set again.
 *
 * - Knowledge graph: company (not person), official spelling «پارس سوئیچ بورد», default share image.
 * - Pages: no Article schema (static pages are WebPage; seo.php also strips it).
 * - Type tests and customer letters: titles, descriptions, index, in the sitemap (they were missing).
 * - Sitemap: product categories and article series added; featured images included.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_RANKMATH_SETUP_VERSION = 1;

/** Option name => [ key => value ]. */
function psb_rankmath_setup_values() {
	$brand = 'پارس سوئیچ بورد';
	$share = 0;
	$front = (int) get_option( 'page_on_front' );
	if ( function_exists( 'psb_pf' ) ) {
		$share = psb_pf_image_id( psb_pf( 'home_hero_image', $front ) );
	}
	$titles = array(
		'knowledgegraph_type'               => 'company',
		'knowledgegraph_name'               => $brand,
		'website_name'                      => $brand,
		'website_alternate_name'            => 'PSB',
		'pt_page_default_rich_snippet'      => 'off',
		'twitter_card_type'                 => 'summary_large_image',
		'noindex_empty_taxonomies'          => 'on',
		'noindex_search'                    => 'on',
		// Type tests.
		'pt_type-test_title'                => '%title% %sep% %sitename%',
		'pt_type-test_description'          => '%excerpt%',
		'pt_type-test_custom_robots'        => 'off',
		'pt_type-test_robots'               => array(),
		'pt_type-test_default_rich_snippet' => 'off',
		'pt_type-test_add_meta_box'         => 'on',
		'pt_type-test_archive_title'        => 'گزارش‌های آزمون نوعی %sep% %sitename%',
		// Customer letters.
		'pt_customer_letter_title'                => '%title% %sep% %sitename%',
		'pt_customer_letter_description'          => '%excerpt%',
		'pt_customer_letter_custom_robots'        => 'off',
		'pt_customer_letter_robots'               => array(),
		'pt_customer_letter_default_rich_snippet' => 'off',
		'pt_customer_letter_add_meta_box'         => 'on',
		// Taxonomies.
		'tax_product_cat_custom_robots'     => 'off',
		'tax_product_cat_robots'            => array( 'index' ),
		'tax_product_tag_custom_robots'     => 'on',
		'tax_product_tag_robots'            => array( 'noindex' ),
		'tax_series_custom_robots'          => 'off',
		'tax_series_robots'                 => array( 'index' ),
	);
	if ( $share ) {
		$src = wp_get_attachment_image_url( $share, 'full' );
		if ( $src ) {
			$titles['open_graph_image']    = $src;
			$titles['open_graph_image_id'] = $share;
		}
	}
	return array(
		'rank-math-options-titles'  => $titles,
		'rank-math-options-sitemap' => array(
			'include_images'                 => 'on',
			'include_featured_image'         => 'on',
			'pt_type-test_sitemap'           => 'on',
			'pt_customer_letter_sitemap'     => post_type_exists( 'customer_letter' ) ? 'on' : 'off',
			'tax_product_cat_sitemap'        => 'on',
			'tax_product_tag_sitemap'        => 'off',
			'tax_series_sitemap'             => taxonomy_exists( 'series' ) ? 'on' : 'off',
			'tax_post_tag_sitemap'           => 'off',
		),
	);
}

add_action(
	'init',
	function () {
		if ( wp_doing_ajax() || wp_doing_cron() || ! defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		if ( (int) get_option( 'psb_rankmath_setup' ) >= PSB_RANKMATH_SETUP_VERSION || get_transient( 'psb_rankmath_setup_lock' ) ) {
			return;
		}
		set_transient( 'psb_rankmath_setup_lock', 1, 120 );
		$backup = (array) get_option( 'psb_rankmath_setup_backup', array() );
		foreach ( psb_rankmath_setup_values() as $option => $values ) {
			$current = get_option( $option );
			if ( ! is_array( $current ) ) {
				continue; // Rank Math not set up yet: never create its options from here.
			}
			foreach ( $values as $key => $value ) {
				if ( ! array_key_exists( $option . '.' . $key, $backup ) ) {
					$backup[ $option . '.' . $key ] = $current[ $key ] ?? null;
				}
				$current[ $key ] = $value;
			}
			update_option( $option, $current );
		}
		update_option( 'psb_rankmath_setup_backup', $backup, false );
		update_option( 'psb_rankmath_setup', PSB_RANKMATH_SETUP_VERSION, false );
		// Sitemaps are cached by Rank Math; rebuild them with the new post types / taxonomies.
		if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
			\RankMath\Sitemap\Cache::invalidate_storage();
		}
		if ( has_action( 'cache_enabler_clear_complete_cache' ) ) {
			do_action( 'cache_enabler_clear_complete_cache' );
		}
		delete_transient( 'psb_rankmath_setup_lock' );
	},
	99
);
