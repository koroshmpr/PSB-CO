<?php
/**
 * One-time live data setup (things the remote MCP API cannot do).
 *
 * Runs once, on the live site only (psb.co.ir), the first time an administrator opens wp-admin
 * after deploy. Every step is idempotent; the run is versioned by the option `psb_live_setup`.
 * The report is stored in `psb_live_setup_report` and shown once as an admin notice.
 *
 * Steps:
 *  1. Publish reviewed drafts (products, services, certificates, type tests, confirmations) by live ID,
 *     only when the post exists, is a draft and has the expected post type.
 *  2. Page templates (FAQ) + the «سوابق اجرایی» page (/project-references/).
 *  3. Products: copy public meta psb_rfq_type → protected _psb_rfq_type.
 *  4. Article series: terms + term meta + _series_order (inc/live-setup-series.json).
 *  6. RFQ notification channels (non-secret parts only: enabled flags, channel ids, e-mail recipient).
 *     Bot tokens never live in git: they come from wp-config constants PSB_RFQ_BALE_TOKEN /
 *     PSB_RFQ_TELEGRAM_TOKEN or the RFQ settings screen.
 *  5. Project references import (inc/project-records/data/projects-public.csv; restricted rows are
 *     filtered by the importer again).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_LIVE_SETUP_VERSION = 3;

function psb_live_setup_is_live() {
	return 'psb.co.ir' === strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
}

function psb_live_setup_maybe_run() {
		if ( ! psb_live_setup_is_live() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( (int) get_option( 'psb_live_setup', 0 ) >= PSB_LIVE_SETUP_VERSION ) {
			return;
		}
		if ( get_transient( 'psb_live_setup_lock' ) ) {
			return;
		}
		set_transient( 'psb_live_setup_lock', 1, 10 * MINUTE_IN_SECONDS );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		$report = psb_live_setup_run();
		update_option( 'psb_live_setup_report', $report, false );
		if ( empty( $report['incomplete'] ) ) {
			update_option( 'psb_live_setup', PSB_LIVE_SETUP_VERSION, false );
		}
		delete_transient( 'psb_live_setup_lock' );
		flush_rewrite_rules( false );
}

// First wp-admin visit by an administrator…
add_action(
	'admin_init',
	static function () {
		if ( ! wp_doing_ajax() ) {
			psb_live_setup_maybe_run();
		}
	},
	99
);
// …or the first authenticated administrator REST/API call (e.g. the remote management connector).
add_filter(
	'rest_pre_dispatch',
	static function ( $result ) {
		psb_live_setup_maybe_run();
		return $result;
	},
	1
);

add_action(
	'admin_notices',
	static function () {
		$report = get_option( 'psb_live_setup_report' );
		if ( ! $report || ! current_user_can( 'manage_options' ) || get_option( 'psb_live_setup_report_seen' ) ) {
			return;
		}
		update_option( 'psb_live_setup_report_seen', 1, false );
		echo '<div class="notice notice-success"><p><strong>راه‌اندازی داده‌های سایت انجام شد.</strong></p><pre style="white-space:pre-wrap;direction:ltr;text-align:left">'
			. esc_html( wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) . '</pre></div>';
	}
);

function psb_live_setup_run() {
	$r = array(
		'published'  => array(),
		'skipped'    => array(),
		'templates'  => array(),
		'rfq_meta'   => 0,
		'series'     => array(),
		'projects'   => null,
		'incomplete' => false,
	);

	// 1. Publish reviewed drafts (NIOPDC 1861 and NIOEC 1863 approved by the owner on 2026-11-26).
	$publish = array(
		'product'      => array( 1891, 1896, 1897, 1898, 1899, 1900, 1901, 1902, 1903, 1904, 1905 ),
		'services'     => array( 1892, 1893, 1894, 1895 ),
		'certificates' => array( 1845, 1847, 1849, 1851, 1853, 1855 ),
		'type-test'    => array( 1857, 1859 ),
		'confirmation' => array( 1861, 1863, 1865, 1867, 1869, 1871, 1873, 1875, 1877, 1879, 1881, 1883 ),
	);
	foreach ( $publish as $type => $ids ) {
		foreach ( $ids as $id ) {
			$p = get_post( $id );
			if ( ! $p || $p->post_type !== $type || 'draft' !== $p->post_status ) {
				$r['skipped'][] = $id . ':' . ( $p ? $p->post_type . '/' . $p->post_status : 'missing' );
				continue;
			}
			$res = wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ), true );
			$r['published'][] = is_wp_error( $res ) ? $id . ':error' : $id;
		}
	}

	// 2. Page templates + project references page.
	$faq = get_page_by_path( 'faq' );
	if ( $faq ) {
		update_post_meta( $faq->ID, '_wp_page_template', 'faq.php' );
		$r['templates'][] = 'faq:' . $faq->ID;
	}
	$refs = get_page_by_path( 'project-references' );
	if ( ! $refs ) {
		$id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'سوابق اجرایی',
				'post_name'   => 'project-references',
			),
			true
		);
		if ( ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_wp_page_template', 'page-project-references.php' );
			$r['templates'][] = 'project-references:' . $id . ' (created)';
		}
	} else {
		update_post_meta( $refs->ID, '_wp_page_template', 'page-project-references.php' );
		$r['templates'][] = 'project-references:' . $refs->ID;
	}

	// 3. Products: protected RFQ type meta.
	foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $pid ) {
		$t = get_post_meta( $pid, 'psb_rfq_type', true );
		if ( $t && get_post_meta( $pid, '_psb_rfq_type', true ) !== $t ) {
			update_post_meta( $pid, '_psb_rfq_type', sanitize_key( $t ) );
			++$r['rfq_meta'];
		}
	}

	// 4. Article series.
	$map = json_decode( (string) file_get_contents( __DIR__ . '/live-setup-series.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( is_array( $map ) && taxonomy_exists( 'series' ) ) {
		$find = static function ( $ref ) {
			if ( is_int( $ref ) ) {
				return get_post( $ref ) ? $ref : 0;
			}
			$p = get_page_by_path( (string) $ref, OBJECT, 'post' );
			return $p ? (int) $p->ID : 0;
		};
		foreach ( $map as $s ) {
			$term = term_exists( $s['slug'], 'series' );
			if ( ! $term ) {
				$term = wp_insert_term( $s['name'], 'series', array( 'slug' => $s['slug'] ) );
			}
			if ( is_wp_error( $term ) ) {
				$r['series'][] = $s['slug'] . ':error';
				continue;
			}
			$tid = (int) $term['term_id'];
			update_term_meta( $tid, '_series_color', $s['color'] );
			update_term_meta( $tid, '_series_icon', $s['icon'] );
			update_term_meta( $tid, '_series_description', $s['description'] );
			$pillar = $find( $s['pillar'] );
			if ( $pillar ) {
				update_term_meta( $tid, '_series_pillar', $pillar );
			}
			$n = 0;
			foreach ( $s['posts'] as $row ) {
				$pid = $find( $row[0] );
				if ( ! $pid ) {
					continue;
				}
				wp_set_object_terms( $pid, $tid, 'series', false );
				update_post_meta( $pid, '_series_order', (int) $row[1] );
				++$n;
			}
			$r['series'][] = $s['slug'] . ':' . $n . '/' . count( $s['posts'] );
		}
	}

	// 6. RFQ channels (merge; never overwrites a token typed in the settings screen).
	if ( function_exists( 'psb_rfq_save_channels' ) ) {
		$saved = get_option( 'psb_rfq_channels', array() );
		$saved = is_array( $saved ) ? $saved : array();
		$want  = array(
			'bale'     => array( 'enabled' => 1, 'chats' => '@psbrequests', 'parse' => 'html', 'files' => 1 ),
			'telegram' => array( 'enabled' => 1, 'chats' => '@psbrequests', 'parse' => 'html' ),
			'email'    => array( 'enabled' => 1, 'to' => get_option( 'admin_email' ) ),
		);
		foreach ( $want as $ch => $vals ) {
			$saved[ $ch ] = array_merge( isset( $saved[ $ch ] ) && is_array( $saved[ $ch ] ) ? $saved[ $ch ] : array(), $vals );
		}
		psb_rfq_save_channels( $saved );
		$r['rfq_channels'] = 'bale+telegram @psbrequests, email ' . get_option( 'admin_email' )
			. '; tokens: bale ' . ( defined( 'PSB_RFQ_BALE_TOKEN' ) || ! empty( $saved['bale']['token'] ) ? 'ok' : 'missing' )
			. ', telegram ' . ( defined( 'PSB_RFQ_TELEGRAM_TOKEN' ) || ! empty( $saved['telegram']['token'] ) ? 'ok' : 'missing' );
	}

	// 5. Project references.
	$csv = __DIR__ . '/project-records/data/projects-public.csv';
	if ( function_exists( 'psb_pr_import_csv' ) && is_readable( $csv ) ) {
		$res = psb_pr_import_csv( $csv, array( 'mode' => 'skip' ) );
		if ( is_wp_error( $res ) ) {
			$r['projects']   = 'error: ' . $res->get_error_message();
			$r['incomplete'] = true;
		} else {
			$r['projects'] = array_intersect_key( (array) $res, array_flip( array( 'created', 'updated', 'skipped', 'skipped_restricted', 'errors', 'total' ) ) );
		}
	} else {
		$r['projects']   = 'importer unavailable';
		$r['incomplete'] = true;
	}

	return $r;
}
