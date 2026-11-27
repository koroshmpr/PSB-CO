<?php
/**
 * One-time: align the service pages with the six-step production flow (owner, 2026-11-27).
 * Titles of steps 2 and 5, English slugs, menu_order in process order, and an assembly / wiring section
 * for step 5. WordPress keeps the old slug in _wp_old_slug and 301-redirects old URLs automatically.
 * Live only (psb.co.ir); versioned by psb_services_setup; report in psb_services_setup_report.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_SERVICES_SETUP_VERSION = 1;

/** Live service ID => changes (title / slug / order / append HTML). */
function psb_services_setup_map() {
	$assembly = '<!-- psb:assembly -->'
		. '<h2>مونتاژ بدنه و قطعات</h2>'
		. '<p>پس از رنگ‌آمیزی و تأیید آزمایشگاه رنگ، بدنه تابلو به سالن مونتاژ می‌رود. کلیدها، رله‌ها و تجهیزات اندازه‌گیری و حفاظتی مطابق نقشه‌های جانمایی نصب می‌شوند و شینه‌های مسی و سیم‌کشی مدارهای قدرت و فرمان اجرا می‌شود.</p>'
		. '<h3>سیم‌کشی و شماره‌گذاری</h3>'
		. '<p>سیم‌کشی مدارهای قدرت و فرمان و شماره‌گذاری سرسیم‌ها و ترمینال‌ها مطابق نقشه‌های اجرایی انجام می‌شود تا بهره‌برداری، عیب‌یابی و نگهداری تابلو ساده باشد.</p>'
		. '<h3>شینه‌کشی</h3>'
		. '<p>شینه‌های مسی با دستگاه‌های برش، پانچ و خم هیدرولیک NOVO PRESS تا ابعاد ۲۰۰ × ۲۰ میلی‌متر ساخته و نصب می‌شوند.</p>'
		. '<p>هر تابلو پس از مونتاژ برای آزمون روتین به آزمایشگاه برق کارخانه می‌رود.</p>';
	return array(
		1892 => array( 'order' => 1 ),
		1413 => array( 'title' => 'فلزکاری و ساخت بدنه تابلو برق با CNC', 'slug' => 'cnc-sheet-metal-fabrication', 'order' => 2 ),
		1412 => array( 'slug' => 'powder-coating-line', 'order' => 3 ),
		1411 => array( 'slug' => 'paint-testing-laboratory', 'order' => 4 ),
		1893 => array( 'title' => 'مونتاژ، سیم‌کشی و شینه‌کشی تابلو برق', 'slug' => 'panel-assembly-wiring-busbar', 'order' => 5, 'append' => $assembly, 'desc' => 'نصب تجهیزات، سیم‌کشی و شماره‌گذاری مطابق نقشه‌ها و ساخت شینه مسی با پرس هیدرولیک NOVO PRESS', 'features' => "نصب کلیدها، رله‌ها و تجهیزات اندازه‌گیری و حفاظتی مطابق نقشه جانمایی\nسیم‌کشی مدارهای قدرت و فرمان و شماره‌گذاری سرسیم‌ها و ترمینال‌ها\nبرش، پانچ و خم شینه مسی با دستگاه‌های هیدرولیک NOVO PRESS\nشینه مسی تا ابعاد ۲۰۰ × ۲۰ میلی‌متر" ),
		1302 => array( 'slug' => 'electrical-testing-laboratory', 'order' => 6 ),
		1894 => array( 'order' => 7 ),
		1895 => array( 'order' => 8 ),
	);
}

add_action(
	'init',
	function () {
		if ( wp_doing_ajax() || wp_doing_cron() || 'psb.co.ir' !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return;
		}
		if ( (int) get_option( 'psb_services_setup' ) >= PSB_SERVICES_SETUP_VERSION || get_transient( 'psb_services_setup_lock' ) ) {
			return;
		}
		set_transient( 'psb_services_setup_lock', 1, 120 );
		$report = array();
		// Runs on a visitor request: keep the stored HTML exactly as it is (no kses re-filtering on save).
		kses_remove_filters();
		foreach ( psb_services_setup_map() as $id => $c ) {
			$post = get_post( $id );
			if ( ! $post || 'services' !== $post->post_type ) {
				$report[ $id ] = 'missing';
				continue;
			}
			$update = array( 'ID' => $id );
			if ( isset( $c['title'] ) && $post->post_title !== $c['title'] ) {
				$update['post_title'] = $c['title'];
			}
			if ( isset( $c['slug'] ) && urldecode( $post->post_name ) !== $c['slug'] ) {
				$update['post_name'] = $c['slug']; // wp_check_for_changed_slugs() stores the old one → 301.
			}
			if ( isset( $c['order'] ) && (int) $post->menu_order !== (int) $c['order'] ) {
				$update['menu_order'] = (int) $c['order'];
			}
			if ( ! empty( $c['append'] ) && false === strpos( $post->post_content, '<!-- psb:assembly -->' ) ) {
				$update['post_content'] = rtrim( $post->post_content ) . "\n\n" . $c['append'];
			}
			$done = count( $update ) > 1 ? wp_update_post( wp_slash( $update ), true ) : 0;
			if ( function_exists( 'update_field' ) ) {
				if ( ! empty( $c['desc'] ) ) {
					update_field( 'services_description', $c['desc'], $id );
				}
				if ( ! empty( $c['features'] ) ) {
					update_field( 'technical_features', $c['features'], $id );
				}
			}
			$report[ $id ] = is_wp_error( $done ) ? 'error: ' . $done->get_error_message() : ( $done ? 'updated' : 'unchanged' );
		}
		kses_init_filters();
		update_option( 'psb_services_setup_report', $report, false );
		update_option( 'psb_services_setup', PSB_SERVICES_SETUP_VERSION, false );
		if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
			\RankMath\Sitemap\Cache::invalidate_storage();
		}
		if ( has_action( 'cache_enabler_clear_complete_cache' ) ) {
			do_action( 'cache_enabler_clear_complete_cache' );
		}
		delete_transient( 'psb_services_setup_lock' );
	},
	99
);
