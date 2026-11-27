<?php
/**
 * One-time: featured images for the articles and catalogue products that had none (2026-11-27).
 * Real PSB photos from the live media library, matched by topic; never AI renders or banners with
 * burned-in text. Only sets a thumbnail when the post has none, so the owner's choices always win.
 * Live only (psb.co.ir); versioned by the option psb_featured_setup; report in psb_featured_setup_report.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_FEATURED_SETUP_VERSION = 1;

/** Post ID => attachment ID. */
function psb_featured_setup_map() {
	return array(
		// Articles.
		1783 => 1921, // تابلو ایزوله بیمارستانی → isolated panel interior.
		1788 => 1427, // نگهداری و سرویس دوره‌ای → R/S/T signal lamps.
		1781 => 2637, // استعلام قیمت → busbar assembly in the factory.
		1789 => 1344, // تأییدیه توانیر و وندور لیست → busbar assembly.
		1786 => 2638, // رنگ پودری → AMADA sheet-metal line (preparation before coating).
		1782 => 1830, // درجه حفاظت IP → enclosure door with lamps.
		1791 => 1831, // SF6 یا هوا → MV switchgear.
		1790 => 1395, // IEC 62271-200 → MV switchgear.
		1787 => 2640, // سلول ثابت و کشویی فشار متوسط → MV line-up.
		1797 => 1367, // فرم‌های جداسازی داخلی → coloured phase busbars.
		1796 => 1408, // تابلو ATS → control panels.
		1795 => 1833, // بانک خازنی → control panel wiring.
		1794 => 1396, // MDB و SMDB → LV switchboard.
		1793 => 1364, // MCC → MCC with a drawer out.
		1779 => 1345, // انتخاب سازنده → busbar and cabling.
		1780 => 1922, // تایپ تست و روتین تست → finished panel interior.
		1785 => 1831, // انواع تابلو فشار متوسط → MV switchgear.
		1792 => 1832, // انواع تابلو فشار ضعیف → LV switchboard.
		// Products.
		1905 => 1922, // تابلو ایزوله بیمارستانی.
		1904 => 1830, // تابلو کنتوری.
		1903 => 2640, // تابلو کمپکت و RMU.
		1902 => 1395, // سلول فشار متوسط کشویی.
		1900 => 1832, // تابلو روشنایی.
		1899 => 1408, // تابلو ATS.
		1898 => 1833, // تابلو بانک خازنی.
	);
}

/** Whether a post's featured image is still the one this routine set (not chosen by the owner). */
function psb_featured_is_auto( $post_id ) {
	$map = psb_featured_setup_map();
	return isset( $map[ $post_id ] ) && (int) get_post_thumbnail_id( $post_id ) === $map[ $post_id ];
}

add_action(
	'init',
	function () {
		if ( wp_doing_ajax() || wp_doing_cron() || 'psb.co.ir' !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return;
		}
		if ( (int) get_option( 'psb_featured_setup' ) >= PSB_FEATURED_SETUP_VERSION || get_transient( 'psb_featured_setup_lock' ) ) {
			return;
		}
		set_transient( 'psb_featured_setup_lock', 1, 120 );
		$report = array();
		foreach ( psb_featured_setup_map() as $post_id => $att_id ) {
			$post = get_post( $post_id );
			if ( ! $post || ! in_array( $post->post_type, array( 'post', 'product' ), true ) ) {
				$report[ $post_id ] = 'missing';
			} elseif ( has_post_thumbnail( $post_id ) ) {
				$report[ $post_id ] = 'kept';
			} elseif ( ! wp_attachment_is_image( $att_id ) ) {
				$report[ $post_id ] = 'no image ' . $att_id;
			} else {
				$report[ $post_id ] = set_post_thumbnail( $post_id, $att_id ) ? 'set ' . $att_id : 'failed';
			}
		}
		update_option( 'psb_featured_setup_report', $report, false );
		update_option( 'psb_featured_setup', PSB_FEATURED_SETUP_VERSION, false );
		delete_transient( 'psb_featured_setup_lock' );
		if ( has_action( 'cache_enabler_clear_complete_cache' ) ) {
			do_action( 'cache_enabler_clear_complete_cache' ); // Cards and og:image change on many pages.
		}
	},
	99
);
