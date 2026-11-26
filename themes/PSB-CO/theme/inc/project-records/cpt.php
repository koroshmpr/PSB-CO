<?php
/**
 * Project records — CPT, taxonomy, meta registration, cache invalidation.
 *
 * Rows are thin (name, client, place, year…), so they get no single pages or archives:
 * the CPT is non-public with an admin UI, and everything renders on the «سوابق اجرایی» page.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Meta keys (all prefixed «_pr_» so they stay hidden from the custom-fields box). */
function psb_pr_meta_keys() {
	return array(
		'client'      => '_pr_client',
		'province'    => '_pr_province',
		'city'        => '_pr_city',
		'year'        => '_pr_year',
		'types'       => '_pr_types',
		'quantity'    => '_pr_quantity',
		'scope'       => '_pr_scope',
		'role'        => '_pr_role',
		'note'        => '_pr_note',
		'show_client' => '_pr_show_client',
		'public_title' => '_pr_public_title',
		'featured'    => '_pr_featured',
		'portfolio'   => '_pr_portfolio',
		'seq'         => '_pr_seq',
		'key'         => '_pr_key',
		'inferred'    => '_pr_sector_inferred',
	);
}

add_action(
	'init',
	static function () {
		register_post_type(
			PSB_PR_CPT,
			array(
				'labels'              => array(
					'name'               => 'سوابق اجرایی',
					'singular_name'      => 'سابقه اجرایی',
					'menu_name'          => 'سوابق اجرایی',
					'all_items'          => 'همه سوابق',
					'add_new'            => 'افزودن سابقه',
					'add_new_item'       => 'افزودن سابقه اجرایی',
					'edit_item'          => 'ویرایش سابقه اجرایی',
					'new_item'           => 'سابقه جدید',
					'search_items'       => 'جستجوی سوابق',
					'not_found'          => 'سابقه‌ای پیدا نشد.',
					'not_found_in_trash' => 'سابقه‌ای در زباله‌دان نیست.',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-clipboard',
				'supports'            => array( 'title', 'page-attributes' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);

		register_taxonomy(
			PSB_PR_TAX,
			PSB_PR_CPT,
			array(
				'labels'             => array(
					'name'          => 'حوزه‌ها',
					'singular_name' => 'حوزه',
					'menu_name'     => 'حوزه‌ها',
					'all_items'     => 'همه حوزه‌ها',
					'edit_item'     => 'ویرایش حوزه',
					'add_new_item'  => 'افزودن حوزه',
					'search_items'  => 'جستجوی حوزه',
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => false,
				'show_tagcloud'      => false,
				'show_in_rest'       => false,
				'show_admin_column'  => true,
				'hierarchical'       => true,
				'rewrite'            => false,
				'query_var'          => false,
			)
		);
	},
	9
);

/** Create the default sector terms once (and again when the vocabulary version changes). */
function psb_pr_seed_sectors() {
	$ver = 'v1:' . md5( wp_json_encode( array_keys( psb_pr_sectors() ) ) );
	if ( get_option( 'psb_pr_seeded' ) === $ver ) {
		return;
	}
	$order = 0;
	foreach ( psb_pr_sectors() as $slug => $s ) {
		$term = get_term_by( 'slug', $slug, PSB_PR_TAX );
		if ( ! $term ) {
			$res = wp_insert_term( $s['name'], PSB_PR_TAX, array( 'slug' => $slug ) );
			$tid = is_wp_error( $res ) ? 0 : (int) $res['term_id'];
		} else {
			$tid = (int) $term->term_id;
		}
		if ( $tid ) {
			update_term_meta( $tid, 'pr_order', $order );
		}
		++$order;
	}
	update_option( 'psb_pr_seeded', $ver, false );
}
add_action( 'admin_init', 'psb_pr_seed_sectors' );

/**
 * Sector term id for a slug (creates the term from the vocabulary if missing).
 *
 * @param string $slug Sector slug.
 * @return int
 */
function psb_pr_sector_term_id( $slug ) {
	static $cache = array();
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}
	$term = get_term_by( 'slug', $slug, PSB_PR_TAX );
	if ( ! $term ) {
		$def = psb_pr_sectors()[ $slug ] ?? null;
		if ( ! $def ) {
			return 0;
		}
		$res = wp_insert_term( $def['name'], PSB_PR_TAX, array( 'slug' => $slug ) );
		$tid = is_wp_error( $res ) ? 0 : (int) $res['term_id'];
	} else {
		$tid = (int) $term->term_id;
	}
	$cache[ $slug ] = $tid;
	return $tid;
}

/* -------------------------------------------------------------------------
 * Cache: everything the front end needs is computed once and stored in a transient.
 * Any change to a record or sector bumps the version.
 * ---------------------------------------------------------------------- */

function psb_pr_flush_cache() {
	if ( ! empty( $GLOBALS['psb_pr_importing'] ) ) {
		return; // The importer flushes once at the end.
	}
	update_option( 'psb_pr_cache_ver', (string) microtime( true ), false );
}

add_action(
	'save_post_' . PSB_PR_CPT,
	static function () {
		psb_pr_flush_cache();
	}
);
foreach ( array( 'deleted_post', 'trashed_post', 'untrashed_post' ) as $psb_pr_hook ) {
	add_action(
		$psb_pr_hook,
		static function ( $post_id ) {
			if ( PSB_PR_CPT === get_post_type( $post_id ) ) {
				psb_pr_flush_cache();
			}
		}
	);
}
unset( $psb_pr_hook );
add_action(
	'transition_post_status',
	static function ( $new, $old, $post ) {
		if ( $post instanceof WP_Post && ( PSB_PR_CPT === $post->post_type || 'portfolio' === $post->post_type ) && $new !== $old ) {
			psb_pr_flush_cache();
		}
	},
	10,
	3
);
foreach ( array( 'created_' . PSB_PR_TAX, 'edited_' . PSB_PR_TAX, 'delete_' . PSB_PR_TAX ) as $psb_pr_hook ) {
	add_action( $psb_pr_hook, 'psb_pr_flush_cache' );
}
unset( $psb_pr_hook );
// A linked portfolio post's photo may change.
add_action(
	'save_post_portfolio',
	static function () {
		psb_pr_flush_cache();
	}
);
