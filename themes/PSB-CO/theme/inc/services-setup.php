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

/*
 * One-time (2026-11-27, later): service texts follow the six owner-defined steps.
 * - Links inside service pages and the services page («خدمات», ID 1299) that still used the old Persian
 *   slugs (301-redirected) now point straight at the English permalinks.
 * - Services page: the «زنجیره خدمات» list is rewritten as the six production steps + after-delivery services.
 * - Assembly service (1893): the «مونتاژ بدنه و قطعات» section moves from the end (after the enquiry CTA)
 *   to before the first <h2>, right after the intro.
 * Live only; versioned by psb_services_content; report in psb_services_content_report.
 */
const PSB_SERVICES_CONTENT_VERSION = 1;

/** Old (decoded) slug => service ID. */
function psb_services_old_slugs() {
	return array(
		'cnc-ماشین-آلات'               => 1413,
		'خط-رنگ-پودری-الکترواستاتیک'  => 1412,
		'آزمایشگاه-تست-رنگ'            => 1411,
		'آزمایشگاه-تست-تابلو-برق'      => 1302,
	);
}

/** Replace every link to an old service slug (raw or %-encoded, any case) with the current permalink. */
function psb_services_fix_links( $html ) {
	foreach ( psb_services_old_slugs() as $old => $id ) {
		$new = get_permalink( $id );
		if ( ! $new ) {
			continue;
		}
		$forms = array_unique( array( $old, rawurlencode( $old ), strtolower( rawurlencode( $old ) ) ) );
		foreach ( $forms as $f ) {
			$html = preg_replace( '#https?://(?:www\.)?psb\.co\.ir/services/' . preg_quote( $f, '#' ) . '/?#u', $new, $html );
		}
	}
	return $html;
}

/** The services page list: six steps + after-delivery. */
function psb_services_chain_html() {
	$l = function ( $id, $text ) {
		$u = get_permalink( $id );
		return $u ? '<a href="' . esc_url( $u ) . '">' . $text . '</a>' : $text;
	};
	return "\n<p>ساخت هر تابلو در کارخانه پارس سوئیچ بورد شش مرحله دارد و همه آن‌ها زیر یک سقف انجام می‌شود:</p>\n<ol>\n"
		. '<li><strong>' . $l( 1892, 'طراحی مکانیکال و الکتریکال تابلو برق' ) . ':</strong> تهیه نقشه‌های قدرت، فرمان و جانمایی بر اساس نقشه تک‌خطی و مشخصات فنی کارفرما، محاسبات اتصال کوتاه و حرارتی با نرم‌افزارهای EPLAN، E-CODIAL، ABB DOCWin، AutoCAD و SolidWorks.</li>' . "\n"
		. '<li><strong>' . $l( 1413, 'فلزکاری' ) . ':</strong> برش، پانچ و خم ورق بدنه با ماشین‌آلات CNC شامل قیچی، پرس بریک و پرس پانچ CNC ساخت آمادا ژاپن.</li>' . "\n"
		. '<li><strong>' . $l( 1412, 'رنگ' ) . ':</strong> چربی‌گیری، فسفاته و رنگ پودری الکترواستاتیک بدنه با ضخامت حداقل ۶۰ تا ۸۰ میکرون.</li>' . "\n"
		. '<li><strong>' . $l( 1411, 'آزمایشگاه رنگ' ) . ':</strong> کنترل آماده‌سازی و کیفیت رنگ: چسبندگی، خمش، سختی، ضخامت و مه‌نمکی.</li>' . "\n"
		. '<li><strong>' . $l( 1893, 'مونتاژ بدنه و قطعات (سیم‌کشی و شینه‌کشی)' ) . ':</strong> نصب تجهیزات، ساخت شینه مسی با دستگاه‌های هیدرولیک NOVO PRESS (تا ۲۰۰ × ۲۰ میلی‌متر) و سیم‌کشی و شماره‌گذاری مطابق نقشه‌ها.</li>' . "\n"
		. '<li><strong>' . $l( 1302, 'آزمایشگاه برق' ) . ':</strong> آزمون روتین و آزمون پذیرش کارخانه‌ای (FAT) هر تابلو پیش از تحویل، با امکان حضور نماینده خریدار و تحویل تست‌شیت امضاشده.</li>' . "\n"
		. "</ol>\n<p>پس از تحویل:</p>\n<ul>\n"
		. '<li><strong>ارسال:</strong> ارسال تابلوها به همه شهرهای کشور، با بسته‌بندی مناسب حمل.</li>' . "\n"
		. '<li><strong>' . $l( 1894, 'نصب و راه‌اندازی' ) . ':</strong> در صورت نیاز کارفرما، نصب و راه‌اندازی تابلوها در محل پروژه توسط کارشناسان شرکت.</li>' . "\n"
		. '<li><strong>' . $l( 1895, 'گارانتی و خدمات پس از فروش' ) . ':</strong> ۱۸ ماه گارانتی و ۱۰ سال تضمین تأمین قطعات یدکی و خدمات پس از فروش.</li>' . "\n"
		. "</ul>\n";
}

add_action(
	'init',
	function () {
		if ( wp_doing_ajax() || wp_doing_cron() || 'psb.co.ir' !== strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
			return;
		}
		if ( (int) get_option( 'psb_services_setup' ) < PSB_SERVICES_SETUP_VERSION ) {
			return; // slugs first (routine above), so get_permalink() returns the English URLs.
		}
		if ( (int) get_option( 'psb_services_content' ) >= PSB_SERVICES_CONTENT_VERSION || get_transient( 'psb_services_content_lock' ) ) {
			return;
		}
		set_transient( 'psb_services_content_lock', 1, 120 );
		$report = array();
		kses_remove_filters();
		$ids = array_merge( array_keys( psb_services_setup_map() ), array( 1299 ) );
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( ! $post ) {
				$report[ $id ] = 'missing';
				continue;
			}
			$html = psb_services_fix_links( $post->post_content );
			if ( 1299 === $id ) {
				$html = preg_replace( '#(<h2[^>]*>\s*زنجیره خدمات\s*</h2>).*?(?=<h2)#su', '$1' . str_replace( '$', '\$', psb_services_chain_html() ), $html, 1, $n );
				$report['1299_chain'] = (int) $n;
			}
			if ( 1893 === $id ) {
				$pos = strpos( $html, '<!-- psb:assembly -->' );
				$h2  = stripos( $html, '<h2' );
				if ( false !== $pos && false !== $h2 && $h2 < $pos ) {
					$block = rtrim( substr( $html, $pos ) );
					$rest  = rtrim( substr( $html, 0, $pos ) );
					$lab   = get_permalink( 1302 );
					if ( $lab ) {
						$block = str_replace( 'آزمایشگاه برق کارخانه', '<a href="' . esc_url( $lab ) . '">آزمایشگاه برق کارخانه</a>', $block );
					}
					$h2   = stripos( $rest, '<h2' );
					$html = rtrim( substr( $rest, 0, $h2 ) ) . "\n\n" . $block . "\n\n" . substr( $rest, $h2 );
					$report['1893_moved'] = 1;
				}
			}
			if ( $html !== $post->post_content ) {
				$r             = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $html ) ), true );
				$report[ $id ] = is_wp_error( $r ) ? 'error: ' . $r->get_error_message() : 'updated';
			} else {
				$report[ $id ] = 'unchanged';
			}
		}
		kses_init_filters();
		update_option( 'psb_services_content_report', $report, false );
		update_option( 'psb_services_content', PSB_SERVICES_CONTENT_VERSION, false );
		if ( has_action( 'cache_enabler_clear_complete_cache' ) ) {
			do_action( 'cache_enabler_clear_complete_cache' );
		}
		delete_transient( 'psb_services_content_lock' );
	},
	100
);
