<?php
/**
 * Helpers for inner templates: documents, credentials hub, catalogue, articles.
 * Loaded from inc/template-functions.php. Everything is null-safe (ACF / WooCommerce optional).
 *
 * @package bluebox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalise an ACF file value (array | attachment ID | URL) into a document descriptor.
 *
 * @param mixed $file ACF file field value.
 * @return array|null id, url, mime, ext, is_pdf, is_image, size (human), title
 */
function psb_doc( $file ) {
	if ( empty( $file ) ) {
		return null;
	}
	$id  = 0;
	$url = '';
	if ( is_array( $file ) ) {
		$id  = (int) ( $file['ID'] ?? ( $file['id'] ?? 0 ) );
		$url = (string) ( $file['url'] ?? '' );
	} elseif ( is_numeric( $file ) ) {
		$id = (int) $file;
	} elseif ( is_string( $file ) ) {
		$url = $file;
	}
	if ( $id && get_post( $id ) ) {
		$url = (string) wp_get_attachment_url( $id );
	} else {
		$id = $url ? (int) attachment_url_to_postid( $url ) : 0;
	}
	if ( ! $url ) {
		return null;
	}
	$mime  = $id ? (string) get_post_mime_type( $id ) : '';
	$ext   = strtolower( (string) pathinfo( wp_parse_url( $url, PHP_URL_PATH ) ?? '', PATHINFO_EXTENSION ) );
	$bytes = $id ? (int) @filesize( (string) get_attached_file( $id ) ) : 0; // phpcs:ignore
	if ( ! $bytes && is_array( $file ) ) {
		$bytes = (int) ( $file['filesize'] ?? 0 );
	}
	return array(
		'id'       => $id,
		'url'      => $url,
		'mime'     => $mime,
		'ext'      => $ext,
		'is_pdf'   => 'pdf' === $ext || 'application/pdf' === $mime,
		'is_image' => 0 === strpos( $mime, 'image/' ) || in_array( $ext, array( 'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif' ), true ),
		'size'     => $bytes ? psb_fa_num( size_format( $bytes, $bytes > 1048576 ? 1 : 0 ) ) : '',
		'title'    => $id ? get_the_title( $id ) : basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ),
	);
}

/**
 * Image attachment ID that previews a document post: the document itself when it is an image,
 * otherwise the featured image.
 */
function psb_doc_preview_id( $doc, $post_id, $allow_thumb = true ) {
	if ( $doc && $doc['is_image'] && $doc['id'] ) {
		return (int) $doc['id'];
	}
	// Confirmations use the issuer logo as featured image: never show a logo as a document preview.
	if ( ! $allow_thumb || 'confirmation' === get_post_type( $post_id ) ) {
		return 0;
	}
	return (int) get_post_thumbnail_id( $post_id );
}

/**
 * Document date for display (spec §2 numerals policy). Gregorian d/m/Y dates from foreign labs are
 * written in Persian order and digits («۲۴ اوت ۲۰۱۴»); Jalali dates/text get Persian digits.
 * Plain text (safe for esc_html contexts such as the page-hero lead).
 */
function psb_doc_date_text( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}
	if ( preg_match( '#(\d{1,2})[/.-](\d{1,2})[/.-]((?:19|20)\d{2})#', $raw, $m ) && checkdate( (int) $m[2], (int) $m[1], (int) $m[3] ) ) {
		$months = array( 'ژانویه', 'فوریه', 'مارس', 'آوریل', 'مه', 'ژوئن', 'ژوئیه', 'اوت', 'سپتامبر', 'اکتبر', 'نوامبر', 'دسامبر' );
		$label  = trim( str_replace( $m[0], '', $raw ) );
		return trim( $label . ' ' . psb_fa_num( (int) $m[1] . ' ' . $months[ (int) $m[2] - 1 ] . ' ' . $m[3] ) );
	}
	return psb_fa_num( $raw );
}

/** HTML version of psb_doc_date_text() (escaped). */
function psb_doc_date( $raw ) {
	return esc_html( psb_doc_date_text( $raw ) );
}

/**
 * Escape a title and wrap Latin tokens that contain digits (IEC 61439, IP54, SYStem6, 20kV) in .lat
 * so Peyda's Persian digits never corrupt codes. Returns HTML.
 */
function psb_lat_html( $text ) {
	if ( function_exists( 'psb_lat' ) ) {
		return psb_lat( $text ); // Design-system helper (inc/design-helpers.php): one behaviour site-wide.
	}
	$html = esc_html( (string) $text );
	return (string) preg_replace_callback(
		'/(?<![\p{L}\d])((?:[A-Za-z]+[\-\/.]?\s?)?[A-Za-z]*\d+[A-Za-z0-9.\-\/]*(?:\s?(?:kV|KV|A|V))?)(?![\p{L}\d])/u',
		function ( $m ) {
			// Only tokens with a Latin letter are codes; bare numbers stay Persian (prose).
			return preg_match( '/[A-Za-z]/', $m[1] ) ? '<span class="lat">' . $m[1] . '</span>' : $m[1];
		},
		$html
	);
}

/** Persian label for a document link. */
function psb_doc_label( $doc ) {
	if ( ! $doc ) {
		return '';
	}
	$type = $doc['is_pdf'] ? 'PDF' : ( $doc['is_image'] ? 'تصویر' : strtoupper( $doc['ext'] ) );
	return trim( $type . ( $doc['size'] ? ' · ' . $doc['size'] : '' ) );
}

/**
 * Standard / rating codes mentioned in a text (IEC 61439-2, ISIRI 3368-1, IP54, ASTM D3359 …).
 *
 * @return string[] unique codes, in order of appearance.
 */
function psb_extract_standards( $text, $limit = 8 ) {
	$text = wp_strip_all_tags( (string) $text );
	$text = strtr( $text, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9' ) );
	if ( false !== stripos( $text, 'IEC' ) ) {
		// "IEC 60529 and 62208" → second code is IEC too.
		$text = preg_replace( '/(\band\b|&|,|،|;)\s*(6\d{4})\b/iu', '$1 IEC $2', $text );
	}
	preg_match_all( '/\b(?:(?:IEC|ISIRI|ISO|ASTM|EN)\s?(?:[A-Z]\s?)?\d{2,5}(?:-\d{1,3}){0,2}|IP\s?[0-6X][0-9X])\b/iu', $text, $m );
	$out = array();
	foreach ( $m[0] as $code ) {
		$code = preg_replace( '/\s+/', ' ', strtoupper( $code ) );
		$code = preg_replace( '/^IP\s/', 'IP', $code );
		$key  = str_replace( ' ', '', $code );
		if ( ! isset( $out[ $key ] ) ) {
			$out[ $key ] = $code;
		}
	}
	return array_slice( array_values( $out ), 0, $limit );
}

/** Text normaliser used for keyword matching (drops spaces / ZWNJ, unifies ی/ي and ک/ك). */
function psb_norm( $text ) {
	$text = mb_strtolower( wp_strip_all_tags( (string) $text ) );
	$text = str_replace( array( 'ي', 'ك', "\u{200C}", ' ', '-', '_' ), array( 'ی', 'ک', '', '', '', '' ), $text );
	return $text;
}

/* -------------------------------------------------------------------------
 * Credentials hub
 * ---------------------------------------------------------------------- */

/**
 * Sections of the credentials hub (only registered post types are returned).
 *
 * @return array[] key => label, short, icon, url, count
 */
function psb_cred_sections() {
	$defs = array(
		'confirmation'    => array( 'تأییدیه‌ها و وندورلیست', 'تأییدیه‌ها', 'award' ),
		'type-test'       => array( 'گزارش‌های تایپ‌تست', 'تایپ‌تست', 'flask' ),
		'certificates'    => array( 'گواهینامه‌ها', 'گواهینامه‌ها', 'shield' ),
		'customer_letter' => array( 'رضایت‌نامه‌ها', 'رضایت‌نامه‌ها', 'doc' ),
	);
	$out  = array();
	foreach ( $defs as $type => $def ) {
		if ( ! post_type_exists( $type ) ) {
			continue;
		}
		$counts       = wp_count_posts( $type );
		$out[ $type ] = array(
			'label' => $def[0],
			'short' => $def[1],
			'icon'  => $def[2],
			'url'   => psb_archive_url( $type ),
			'count' => (int) ( $counts->publish ?? 0 ),
		);
	}
	return $out;
}

/** Testing laboratory short name, from the issuing_body text. */
function psb_lab_name( $issuer ) {
	$issuer = (string) $issuer;
	if ( false !== stripos( $issuer, 'EPIL' ) ) {
		return 'EPIL';
	}
	if ( false !== stripos( $issuer, 'SAREL' ) || false !== mb_strpos( $issuer, 'سارل' ) ) {
		return 'SAREL';
	}
	return '';
}

/**
 * Data rows of a type-test post. Reads the fields that really exist on the live site
 * (issuing_body, standard_number, validity_date, certificate_file / type_test_file) and, if
 * filled in the future, tested_equipment / test_parameters.
 *
 * @return array rows (label => value), standards (string[]), doc (array|null), issuer, date
 */
function psb_type_test_data( $post_id ) {
	$issuer = trim( (string) psb_field( 'issuing_body', '', $post_id ) );
	$std    = trim( (string) psb_field( 'standard_number', '', $post_id ) );
	$date   = trim( (string) psb_field( 'validity_date', '', $post_id ) );
	$equip  = trim( (string) psb_field( 'tested_equipment', '', $post_id ) );
	$params = trim( (string) psb_field( 'test_parameters', '', $post_id ) );
	$doc    = psb_doc( psb_field( 'type_test_file', null, $post_id ) );
	if ( ! $doc ) {
		$doc = psb_doc( psb_field( 'certificate_file', null, $post_id ) );
	}
	$standards = psb_extract_standards( $std );
	$rows      = array();
	if ( $issuer ) {
		$rows['مرجع صدور / آزمایشگاه'] = $issuer;
	}
	if ( $equip ) {
		$rows['تجهیز آزمون‌شده'] = $equip;
	}
	if ( $params ) {
		$rows['پارامترهای آزمون'] = $params;
	}
	if ( $std && ! $standards ) {
		$rows['استاندارد مرجع'] = $std; // Shown as chips when codes could be extracted.
	}
	if ( $date ) {
		$rows['تاریخ سند'] = $date;
	}
	return array(
		'issuer'    => $issuer,
		'lab'       => psb_lab_name( $issuer . ' ' . get_the_title( $post_id ) ),
		'standards' => $standards,
		'date'      => $date,
		'doc'       => $doc,
		'rows'      => $rows,
	);
}

/**
 * Data of a confirmation (approval / vendor-list) post.
 *
 * @return array issuer, number, scope, doc, rows
 */
function psb_confirmation_data( $post_id ) {
	$issuer = psb_brand_fix( trim( (string) psb_field( 'issuing_organization', '', $post_id ) ) );
	$number = trim( (string) psb_field( 'letter_number', '', $post_id ) );
	$scope  = trim( (string) psb_field( 'approval_scope', '', $post_id ) );
	$doc    = psb_doc( psb_field( 'letter_file', null, $post_id ) );
	$rows   = array();
	if ( $issuer ) {
		$rows['مرجع صدور'] = $issuer;
	}
	if ( $scope ) {
		$rows['موضوع و دامنه'] = $scope;
	}
	if ( $number ) {
		$rows['شماره و تاریخ سند'] = $number;
	}
	return array(
		'issuer' => $issuer,
		'number' => $number,
		'scope'  => $scope,
		'doc'    => $doc,
		'rows'   => $rows,
	);
}

/** Data of a certificates post (ISO 9001 and future items). */
function psb_certificate_data( $post_id ) {
	$issuer = trim( (string) psb_field( 'issuing_body', '', $post_id ) );
	$number = trim( (string) psb_field( 'standard_number', '', $post_id ) );
	$date   = trim( (string) psb_field( 'validity_date', '', $post_id ) );
	$doc    = psb_doc( psb_field( 'certificate_file', null, $post_id ) );
	$rows   = array();
	if ( $issuer ) {
		$rows['مرجع صدور'] = $issuer;
	}
	if ( $number ) {
		$rows['استاندارد / شماره'] = $number;
	}
	if ( $date ) {
		$rows['تاریخ سند'] = $date;
	}
	return array(
		'issuer'    => $issuer,
		'standards' => psb_extract_standards( $number . ' ' . get_the_title( $post_id ) ),
		'doc'       => $doc,
		'rows'      => $rows,
	);
}

/**
 * Credentials (type-test + confirmation posts) related to a product or project.
 * Order: ACF relationship "related_credentials" if it exists, then keyword matching on titles.
 *
 * @param int    $post_id Product / portfolio ID.
 * @param string $text    Text that describes the item (title + category names).
 * @return WP_Post[]
 */
function psb_related_credentials( $post_id, $text = '' ) {
	$manual = psb_field( 'related_credentials', array(), $post_id );
	if ( is_array( $manual ) && $manual ) {
		return array_values( array_filter( array_map( 'get_post', $manual ) ) );
	}
	$text = psb_norm( $text ? $text : get_the_title( $post_id ) );
	$map  = array(
		'فشارضعیف'     => array( 'فشارضعیف', 'ip54', 'ip65', 'ایزوله', 'کنتوری', 'اندازهگیری', 'توانیر' ),
		'فشارمتوسط'    => array( 'فشارمتوسط', 'کمپکت', 'کمکپت', 'system6', 'کیلوولت', 'توانیر' ),
		'فرمانوکنترل'  => array( 'توانیر' ),
		'کنترل'        => array( 'توانیر' ),
		'کنتوری'       => array( 'کنتوری', 'اندازهگیری' ),
		'ایزوله'       => array( 'ایزوله' ),
		'بیمارستان'    => array( 'ایزوله' ),
		'کمپکت'        => array( 'کمپکت', 'کمکپت' ),
	);
	$keys = array();
	foreach ( $map as $needle => $words ) {
		if ( false !== mb_strpos( $text, $needle ) ) {
			$keys = array_merge( $keys, $words );
		}
	}
	$keys = array_unique( (array) apply_filters( 'psb_related_credential_keywords', $keys, $post_id ) );
	if ( ! $keys ) {
		return array();
	}
	$types = array_values( array_filter( array( 'type-test', 'confirmation' ), 'post_type_exists' ) );
	$posts = get_posts(
		array(
			'post_type'        => $types,
			'post_status'      => 'publish',
			'numberposts'      => 30,
			'suppress_filters' => false,
		)
	);
	$out   = array();
	foreach ( $posts as $p ) {
		$title = psb_norm( $p->post_title );
		foreach ( $keys as $k ) {
			if ( false !== mb_strpos( $title, $k ) ) {
				$out[] = $p;
				break;
			}
		}
	}
	// Type tests first, then approvals.
	usort(
		$out,
		function ( $a, $b ) {
			return strcmp( $b->post_type, $a->post_type );
		}
	);
	return array_slice( $out, 0, 6 );
}

/* -------------------------------------------------------------------------
 * Articles
 * ---------------------------------------------------------------------- */

/** Estimated reading time in minutes (Persian text, ~180 words/min). */
function psb_reading_minutes( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}
	$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
	$words = preg_split( '/[\s\x{200C}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
	return max( 1, (int) ceil( count( (array) $words ) / 180 ) );
}

/** Jalali date of a post, Persian digits ("۱۲ آبان ۱۴۰۵"). */
function psb_post_date( $post = null, $format = 'd F Y' ) {
	$ts = get_post_time( 'U', false, $post );
	if ( ! $ts ) {
		return '';
	}
	$date = function_exists( 'shamsi_date' ) ? shamsi_date( $format, $ts ) : wp_date( 'Y/m/d', $ts );
	return psb_fa_num( ltrim( $date, '0' ) );
}

/**
 * Table of contents (h2 + h3) for HTML that already went through auto_id_headings().
 *
 * @return array[] each: level, id, text
 */
function psb_toc_items( $html ) {
	preg_match_all( '/<h([23])[^>]*\sid="([^"]+)"[^>]*>(.*?)<\/h\1>/is', (string) $html, $m, PREG_SET_ORDER );
	$out = array();
	foreach ( $m as $row ) {
		$text = trim( wp_strip_all_tags( $row[3] ) );
		if ( '' !== $text ) {
			$out[] = array(
				'level' => (int) $row[1],
				'id'    => $row[2],
				'text'  => $text,
			);
		}
	}
	return $out;
}

/**
 * Pagination for archives (uses the global query unless one is passed).
 *
 * @param WP_Query|null $query Custom query.
 */
function psb_pagination( $query = null ) {
	global $wp_query;
	$q     = $query ? $query : $wp_query;
	$total = (int) ( $q->max_num_pages ?? 1 );
	if ( $total < 2 ) {
		return;
	}
	$links = paginate_links(
		array(
			'total'     => $total,
			'current'   => max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ),
			'type'      => 'array',
			'prev_text' => '<span class="sr-only">صفحه قبل</span>' . psb_icon( 'chevron', 'size-4 rotate-180' ),
			'next_text' => '<span class="sr-only">صفحه بعد</span>' . psb_icon( 'chevron', 'size-4' ),
		)
	);
	if ( ! $links ) {
		return;
	}
	echo '<nav class="mt-12" aria-label="صفحه‌بندی"><ul class="flex flex-wrap items-center justify-center gap-2">';
	foreach ( $links as $link ) {
		$link = preg_replace_callback(
			'/>(\d+)</',
			function ( $m ) {
				return '>' . psb_fa_num( $m[1] ) . '<';
			},
			$link
		);
		echo '<li class="page-item">' . $link . '</li>'; // phpcs:ignore
	}
	echo '</ul></nav>';
}

/**
 * Plain list of term names of a post (first taxonomy that has terms).
 *
 * @param int      $post_id    Post ID.
 * @param string[] $taxonomies Candidate taxonomies.
 * @return string[]
 */
function psb_term_names( $post_id, $taxonomies ) {
	foreach ( (array) $taxonomies as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = get_the_terms( $post_id, $tax );
		if ( $terms && ! is_wp_error( $terms ) ) {
			return wp_list_pluck( $terms, 'name' );
		}
	}
	return array();
}

/** Value of an ACF taxonomy/text field as plain text. */
function psb_field_text( $name, $post_id ) {
	$v = psb_field( $name, '', $post_id );
	if ( is_array( $v ) && isset( $v['name'] ) && is_string( $v['name'] ) ) {
		return $v['name']; // Single term as array.
	}
	if ( is_array( $v ) ) {
		$names = array();
		foreach ( $v as $item ) {
			if ( is_array( $item ) && isset( $item['name'] ) ) {
				$names[] = (string) $item['name'];
			} elseif ( $item instanceof WP_Term ) {
				$names[] = $item->name;
			} elseif ( is_numeric( $item ) ) {
				$t = get_term( (int) $item );
				if ( $t && ! is_wp_error( $t ) ) {
					$names[] = $t->name;
				}
			} elseif ( is_string( $item ) ) {
				$names[] = $item;
			}
		}
		return implode( '، ', $names );
	}
	if ( $v instanceof WP_Term ) {
		return $v->name;
	}
	return trim( (string) $v );
}

/* -------------------------------------------------------------------------
 * Catalogue (products)
 * ---------------------------------------------------------------------- */

/**
 * Post statuses listed in the catalogue (grid, counts, related lists). Production: publish only.
 * A local preview can add 'draft' through the `psb_catalogue_statuses` filter.
 *
 * @return string[]
 */
function psb_catalogue_statuses() {
	return array_values( array_unique( (array) apply_filters( 'psb_catalogue_statuses', array( 'publish' ) ) ) );
}

/**
 * Quote URL for a product: ?product=<slug> plus an explicit ?type=<rfq key> so the wizard
 * pre-selects the right card even when the product is not in its cached deep-link map.
 */
function psb_product_quote_url( $product_id ) {
	$product_id = (int) $product_id;
	$url        = psb_quote_url( urldecode( (string) get_post_field( 'post_name', $product_id ) ) );
	$type       = function_exists( 'psb_rfq_type_for_product' ) ? psb_rfq_type_for_product( $product_id ) : '';
	return $type ? add_query_arg( 'type', rawurlencode( $type ), $url ) : $url;
}

/**
 * Values of a product attribute by its label (e.g. «سطح ولتاژ»), visible attributes only.
 *
 * @return string[]
 */
function psb_product_attr( $product_id, $label ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return array();
	}
	foreach ( $product->get_attributes() as $attr ) {
		if ( ! is_object( $attr ) || trim( wc_attribute_label( $attr->get_name(), $product ) ) !== $label ) {
			continue;
		}
		$vals = $attr->is_taxonomy() ? wc_get_product_terms( $product_id, $attr->get_name(), array( 'fields' => 'names' ) ) : $attr->get_options();
		return array_values( array_filter( array_map( 'trim', (array) $vals ), 'strlen' ) );
	}
	return array();
}

/** Most specific product_cat of a product (a child category when there is one), or null. */
function psb_product_primary_cat( $product_id ) {
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	$default = (int) get_option( 'default_product_cat' );
	$terms   = array_values(
		array_filter(
			$terms,
			function ( $t ) use ( $default ) {
				return (int) $t->term_id !== $default;
			}
		)
	);
	usort(
		$terms,
		function ( $a, $b ) {
			return (int) ( $b->parent > 0 ) - (int) ( $a->parent > 0 );
		}
	);
	return $terms[0] ?? null;
}

/** Number of catalogue products in a category (children included), using psb_catalogue_statuses(). */
function psb_cat_count( $term ) {
	static $cache = array();
	$term_id = is_object( $term ) ? (int) $term->term_id : (int) $term;
	if ( ! isset( $cache[ $term_id ] ) ) {
		$q                 = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => psb_catalogue_statuses(),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'tax_query'      => array( // phpcs:ignore
					array(
						'taxonomy'         => 'product_cat',
						'terms'            => $term_id,
						'include_children' => true,
					),
				),
			)
		);
		$cache[ $term_id ] = (int) $q->found_posts;
	}
	return $cache[ $term_id ];
}

/** Line icon of a product category (psb_icon key). */
function psb_cat_icon( $term ) {
	$slug = is_object( $term ) ? urldecode( $term->slug ) : (string) $term;
	$map  = array(
		'low-voltage'  => 'cabinet',
		'medium-volt'  => 'mv-cell',
		'compact'      => 'rmu',
		'rmu'          => 'rmu',
		'metering'     => 'meter',
		'control'      => 'plc',
		'hospital'     => 'hospital',
		'capacitor'    => 'capacitor',
		'ats'          => 'ats',
	);
	foreach ( $map as $needle => $icon ) {
		if ( false !== strpos( $slug, $needle ) ) {
			return $icon;
		}
	}
	return 'grid';
}

/**
 * Wrap Latin codes with digits (IEC 61439-2, IP54, Form 4, 4000 A?) inside an HTML fragment in .lat,
 * touching text nodes only (never tags, attributes, script/style/code/pre). Peyda FaNum would otherwise
 * render «IEC ۶۱۴۳۹». Tokens must contain a Latin letter; bare numbers stay Persian.
 */
function psb_lat_content( $html ) {
	$parts = preg_split( '/(<[^>]+>)/u', (string) $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! $parts ) {
		return (string) $html;
	}
	$skip = 0;
	foreach ( $parts as $i => $part ) {
		if ( '' === $part ) {
			continue;
		}
		if ( '<' === $part[0] ) {
			if ( preg_match( '#^<(script|style|code|pre|span class="lat")\b#i', $part ) ) {
				++$skip;
			} elseif ( $skip && preg_match( '#^</(script|style|code|pre|span)>#i', $part ) ) {
				--$skip;
			}
			continue;
		}
		if ( $skip ) {
			continue;
		}
		$parts[ $i ] = preg_replace_callback(
			'/(?<![\p{L}\d])((?:[A-Za-z]+[\-\/.]?\s?)?[A-Za-z]*\d+[A-Za-z0-9.\-\/]*(?:\s?(?:kV|kA|A|V|Hz))?)(?![\p{L}\d])/u',
			function ( $m ) {
				return preg_match( '/[A-Za-z]/', $m[1] ) ? '<span class="lat">' . $m[1] . '</span>' : $m[1];
			},
			$part
		);
	}
	return implode( '', $parts );
}
