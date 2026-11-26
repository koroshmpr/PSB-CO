<?php
/**
 * Performance module — what is left of Perfmatters after inc/optimize.php.
 *
 * Audit (Perfmatters 2.2.5, local copy): the only feature switched on was Login URL
 * (→ inc/login-guard.php). Script Manager had no rules, Preload / Lazy Load / Fonts / CDN /
 * Analytics were all empty and the database-optimisation schedule was off (it had been run
 * once by hand). Emojis, embeds, RSD/WLW/shortlink/generator, block CSS, jQuery Migrate,
 * WooCommerce assets outside the shop, dashicons + Heartbeat for guests, revisions (15) and
 * image lazy-loading are already handled by inc/optimize.php and inc/stability.php.
 *
 * So this file only adds:
 *  1. jquery_migrate      — make optimize.php's jQuery Migrate removal actually apply
 *                           (its wp_default_scripts hook is added on `init`, after WP_Scripts
 *                           was already built, so Migrate still loaded on shop/product/contact).
 *  2. order_attribution   — WooCommerce order-attribution scripts (sourcebuster.js) off on
 *                           Woo pages too (optimize.php's filter name does not exist in Woo 11).
 *  3. gf_ie11_css         — Gravity Forms' IE11-only stylesheet off.
 *  4. arve_css            — ARVE video CSS only where a video is rendered (no content uses it today;
 *                           ARVE re-enqueues the file itself when a video is printed).
 *  5. google_fonts        — any plugin-enqueued Google Fonts stylesheet + its preconnect hints
 *                           removed (theme uses local Peyda; Google is often unreachable from Iran
 *                           and would block rendering).
 *  6. db_cleanup          — weekly conservative database cleanup (WP-Cron), logged, with dry-run.
 *
 * Every feature: filter `psb_perf_<feature>` (return false to turn it off), and nothing here
 * runs while Perfmatters is active (no double work). Dry run: psb_db_cleanup_run( true ).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'psb_perfmatters_active' ) ) {
	/** Perfmatters plugin is loaded (active) on this request. */
	function psb_perfmatters_active() {
		return defined( 'PERFMATTERS_VERSION' );
	}
}

/**
 * Is a performance feature on? Off while Perfmatters is active; each can be disabled with
 * add_filter( 'psb_perf_<feature>', '__return_false' );
 */
function psb_perf_on( $feature ) {
	if ( psb_perfmatters_active() ) {
		return false;
	}
	return (bool) apply_filters( 'psb_perf_' . $feature, true );
}

/*
 * 1–5. Front-end asset trimming.
 */
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( is_admin() ) {
			return;
		}
		if ( psb_perf_on( 'jquery_migrate' ) ) {
			$scripts = wp_scripts();
			if ( isset( $scripts->registered['jquery'] ) ) {
				$scripts->registered['jquery']->deps = array_values( array_diff( (array) $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) ) );
			}
		}
	},
	1
);

/** Late dequeues (run again right before head/footer printing: some plugins enqueue while rendering). */
function psb_perf_dequeue_assets() {
	if ( is_admin() ) {
		return;
	}
	if ( psb_perf_on( 'order_attribution' ) ) {
		wp_dequeue_script( 'sourcebuster-js' );
		wp_dequeue_script( 'wc-order-attribution' );
	}
	if ( psb_perf_on( 'gf_ie11_css' ) ) {
		$styles = wp_styles();
		if ( isset( $styles->registered['gform_theme'] ) ) {
			$styles->registered['gform_theme']->deps = array_values( array_diff( (array) $styles->registered['gform_theme']->deps, array( 'gform_theme_ie11' ) ) );
		}
		wp_dequeue_style( 'gform_theme_ie11' );
	}
	if ( psb_perf_on( 'google_fonts' ) ) {
		$styles = wp_styles();
		foreach ( (array) $styles->queue as $handle ) {
			$src = isset( $styles->registered[ $handle ] ) ? (string) $styles->registered[ $handle ]->src : '';
			if ( false !== stripos( $src, 'fonts.googleapis.com' ) || false !== stripos( $src, 'fonts.gstatic.com' ) ) {
				wp_dequeue_style( $handle );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'psb_perf_dequeue_assets', 1000 );
add_action( 'wp_print_styles', 'psb_perf_dequeue_assets', 2 );
add_action( 'wp_print_footer_scripts', 'psb_perf_dequeue_assets', 2 );

// ARVE: drop the globally enqueued CSS once; when a video renders, ARVE enqueues it again.
add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! is_admin() && psb_perf_on( 'arve_css' ) ) {
			wp_dequeue_style( 'arve' );
		}
	},
	1000
);

// Google Fonts preconnect / dns-prefetch hints added by plugins.
add_filter(
	'wp_resource_hints',
	static function ( $urls ) {
		if ( ! psb_perf_on( 'google_fonts' ) ) {
			return $urls;
		}
		return array_values(
			array_filter(
				(array) $urls,
				static function ( $u ) {
					$href = is_array( $u ) ? (string) ( $u['href'] ?? '' ) : (string) $u;
					return false === stripos( $href, 'fonts.googleapis.com' ) && false === stripos( $href, 'fonts.gstatic.com' );
				}
			)
		);
	},
	99
);

/*
 * 6. Weekly database cleanup (replaces Perfmatters › Tools › Database).
 *
 * Conservative on purpose: never touches published content, trashed posts, orders or options
 * other than expired transients. Batch-limited per run; the next run continues.
 *  - expired transients                       (core delete_expired_transients())
 *  - revisions above the newest 15 per post   (wp_delete_post_revision())
 *  - auto-drafts older than 7 days            (wp_delete_post())
 *  - spam / trashed comments older than 30 days (wp_delete_comment( force ))
 *  - post meta of posts that no longer exist  (orphans only)
 * Log: option `psb_db_cleanup_log` (last 12 runs) + error_log() line.
 */
const PSB_DB_CLEANUP_HOOK = 'psb_db_cleanup';

function psb_db_cleanup_settings() {
	return apply_filters(
		'psb_db_cleanup_settings',
		array(
			'keep_revisions'     => 15,
			'auto_draft_days'    => 7,
			'comment_days'       => 30,
			'batch'              => 500,
		)
	);
}

/**
 * Run (or preview) the cleanup.
 *
 * @param bool $dry_run True = only count what would be removed.
 * @return array Counts per task.
 */
function psb_db_cleanup_run( $dry_run = true ) {
	global $wpdb;
	$cfg   = psb_db_cleanup_settings();
	$batch = max( 1, (int) $cfg['batch'] );
	$out   = array(
		'dry_run'            => (bool) $dry_run,
		'expired_transients' => 0,
		'revisions'          => 0,
		'auto_drafts'        => 0,
		'comments'           => 0,
		'orphan_postmeta'    => 0,
	);

	// Expired transients.
	$out['expired_transients'] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
			$wpdb->esc_like( '_transient_timeout_' ) . '%',
			time()
		)
	);
	if ( ! $dry_run && $out['expired_transients'] ) {
		delete_expired_transients( true );
	}

	// Revisions above the newest N per parent.
	$keep    = max( 1, (int) $cfg['keep_revisions'] );
	$parents = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_parent FROM {$wpdb->posts} WHERE post_type = 'revision' GROUP BY post_parent HAVING COUNT(*) > %d",
			$keep
		)
	);
	$todo = array();
	foreach ( $parents as $parent ) {
		$ids  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent = %d ORDER BY post_date DESC, ID DESC", (int) $parent ) );
		$todo = array_merge( $todo, array_slice( $ids, $keep ) );
		if ( count( $todo ) >= $batch ) {
			break;
		}
	}
	$todo             = array_slice( $todo, 0, $batch );
	$out['revisions'] = count( $todo );
	if ( ! $dry_run ) {
		foreach ( $todo as $id ) {
			wp_delete_post_revision( (int) $id );
		}
	}

	// Auto-drafts.
	$ids                = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'auto-draft' AND post_date_gmt < %s LIMIT %d",
			gmdate( 'Y-m-d H:i:s', time() - (int) $cfg['auto_draft_days'] * DAY_IN_SECONDS ),
			$batch
		)
	);
	$out['auto_drafts'] = count( $ids );
	if ( ! $dry_run ) {
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
		}
	}

	// Spam / trashed comments.
	$ids             = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved IN ('spam','trash') AND comment_date_gmt < %s LIMIT %d",
			gmdate( 'Y-m-d H:i:s', time() - (int) $cfg['comment_days'] * DAY_IN_SECONDS ),
			$batch
		)
	);
	$out['comments'] = count( $ids );
	if ( ! $dry_run ) {
		foreach ( $ids as $id ) {
			wp_delete_comment( (int) $id, true );
		}
	}

	// Orphaned post meta.
	$ids                    = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT pm.meta_id FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL LIMIT %d",
			$batch * 4
		)
	);
	$out['orphan_postmeta'] = count( $ids );
	if ( ! $dry_run && $ids ) {
		$ids = array_map( 'intval', $ids );
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN (" . implode( ',', $ids ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- integers only.
		wp_cache_flush_group( 'post_meta' );
	}

	if ( ! $dry_run ) {
		$log = get_option( 'psb_db_cleanup_log', array() );
		$log = is_array( $log ) ? $log : array();
		array_unshift( $log, array( 'time' => time() ) + $out );
		update_option( 'psb_db_cleanup_log', array_slice( $log, 0, 12 ), false );
		error_log( 'PSB db cleanup: ' . wp_json_encode( $out ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
	return $out;
}

add_action(
	PSB_DB_CLEANUP_HOOK,
	static function () {
		if ( psb_perf_on( 'db_cleanup' ) ) {
			psb_db_cleanup_run( false );
		}
	}
);

// Schedule weekly (Friday 04:00 Tehran ≈ 00:30 UTC); unschedule when switched off by filter.
add_action(
	'init',
	static function () {
		$next = wp_next_scheduled( PSB_DB_CLEANUP_HOOK );
		if ( psb_perf_on( 'db_cleanup' ) ) {
			if ( ! $next ) {
				$first = strtotime( 'next friday 00:30 UTC' );
				wp_schedule_event( $first ? $first : time() + DAY_IN_SECONDS, 'weekly', PSB_DB_CLEANUP_HOOK );
			}
		} elseif ( $next && ! psb_perfmatters_active() ) {
			wp_clear_scheduled_hook( PSB_DB_CLEANUP_HOOK );
		}
	},
	20
);
