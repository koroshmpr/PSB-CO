<?php
/**
 * One-time (owner, 2026-11-27): customer satisfaction letters were entered as approvals (confirmation).
 * They move to their own post type «رضایت‌نامه‌ها» (customer_letter, /customer-letters/<slug>/):
 * - same post, same slug/title/text/featured image; ACF data copied to the letter meta
 *   (issuing_organization → _psb_letter_org, approval_scope → _psb_letter_scope,
 *   letter_number → _psb_letter_number + the «مورخ …» date → _psb_letter_date,
 *   letter_file → featured image when it is an image without one, else _psb_letter_pdf when a PDF);
 * - type «رضایت‌نامه» when the title says so;
 * - old /confirmation/<slug>/ URLs 301 to the new address (permanent, below — not part of the one-time run).
 * Live only; versioned by psb_letters_migrate; report in psb_letters_migrate_report.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_LETTERS_MIGRATE_VERSION = 1;

/** Approval posts that are really customer letters (slugs on live). */
function psb_letters_migrate_slugs() {
	return array(
		'nioec-satisfaction-letter-34-panels',
		'arak-machine-manufacturing-satisfaction-letter',
		'ardabil-cement-azarshahr-lime-satisfaction-letter',
		'chenareh-limestone-mine-satisfaction-letter',
		'tabriz-tile-switchboard-letter',
		'sayol-satisfaction-letter',
		'pars-takmil-satisfaction-letter',
		'pars-nakh-satisfaction-letter',
		'pars-decor-satisfaction-letter',
		'mahsar-abnieh-sirjan-dam-satisfaction-letter',
		'dashtneshat-satisfaction-letter',
	);
}

add_action(
	'init',
	function () {
		if ( wp_doing_ajax() || wp_doing_cron() || ! post_type_exists( 'customer_letter' ) || 'psb.co.ir' !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return;
		}
		if ( (int) get_option( 'psb_letters_migrate' ) >= PSB_LETTERS_MIGRATE_VERSION || get_transient( 'psb_letters_migrate_lock' ) ) {
			return;
		}
		set_transient( 'psb_letters_migrate_lock', 1, 120 );
		psb_letters_migrate_run();
		delete_transient( 'psb_letters_migrate_lock' );
	},
	101
);

/** The move itself (callable from a local test). */
function psb_letters_migrate_run() {
		$report = array();
		foreach ( psb_letters_migrate_slugs() as $slug ) {
			global $wpdb;
			// Direct lookup: query filters (catalogue/visibility) must not hide a post from this one-time move.
			$pid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'confirmation' AND post_name = %s AND post_status NOT IN ('trash','auto-draft') LIMIT 1", $slug ) ); // phpcs:ignore
			$p   = $pid ? get_post( $pid ) : null;
			if ( ! $p ) {
				$report[ $slug ] = 'not found';
				continue;
			}
			$id  = $p->ID;
			$org = trim( (string) get_post_meta( $id, 'issuing_organization', true ) );
			$num = trim( (string) get_post_meta( $id, 'letter_number', true ) );
			$sco = trim( (string) get_post_meta( $id, 'approval_scope', true ) );
			$fid = (int) get_post_meta( $id, 'letter_file', true );

			$r = wp_update_post( array( 'ID' => $id, 'post_type' => 'customer_letter' ), true );
			if ( is_wp_error( $r ) ) {
				$report[ $slug ] = 'error: ' . $r->get_error_message();
				continue;
			}
			$org && update_post_meta( $id, '_psb_letter_org', $org );
			$sco && update_post_meta( $id, '_psb_letter_scope', $sco );
			$num && update_post_meta( $id, '_psb_letter_number', $num );
			if ( $num && preg_match( '#مورخ\s*([۰-۹0-9]{2,4}/[۰-۹0-9]{1,2}/[۰-۹0-9]{1,4})#u', $num, $m ) ) {
				update_post_meta( $id, '_psb_letter_date', $m[1] );
			}
			if ( $fid ) {
				if ( wp_attachment_is_image( $fid ) && ! has_post_thumbnail( $id ) ) {
					set_post_thumbnail( $id, $fid );
				} elseif ( 'application/pdf' === get_post_mime_type( $fid ) ) {
					update_post_meta( $id, '_psb_letter_pdf', $fid );
				}
			}
			if ( false !== mb_strpos( $p->post_title, 'رضایت' ) && term_exists( 'satisfaction', 'letter_type' ) ) {
				wp_set_object_terms( $id, 'satisfaction', 'letter_type', false );
			}
			update_post_meta( $id, '_psb_old_confirmation_slug', $slug );
			$report[ $slug ] = 'moved #' . $id;
		}
		update_option( 'psb_letters_migrate_report', $report, false );
		update_option( 'psb_letters_migrate', PSB_LETTERS_MIGRATE_VERSION, false );
		if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
			\RankMath\Sitemap\Cache::invalidate_storage();
		}
		if ( has_action( 'cache_enabler_clear_complete_cache' ) ) {
			do_action( 'cache_enabler_clear_complete_cache' );
		}
		return $report;
}

/** /confirmation/<slug>/ of a moved letter → 301 to /customer-letters/<slug>/. */
add_action(
	'template_redirect',
	function () {
		if ( ! is_404() || ! post_type_exists( 'customer_letter' ) ) {
			return;
		}
		$path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' ); // phpcs:ignore
		if ( ! preg_match( '#^confirmation/([^/]+)$#', urldecode( $path ), $m ) ) {
			return;
		}
		global $wpdb;
		$lid = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'customer_letter' AND post_status = 'publish' AND post_name = %s LIMIT 1", sanitize_title( $m[1] ) ) ); // phpcs:ignore
		if ( $lid ) {
			wp_safe_redirect( get_permalink( $lid ), 301 );
			exit;
		}
	},
	1
);
