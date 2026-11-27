<?php
/**
 * Type-matched default illustrations («تصویر نمایشی») + an image-quality guard.
 *
 * Files: theme/images/placeholders/<key>.svg (with the corner tag), <key>-notext.svg, <key>.webp (1200×900, og:image).
 * They are honest line-art illustrations, never fake photos, and are always labelled «تصویر نمایشی».
 *
 * - psb_placeholder_key( $post )               → illustration key for a product / service / project / post
 * - psb_placeholder_img( $key, $attr )         → <img class="is-placeholder"> (1200×900)
 * - psb_placeholder_url( $key, $ext )          → URL of the svg / webp
 * - psb_image_fits( $att, $slot_w, $ratio )    → can the real photo fill the slot sharply?
 * - psb_best_image( $post_or_att, $slot_w, $ratio, $args ) → real photo when sharp enough, else the illustration
 * - psb_single_image( $post_or_att, $slot_w, $ratio, $args ) → singles: photo / low-res photo at natural size / illustration
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'PSB_PLACEHOLDERS_LOADED' ) ) {
	return;
}
define( 'PSB_PLACEHOLDERS_LOADED', true );

/** Key => Persian label (used in alt text). */
function psb_placeholder_keys() {
	return array(
		// Equipment (quote-form type keys).
		'lv_mdb'                => 'تابلو اصلی فشار ضعیف (MDB)',
		'lv_smdb'               => 'تابلو نیمه‌اصلی و توزیع',
		'lv_mcc'                => 'تابلو MCC کشویی',
		'lv_capacitor'          => 'بانک خازنی',
		'lv_ats'                => 'تابلو ATS / چنج‌اور',
		'lv_lighting'           => 'تابلو روشنایی',
		'mv_fixed'              => 'سلول فشار متوسط ثابت',
		'mv_withdrawable'       => 'سلول فشار متوسط کشویی',
		'rmu'                   => 'پست کمپکت و RMU',
		'meter'                 => 'تابلو کنتوری',
		'control'               => 'تابلو فرمان و کنترل',
		'hospital'              => 'تابلو ایزوله بیمارستانی',
		'busduct'               => 'باس‌داکت',
		// Services.
		'design'                => 'طراحی و نقشه‌کشی',
		'cnc'                   => 'برش و خم CNC ورق',
		'coating'               => 'رنگ پودری الکترواستاتیک',
		'lab'                   => 'آزمایشگاه و آزمون',
		'installation'          => 'نصب و راه‌اندازی',
		'aftersales'            => 'خدمات پس از فروش',
		// Project sectors (psb_pr_sectors() keys; no image for «government»).
		'sector-industrial'     => 'پروژه صنعتی و تولیدی',
		'sector-oil-gas'        => 'پروژه نفت، گاز و پتروشیمی',
		'sector-power'          => 'پروژه آب، برق و نیروگاه',
		'sector-building'       => 'پروژه ساختمانی و تجاری',
		'sector-healthcare'     => 'پروژه بیمارستانی و درمانی',
		'sector-food'           => 'پروژه صنایع غذایی',
		'sector-mining'         => 'پروژه معدن و فلزات',
		'sector-infrastructure' => 'پروژه حمل‌ونقل و زیرساخت',
		'sector-other'          => 'پروژه',
		'default'               => 'تابلو برق',
	);
}

/**
 * URL of an illustration.
 *
 * @param string $key     Key (unknown → default).
 * @param string $ext     'svg' | 'webp'.
 * @param bool   $notext  Text-free svg variant.
 */
function psb_placeholder_url( $key, $ext = 'svg', $notext = false ) {
	$key = isset( psb_placeholder_keys()[ $key ] ) ? $key : 'default';
	$ext = 'webp' === $ext ? 'webp' : 'svg';
	return get_template_directory_uri() . '/images/placeholders/' . $key . ( $notext && 'svg' === $ext ? '-notext' : '' ) . '.' . $ext;
}

/** Normalise Persian text for keyword matching (Arabic ي/ك, ZWNJ, hamza-yeh, case). */
function psb_placeholder_norm( $text ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
	$text = str_replace( array( 'ي', 'ك', 'ئی', 'ئ', "\u{200C}", '-', '_', '‌' ), array( 'ی', 'ک', 'یی', 'ی', ' ', ' ', ' ', ' ' ), $text );
	$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text ) : strtolower( $text );
	return ' ' . preg_replace( '/\s+/u', ' ', $text ) . ' ';
}

/**
 * First key whose needle occurs in the text. A needle wrapped in spaces (« مال ») only matches a whole word.
 *
 * @param string $text Haystack (normalised by psb_placeholder_norm()).
 * @param array  $map  needle => key, in priority order.
 */
function psb_placeholder_match( $text, $map ) {
	foreach ( $map as $needle => $key ) {
		$n = trim( psb_placeholder_norm( $needle ) );
		$n = ( ' ' === substr( (string) $needle, 0, 1 ) ) ? ' ' . $n . ' ' : $n;
		if ( '' !== $n && false !== mb_strpos( $text, $n ) ) {
			return $key;
		}
	}
	return '';
}

/**
 * Like psb_placeholder_match(), but the needle that occurs EARLIEST in the text wins (article titles name their subject first).
 *
 * @param string $text Haystack (normalised).
 * @param array  $map  needle => key.
 */
function psb_placeholder_match_first( $text, $map ) {
	$best = '';
	$pos  = PHP_INT_MAX;
	foreach ( $map as $needle => $key ) {
		$n = trim( psb_placeholder_norm( $needle ) );
		$n = ( ' ' === substr( (string) $needle, 0, 1 ) ) ? ' ' . $n . ' ' : $n;
		$p = '' === $n ? false : mb_strpos( $text, $n );
		if ( false !== $p && $p < $pos ) {
			$pos  = $p;
			$best = $key;
		}
	}
	return $best;
}

/** Equipment keywords (titles, product categories, blog posts). Specific types first. */
function psb_placeholder_equipment_map() {
	return array(
		'ایزوله'          => 'hospital',
		'بیمارستان'       => 'hospital',
		'it پزشکی'        => 'hospital',
		'rmu'             => 'rmu',
		'رینگ مین'        => 'rmu',
		'کمپکت'           => 'rmu',
		'پست زمینی'       => 'rmu',
		'باس داکت'        => 'busduct',
		'باسداکت'         => 'busduct',
		'busduct'         => 'busduct',
		'busway'          => 'busduct',
		'شینه کشی'        => 'busduct',
		'خازن'            => 'lv_capacitor',
		'اصلاح ضریب'      => 'lv_capacitor',
		'mcc'             => 'lv_mcc',
		'موتور کنترل'     => 'lv_mcc',
		'ats'             => 'lv_ats',
		'چنج'             => 'lv_ats',
		'ژنراتور'         => 'lv_ats',
		'اضطراری'         => 'lv_ats',
		'روشنایی'         => 'lv_lighting',
		'کنتور'           => 'meter',
		'اندازه گیری'     => 'meter',
		'plc'             => 'control',
		'فرمان'           => 'control',
		'کنترل'           => 'control',
		'اتوماسیون'       => 'control',
		'کشویی'           => 'mv_withdrawable',
		'کشوئی'           => 'mv_withdrawable',
		'withdrawable'    => 'mv_withdrawable',
		'فشار متوسط'      => 'mv_fixed',
		'فشارمتوسط'       => 'mv_fixed',
		'فشار قوی'        => 'mv_fixed',
		'20kv'            => 'mv_fixed',
		'۲۰ کیلوولت'      => 'mv_fixed',
		'medium voltage'  => 'mv_fixed',
		'62271'           => 'mv_fixed',
		'sf6'             => 'mv_fixed',
		'توزیع'           => 'lv_smdb',
		'smdb'            => 'lv_smdb',
		'mdb'             => 'lv_mdb',
		'61439'           => 'lv_mdb',
		'فشار ضعیف'       => 'lv_mdb',
		'فشارضعیف'        => 'lv_mdb',
		'تابلو اصلی'      => 'lv_mdb',
		'تابلو برق'       => 'lv_mdb',
	);
}

/** Service keywords (title / slug), in priority order. */
function psb_placeholder_service_map() {
	return array(
		'باس داکت'     => 'busduct',
		'باسداکت'      => 'busduct',
		'شینه'         => 'busduct',
		'آزمایشگاه'    => 'lab',
		'تست'          => 'lab',
		'آزمون'        => 'lab',
		'cnc'          => 'cnc',
		'ورق'          => 'cnc',
		'پرس'          => 'cnc',
		'برش'          => 'cnc',
		'خم کاری'      => 'cnc',
		'رنگ'          => 'coating',
		'پودری'        => 'coating',
		'coating'      => 'coating',
		'پس از فروش'   => 'aftersales',
		'گارانتی'      => 'aftersales',
		'تعمیر'        => 'aftersales',
		'قطعات یدکی'   => 'aftersales',
		'نگهداری'      => 'aftersales',
		'سرویس'        => 'aftersales',
		'طراحی'        => 'design',
		'نقشه'         => 'design',
		'مهندسی'       => 'design',
		'نصب'          => 'installation',
		'راه اندازی'   => 'installation',
	);
}

/** Extra sector words not covered by psb_pr_sectors() keywords (whole-word needles start with a space). */
function psb_placeholder_sector_map() {
	return array(
		'بیمارستان'   => 'healthcare',
		'درمانگاه'    => 'healthcare',
		'کلینیک'      => 'healthcare',
		'پالایشگاه'   => 'oil-gas',
		'پتروشیمی'    => 'oil-gas',
		'نفت'         => 'oil-gas',
		' گاز '       => 'oil-gas',
		'نیروگاه'     => 'power',
		'پست برق'     => 'power',
		'آب و فاضلاب' => 'power',
		'فولاد'       => 'mining',
		'معدن'        => 'mining',
		'سیمان'       => 'mining',
		'آهک'         => 'mining',
		' مس '        => 'mining',
		'لبنی'        => 'food',
		'غذایی'       => 'food',
		'کشت و صنعت'  => 'food',
		'فرودگاه'     => 'infrastructure',
		'مترو'        => 'infrastructure',
		'راه آهن'     => 'infrastructure',
		'بندر'        => 'infrastructure',
		' مال '       => 'building',
		'مرکز خرید'   => 'building',
		'پاساژ'       => 'building',
		'برج'         => 'building',
		'هتل'         => 'building',
		'مجتمع'       => 'building',
		'ساختمان'     => 'building',
		'تجاری'       => 'building',
		'اداری'       => 'building',
		'کارخانه'     => 'industrial',
		'صنایع'       => 'industrial',
		'صنعتی'       => 'industrial',
		'تولیدی'      => 'industrial',
	);
}

/** Sector slug (psb_pr_sectors()) → illustration key. Government (and unknown) → the generic project scene. */
function psb_placeholder_sector_key( $slug ) {
	$slug = sanitize_key( (string) $slug );
	if ( '' === $slug ) {
		return '';
	}
	$key = 'sector-' . $slug;
	return isset( psb_placeholder_keys()[ $key ] ) ? $key : 'sector-other';
}

/** Sector slug from free text (sector cell, term names, title, client). */
function psb_placeholder_sector_from_text( $text ) {
	$slug = '';
	if ( function_exists( 'psb_pr_match_sector' ) ) {
		$slug = psb_pr_match_sector( $text );
	}
	if ( ! $slug ) {
		$slug = psb_placeholder_match( psb_placeholder_norm( $text ), psb_placeholder_sector_map() );
	}
	if ( ! $slug && function_exists( 'psb_pr_infer_sector' ) ) {
		$slug = psb_pr_infer_sector( $text );
	}
	return $slug;
}

/** Family of an equipment key (lv / mv / own key) — used to let a title refine a generic category. */
function psb_placeholder_family( $key ) {
	if ( 0 === strpos( $key, 'lv_' ) ) {
		return 'lv';
	}
	if ( 0 === strpos( $key, 'mv_' ) ) {
		return 'mv';
	}
	return $key;
}

/**
 * Illustration key for a post.
 *
 * @param int|WP_Post|null $post Post (default: current).
 * @return string
 */
function psb_placeholder_key( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 'default';
	}
	$keys  = psb_placeholder_keys();
	$title = $post->post_title . ' ' . urldecode( $post->post_name );
	$key   = '';

	switch ( $post->post_type ) {
		case 'product':
			foreach ( array( '_psb_rfq_type', 'psb_rfq_type' ) as $meta ) {
				$m = sanitize_key( (string) get_post_meta( $post->ID, $meta, true ) );
				if ( $m && isset( $keys[ $m ] ) ) {
					$key = $m;
					break;
				}
			}
			if ( ! $key ) {
				$cat_map = array(
					'low-voltage'    => 'lv_mdb',
					'medium-voltage' => 'mv_fixed',
					'compact-rmu'    => 'rmu',
					'rmu'            => 'rmu',
					'metering'       => 'meter',
					'control'        => 'control',
					'hospital'       => 'hospital',
					'busduct'        => 'busduct',
				);
				$cat   = '';
				$names = '';
				$terms = taxonomy_exists( 'product_cat' ) ? get_the_terms( $post->ID, 'product_cat' ) : array();
				foreach ( is_array( $terms ) ? $terms : array() as $t ) {
					$names .= ' ' . $t->name;
					foreach ( $cat_map as $needle => $k ) {
						if ( ! $cat && false !== strpos( urldecode( $t->slug ), $needle ) ) {
							$cat = $k;
						}
					}
				}
				$kw = psb_placeholder_match( psb_placeholder_norm( $title ), psb_placeholder_equipment_map() );
				if ( $cat && $kw && psb_placeholder_family( $cat ) === psb_placeholder_family( $kw ) ) {
					$key = $kw;
				} elseif ( $cat ) {
					$key = $cat;
				} else {
					$key = $kw ? $kw : psb_placeholder_match( psb_placeholder_norm( $names ), psb_placeholder_equipment_map() );
				}
			}
			if ( ! $key && function_exists( 'psb_rfq_type_for_product' ) ) {
				$k   = psb_rfq_type_for_product( $post );
				$key = isset( $keys[ $k ] ) ? $k : '';
			}
			break;

		case 'services':
			$key = psb_placeholder_match( psb_placeholder_norm( $title ), psb_placeholder_service_map() );
			if ( ! $key ) {
				$key = psb_placeholder_match( psb_placeholder_norm( $title ), psb_placeholder_equipment_map() );
			}
			break;

		case 'portfolio':
			$text = '';
			if ( function_exists( 'psb_field_text' ) ) {
				$text .= ' ' . psb_field_text( 'industry_sector', $post->ID );
			}
			$terms = get_the_terms( $post->ID, 'portfolio_categories' );
			$text .= is_array( $terms ) ? ' ' . implode( ' ', wp_list_pluck( $terms, 'name' ) ) : '';
			$slug  = function_exists( 'psb_pr_match_sector' ) ? psb_pr_match_sector( trim( $text ) ) : '';
			if ( ! $slug ) {
				$client = function_exists( 'psb_field_text' ) ? psb_field_text( 'client_name', $post->ID ) : '';
				$slug   = psb_placeholder_sector_from_text( $post->post_title . ' ' . $client . ' ' . $text );
			}
			$key = psb_placeholder_sector_key( $slug );
			break;

		default:
			if ( defined( 'PSB_PR_CPT' ) && PSB_PR_CPT === $post->post_type ) {
				$terms = defined( 'PSB_PR_TAX' ) ? get_the_terms( $post->ID, PSB_PR_TAX ) : array();
				$slug  = is_array( $terms ) && $terms ? $terms[0]->slug : '';
				if ( ! $slug ) {
					$slug = psb_placeholder_sector_from_text( $post->post_title . ' ' . get_post_meta( $post->ID, '_pr_client', true ) );
				}
				$key = psb_placeholder_sector_key( $slug );
				break;
			}
			// Posts and anything else: series / categories / tags / title.
			$text = $post->post_title;
			foreach ( array( 'series', 'category', 'post_tag' ) as $tax ) {
				if ( taxonomy_exists( $tax ) ) {
					$terms = get_the_terms( $post->ID, $tax );
					$text .= is_array( $terms ) ? ' ' . implode( ' ', wp_list_pluck( $terms, 'name' ) ) : '';
				}
			}
			$norm = psb_placeholder_norm( $text );
			$key  = psb_placeholder_match_first( psb_placeholder_norm( $post->post_title ), psb_placeholder_service_map() + psb_placeholder_equipment_map() );
			if ( ! $key ) {
				$key = psb_placeholder_match( $norm, psb_placeholder_equipment_map() + psb_placeholder_service_map() );
			}
	}

	$key = (string) apply_filters( 'psb_placeholder_key', $key ? $key : 'default', $post );
	return isset( $keys[ $key ] ) ? $key : 'default';
}

/**
 * <img> of an illustration.
 *
 * @param string $key  Key.
 * @param array  $attr class, alt (suffix after «تصویر نمایشی»), loading, fetchpriority, style,
 *                    tag (default true: text-free svg + a crisp HTML «تصویر نمایشی» chip, `.ph-tag`, which needs a
 *                    positioned parent; false: the svg with its own built-in corner tag).
 */
function psb_placeholder_img( $key, $attr = array() ) {
	$keys  = psb_placeholder_keys();
	$key   = isset( $keys[ $key ] ) ? $key : 'default';
	$label = isset( $attr['alt'] ) && '' !== $attr['alt'] ? $attr['alt'] : $keys[ $key ];
	$tag   = ! isset( $attr['tag'] ) || $attr['tag'];
	$html  = sprintf(
		'<img src="%s" width="1200" height="900" alt="%s" class="%s" loading="%s" decoding="async"%s%s data-placeholder="%s">',
		esc_url( psb_placeholder_url( $key, 'svg', $tag ) ),
		esc_attr( 'تصویر نمایشی: ' . $label ),
		esc_attr( trim( 'is-placeholder ' . ( $attr['class'] ?? '' ) ) ),
		esc_attr( $attr['loading'] ?? 'lazy' ),
		! empty( $attr['fetchpriority'] ) && 'auto' !== $attr['fetchpriority'] ? ' fetchpriority="' . esc_attr( $attr['fetchpriority'] ) . '"' : '',
		! empty( $attr['style'] ) ? ' style="' . esc_attr( $attr['style'] ) . '"' : '',
		esc_attr( $key )
	);
	if ( $tag ) {
		$html .= '<span class="ph-tag" aria-hidden="true">تصویر نمایشی</span>';
	}
	return $html;
}

/** '4 / 3', '4:3', '16/9', 1.5 → float width/height ratio. */
function psb_placeholder_ratio( $ratio ) {
	if ( is_numeric( $ratio ) ) {
		return max( 0.1, (float) $ratio );
	}
	if ( preg_match( '#([\d.]+)\s*[/:]\s*([\d.]+)#', (string) $ratio, $m ) && (float) $m[2] > 0 ) {
		return (float) $m[1] / (float) $m[2];
	}
	return 4 / 3;
}

/** Natural size of an attachment: array( w, h ) or null. */
function psb_image_natural_size( $att_id ) {
	$meta = wp_get_attachment_metadata( (int) $att_id );
	if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
		return null;
	}
	return array( (int) $meta['width'], (int) $meta['height'] );
}

/**
 * Can this attachment fill a slot at ≥1.5× DPR without being upscaled more than 1.25×?
 * Uses the part of the image left after the cover crop to the slot ratio.
 *
 * @param int          $att_id Attachment ID.
 * @param int          $slot_w Slot width in CSS px (desktop).
 * @param string|float $ratio  Slot aspect ratio.
 */
function psb_image_fits( $att_id, $slot_w, $ratio = '4 / 3' ) {
	$size = psb_image_natural_size( $att_id );
	if ( ! $size ) {
		// SVG or unknown metadata: trust it.
		return (bool) wp_attachment_is_image( $att_id ) || 'image/svg+xml' === get_post_mime_type( $att_id );
	}
	list( $w, $h ) = $size;
	$r      = psb_image_ratio_safe( $ratio );
	$crop_w = ( $w / $h > $r ) ? $h * $r : $w;
	$fits   = $crop_w * 1.25 >= $slot_w * 1.5;
	return (bool) apply_filters( 'psb_image_fits', $fits, $att_id, $slot_w, $r, $size );
}

/** Internal: ratio helper alias (keeps psb_image_fits readable). */
function psb_image_ratio_safe( $ratio ) {
	return psb_placeholder_ratio( $ratio );
}

/**
 * Resolve ($post_or_attachment) → array( attachment_id, post|null ).
 *
 * @param int|WP_Post $thing Post or attachment.
 */
function psb_placeholder_resolve( $thing ) {
	$p = empty( $thing ) ? null : get_post( $thing );
	if ( ! $p ) {
		return array( 0, null );
	}
	if ( 'attachment' === $p->post_type ) {
		return array( psb_image_blocked( $p->ID ) ? 0 : (int) $p->ID, $p->post_parent ? get_post( $p->post_parent ) : null );
	}
	$att = (int) get_post_thumbnail_id( $p );
	return array( ( $att && psb_image_blocked( $att ) ) ? 0 : $att, $p );
}

/**
 * Images that must never be shown as photos (AI-generated renders, stock/catalogue collages,
 * vector templates). Matched by live attachment ID and by file basename (local copies have other IDs).
 * Blocked images fall back to the matching illustration.
 */
function psb_image_blocked( $att_id ) {
	static $memo = array();
	$att_id = (int) $att_id;
	if ( ! $att_id ) {
		return false;
	}
	if ( isset( $memo[ $att_id ] ) ) {
		return $memo[ $att_id ];
	}
	$ids   = array( 1336, 1388, 1703, 1734, 1742, 1743, 1744, 1745, 1750, 1752, 1776, 1777, 1837, 1838, 1839, 1841, 1842, 1705, 1708, 1836 ); // 1705/1708/1836: AI-generated «Electrical-Panel» renders (garbled labels).
	$files = array(
		'psb-powder-coating-unit', 'psb-sheet-metal-machinery', 'psb-cnc-amada-machinery-banner',
		'0b10e189-5a18-4b99-a943-6218b886efc9', '4ef125b9-cf0b-4c84-b2ad-5e5ce714f9c2',
		'psb-rmu-20kv-compact-switchgear-banner', 'psb-iec-61439-switchgear-excellence', 'psb-iec-61439-switchgear-banner',
		'yellow-and-gray-mobile-industrial-sprayer', 'certificate-vector-free-download',
		'picture1', 'picture4', 'picture5', 'picture6', 'picture7',
	);
	$blocked = false;
	if ( 'psb.co.ir' === wp_parse_url( home_url(), PHP_URL_HOST ) && in_array( $att_id, $ids, true ) ) {
		$blocked = true;
	} else {
		$file = (string) get_post_meta( $att_id, '_wp_attached_file', true );
		$base = strtolower( preg_replace( '/(-\d+)?(-\d+x\d+)?$/', '', pathinfo( $file, PATHINFO_FILENAME ) ) );
		$blocked = '' !== $base && in_array( $base, $files, true );
	}
	$memo[ $att_id ] = (bool) apply_filters( 'psb_image_blocked', $blocked, $att_id );
	return $memo[ $att_id ];
}

/**
 * Cards: the real photo when it is sharp enough for the slot, otherwise the matching illustration.
 *
 * @param int|WP_Post  $thing  Post (uses its featured image) or attachment ID.
 * @param int          $slot_w Slot width in CSS px.
 * @param string|float $ratio  Slot aspect ratio.
 * @param array        $args   size (default medium_large), attr (for psb_image / the placeholder), key (illustration override).
 * @return string HTML
 */
function psb_best_image( $thing, $slot_w = 400, $ratio = '4 / 3', $args = array() ) {
	list( $att, $post ) = psb_placeholder_resolve( $thing );
	$attr = (array) ( $args['attr'] ?? array() );
	// Product cards keep the line illustration while the featured photo is only the automatic one
	// (inc/featured-setup.php: it serves og:image / schema); a photo the owner picks is shown.
	if ( $att && $post && 'product' === $post->post_type && function_exists( 'psb_featured_is_auto' ) && psb_featured_is_auto( $post->ID ) ) {
		$att = 0;
	}
	if ( $att && psb_image_fits( $att, $slot_w, $ratio ) ) {
		$html = psb_image( $att, $args['size'] ?? 'medium_large', $attr );
		if ( $html ) {
			return $html;
		}
	}
	$key = $args['key'] ?? ( $post ? psb_placeholder_key( $post ) : 'default' );
	unset( $attr['alt'], $attr['sizes'] );
	return psb_placeholder_img( $key, $attr );
}

/**
 * Single pages: sharp photo → normal (cover) image; low-res photo → shown at its natural size, never upscaled,
 * centred in a framed box; no photo → the illustration.
 *
 * @param int|WP_Post  $thing  Post or attachment.
 * @param int          $slot_w Slot width in CSS px.
 * @param string|float $ratio  Slot aspect ratio.
 * @param array        $args   size (default large), attr, key.
 * @return array( 'mode' => photo|lowres|placeholder, 'html' => string, 'id' => int )
 */
function psb_single_image( $thing, $slot_w = 720, $ratio = '4 / 3', $args = array() ) {
	list( $att, $post ) = psb_placeholder_resolve( $thing );
	$attr = (array) ( $args['attr'] ?? array() );
	if ( $att ) {
		if ( psb_image_fits( $att, $slot_w, $ratio ) ) {
			return array( 'mode' => 'photo', 'id' => $att, 'html' => psb_image( $att, $args['size'] ?? 'large', $attr ) );
		}
		$size = psb_image_natural_size( $att );
		$attr['class'] = trim( 'is-lowres ' . ( $attr['class'] ?? '' ) );
		$attr['sizes'] = $size ? $size[0] . 'px' : ( $attr['sizes'] ?? '' );
		$html          = psb_image( $att, 'full', $attr );
		if ( $html ) {
			return array( 'mode' => 'lowres', 'id' => $att, 'html' => $html );
		}
	}
	$key = $args['key'] ?? ( $post ? psb_placeholder_key( $post ) : 'default' );
	unset( $attr['alt'], $attr['sizes'] );
	return array( 'mode' => 'placeholder', 'id' => 0, 'html' => psb_placeholder_img( $key, $attr ) );
}
