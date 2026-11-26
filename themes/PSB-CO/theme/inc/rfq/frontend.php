<?php
/**
 * RFQ — front end: assets, JS config, shortcodes, field renderer, product → type mapping.
 *
 * The page output contains no nonce or per-user data, so it is safe for the page cache.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Theme-relative URL of an RFQ asset with filemtime cache busting. */
function psb_rfq_asset( $rel ) {
	$path = get_template_directory() . '/' . $rel;
	return array( get_template_directory_uri() . '/' . $rel, PSB_RFQ_VERSION . ( file_exists( $path ) ? '.' . filemtime( $path ) : '' ) );
}

/**
 * Load CSS + JS (+ config). Safe to call from inside templates: if <head> is already printed,
 * the stylesheet link is output in place (valid in <body>), so there is no flash of unstyled form.
 */
function psb_rfq_enqueue() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	list( $css, $cver ) = psb_rfq_asset( 'css/rfq.css' );
	// rfq.min.js = esbuild-minified copy of rfq.js (see RFQ.md); falls back to the readable source.
	$min                = file_exists( get_template_directory() . '/js/rfq.min.js' ) && ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
	list( $js, $jver )  = psb_rfq_asset( $min ? 'js/rfq.min.js' : 'js/rfq.js' );
	if ( did_action( 'wp_head' ) && ! doing_action( 'wp_enqueue_scripts' ) ) {
		echo '<link rel="stylesheet" id="psb-rfq-css" href="' . esc_url( add_query_arg( 'ver', $cver, $css ) ) . '">' . "\n"; // phpcs:ignore WordPress.WP.EnqueuedResources
	} else {
		wp_enqueue_style( 'psb-rfq', $css, array(), $cver );
	}
	wp_enqueue_script( 'psb-rfq', $js, array(), $jver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script( 'psb-rfq', 'window.psbRfq=' . wp_json_encode( psb_rfq_public_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ';', 'before' );
}

/** Is this the quote page (template chosen, or the page with slug request-quote and no other template)? */
function psb_rfq_is_quote_page() {
	if ( is_page_template( 'page-request-quote.php' ) ) {
		return true;
	}
	if ( ! is_page( 'request-quote' ) ) {
		return false;
	}
	$tpl = get_page_template_slug( get_queried_object_id() );
	return '' === $tpl || 'default' === $tpl;
}

// The page /request-quote/ uses the RFQ template even if «قالب» was left on default.
add_filter(
	'template_include',
	static function ( $template ) {
		if ( psb_rfq_is_quote_page() && ! is_page_template( 'page-request-quote.php' ) ) {
			$t = locate_template( 'page-request-quote.php' );
			return $t ? $t : $template;
		}
		return $template;
	},
	20
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( psb_rfq_is_quote_page() ) {
			psb_rfq_enqueue();
		}
	},
	20
);

/** Persian label for groups shown as card section headings. */
function psb_rfq_types_by_group() {
	$out = array();
	foreach ( psb_rfq_types() as $k => $t ) {
		$out[ $t['group'] ][ $k ] = $t;
	}
	$order = array_keys( psb_rfq_groups() );
	uksort(
		$out,
		static function ( $a, $b ) use ( $order ) {
			return array_search( $a, $order, true ) - array_search( $b, $order, true );
		}
	);
	return $out;
}

/** key => label pairs as [[k,l],…] (keeps order in JSON). */
function psb_rfq_pairs( $assoc ) {
	$out = array();
	foreach ( (array) $assoc as $k => $l ) {
		$out[] = array( (string) $k, (string) $l );
	}
	return $out;
}

/** Config consumed by js/rfq.js. */
function psb_rfq_public_config() {
	$s     = psb_rfq_settings();
	$types = array();
	foreach ( psb_rfq_types() as $k => $t ) {
		$types[ $k ] = array( 'l' => $t['label'], 'g' => $t['group'] );
	}
	$specs = array();
	foreach ( psb_rfq_spec_defs() as $k => $def ) {
		$vis = psb_rfq_vis( 'spec.' . $k );
		if ( 'hide' === $vis ) {
			continue;
		}
		$g = array();
		foreach ( array_keys( psb_rfq_groups() ) as $group ) {
			$opts = psb_rfq_spec_options( $def, $group );
			if ( null !== $opts ) {
				$g[ $group ] = psb_rfq_pairs( $opts );
			}
		}
		$specs[] = array(
			'k' => $k,
			'l' => $def['label'],
			't' => $def['type'],
			'u' => isset( $def['unit'] ) ? $def['unit'] : '',
			'p' => isset( $def['placeholder'] ) ? $def['placeholder'] : '',
			'r' => 'required' === $vis,
			'g' => $g,
		);
	}
	$fields = array();
	foreach ( psb_rfq_field_defs() as $k => $def ) {
		$fields[ $k ] = array(
			'l' => $def['label'],
			'v' => psb_rfq_vis( $k ),
			'o' => in_array( $def['type'], array( 'select', 'radio' ), true ) ? psb_rfq_field_options( $def ) : null,
		);
	}
	$steps = array();
	foreach ( psb_rfq_steps() as $k => $l ) {
		$steps[] = array( $k, $l );
	}
	$wa = psb_rfq_intl( $s['sales_whatsapp'] );
	return array(
		'rest'     => esc_url_raw( rest_url( 'psb/v1/rfq' ) ),
		'steps'    => $steps,
		'types'    => $types,
		'specs'    => $specs,
		'fields'   => $fields,
		'products' => psb_rfq_products_map(),
		'files'    => array(
			'max'   => max( 1, (int) $s['max_files'] ),
			'bytes' => psb_rfq_max_bytes(),
			'ext'   => psb_rfq_allowed_ext(),
		),
		'sales'    => array(
			'tel' => psb_rfq_tel( $s['sales_phone'] ),
			'telText' => psb_rfq_fa( $s['sales_phone'] ),
			'wa'  => $wa ? 'https://wa.me/' . $wa : '',
		),
		'unknown'  => psb_rfq_unknown_label(),
	);
}

/**
 * Line-item type for a product (meta `_psb_rfq_type`, else keywords in the title / categories).
 *
 * @param int|WP_Post|string $product ID, post or slug.
 */
function psb_rfq_type_for_product( $product ) {
	if ( is_string( $product ) && ! is_numeric( $product ) ) {
		$product = get_page_by_path( sanitize_title( $product ), OBJECT, 'product' );
	}
	$post = get_post( $product );
	if ( ! $post ) {
		return '';
	}
	$types = psb_rfq_types();
	$meta  = sanitize_key( (string) get_post_meta( $post->ID, '_psb_rfq_type', true ) );
	if ( ! $meta ) {
		$meta = sanitize_key( (string) get_post_meta( $post->ID, 'psb_rfq_type', true ) );
	}
	if ( $meta && isset( $types[ $meta ] ) ) {
		return $meta;
	}
	$hay = $post->post_title . ' ' . $post->post_name;
	if ( taxonomy_exists( 'product_cat' ) ) {
		$terms = get_the_terms( $post->ID, 'product_cat' );
		if ( is_array( $terms ) ) {
			$hay .= ' ' . implode( ' ', wp_list_pluck( $terms, 'name' ) );
		}
	}
	$map  = array(
		'ایزوله'     => 'hospital',
		'بیمارستان'  => 'hospital',
		'کمپکت'      => 'rmu',
		'RMU'        => 'rmu',
		'کنتور'      => 'meter',
		'اندازه'     => 'meter',
		'فرمان'      => 'control',
		'کنترل'      => 'control',
		'خازن'       => 'lv_capacitor',
		'MCC'        => 'lv_mcc',
		'ATS'        => 'lv_ats',
		'چنج'        => 'lv_ats',
		'روشنایی'    => 'lv_lighting',
		'کشویی'      => 'mv_withdrawable',
		'فشار متوسط' => 'mv_fixed',
		'فشارمتوسط'  => 'mv_fixed',
		'فشار ضعیف'  => 'lv_mdb',
		'فشارضعیف'   => 'lv_mdb',
	);
	$type = '';
	foreach ( $map as $needle => $key ) {
		if ( false !== mb_stripos( $hay, $needle ) && isset( $types[ $key ] ) ) {
			$type = $key;
			break;
		}
	}
	return (string) apply_filters( 'psb_rfq_type_for_product', $type, $post );
}

/** slug / id → array( type, title ) for deep links (?product=…), cached 12h. */
function psb_rfq_products_map() {
	if ( ! post_type_exists( 'product' ) ) {
		return array();
	}
	$map = get_transient( 'psb_rfq_products_map' );
	if ( is_array( $map ) ) {
		return $map;
	}
	$map = array();
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	foreach ( $ids as $id ) {
		$entry = array( psb_rfq_type_for_product( $id ), html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ) );
		$map[ (string) $id ] = $entry;
		$map[ rawurldecode( (string) get_post_field( 'post_name', $id ) ) ] = $entry;
	}
	set_transient( 'psb_rfq_products_map', $map, 12 * HOUR_IN_SECONDS );
	return $map;
}
add_action(
	'save_post_product',
	static function () {
		delete_transient( 'psb_rfq_products_map' );
	}
);
add_action(
	'update_option_psb_rfq_settings',
	static function () {
		delete_transient( 'psb_rfq_products_map' );
	}
);

/**
 * Render a project/contact field (server-side; the JS reads `name` = "project.city" etc.).
 *
 * @param string $key  Field key.
 * @param string $uid  Unique id prefix.
 */
function psb_rfq_render_field( $key, $uid ) {
	$defs = psb_rfq_field_defs();
	$def  = $defs[ $key ];
	$vis  = psb_rfq_vis( $key );
	if ( 'hide' === $vis ) {
		return;
	}
	$id    = $uid . '-' . str_replace( '.', '-', $key );
	$req   = 'required' === $vis;
	$err   = $id . '-err';
	// Label output only (bidi): isolate Latin runs, and do not repeat «(اختیاری)» when the label already says it.
	$ltext = $req ? $def['label'] : trim( str_replace( '(اختیاری)', '', $def['label'] ) );
	$lout  = function_exists( 'psb_rfq_bidi' ) ? 'psb_rfq_bidi' : 'esc_html';
	$label = $lout( $ltext ) . ( $req ? ' <span class="rfq-req" aria-hidden="true">*</span>' : ' <span class="rfq-opt">(اختیاری)</span>' );
	$wide  = in_array( $def['type'], array( 'textarea', 'radio' ), true ) || in_array( $key, array( 'project.name', 'project.approval_note' ), true );
	$attrs = ' name="' . esc_attr( $key ) . '"' . ( $req ? ' aria-required="true"' : '' ) . ' aria-describedby="' . esc_attr( $err ) . '"';
	echo '<div class="rfq-field' . ( $wide ? ' rfq-field--wide' : '' ) . '" data-f="' . esc_attr( $key ) . '">';
	switch ( $def['type'] ) {
		case 'radio':
			echo '<fieldset class="rfq-fs"><legend class="rfq-label">' . $label . '</legend><div class="rfq-chips">'; // phpcs:ignore -- escaped above.
			foreach ( psb_rfq_field_options( $def ) as $v => $l ) {
				echo '<label class="rfq-chip"><input type="radio"' . $attrs . ' value="' . esc_attr( $v ) . '"><span>' . $lout( $l ) . '</span></label>'; // phpcs:ignore
			}
			echo '</div></fieldset>';
			break;
		case 'select':
			echo '<label class="rfq-label" for="' . esc_attr( $id ) . '">' . $label . '</label><select class="rfq-input" id="' . esc_attr( $id ) . '"' . $attrs . '><option value="">انتخاب کنید</option>'; // phpcs:ignore
			foreach ( psb_rfq_field_options( $def ) as $v => $l ) {
				echo '<option value="' . esc_attr( $v ) . '">' . ( function_exists( 'psb_rfq_bidi_plain' ) ? psb_rfq_bidi_plain( $l ) : esc_html( $l ) ) . '</option>';
			}
			echo '</select>';
			break;
		case 'textarea':
			echo '<label class="rfq-label" for="' . esc_attr( $id ) . '">' . $label . '</label><textarea class="rfq-input" rows="4" id="' . esc_attr( $id ) . '"' . $attrs . ' maxlength="' . (int) ( isset( $def['max'] ) ? $def['max'] : 2000 ) . '" placeholder="' . esc_attr( isset( $def['placeholder'] ) ? $def['placeholder'] : '' ) . '"></textarea>'; // phpcs:ignore
			break;
		default:
			$type = in_array( $def['type'], array( 'tel', 'email' ), true ) ? $def['type'] : 'text';
			$list = '';
			if ( ! empty( $def['datalist'] ) ) {
				$list = $id . '-list';
				echo '<datalist id="' . esc_attr( $list ) . '">';
				foreach ( $def['datalist'] as $o ) {
					echo '<option value="' . esc_attr( $o ) . '"></option>';
				}
				echo '</datalist>';
			}
			echo '<label class="rfq-label" for="' . esc_attr( $id ) . '">' . $label . '</label>'; // phpcs:ignore
			printf(
				'<input class="rfq-input%1$s" type="%2$s" id="%3$s"%4$s%5$s%6$s%7$s%8$s%9$s>',
				in_array( $type, array( 'tel', 'email' ), true ) ? ' rfq-ltr' : '',
				esc_attr( $type ),
				esc_attr( $id ),
				$attrs, // phpcs:ignore -- built from escaped parts.
				! empty( $def['autocomplete'] ) ? ' autocomplete="' . esc_attr( $def['autocomplete'] ) . '"' : '',
				'tel' === $type ? ' inputmode="tel" dir="ltr"' : ( 'email' === $type ? ' inputmode="email" dir="ltr"' : '' ),
				! empty( $def['placeholder'] ) ? ' placeholder="' . esc_attr( $def['placeholder'] ) . '"' : '',
				$list ? ' list="' . esc_attr( $list ) . '"' : '',
				' maxlength="' . (int) ( isset( $def['max'] ) ? $def['max'] : 40 ) . '"'
			);
	}
	echo '<p class="rfq-err" id="' . esc_attr( $err ) . '" aria-live="polite"></p></div>';
}

/* -------------------------------------------------------------------------
 * Shortcodes + template helpers
 * ---------------------------------------------------------------------- */

/** Full wizard markup. */
function psb_rfq_wizard( $args = array() ) {
	ob_start();
	get_template_part( 'template-parts/rfq/wizard', null, $args );
	return ob_get_clean();
}

/** Quick form markup. $args: product (ID|slug|WP_Post), title, text, class. */
function psb_rfq_quick_form( $args = array() ) {
	ob_start();
	get_template_part( 'template-parts/rfq/quick-form', null, $args );
	return ob_get_clean();
}

add_shortcode(
	'psb_rfq',
	static function () {
		return psb_rfq_wizard();
	}
);
add_shortcode(
	'psb_rfq_quick',
	static function ( $atts ) {
		$atts = shortcode_atts( array( 'product' => '', 'title' => '', 'text' => '' ), $atts, 'psb_rfq_quick' );
		if ( '' === $atts['product'] && is_singular( 'product' ) ) {
			$atts['product'] = get_the_ID();
		}
		return psb_rfq_quick_form( $atts );
	}
);
