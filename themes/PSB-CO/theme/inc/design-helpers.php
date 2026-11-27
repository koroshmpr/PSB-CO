<?php
/**
 * Design-system helpers shared by layout, home and inner templates.
 * All helpers are null-safe (ACF / WooCommerce may be inactive or return null).
 *
 * @package bluebox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Company facts: editable in «تنظیمات سایت» → «اطلاعات شرکت» (inc/page-fields/site-settings.php,
 * stored by ACF as options_*). Read with get_option() so they are ready before ACF loads.
 * Defaults are the owner-confirmed facts (2026-11): founded ۱۳۵۵, 18-month warranty,
 * 10 years after-sales service, ISO 9001 (no number shown).
 */
$psb_fact = function ( $key, $default ) {
	$v = get_option( 'options_' . $key, '' );
	return ( '' === $v || false === $v || null === $v ) ? $default : (int) $v;
};
if ( ! defined( 'PSB_FOUNDED_JALALI' ) ) {
	define( 'PSB_FOUNDED_JALALI', max( 1300, $psb_fact( 'founded_year', 1355 ) ) );
}
if ( ! defined( 'PSB_WARRANTY_MONTHS' ) ) {
	define( 'PSB_WARRANTY_MONTHS', $psb_fact( 'warranty_months', 18 ) );
}
if ( ! defined( 'PSB_SERVICE_YEARS' ) ) {
	define( 'PSB_SERVICE_YEARS', $psb_fact( 'service_years', 10 ) );
}
if ( ! defined( 'PSB_ISO_9001' ) ) {
	define( 'PSB_ISO_9001', (bool) $psb_fact( 'iso_9001', 1 ) );
}
unset( $psb_fact );

/**
 * Safe ACF option/field getter.
 *
 * @param string     $name    Field name.
 * @param mixed      $default Fallback.
 * @param int|string $post_id 'option' or post ID (null = current post).
 * @return mixed
 */
function psb_field( $name, $default = null, $post_id = 'option' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = null === $post_id ? get_field( $name ) : get_field( $name, $post_id );
	return ( null === $value || false === $value || '' === $value ) ? $default : $value;
}

/** Latin → Persian digits for display text. */
function psb_fa_num( $value ) {
	return strtr( (string) $value, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
}

/** Current Jalali year (approximation good enough for "years of experience"). */
function psb_jalali_year() {
	// Gregorian date in the site timezone (wp_date() may already be Jalali via date plugins).
	$now = new DateTime( 'now', wp_timezone() );
	$y   = (int) $now->format( 'Y' );
	$m   = (int) $now->format( 'n' );
	$d   = (int) $now->format( 'j' );
	return ( $m > 3 || ( 3 === $m && $d >= 21 ) ) ? $y - 621 : $y - 622;
}

/** Years in business, from the founding year stated on the site. */
function psb_years_active() {
	return max( 1, psb_jalali_year() - PSB_FOUNDED_JALALI );
}

/** Company name from Site Setting. */
function psb_brand_name() {
	// Official spelling is «پارس سوئیچ بورد»; normalise variants stored in options.
	return psb_brand_fix( trim( (string) psb_field( 'brand_name', 'پارس سوئیچ بورد' ) ) );
}

/** Normalise brand-name spelling variants in free text to «پارس سوئیچ بورد». */
function psb_brand_fix( $text ) {
	$text = (string) preg_replace( '/پارس\s*سو(?:ی|یی|ئی)چ\s*(?:برد|بورد)/u', 'پارس سوئیچ بورد', (string) $text );
	// Unified spelling «تأییدیه» (titles on the site mix «تاییدیه»).
	return (string) preg_replace( '/تا[یي]ید[یي]ه/u', 'تأییدیه', $text );
}

/**
 * Normalised phone list from the "phones" repeater.
 *
 * @param bool $footer_only Only rows flagged "show-on-footer".
 * @return array[] each: title, number, href
 */
function psb_phones( $footer_only = false ) {
	$rows = psb_field( 'phones', array() );
	$out  = array();
	if ( ! is_array( $rows ) ) {
		return $out;
	}
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		if ( $footer_only ) {
			$show = $row['show-on-footer'] ?? ( $row['show_on_footer'] ?? true );
			if ( ! $show || '0' === $show || 'false' === $show ) {
				continue;
			}
		}
		$num = $row['number'] ?? ( $row['phone'] ?? '' );
		if ( is_array( $num ) ) {
			$num = $num['url'] ?? '';
		}
		$num = trim( str_replace( 'tel:', '', preg_replace( '#^https?://#i', '', (string) $num ) ) );
		if ( '' === $num ) {
			continue;
		}
		$out[] = array(
			'title'  => trim( (string) ( $row['title'] ?? '' ) ),
			'number' => $num,
			'href'   => 'tel:' . preg_replace( '/[^0-9+]/', '', $num ),
		);
	}
	return $out;
}

/** First phone (main office). */
function psb_primary_phone() {
	$phones = psb_phones();
	return $phones[0] ?? null;
}

/** Human readable phone: 021-66707011 style. */
function psb_format_phone( $number ) {
	$n = preg_replace( '/[^0-9+]/', '', (string) $number );
	if ( preg_match( '/^(09\d{2})(\d{3})(\d{4})$/', $n, $m ) ) { // mobile first: 0912 495 0082
		return $m[1] . ' ' . $m[2] . ' ' . $m[3];
	}
	if ( preg_match( '/^(0\d{2})(\d{8})$/', $n, $m ) ) { // landline: 021-66707011
		return $m[1] . '-' . $m[2];
	}
	return $n;
}

/** Email list. */
function psb_emails() {
	$rows = psb_field( 'emails', array() );
	$out  = array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$email = trim( (string) ( is_array( $row ) ? ( $row['email'] ?? '' ) : $row ) );
			if ( is_email( $email ) ) {
				$out[] = $email;
			}
		}
	}
	return $out;
}

/** Address list (contact repeater): title, address, map_link. */
function psb_addresses() {
	$rows = psb_field( 'contact', array() );
	$out  = array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( empty( $row['address'] ) ) {
				continue;
			}
			$out[] = array(
				'title'    => trim( (string) ( $row['title'] ?? '' ) ),
				'address'  => trim( preg_replace( '/\s+/', ' ', (string) $row['address'] ) ),
				'map_link' => (string) ( $row['map-link'] ?? '' ),
			);
		}
	}
	return $out;
}

/** Social links (ACF field is named "Social"; older code read "social"). */
function psb_socials() {
	$rows = psb_field( 'Social', null );
	if ( ! $rows ) {
		$rows = psb_field( 'social', array() );
	}
	$out = array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$name = sanitize_key( $row['name'] ?? '' );
			$url  = is_array( $row['link'] ?? null ) ? ( $row['link']['url'] ?? '' ) : ( $row['link'] ?? '' );
			if ( $name && $url ) {
				$out[] = array(
					'name' => $name,
					'url'  => $url,
				);
			}
		}
	}
	return $out;
}

/** Persian label for a social network key. */
function psb_social_label( $name ) {
	$labels = array(
		'whatsapp'  => 'واتساپ',
		'bale'      => 'بله',
		'instagram' => 'اینستاگرام',
		'telegram'  => 'تلگرام',
		'linkedin'  => 'لینکدین',
		'youtube'   => 'یوتیوب',
		'aparat'    => 'آپارات',
		'rubika'    => 'روبیکا',
		'twitter'   => 'ایکس',
		'pinterest' => 'پینترست',
	);
	return $labels[ $name ] ?? $name;
}

/** Contact page URL (quote requests go there; it hosts the Gravity Form). */
function psb_contact_url() {
	$page = get_page_by_path( 'contact-us' );
	return $page ? get_permalink( $page ) : home_url( '/contact-us/' );
}

/** Products (catalogue) URL. */
function psb_shop_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'shop' );
		if ( $url ) {
			return $url;
		}
	}
	return home_url( '/shop/' );
}

/** Archive URL of a CPT with a safe fallback. */
function psb_archive_url( $post_type, $fallback = '' ) {
	$url = post_type_exists( $post_type ) ? get_post_type_archive_link( $post_type ) : '';
	return $url ? $url : home_url( $fallback ? $fallback : '/' . $post_type . '/' );
}

/**
 * Responsive image from an attachment ID or ACF image array.
 * Defaults: lazy + async decoding; width/height always present (no CLS).
 *
 * @param int|array $image ACF image array or attachment ID.
 * @param string    $size  Registered size.
 * @param array     $attr  Extra attributes (class, sizes, loading, fetchpriority, alt …).
 * @return string
 */
function psb_image( $image, $size = 'large', $attr = array() ) {
	$id = is_array( $image ) ? (int) ( $image['ID'] ?? ( $image['id'] ?? 0 ) ) : (int) $image;
	if ( ! $id ) {
		return '';
	}
	$attr = wp_parse_args(
		$attr,
		array(
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);
	return wp_get_attachment_image( $id, $size, false, $attr );
}

/**
 * Inline SVG icon set (24px grid, 1.75 stroke, currentColor).
 *
 * @param string $name  Icon key.
 * @param string $class CSS classes.
 * @return string
 */
function psb_icon_paths() {
	/*
	 * One monochrome line family: 24px grid, 1.5 stroke, round caps/joins, currentColor only.
	 * Colour comes from the parent (graphite on light, slate-300/400 on dark); never red for decoration.
	 */
	return array(
		// UI
		'arrow'     => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'chevron'   => '<path d="m14 6-6 6 6 6"/>',
		'up'        => '<path d="m6 15 6-6 6 6"/>',
		'search'    => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4-4"/>',
		'menu'      => '<path d="M4 7h16M4 12h16M4 17h10"/>',
		'close'     => '<path d="M6 6l12 12M18 6 6 18"/>',
		'check'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'external'  => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
		'home'      => '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-4.5v-6h-5v6H5a1 1 0 0 1-1-1z"/>',
		'grid'      => '<rect x="4" y="4" width="7" height="7" rx="1"/><rect x="13" y="4" width="7" height="7" rx="1"/><rect x="4" y="13" width="7" height="7" rx="1"/><rect x="13" y="13" width="7" height="7" rx="1"/>',
		'quote'     => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M12 11v6M9 14h6"/>',
		'doc'       => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
		'cog'       => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2.5M12 18.5V21M5.6 5.6l1.8 1.8M16.6 16.6l1.8 1.8M3 12h2.5M18.5 12H21M5.6 18.4l1.8-1.8M16.6 7.4l1.8-1.8"/>',
		'bolt'      => '<path d="M13 3 5.5 13.5H11L10 21l7.5-10.5H12z"/>',
		'flask'     => '<path d="M9.5 3h5M10.5 3v6L5 18.6A1.6 1.6 0 0 0 6.4 21h11.2a1.6 1.6 0 0 0 1.4-2.4L13.5 9V3"/><path d="M7.5 15h9"/>',
		// Contact
		'phone'     => '<path d="M5.5 4h3.2l1.6 4.2-2.1 1.3a10.5 10.5 0 0 0 5.3 5.3l1.3-2.1 4.2 1.6v3.2a2 2 0 0 1-2 2A15.5 15.5 0 0 1 3.5 6a2 2 0 0 1 2-2z"/>',
		'fax'       => '<path d="M7 9V3.5h8l2 2V9"/><rect x="3.5" y="9" width="17" height="8" rx="1.5"/><path d="M7 17v3.5h10V17M7 13h3M16 12.5h1"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/>',
		'map'       => '<path d="M12 21s-6.5-5.4-6.5-11a6.5 6.5 0 0 1 13 0c0 5.6-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
		'pin'       => '<path d="M12 21s-6.5-5.4-6.5-11a6.5 6.5 0 0 1 13 0c0 5.6-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
		'chat'      => '<path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v8a2.5 2.5 0 0 1-2.5 2.5H10l-4.5 3.5V17h0A1.5 1.5 0 0 1 4 15.5z"/><path d="M8 9h8M8 12.5h5"/>',
		'clock'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
		'whatsapp'  => '<path d="M4 20l1.2-3.8A8 8 0 1 1 8 19z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 1c-1-.4-1.7-1.1-2-2l1-1-1-2z"/>',
		// Proof & company
		'calendar'  => '<rect x="3.5" y="5" width="17" height="15.5" rx="1.5"/><path d="M3.5 10h17M8 3v4M16 3v4M8 14h2M14 14h2M8 17h2"/>',
		'factory'   => '<path d="M3 20.5V10l5 3v-3l5 3v-3l5 3V4h3v16.5z"/><path d="M2 20.5h20M7 17h2M11.5 17h2M16 17h2"/>',
		'shield'    => '<path d="M12 3 5 5.8v5.7c0 4.3 2.9 8 7 9.5 4.1-1.5 7-5.2 7-9.5V5.8z"/><path d="m9 12 2.2 2.2L15.2 10"/>',
		'seal'      => '<path d="m12 2.8 2 1.5 2.5-.2.8 2.4 2.1 1.4-.8 2.4.8 2.4-2.1 1.4-.8 2.4-2.5-.2-2 1.5-2-1.5-2.5.2-.8-2.4-2.1-1.4.8-2.4-.8-2.4 2.1-1.4.8-2.4 2.5.2z"/><path d="m9.3 10.6 1.8 1.8 3.6-3.6"/>',
		'report'    => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M8 14.5h1.5l1.2-2.5 1.6 5 1.2-2.5H16"/>',
		'award'     => '<circle cx="12" cy="9" r="5.5"/><path d="m9 13.8-1.5 7.2 4.5-2.5 4.5 2.5-1.5-7.2"/><path d="M10 9h4"/>',
		'iso'       => '<circle cx="12" cy="9" r="5.5"/><path d="m9 13.8-1.5 7.2 4.5-2.5 4.5 2.5-1.5-7.2"/><path d="m9.8 9 1.5 1.5 3-3"/>',
		'warranty'  => '<path d="M12 3 5 5.8v5.7c0 4.3 2.9 8 7 9.5 4.1-1.5 7-5.2 7-9.5V5.8z"/><path d="M12 8.5V12l2.2 1.5"/>',
		'wrench'    => '<path d="M14.5 5.5a4 4 0 0 0 4.9 4.9l-9 9a2.1 2.1 0 0 1-3-3l9-9a4 4 0 0 1 4.9-4.9l-2.8 2.8.4 2.6 2.6.4z"/>',
		'truck'     => '<path d="M2.5 6.5h11v10h-11zM13.5 10h4l3 3.5v3h-7"/><circle cx="6.5" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
		'helmet'    => '<path d="M3.5 17.5h17M5 17.5V15a7 7 0 0 1 14 0v2.5"/><path d="M10 8.3V6h4v2.3M12 9v4"/>',
		'plug'      => '<path d="M9 3v4.5M15 3v4.5M6.5 7.5h11v3a5.5 5.5 0 0 1-11 0zM12 16v5"/>',
		'ruler'     => '<path d="m3.5 16.5 13-13 4 4-13 13z"/><path d="m7.5 12.5 1.8 1.8M10.5 9.5l1.8 1.8M13.5 6.5l1.8 1.8"/>',
		'drawing'   => '<rect x="3.5" y="4.5" width="17" height="15" rx="1.5"/><path d="M7 15.5h3.5V9H7M10.5 12.5h2.5V9h4M13 12.5h4"/>',
		'users'     => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19.5a5.5 5.5 0 0 1 11 0M15.5 5.8a3 3 0 0 1 0 5.5M17.5 14.3a5.5 5.5 0 0 1 3 5.2"/>',
		'target'    => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1"/>',
		'building'  => '<path d="M4 21V5.5L12 3v18M12 8h8v13M2.5 21h19"/><path d="M7 8h2M7 11.5h2M7 15h2M15 11.5h2M15 15h2"/>',
		'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="1.5"/><path d="M8.5 7V5a1 1 0 0 1 1-1h5a1 1 0 0 1 1 1v2M3 12.5h18M10.5 12.5v1.5h3v-1.5"/>',
		'letter'    => '<path d="M4 4.5h16v15H4z"/><path d="M7.5 8.5h9M7.5 12h9M7.5 15.5h4.5"/><path d="m15 17 1.5 1.5 3-3"/>',
		'eye'       => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/>',
		// Products / switchgear
		'cabinet'   => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M12 3v18M8 7h1.5M14.5 7H16M8 11h1.5M14.5 11H16M5 18h14"/>',
		'mv-cell'   => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M5 9h14M5 15h14"/><circle cx="12" cy="6" r="1"/><path d="M9 12h6M12 15v3"/>',
		'rmu'       => '<path d="M3 9.5 12 4l9 5.5M4.5 9v11.5h15V9"/><path d="M8 20.5v-6.5h8v6.5M12 14v6.5"/>',
		'meter'     => '<rect x="5" y="3" width="14" height="18" rx="1.5"/><rect x="8" y="6" width="8" height="4" rx=".5"/><path d="M9 14h1.5M13.5 14H15M9 17.5h6"/>',
		'plc'       => '<rect x="3" y="6" width="18" height="12" rx="1"/><path d="M7 6v12M11 6v12M15 6v12M8.8 9.5h.4M12.8 9.5h.4M16.8 9.5h.4M3 15h18"/>',
		'hospital'  => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M12 8.5v6M9 11.5h6"/>',
		'capacitor' => '<path d="M3 12h7M14 12h7M10 6v12M14 6v12"/>',
		'ats'       => '<path d="M4 8h13l-3-3M20 16H7l3 3"/>',
		'busbar'    => '<path d="M3 7h18M3 12h18M3 17h18"/><path d="M7 7v10M17 7v10"/>',
	);
}

/**
 * Inline SVG icon (24px grid, 1.75 stroke, currentColor) referencing the sprite printed
 * once after <body> (psb_icon_sprite), so repeated icons cost ~90 bytes each.
 *
 * @param string $name  Icon key (see psb_icon_paths()).
 * @param string $class CSS classes.
 * @return string
 */
function psb_icon( $name, $class = 'size-5' ) {
	$paths = psb_icon_paths();
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	if ( did_action( 'wp_body_open' ) || doing_action( 'wp_body_open' ) ) {
		return '<svg class="' . esc_attr( $class ) . '" aria-hidden="true"><use href="#i-' . $name . '"/></svg>';
	}
	// Before the sprite exists (e.g. <head>, REST): full inline SVG.
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
}

/** Print the icon sprite once, right after <body>. */
function psb_icon_sprite() {
	echo '<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true"><defs>';
	foreach ( psb_icon_paths() as $name => $path ) {
		echo '<symbol id="i-' . esc_attr( $name ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' . $path . '</symbol>'; // phpcs:ignore
	}
	echo '</defs></svg>';
}
add_action( 'wp_body_open', 'psb_icon_sprite', 1 );

/**
 * Quote request (RFQ) URL — page /request-quote/ (RFQ system in inc/rfq/).
 *
 * @param string $product_slug Optional product slug for product-specific CTAs.
 */
function psb_quote_url( $product_slug = '' ) {
	$url = home_url( '/request-quote/' );
	return $product_slug ? add_query_arg( 'product', urldecode( $product_slug ), $url ) : $url;
}

/**
 * Whether the mobile app tab bar is shown on this request (also adds body class "has-tabbar").
 * Hidden on product, quote, cart/checkout/account and login pages (spec §4.11).
 */
function psb_show_tabbar() {
	global $woo_active;
	if ( is_page( array( 'login', 'request-quote' ) ) || is_page_template( 'page-request-quote.php' ) ) {
		return false;
	}
	if ( $woo_active && function_exists( 'is_product' ) && ( is_product() || is_cart() || is_checkout() || is_account_page() ) ) {
		return false;
	}
	return (bool) apply_filters( 'psb_show_tabbar', true );
}

add_filter(
	'body_class',
	function ( $classes ) {
		if ( psb_show_tabbar() ) {
			$classes[] = 'has-tabbar';
		}
		return $classes;
	}
);

/**
 * Shorten a URL for HTML output by decoding percent-encoded UTF-8 (Persian slugs) into an IRI.
 * Browsers accept IRIs in href; this cuts Persian URLs to ~1/3 of their encoded length.
 */
function psb_iri( $url ) {
	return (string) preg_replace_callback(
		'/(?:%[CDEF][0-9A-F](?:%[89AB][0-9A-F])+)+/i',
		function ( $m ) {
			$s = rawurldecode( $m[0] );
			return preg_match( '//u', $s ) ? $s : $m[0];
		},
		(string) $url
	);
}

/**
 * Favicons from the vector brand kit (theme/images/brand/). Replaces WordPress' Site Icon tags so
 * the old raster logo is never used; the PWA module prints the apple-touch-icon when active.
 */
add_filter( 'site_icon_meta_tags', '__return_empty_array', 20 );
add_action(
	'wp_head',
	function () {
		$base = get_template_directory_uri() . '/images/brand/';
		echo '<link rel="icon" href="' . esc_url( $base . 'favicon.svg' ) . '" type="image/svg+xml">' . "\n";
		echo '<link rel="icon" href="' . esc_url( $base . 'favicon-48.png' ) . '" sizes="48x48" type="image/png">' . "\n";
		echo '<link rel="icon" href="' . esc_url( $base . 'favicon-32.png' ) . '" sizes="32x32" type="image/png">' . "\n";
		if ( ! function_exists( 'psb_pwa_head_tags' ) ) {
			echo '<link rel="apple-touch-icon" href="' . esc_url( $base . 'apple-touch-icon.png' ) . '">' . "\n";
		}
	},
	3
);

/**
 * Hero photo (spec §5.1): attachment 1344 (busbar assembly) when it exists, else the
 * front-page ACF hero image. Filter: psb_home_hero_image_ids.
 */
function psb_home_hero_image() {
	foreach ( (array) apply_filters( 'psb_home_hero_image_ids', array( 1344 ) ) as $id ) {
		if ( $id && wp_attachment_is_image( $id ) ) {
			return (int) $id;
		}
	}
	$hero = psb_home_hero();
	$img  = $hero['image'] ?? 0;
	return is_array( $img ) ? (int) ( $img['ID'] ?? 0 ) : (int) $img;
}

/**
 * Escape text and wrap Latin codes that contain digits (IEC 61439, IP54, SYStem6, 20~36 kV …)
 * in <span class="lat"> so Peyda FaNum does not turn their digits Persian (spec §2).
 *
 * @param string $text Plain text.
 * @return string Safe HTML.
 */
function psb_lat( $text ) {
	$html = esc_html( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' ) );
	$html = (string) preg_replace(
		'/(?<![\p{L}\p{N}])([A-Za-z][A-Za-z.\-]*\s?[0-9](?:[0-9~\-\/.]*[0-9])?(?:[A-Za-z][A-Za-z0-9]*)?(?:\s?(?:kV|kA|A|V|Hz))?|[0-9](?:[0-9~\-.]*[0-9])?\s?(?:kV|kA|Hz))(?![\p{L}\p{N}])/u',
		'<span class="lat">$1</span>',
		$html
	);
	// Latin company forms without digits (e.g. «SAREL s.r.l.») also use the Latin face.
	return (string) preg_replace( '/(?<![\p{L}>])(SAREL s\.r\.l\.?|EPIL)(?![\p{L}<])/u', '<span class="lat">$1</span>', $html );
}

/**
 * Theme-shipped photos (images/photos/): real PSB photos from the live media library,
 * re-graded (white balance, gentle contrast) and cut per breakpoint as AVIF + WebP with a
 * JPEG fallback (scratchpad proc.py). No retouching beyond that — nothing added or removed.
 * Live sources: hero-mv-lineup* = attachment 1831 (MV switchgear line-up),
 * about-assembly* = 1922 / 1921 (panel assembly interior).
 *
 * @return array<string,array> Set name => { img: [file, w, h], sources: [ {media, base, widths, w, h, sizes} ] }.
 */
function psb_photo_sets() {
	return array(
		'hero'  => array(
			'img'     => array( 'hero-mv-lineup-960.jpg', 1280, 920 ),
			'sources' => array(
				array(
					'media'  => '(min-width: 48rem)',
					'base'   => 'hero-mv-lineup',
					'widths' => array( 640, 960, 1280 ),
					'w'      => 1280,
					'h'      => 920,
					'sizes'  => '(min-width: 64rem) 60vw, 100vw',
				),
				array(
					'media'  => '(max-width: 47.99rem)',
					'base'   => 'hero-mv-lineup-m',
					'widths' => array( 480, 720 ),
					'w'      => 720,
					'h'      => 900,
					'sizes'  => '100vw',
				),
			),
		),
		'about' => array(
			'img'     => array( 'about-assembly-640.jpg', 640, 800 ),
			'sources' => array(
				array(
					'media'  => '(min-width: 64rem)',
					'base'   => 'about-assembly',
					'widths' => array( 640, 960 ),
					'w'      => 960,
					'h'      => 1200,
					'sizes'  => '(min-width: 80rem) 36rem, 45vw',
				),
				array(
					'media'  => '(max-width: 63.99rem)',
					'base'   => 'about-assembly-w',
					'widths' => array( 768, 1280 ),
					'w'      => 1280,
					'h'      => 720,
					'sizes'  => '(min-width: 48rem) 42rem, 100vw',
				),
			),
		),
	);
}

/** srcset string for one photo source in one format. */
function psb_photo_srcset( $base, $widths, $ext ) {
	$out = array();
	foreach ( $widths as $w ) {
		$out[] = get_theme_file_uri( "images/photos/{$base}-{$w}.{$ext}" ) . " {$w}w";
	}
	return implode( ', ', $out );
}

/**
 * <picture> for a theme photo set (AVIF, WebP, then JPEG <img>).
 *
 * @param string $set   Key of psb_photo_sets().
 * @param array  $attrs <img> attributes (class, alt, loading, fetchpriority …).
 * @return string HTML.
 */
function psb_photo_picture( $set, $attrs = array() ) {
	$sets = psb_photo_sets();
	if ( empty( $sets[ $set ] ) ) {
		return '';
	}
	$s    = $sets[ $set ];
	$html = '<picture>';
	foreach ( $s['sources'] as $src ) {
		foreach ( array( 'avif', 'webp' ) as $ext ) {
			$html .= sprintf(
				'<source type="image/%1$s" media="%2$s" srcset="%3$s" sizes="%4$s" width="%5$d" height="%6$d">',
				$ext,
				esc_attr( $src['media'] ),
				esc_attr( psb_photo_srcset( $src['base'], $src['widths'], $ext ) ),
				esc_attr( $src['sizes'] ),
				$src['w'],
				$src['h']
			);
		}
	}
	$attrs = array_merge(
		array(
			'src'      => get_theme_file_uri( 'images/photos/' . $s['img'][0] ),
			'width'    => $s['img'][1],
			'height'   => $s['img'][2],
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		),
		$attrs
	);
	$html .= '<img';
	foreach ( $attrs as $k => $v ) {
		$html .= ' ' . $k . '="' . esc_attr( $v ) . '"';
	}
	return $html . '></picture>';
}

/** AVIF preload links (one per breakpoint) for a theme photo set — used for the home hero (LCP). */
function psb_photo_preload( $set ) {
	$sets = psb_photo_sets();
	foreach ( $sets[ $set ]['sources'] ?? array() as $src ) {
		printf(
			'<link rel="preload" as="image" type="image/avif" media="%s" imagesrcset="%s" imagesizes="%s" fetchpriority="high">' . "\n",
			esc_attr( $src['media'] ),
			esc_attr( psb_photo_srcset( $src['base'], $src['widths'], 'avif' ) ),
			esc_attr( $src['sizes'] )
		);
	}
}
