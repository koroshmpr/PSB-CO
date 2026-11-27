<?php
/**
 * Page content fields: every text and photo on content pages comes from ACF fields on that page,
 * so the site manager can change it from the page edit screen (tabs, one per section).
 *
 * - Field groups are registered in PHP (versioned with the theme) by home.php / about.php. They
 *   reuse the original database group keys, so they replace those groups and keep saved values.
 * - Each field's default is the text the page showed before; a field left untouched shows it.
 *   Clearing a field hides that element. Repeaters are seeded once with their defaults.
 * - Tokens in any text: {brand} {years} {founded} {year} {warranty} {service} {approvals} {tests}
 *   (live values, Persian digits), so «۵۰ سال» stays correct every year.
 * - Photos: when an image field is empty the theme's own photo is used.
 *
 * Read with psb_pf(), psb_pf_rows(), psb_pf_head(); print with psb_pf_text() / psb_pf_html().
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Defaults registry: field path («name» or «group.sub») => default value. */
function psb_pf_registry( $set = null ) {
	static $defaults = array();
	if ( is_array( $set ) ) {
		$defaults = array_merge( $defaults, $set );
	}
	return $defaults;
}

/** Default of a field path ('' when unknown). */
function psb_pf_default( $path ) {
	$all = psb_pf_registry();
	return $all[ $path ] ?? '';
}

/**
 * Field value for a page. «group.sub» reads a sub field of an ACF group.
 * Falls back to the registered default when ACF is unavailable or the value was never saved.
 */
function psb_pf( $path, $post_id ) {
	$post_id = (int) $post_id;
	$parts   = explode( '.', $path, 2 );
	if ( ! $post_id || ! function_exists( 'get_field' ) ) {
		return psb_pf_default( $path );
	}
	$meta_key = isset( $parts[1] ) ? $parts[0] . '_' . $parts[1] : $parts[0];
	if ( ! metadata_exists( 'post', $post_id, $meta_key ) ) {
		return psb_pf_default( $path );
	}
	$value = get_field( $parts[0], $post_id );
	if ( isset( $parts[1] ) ) {
		$value = is_array( $value ) ? ( $value[ $parts[1] ] ?? null ) : null;
	}
	return null === $value || false === $value ? '' : $value;
}

/** Repeater rows; the registered default rows until the field has been saved once. */
function psb_pf_rows( $name, $post_id ) {
	$value = psb_pf( $name, $post_id );
	return is_array( $value ) ? array_values( $value ) : array();
}

/** Live values for the text tokens. */
function psb_pf_tokens() {
	static $tokens = null;
	if ( null === $tokens ) {
		$count  = function ( $type ) {
			return post_type_exists( $type ) ? (int) ( wp_count_posts( $type )->publish ?? 0 ) : 0;
		};
		$tokens = array(
			'{brand}'     => psb_brand_name(),
			'{years}'     => psb_fa_num( psb_years_active() ),
			'{founded}'   => psb_fa_num( PSB_FOUNDED_JALALI ),
			'{year}'      => psb_fa_num( psb_jalali_year() ),
			'{warranty}'  => psb_fa_num( PSB_WARRANTY_MONTHS ),
			'{service}'   => psb_fa_num( PSB_SERVICE_YEARS ),
			'{approvals}' => psb_fa_num( $count( 'confirmation' ) ),
			'{tests}'     => psb_fa_num( $count( 'type-test' ) ),
		);
	}
	return $tokens;
}

/** Plain text with tokens replaced (not escaped). */
function psb_pf_text( $text ) {
	return trim( strtr( psb_brand_fix( (string) $text ), psb_pf_tokens() ) );
}

/** Escaped HTML: tokens replaced, Latin codes and words (IEC 61439, ISO 9001, AMADA) wrapped in .lat. */
function psb_pf_html( $text ) {
	$html = psb_lat( psb_pf_text( $text ) );
	return (string) preg_replace_callback(
		'#(<span class="lat">.*?</span>)|([A-Za-z][A-Za-z0-9.\-/]*(?:\s[A-Za-z0-9][A-Za-z0-9.\-/]*)*)#u',
		function ( $m ) {
			return '' !== $m[1] ? $m[1] : '<span class="lat">' . $m[2] . '</span>';
		},
		$html
	);
}

/** Attachment ID from an ACF image value (ID, array or URL-less), 0 when not an image. */
function psb_pf_image_id( $value ) {
	$id = is_array( $value ) ? (int) ( $value['ID'] ?? ( $value['id'] ?? 0 ) ) : (int) $value;
	return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
}

/**
 * Whether an attachment is a wide landscape image (e.g. the factory front drawing, about 2:1).
 * Photo slots with a fixed portrait/4:3 crop show such images at their own ratio instead,
 * so a wide drawing is never cut at the sides.
 */
function psb_pf_is_wide( $id, $ratio = 1.5 ) {
	$m = $id ? wp_get_attachment_metadata( $id ) : array();
	return ! empty( $m['width'] ) && ! empty( $m['height'] ) && ( $m['width'] / $m['height'] ) >= $ratio;
}

/**
 * Section-head args with eyebrow / title / lead / link label from fields «{$prefix}_eyebrow» … «_link».
 *
 * @param string $prefix  Field prefix (e.g. home_products).
 * @param int    $post_id Page ID.
 * @param array  $args    Other section-head args (id, invert, link url …).
 */
function psb_pf_head( $prefix, $post_id, $args = array() ) {
	foreach ( array( 'eyebrow', 'title', 'lead' ) as $k ) {
		$args[ $k ] = psb_pf_text( psb_pf( "{$prefix}_{$k}", $post_id ) );
	}
	if ( isset( $args['link'] ) ) {
		$label = psb_pf_text( psb_pf( "{$prefix}_link", $post_id ) );
		if ( '' === $label ) {
			unset( $args['link'] );
		} else {
			$args['link']['label'] = $label;
		}
	}
	return $args;
}

/**
 * <picture> for a field photo: optional mobile attachment below $mobile_media, desktop <img> otherwise.
 *
 * @param int    $desktop      Attachment ID.
 * @param int    $mobile       Attachment ID or 0.
 * @param array  $attrs        <img> attributes; 'sizes' = desktop sizes.
 * @param string $mobile_media Media query of the mobile source.
 */
function psb_pf_picture( $desktop, $mobile = 0, $attrs = array(), $mobile_media = '(max-width: 47.99rem)' ) {
	if ( ! $desktop ) {
		return '';
	}
	$html = '<picture>';
	if ( $mobile ) {
		$html .= sprintf(
			'<source media="%s" srcset="%s" sizes="100vw">',
			esc_attr( $mobile_media ),
			esc_attr( (string) wp_get_attachment_image_srcset( $mobile, 'full' ) )
		);
	}
	$html .= wp_get_attachment_image( $desktop, 'full', false, wp_parse_args( $attrs, array( 'loading' => 'lazy', 'decoding' => 'async' ) ) );
	return $html . '</picture>';
}

/**
 * Whether a field photo is one of the theme's own optimised photos (uploaded so the manager sees it in
 * the field). Then templates print the theme set instead (AVIF + WebP, per-breakpoint crops: the mobile
 * hero is 42 KB AVIF vs 73 KB WebP from the media library).
 *
 * @param int    $att_id Attachment ID.
 * @param string $prefix File-name prefix of the uploaded copy (e.g. psb-hero-mv-switchgear-lineup).
 */
function psb_pf_is_theme_photo( $att_id, $prefix ) {
	$file = $att_id ? basename( (string) get_attached_file( $att_id ) ) : '';
	return '' !== $file && 0 === strpos( $file, $prefix );
}

/** Preload links for a field photo (LCP), matching psb_pf_picture(). */
function psb_pf_preload( $desktop, $mobile, $sizes, $mobile_media = '(max-width: 47.99rem)' ) {
	$desktop_media = $mobile ? 'not all and ' . $mobile_media : '';
	foreach ( array( array( $desktop, $desktop_media, $sizes ), array( $mobile, $mobile_media, '100vw' ) ) as $p ) {
		if ( ! $p[0] ) {
			continue;
		}
		printf(
			'<link rel="preload" as="image"%s imagesrcset="%s" imagesizes="%s" fetchpriority="high">' . "\n",
			$p[1] ? ' media="' . esc_attr( $p[1] ) . '"' : '',
			esc_attr( (string) wp_get_attachment_image_srcset( $p[0], 'full' ) ),
			esc_attr( $p[2] )
		);
	}
}

/* ------------------------------------------------------------------------------------------------
 * Field builder: compact specs → ACF fields, and the defaults registry.
 *
 * Spec: array( type, name, label, default, extra ). type: tab | text | textarea | wysiwyg | image |
 * gallery | link | url | select | message | repeater | group. For repeater/group, extra['sub'] holds
 * sub specs; a repeater's default is its rows. extra['key'] keeps an existing field key.
 * ---------------------------------------------------------------------------------------------- */

/** Icon choices for select fields (keys of psb_icon()). */
function psb_pf_icon_choices() {
	$labels = array(
		'check'    => 'تیک',
		'calendar' => 'تقویم',
		'seal'     => 'مهر تأیید',
		'report'   => 'گزارش',
		'warranty' => 'گارانتی',
		'iso'      => 'گواهینامه ISO',
		'shield'   => 'سپر (ایمنی)',
		'users'    => 'افراد',
		'target'   => 'هدف',
		'cog'      => 'چرخ‌دنده',
		'eye'      => 'چشم',
		'factory'  => 'کارخانه',
		'bolt'     => 'برق',
		'clock'    => 'ساعت',
		'award'    => 'جایزه',
		'wrench'   => 'آچار',
		'truck'    => 'حمل',
		'helmet'   => 'کلاه ایمنی',
		'flask'    => 'آزمایشگاه',
		'building' => 'ساختمان',
	);
	return array( '' => 'خودکار' ) + $labels;
}

function psb_pf_build( $group_key, $specs, $path_prefix = '', &$defaults = array() ) {
	$fields = array();
	foreach ( $specs as $i => $s ) {
		list( $type, $name, $label ) = array_pad( $s, 3, '' );
		$default = $s[3] ?? '';
		$extra   = $s[4] ?? array();
		$key     = $extra['key'] ?? ( 'field_psbpf_' . ( $name ? $path_prefix . $name : $group_key . '_' . $type . $i ) );
		$field   = array(
			'key'          => $key,
			'label'        => $label,
			'name'         => $name,
			'type'         => $type,
			'instructions' => $extra['help'] ?? '',
		);
		if ( isset( $extra['width'] ) ) {
			$field['wrapper'] = array( 'width' => $extra['width'] );
		}
		switch ( $type ) {
			case 'tab':
				$field['label']     = $name;
				$field['name']      = '';
				$field['placement'] = 'top';
				break;
			case 'message':
				$field['name']      = '';
				$field['message']   = $default;
				$field['new_lines'] = 'wpautop';
				$field['psb_note']  = $extra['note'] ?? '';
				break;
			case 'image':
			case 'gallery':
				$field['return_format'] = 'id';
				$field['preview_size']  = 'medium';
				$field['library']       = 'all';
				$field['mime_types']    = 'jpg,jpeg,png,webp,avif';
				break;
			case 'link':
				$field['return_format'] = 'array';
				break;
			case 'textarea':
				$field['rows']          = $extra['rows'] ?? 3;
				$field['new_lines']     = '';
				$field['default_value'] = $default;
				break;
			case 'wysiwyg':
				$field['tabs']          = 'all';
				$field['toolbar']       = 'basic';
				$field['media_upload']  = 0;
				$field['default_value'] = $default;
				break;
			case 'select':
				$field['choices']       = $extra['choices'] ?? array();
				$field['default_value'] = $default;
				$field['allow_null']    = 0;
				$field['ui']            = 0;
				$field['return_format'] = 'value';
				break;
			case 'repeater':
			case 'group':
				$sub_defaults       = array();
				$field['sub_fields'] = psb_pf_build( $group_key, $extra['sub'], $path_prefix . $name . '__', $sub_defaults );
				$field['layout']     = $extra['layout'] ?? ( 'group' === $type ? 'block' : 'table' );
				if ( 'repeater' === $type ) {
					$field['button_label'] = $extra['button'] ?? 'افزودن ردیف';
					$field['collapsed']    = '';
				} else {
					foreach ( $sub_defaults as $sub_name => $sub_default ) {
						$defaults[ $name . '.' . $sub_name ] = $sub_default;
					}
				}
				break;
			case 'true_false':
				$field['ui']            = 1;
				$field['ui_on_text']    = 'بله';
				$field['ui_off_text']   = 'خیر';
				$field['default_value'] = $default;
				break;
			default:
				$field['default_value'] = $default;
		}
		if ( $name && 'group' !== $type ) {
			$defaults[ $name ] = $default;
		}
		$fields[] = $field;
	}
	return $fields;
}

/**
 * Register one page field group.
 *
 * @param string $key      Group key (reuse the DB key to replace that group).
 * @param string $title    Group title.
 * @param array  $location ACF location rules.
 * @param array  $specs    Field specs.
 */
function psb_pf_register( $key, $title, $location, $specs ) {
	$defaults = array();
	$fields   = psb_pf_build( $key, $specs, '', $defaults );
	psb_pf_registry( $defaults );
	$GLOBALS['psb_pf_groups'][ $key ] = $specs;
	$add = function () use ( $key, $title, $location, $fields ) {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		acf_add_local_field_group(
			array(
				'key'                   => $key,
				'title'                 => $title,
				'fields'                => $fields,
				'location'              => $location,
				'position'              => 'acf_after_title',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'active'                => true,
			)
		);
	};
	if ( did_action( 'acf/init' ) ) {
		$add();
	} else {
		add_action( 'acf/init', $add );
	}
}

/** Pages the groups apply to: [ group key => page IDs ]. */
function psb_pf_pages() {
	$about = get_posts(
		array(
			'post_type'   => 'page',
			'post_status' => 'any',
			'numberposts' => 5,
			'fields'      => 'ids',
			'meta_key'    => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => 'about-us.php', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return array(
		'group_63c2710475bbb'     => array_filter( array( (int) get_option( 'page_on_front' ) ) ),
		'group_about_page_fields' => $about,
	);
}

/**
 * Seed repeaters once with their default rows, so the manager edits real rows (not empty lists).
 * Only fields that were never saved are touched.
 */
const PSB_PF_SEED_VERSION = 2; // v2: home_flow (production steps).

/** Seed never-saved repeaters on the content pages with their default rows. */
function psb_pf_seed() {
	if ( ! function_exists( 'update_field' ) ) {
		return;
	}
	$defaults = psb_pf_registry();
	foreach ( psb_pf_pages() as $group => $ids ) {
		foreach ( (array) ( $GLOBALS['psb_pf_groups'][ $group ] ?? array() ) as $s ) {
			if ( 'repeater' !== $s[0] || empty( $defaults[ $s[1] ] ) || ! is_array( $defaults[ $s[1] ] ) ) {
				continue;
			}
			foreach ( $ids as $id ) {
				if ( metadata_exists( 'post', $id, $s[1] ) ) {
					continue;
				}
				$rows = $defaults[ $s[1] ];
				foreach ( $rows as &$row ) {
					if ( isset( $row['link'] ) && function_exists( 'psb_pf_url' ) ) {
						$row['link'] = psb_pf_url( $row['link'] ); // archive:/doc: → real URL.
					}
				}
				unset( $row );
				update_field( $s[4]['key'] ?? 'field_psbpf_' . $s[1], $rows, $id );
			}
		}
	}
}
add_action(
	'admin_init',
	function () {
		if ( (int) get_option( 'psb_pf_seed' ) >= PSB_PF_SEED_VERSION || ! current_user_can( 'edit_pages' ) ) {
			return;
		}
		psb_pf_seed();
		update_option( 'psb_pf_seed', PSB_PF_SEED_VERSION, false );
	}
);

/**
 * Help box at the top of each page's fields: a clear table of the automatic tokens with their
 * current values (built when the edit screen renders, so the counts are live).
 */
function psb_pf_tokens_help() {
	$rows = array(
		'{brand}'     => 'نام شرکت',
		'{years}'     => 'تعداد سال‌های فعالیت',
		'{founded}'   => 'سال تأسیس',
		'{year}'      => 'سال جاری (شمسی)',
		'{warranty}'  => 'مدت گارانتی (ماه)',
		'{service}'   => 'سال‌های خدمات پس از فروش',
		'{approvals}' => 'تعداد تأییدیه‌های منتشرشده',
		'{tests}'     => 'تعداد گزارش‌های آزمون منتشرشده',
	);
	$vals = psb_pf_tokens();
	$td   = 'style="padding:6px 10px;border:1px solid #D9DEE5;text-align:right"';
	$html = '<div dir="rtl" style="text-align:right;line-height:1.9"><p><strong>نکته‌های این صفحه</strong></p><ul style="list-style:disc;padding-right:20px;margin:0 0 12px">'
		. '<li>اگر یک فیلد متنی را خالی کنید، آن متن از صفحه حذف می‌شود.</li>'
		. '<li>اگر یک فیلد عکس خالی بماند، عکس پیش‌فرض قالب نمایش داده می‌شود.</li>'
		. '<li>مقدار عبارت‌های زیر (نام شرکت، سال تأسیس، گارانتی، خدمات پس از فروش) در منوی «تنظیمات سایت» ← «اطلاعات شرکت» تغییر می‌کند.</li></ul>'
		. '<p><strong>عبارت‌های خودکار</strong>: هر عبارت ستون اول را (با آکولادها) داخل متن بنویسید؛ در سایت مقدار ستون سوم جای آن نمایش داده می‌شود و هر سال یا با انتشار سند جدید خودکار به‌روز می‌شود.</p>'
		. '<table style="border-collapse:collapse;margin:6px 0 10px"><thead><tr>'
		. '<th ' . $td . '>عبارت</th><th ' . $td . '>معنی</th><th ' . $td . '>مقدار فعلی</th></tr></thead><tbody>';
	foreach ( $rows as $token => $label ) {
		$html .= '<tr><td ' . $td . '><code dir="ltr" style="unicode-bidi:isolate">' . esc_html( $token ) . '</code></td><td ' . $td . '>' . esc_html( $label ) . '</td><td ' . $td . '>' . esc_html( $vals[ $token ] ?? '' ) . '</td></tr>';
	}
	$html .= '</tbody></table>';
	$html .= '<p><strong>مثال</strong></p><table style="border-collapse:collapse;margin:0 0 6px"><tbody>'
		. '<tr><td ' . $td . '>متنی که در فیلد می‌نویسید</td><td ' . $td . '><bdi>نزدیک به <code dir="ltr" style="unicode-bidi:isolate">{years}</code> سال تابلوسازی</bdi></td></tr>'
		. '<tr><td ' . $td . '>آنچه در سایت دیده می‌شود</td><td ' . $td . '>نزدیک به ' . esc_html( $vals['{years}'] ?? '' ) . ' سال تابلوسازی</td></tr>'
		. '</tbody></table></div>';
	return $html;
}
/** The field boxes are Persian: lay them out right-to-left even when the admin language is LTR. */
add_action(
	'admin_head',
	function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ( 'page' !== $screen->post_type && false === strpos( (string) $screen->id, 'site-setting' ) ) ) {
			return;
		}
		$b   = array( '#acf-group_63c2710475bbb', '#acf-group_about_page_fields', '#acf-group_6a759dbaade00' );
		$sel = function ( $suffix ) use ( $b ) {
			return implode( ',', array_map( fn( $g ) => $g . $suffix, $b ) );
		};
		echo '<style>'
			. $sel( '' ) . '{direction:rtl;text-align:right}'
			. $sel( ' .acf-field[data-width]' ) . '{float:right;border-left:0!important}'
			. $sel( ' .acf-field[data-width]+.acf-field[data-width]' ) . '{border-right:1px solid #eee}'
			. $sel( ' .acf-tab-wrap .acf-tab-group li' ) . '{float:right;margin:0 0 0 5px}'
			. $sel( ' .acf-table th' ) . ',' . $sel( ' .acf-table td' ) . '{text-align:right}'
			. $sel( ' .acf-actions' ) . '{text-align:left}'
			. $sel( ' .acf-switch' ) . '{direction:ltr}'
			. $sel( ' select' ) . '{background-position:left 8px top 55%;padding:0 8px 0 24px}'
			. $sel( ' input[type=url]' ) . ',' . $sel( ' input[type=email]' ) . ',' . $sel( ' textarea[name*="scripts"]' ) . '{direction:ltr;text-align:left}'
			. '</style>';
	}
);

add_filter(
	'acf/prepare_field/type=message',
	function ( $field ) {
		if ( 0 === strpos( (string) $field['key'], 'field_psbpf_help' ) ) {
			$field['message']   = psb_pf_tokens_help() . ( $field['psb_note'] ?? '' );
			$field['new_lines'] = '';
		}
		return $field;
	}
);

require_once __DIR__ . '/home.php';
require_once __DIR__ . '/about.php';
require_once __DIR__ . '/site-settings.php';
