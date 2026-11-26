<?php
/**
 * RFQ — post type, capabilities, tracking codes, data accessors.
 *
 * Meta (all prefixed _psb_rfq_):
 *   code, status, mode (full|quick), data (full structured array), mobile, company, city, name,
 *   type (one row per line-item type, for filtering), files (array), log (channel results),
 *   notes (array of internal notes), ip, ua.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	static function () {
		register_post_type(
			PSB_RFQ_CPT,
			array(
				'labels'              => array(
					'name'               => 'استعلام‌ها و سفارش‌ها',
					'singular_name'      => 'درخواست استعلام',
					'menu_name'          => 'استعلام‌ها و سفارش‌ها',
					'all_items'          => 'همه درخواست‌ها',
					'edit_item'          => 'جزئیات درخواست',
					'view_item'          => 'مشاهده درخواست',
					'search_items'       => 'جستجو (کد پیگیری، موبایل، نام، شرکت)',
					'not_found'          => 'درخواستی پیدا نشد.',
					'not_found_in_trash' => 'سطل زباله خالی است.',
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'menu_icon'           => 'dashicons-clipboard',
				'menu_position'       => 3,
				'supports'            => array( 'title' ),
				'capability_type'     => array( 'psb_rfq', 'psb_rfqs' ),
				'map_meta_cap'        => true,
				'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			)
		);
	}
);

/**
 * Grant the RFQ capabilities once per version to the roles that handle sales.
 * Filter `psb_rfq_roles` to change the role list.
 */
add_action(
	'admin_init',
	static function () {
		if ( get_option( 'psb_rfq_caps_ver' ) === PSB_RFQ_VERSION ) {
			return;
		}
		$caps  = array( 'edit_psb_rfqs', 'edit_others_psb_rfqs', 'edit_published_psb_rfqs', 'read_private_psb_rfqs', 'delete_psb_rfqs', 'delete_others_psb_rfqs', 'delete_published_psb_rfqs', 'delete_private_psb_rfqs', 'edit_private_psb_rfqs', 'publish_psb_rfqs' );
		$roles = apply_filters( 'psb_rfq_roles', array( 'administrator', 'editor', 'shop_manager' ) );
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( $caps as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}
		update_option( 'psb_rfq_caps_ver', PSB_RFQ_VERSION, true );
	}
);

/**
 * Next tracking code, e.g. PSB-1405-0012 (sequence per Jalali year, atomic UPDATE).
 */
function psb_rfq_next_code() {
	global $wpdb;
	$year   = psb_rfq_jyear();
	$option = 'psb_rfq_seq_' . $year;
	add_option( $option, '0', '', 'no' );
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = option_value + 1 WHERE option_name = %s", $option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$seq = (int) $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	wp_cache_delete( $option, 'options' );
	$prefix = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) psb_rfq_opt( 'prefix', 'PSB' ) ) );
	return ( $prefix ? $prefix : 'PSB' ) . '-' . $year . '-' . str_pad( (string) max( 1, $seq ), 4, '0', STR_PAD_LEFT );
}

/** Structured data of a request (as stored at submit time). */
function psb_rfq_get( $post_id ) {
	$data = get_post_meta( $post_id, '_psb_rfq_data', true );
	$data = is_array( $data ) ? $data : array();
	return array_merge(
		array(
			'code'    => get_post_meta( $post_id, '_psb_rfq_code', true ),
			'mode'    => 'full',
			'items'   => array(),
			'project' => array(),
			'contact' => array(),
			'files'   => array(),
			'source'  => array(),
		),
		$data
	);
}

/** Current status key. */
function psb_rfq_status( $post_id ) {
	$s = get_post_meta( $post_id, '_psb_rfq_status', true );
	return isset( psb_rfq_statuses()[ $s ] ) ? $s : 'new';
}

/** Admin edit URL (works outside the admin, e.g. in cron). */
function psb_rfq_edit_url( $post_id ) {
	return admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' );
}

/**
 * Human-readable rows of the specs of one line item.
 *
 * @return array label => value
 */
function psb_rfq_item_spec_rows( $item ) {
	$types = psb_rfq_types();
	$group = isset( $types[ $item['type'] ] ) ? $types[ $item['type'] ]['group'] : 'other';
	$rows  = array();
	foreach ( psb_rfq_spec_defs() as $k => $def ) {
		if ( empty( $item['specs'][ $k ] ) ) {
			continue;
		}
		$v    = $item['specs'][ $k ];
		$opts = psb_rfq_spec_options( $def, $group );
		if ( is_array( $v ) ) {
			$v = implode( '، ', array_map( static function ( $x ) use ( $opts ) {
				return isset( $opts[ $x ] ) ? $opts[ $x ] : $x;
			}, $v ) );
		} elseif ( $opts && isset( $opts[ $v ] ) ) {
			$v = $opts[ $v ];
		} elseif ( ! empty( $def['unit'] ) && is_numeric( $v ) ) {
			$v .= ' ' . $def['unit'];
		}
		$rows[ $def['label'] ] = (string) $v;
	}
	return $rows;
}

/** Display value of a project/contact field. */
function psb_rfq_field_display( $key, $value ) {
	$defs = psb_rfq_field_defs();
	if ( '' === $value || null === $value || ! isset( $defs[ $key ] ) ) {
		return (string) $value;
	}
	$opts = psb_rfq_field_options( $defs[ $key ] );
	return isset( $opts[ $value ] ) ? $opts[ $value ] : (string) $value;
}

/** Short "MDB ×2، MCC ×1" summary of line items. */
function psb_rfq_items_summary( $items ) {
	$out = array();
	foreach ( (array) $items as $item ) {
		$out[] = psb_rfq_type_label( $item['type'] ) . ' ×' . (int) $item['qty'];
	}
	return implode( '، ', $out );
}
