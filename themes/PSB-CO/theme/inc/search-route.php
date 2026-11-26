<?php
/**
 * PSB site search engine + REST endpoint.
 *
 * GET /wp-json/psb/v1/search?q=…&type=all|product|services|…&per=6
 *
 * - Builds a small normalised index of every searchable item (products, services, approvals,
 *   type tests, certificates, customer letters, portfolio, project records, posts, pages),
 *   cached in a transient and rebuilt after any save / delete.
 * - Persian-aware matching: Arabic ي/ك → ی/ک, hamza/alef variants, half-space ↔ space,
 *   Persian/Arabic ↔ Latin digits, «ها/های» suffixes, word-prefix matching, joined/split
 *   spellings («فشارضعیف» = «فشار ضعیف») and domain synonyms (LV, MV, RMU, type test …).
 * - Relevance: title ≫ meta (ACF / terms) ≫ content, plus a small per-type boost.
 * - Query results are cached per normalised query (short TTL) and invalidated with the index.
 *
 * Used by: template-parts/layout/header/search-modal.php (instant search), search.php
 * (results page) and inc/ajax-search.php (legacy admin-ajax action).
 *
 * @package bluebox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PSB_SEARCH_INDEX_KEY = 'psb_search_index_v4';
const PSB_SEARCH_VER_KEY   = 'psb_search_ver';

/**
 * Searchable post types in display order => [ label, icon ].
 * Unregistered types (e.g. customer_letter, project_record) are skipped automatically.
 */
function psb_search_types() {
	$types = array(
		'product'         => array( 'محصولات', 'cabinet' ),
		'services'        => array( 'خدمات و امکانات', 'cog' ),
		'confirmation'    => array( 'تأییدیه‌ها', 'seal' ),
		'type-test'       => array( 'آزمون‌های نوعی', 'report' ),
		'certificates'    => array( 'گواهینامه‌ها', 'iso' ),
		'customer_letter' => array( 'رضایت‌نامه‌ها', 'doc' ),
		'portfolio'       => array( 'پروژه‌ها', 'factory' ),
		// project_record deliberately excluded: anonymised rows' real titles must never be searchable (security review C2). The /project-references/ page has its own filter.
		'post'            => array( 'مقالات', 'doc' ),
		'page'            => array( 'صفحات', 'doc' ),
	);
	$types = apply_filters( 'psb_search_types', $types );
	return array_filter(
		$types,
		function ( $key ) {
			return post_type_exists( $key );
		},
		ARRAY_FILTER_USE_KEY
	);
}

/* -------------------------------------------------------------------------
 * Normalisation
 * ---------------------------------------------------------------------- */

/**
 * Normalise Persian/Arabic/Latin text for matching.
 *
 * @param string $s Raw text (HTML allowed).
 * @return string lower-case, single-spaced, letters/digits only.
 */
function psb_search_normalize( $s ) {
	$s = html_entity_decode( wp_strip_all_tags( (string) $s ), ENT_QUOTES, 'UTF-8' );
	$s = mb_strtolower( $s, 'UTF-8' );
	static $map = null;
	if ( null === $map ) {
		$map = array(
			'ي' => 'ی', 'ى' => 'ی', 'ئ' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه', 'ہ' => 'ه',
			'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ؤ' => 'و', 'ء' => '', 'ـ' => '',
			"\u{200C}" => ' ', "\u{200D}" => '', "\u{200F}" => '', "\u{200E}" => '', "\u{00A0}" => ' ',
		);
		foreach ( array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ) as $i => $d ) {
			$map[ $d ] = (string) $i;
		}
		foreach ( array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ) as $i => $d ) {
			$map[ $d ] = (string) $i;
		}
	}
	$s = strtr( $s, $map );
	$s = preg_replace( '/[\x{064B}-\x{065F}\x{0670}]/u', '', $s );
	$s = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $s );
	// "20kv" → "20 kv", "400ولت" → "400 ولت".
	$s = preg_replace( '/(?<=\p{N})(?=\p{L})|(?<=\p{L})(?=\p{N})/u', ' ', $s );
	return trim( preg_replace( '/\s+/u', ' ', $s ) );
}

/** Synonym groups (raw; normalised on use). */
function psb_search_synonyms() {
	return apply_filters(
		'psb_search_synonyms',
		array(
			array( 'فشار ضعیف', 'lv', 'low voltage', '400 ولت', '400 v', '400v', 'ال وی' ),
			array( 'فشار متوسط', 'mv', 'medium voltage', '20 کیلوولت', '20 کیلو ولت', '20 kv', '20kv', '24 kv', 'ام وی' ),
			array( 'کمپکت', 'کامپکت', 'rmu', 'ring main unit' ),
			array( 'تایپ تست', 'تایپتست', 'type test', 'typetest', 'آزمون نوعی' ),
			array( 'تاییدیه', 'تأییدیه', 'تائیدیه', 'وندور لیست', 'vendor list', 'avl' ),
			array( 'رضایت نامه', 'رضایتنامه', 'حسن انجام کار', 'تقدیرنامه', 'تقدیر نامه' ),
			array( 'استعلام قیمت', 'استعلام', 'قیمت', 'rfq' ),
			array( 'ایزوله بیمارستانی', 'ایزوله', 'it system', 'hospital' ),
			array( 'mcc', 'ام سی سی', 'مرکز کنترل موتور' ),
			array( 'کنتوری', 'اندازه گیری', 'سنجش' ),
		)
	);
}

/** Normalised synonym groups, longest phrases first (cached per request). */
function psb_search_synonym_groups() {
	static $groups = null;
	if ( null !== $groups ) {
		return $groups;
	}
	$groups = array();
	foreach ( psb_search_synonyms() as $group ) {
		$norm = array_values( array_unique( array_filter( array_map( 'psb_search_normalize', $group ) ) ) );
		usort(
			$norm,
			function ( $a, $b ) {
				return mb_strlen( $b ) - mb_strlen( $a );
			}
		);
		$groups[] = $norm;
	}
	return $groups;
}

/** Strip common Persian plural suffixes from a single token. */
function psb_search_stem( $tok ) {
	if ( mb_strlen( $tok ) >= 5 ) {
		foreach ( array( 'های', 'ها' ) as $suf ) {
			if ( mb_substr( $tok, -mb_strlen( $suf ) ) === $suf ) {
				return mb_substr( $tok, 0, -mb_strlen( $suf ) );
			}
		}
	}
	return $tok;
}

/**
 * Parse a query into matching units.
 * Each unit is a list of alternative normalised phrases; a document must contain one
 * alternative of every unit (AND), falling back to OR when nothing matches.
 *
 * @param string $q Raw query.
 * @return array{norm:string, units:array<int,string[]>}
 */
function psb_search_parse( $q ) {
	$norm  = psb_search_normalize( $q );
	$rest  = ' ' . $norm . ' ';
	$units = array();

	foreach ( psb_search_synonym_groups() as $group ) {
		foreach ( $group as $phrase ) {
			if ( false !== mb_strpos( $rest, ' ' . $phrase . ' ' ) ) {
				$units[] = $group;
				$rest    = str_replace( ' ' . $phrase . ' ', ' ', $rest );
				break;
			}
		}
	}

	$stop = array( 'و', 'در', 'به', 'از', 'با', 'برای', 'که', 'را', 'این', 'ان', 'ها', 'های', 'یا', 'تا', 'هم', 'the', 'of', 'and', 'for' );
	foreach ( preg_split( '/\s+/u', trim( $rest ) ) as $tok ) {
		if ( '' === $tok || in_array( $tok, $stop, true ) ) {
			continue;
		}
		if ( mb_strlen( $tok ) < 2 && ! ctype_digit( $tok ) ) {
			continue;
		}
		$units[] = array( psb_search_stem( $tok ) );
	}
	return array(
		'norm'  => $norm,
		'units' => array_slice( $units, 0, 8 ),
	);
}

/**
 * Regex that finds a normalised term inside ORIGINAL text (for highlight/snippet).
 */
function psb_search_term_regex( $term ) {
	static $classes = array(
		'ی' => '[یيئى]',
		'ک' => '[کك]',
		'ا' => '[اأإآٱ]',
		'ه' => '[هةۀ]',
		'و' => '[وؤ]',
	);
	$digits_fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	$digits_ar = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
	$out       = '';
	$chars     = preg_split( '//u', $term, -1, PREG_SPLIT_NO_EMPTY );
	foreach ( $chars as $ch ) {
		if ( ' ' === $ch ) {
			$out .= '[\s\x{200C}\-_\/]*';
		} elseif ( isset( $classes[ $ch ] ) ) {
			$out .= $classes[ $ch ];
		} elseif ( ctype_digit( $ch ) ) {
			$out .= '[' . $ch . $digits_fa[ (int) $ch ] . $digits_ar[ (int) $ch ] . ']';
		} else {
			$out .= preg_quote( $ch, '/' );
		}
		$out .= '\x{200C}?';
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * Index
 * ---------------------------------------------------------------------- */

/** Meta keys never indexed. */
function psb_search_meta_skip( $key ) {
	if ( '' === $key || '_' === $key[0] ) {
		return true;
	}
	return (bool) preg_match( '/^(rank_math|post_views|faq_list|total_sales|wc_|_wc|brand-logo|.*_file$|.*gallery.*|.*_pdf$|sld_document|letter_file|image|thumbnail)/', $key );
}

/** Build (or fetch) the search index. */
function psb_search_index() {
	$index = get_transient( PSB_SEARCH_INDEX_KEY );
	if ( is_array( $index ) ) {
		return $index;
	}

	$types   = array_keys( psb_search_types() );
	$exclude = array();
	foreach ( array( 'login', 'cart', 'checkout', 'my-account', 'compare' ) as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$exclude[] = $p->ID;
		}
	}
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $wc_page ) {
			$exclude[] = (int) wc_get_page_id( $wc_page );
		}
	}

	$q = new WP_Query(
		array(
			'post_type'              => $types,
			'post_status'            => 'publish',
			'posts_per_page'         => 800,
			'no_found_rows'          => true,
			'post__not_in'           => array_filter( $exclude ),
			'has_password'           => false,
			'update_post_term_cache' => true,
			'update_post_meta_cache' => true,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'suppress_filters'       => true,
		)
	);

	$index = array();
	foreach ( $q->posts as $p ) {
		$meta = array();
		foreach ( (array) get_post_meta( $p->ID ) as $key => $values ) {
			if ( psb_search_meta_skip( $key ) ) {
				continue;
			}
			foreach ( (array) $values as $v ) {
				if ( is_string( $v ) && '' !== $v && ! is_serialized( $v ) && strlen( $v ) < 600 && ! preg_match( '/^\d{1,6}$|^field_/', $v ) ) {
					$meta[] = $v;
				}
			}
		}
		foreach ( array_diff( get_object_taxonomies( $p->post_type ), array( 'product_type', 'product_visibility', 'product_shipping_class', 'post_format' ) ) as $tax ) {
			$terms = get_the_terms( $p, $tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $t ) {
					$meta[] = $t->name;
				}
			}
		}
		$meta_text = mb_substr( implode( ' · ', array_unique( $meta ) ), 0, 900 );
		$plain     = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $p->post_excerpt . ' ' . $p->post_content ) ) ) );
		$plain     = mb_substr( html_entity_decode( $plain, ENT_QUOTES, 'UTF-8' ), 0, 1400 );

		$index[] = array(
			'id' => $p->ID,
			'y'  => $p->post_type,
			'ti' => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
			't'  => psb_search_normalize( get_the_title( $p ) ),
			'm'  => psb_search_normalize( $meta_text ),
			'mo' => $meta_text,
			'c'  => psb_search_normalize( $plain ),
			'co' => $plain,
			'd'  => strtotime( $p->post_date_gmt ) ?: 0,
		);
	}

	set_transient( PSB_SEARCH_INDEX_KEY, $index, 12 * HOUR_IN_SECONDS );
	return $index;
}

/** Invalidate index + all cached queries. */
function psb_search_flush() {
	delete_transient( PSB_SEARCH_INDEX_KEY );
	update_option( PSB_SEARCH_VER_KEY, (int) get_option( PSB_SEARCH_VER_KEY, 1 ) + 1, false );
}

/**
 * Flush only when a searchable post changes (security review L8): RFQ inserts, WooCommerce orders,
 * revisions, autosaves, menu items etc. no longer throw away the whole index.
 *
 * @param int          $post_id Post ID.
 * @param WP_Post|null $post    Post object (deleted_post passes it; the row may already be gone).
 */
function psb_search_flush_post( $post_id, $post = null ) {
	$post_id = (int) $post_id;
	if ( ! $post_id || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	$post = $post instanceof WP_Post ? $post : get_post( $post_id );
	if ( ! $post || 'auto-draft' === $post->post_status || ! isset( psb_search_types()[ $post->post_type ] ) ) {
		return;
	}
	psb_search_flush();
}
add_action( 'save_post', 'psb_search_flush_post', 10, 2 );
add_action( 'deleted_post', 'psb_search_flush_post', 10, 2 );
add_action( 'trashed_post', 'psb_search_flush_post', 10, 1 );
add_action( 'edited_term', 'psb_search_flush', 10, 0 );
add_action(
	'acf/save_post',
	static function ( $post_id ) {
		if ( is_numeric( $post_id ) ) {
			psb_search_flush_post( (int) $post_id );
		} elseif ( is_string( $post_id ) && 0 === strpos( $post_id, 'term_' ) ) {
			psb_search_flush();
		}
	},
	20
);

/* -------------------------------------------------------------------------
 * Matching & scoring
 * ---------------------------------------------------------------------- */

/** Does $hay (normalised) contain $needle at a word start? */
function psb_search_has( $hay, $needle ) {
	return '' !== $needle && false !== mb_strpos( ' ' . $hay, ' ' . $needle );
}

/** Same test ignoring spaces (joined/split spellings). */
function psb_search_has_compact( $hay, $needle ) {
	$n = str_replace( ' ', '', $needle );
	return mb_strlen( $n ) >= 3 && false !== mb_strpos( str_replace( ' ', '', $hay ), $n );
}

/**
 * Score one document against parsed units.
 *
 * @return array{score:float, matched:int}
 */
function psb_search_score_doc( $doc, $parsed ) {
	$score   = 0.0;
	$matched = 0;
	foreach ( $parsed['units'] as $alts ) {
		$best = 0;
		foreach ( $alts as $alt ) {
			if ( psb_search_has( $doc['t'], $alt ) ) {
				$s = 0 === mb_strpos( $doc['t'], $alt ) ? 14 : 10;
			} elseif ( psb_search_has_compact( $doc['t'], $alt ) ) {
				$s = 8;
			} elseif ( psb_search_has( $doc['m'], $alt ) ) {
				$s = 5;
			} elseif ( psb_search_has( $doc['c'], $alt ) ) {
				$s = 2 + min( 3, mb_substr_count( $doc['c'], $alt ) ) * 0.5;
			} elseif ( psb_search_has_compact( $doc['m'] . ' ' . $doc['c'], $alt ) ) {
				$s = 1.5;
			} else {
				$s = 0;
			}
			$best = max( $best, $s );
		}
		if ( $best > 0 ) {
			++$matched;
			$score += $best;
		}
	}
	if ( $matched && '' !== $parsed['norm'] ) {
		if ( $doc['t'] === $parsed['norm'] ) {
			$score += 25;
		} elseif ( psb_search_has( $doc['t'], $parsed['norm'] ) ) {
			$score += 15;
		}
	}
	return array(
		'score'   => $score,
		'matched' => $matched,
	);
}

/**
 * Run a search.
 *
 * @param string $q     Raw query.
 * @param string $type  'all' or a post type key.
 * @return array{norm:string, units:array, hits:array<int,array>, counts:array<string,int>, mode:string}
 *               hits: [ id, type, score ] sorted by relevance (max 200).
 */
function psb_search_run( $q, $type = 'all' ) {
	$q      = mb_substr( trim( (string) $q ), 0, 80 );
	$parsed = psb_search_parse( $q );
	$types  = psb_search_types();
	$empty  = array(
		'norm'   => $parsed['norm'],
		'units'  => $parsed['units'],
		'hits'   => array(),
		'counts' => array(),
		'mode'   => 'none',
	);
	if ( mb_strlen( $parsed['norm'] ) < 2 || ! $parsed['units'] ) {
		return $empty;
	}

	$boost = array(
		'product'      => 3,
		'confirmation' => 2,
		'type-test'    => 2,
		'services'     => 1.5,
		'portfolio'    => 1,
		'page'         => -1,
	);
	$need  = count( $parsed['units'] );

	$run = function ( $min ) use ( $parsed, $boost ) {
		$hits = array();
		foreach ( psb_search_index() as $doc ) {
			$r = psb_search_score_doc( $doc, $parsed );
			if ( $r['matched'] >= $min ) {
				$hits[] = array(
					'id'    => $doc['id'],
					'type'  => $doc['y'],
					'score' => $r['score'] + ( $boost[ $doc['y'] ] ?? 0 ) + $r['matched'] * 2,
					'd'     => $doc['d'],
				);
			}
		}
		return $hits;
	};

	$mode = 'all';
	$hits = $run( $need );
	if ( ! $hits && $need > 1 ) {
		$mode = 'any';
		$hits = $run( max( 1, (int) ceil( $need / 2 ) ) );
	}
	if ( ! $hits ) {
		return array_merge( $empty, array( 'mode' => 'none' ) );
	}

	usort(
		$hits,
		function ( $a, $b ) {
			return ( $b['score'] <=> $a['score'] ) ?: ( $b['d'] <=> $a['d'] );
		}
	);

	$counts = array();
	foreach ( $hits as $h ) {
		$counts[ $h['type'] ] = ( $counts[ $h['type'] ] ?? 0 ) + 1;
	}
	if ( 'all' !== $type && isset( $types[ $type ] ) ) {
		$hits = array_values(
			array_filter(
				$hits,
				function ( $h ) use ( $type ) {
					return $h['type'] === $type;
				}
			)
		);
	}

	return array(
		'norm'   => $parsed['norm'],
		'units'  => $parsed['units'],
		'hits'   => array_slice( $hits, 0, 200 ),
		'counts' => $counts,
		'mode'   => $mode,
	);
}

/** Index row by post ID (for snippets). */
function psb_search_doc( $id ) {
	static $by_id = null;
	if ( null === $by_id ) {
		$by_id = array();
		foreach ( psb_search_index() as $doc ) {
			$by_id[ $doc['id'] ] = $doc;
		}
	}
	return $by_id[ $id ] ?? null;
}

/** Highlight unit matches in plain text → escaped HTML with <mark>. */
function psb_search_highlight( $text, $units ) {
	$html = esc_html( $text );
	$alts = array();
	foreach ( $units as $u ) {
		foreach ( $u as $alt ) {
			if ( mb_strlen( $alt ) >= 2 ) {
				$alts[] = psb_search_term_regex( $alt );
			}
		}
	}
	if ( ! $alts ) {
		return $html;
	}
	usort(
		$alts,
		function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	);
	$out = @preg_replace( '/(' . implode( '|', $alts ) . ')/iu', '<mark>$1</mark>', $html ); // phpcs:ignore
	return is_string( $out ) ? $out : $html;
}

/** Snippet (~180 chars) around the first match in content or meta. */
function psb_search_snippet( $doc, $units, $len = 180 ) {
	$sources = array( $doc['co'], $doc['mo'] );
	foreach ( $sources as $src ) {
		if ( '' === $src ) {
			continue;
		}
		foreach ( $units as $u ) {
			foreach ( $u as $alt ) {
				if ( mb_strlen( $alt ) < 2 ) {
					continue;
				}
				if ( @preg_match( '/' . psb_search_term_regex( $alt ) . '/iu', $src, $m, PREG_OFFSET_CAPTURE ) ) { // phpcs:ignore
					$pos   = mb_strlen( substr( $src, 0, $m[0][1] ) );
					$start = max( 0, $pos - 60 );
					$cut   = mb_substr( $src, $start, $len );
					return ( $start > 0 ? '…' : '' ) . trim( $cut ) . ( $start + $len < mb_strlen( $src ) ? '…' : '' );
				}
			}
		}
	}
	$first = '' !== $doc['co'] ? $doc['co'] : $doc['mo'];
	return mb_substr( $first, 0, $len ) . ( mb_strlen( $first ) > $len ? '…' : '' );
}

/** Short meta line for a result. */
function psb_search_meta_line( $id, $type ) {
	switch ( $type ) {
		case 'type-test':
		case 'certificates':
			return (string) psb_field( 'standard_number', '', $id ) ?: (string) psb_field( 'issuing_body', '', $id );
		case 'confirmation':
			return (string) psb_field( 'issuing_organization', '', $id );
		case 'portfolio':
			return (string) psb_field( 'client_name', '', $id ) ?: (string) psb_field( 'project_location', '', $id );
		case 'product':
			$terms = get_the_terms( $id, 'product_cat' );
			return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
		case 'post':
			return get_the_date( '', $id );
	}
	return '';
}

/**
 * Shape hits into display items.
 *
 * @param array $hits  From psb_search_run().
 * @param array $units Parsed units.
 * @return array[] id, type, title (HTML), url, snippet (HTML), meta, thumb
 */
function psb_search_items( $hits, $units ) {
	$items = array();
	foreach ( $hits as $h ) {
		$doc = psb_search_doc( $h['id'] );
		if ( ! $doc ) {
			continue;
		}
		$thumb   = get_the_post_thumbnail_url( $h['id'], 'thumbnail' );
		$items[] = array(
			'id'      => $h['id'],
			'type'    => $h['type'],
			'title'   => psb_search_highlight( $doc['ti'], $units ),
			'url'     => get_permalink( $h['id'] ),
			'snippet' => psb_search_highlight( psb_search_snippet( $doc, $units ), $units ),
			'meta'    => psb_brand_fix( psb_search_meta_line( $h['id'], $h['type'] ) ),
			'thumb'   => $thumb ? $thumb : '',
		);
	}
	return $items;
}

/* -------------------------------------------------------------------------
 * REST endpoint
 * ---------------------------------------------------------------------- */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'psb/v1',
			'/search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => 'psb_search_rest',
				'args'                => array(
					'q'    => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'type' => array(
						'type'              => 'string',
						'default'           => 'all',
						'sanitize_callback' => 'sanitize_key',
					),
					'per'  => array(
						'type'    => 'integer',
						'default' => 5,
						'minimum' => 1,
						'maximum' => 10,
					),
				),
			)
		);
	}
);

/**
 * REST callback: grouped results, ≤ ~40 items, cached per normalised query.
 *
 * @param WP_REST_Request $req Request.
 * @return WP_REST_Response
 */
function psb_search_rest( $req ) {
	$q     = (string) $req['q'];
	$type  = (string) $req['type'];
	$per   = (int) $req['per'];
	$types = psb_search_types();
	if ( 'all' !== $type && ! isset( $types[ $type ] ) ) {
		$type = 'all';
	}
	$norm = psb_search_normalize( $q );

	if ( mb_strlen( $norm ) < 2 ) {
		return psb_search_response( array( 'query' => $q, 'total' => 0, 'groups' => array(), 'suggest' => psb_search_popular() ) );
	}

	$key    = 'psb_sq_' . md5( $norm . '|' . $type . '|' . $per . '|' . get_option( PSB_SEARCH_VER_KEY, 1 ) . '|' . PSB_SEARCH_INDEX_KEY );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		$cached['query'] = $q;
		return psb_search_response( $cached );
	}

	// Rate-light: only cache misses count (60 / minute / IP).
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rlk  = 'psb_srl_' . md5( $ip . gmdate( 'YmdHi' ) );
	$hits = (int) get_transient( $rlk );
	if ( $hits > 60 ) {
		return new WP_REST_Response( array( 'message' => 'too many requests' ), 429 );
	}
	set_transient( $rlk, $hits + 1, 70 );

	$res    = psb_search_run( $q, $type );
	$groups = array();
	$total  = 0;
	$budget = 40;
	foreach ( $types as $pt => $info ) {
		if ( 'all' !== $type && $pt !== $type ) {
			continue;
		}
		$count = (int) ( $res['counts'][ $pt ] ?? 0 );
		if ( ! $count || $budget <= 0 ) {
			continue;
		}
		$group_hits = array_slice(
			array_values(
				array_filter(
					$res['hits'],
					function ( $h ) use ( $pt ) {
						return $h['type'] === $pt;
					}
				)
			),
			0,
			min( 'all' === $type ? $per : 10, $budget )
		);
		$budget  -= count( $group_hits );
		$total   += $count;
		$groups[] = array(
			'type'  => $pt,
			'label' => $info[0],
			'icon'  => $info[1],
			'count' => $count,
			'more'  => add_query_arg( array( 's' => rawurlencode( $q ), 'type' => $pt ), home_url( '/' ) ),
			'items' => psb_search_items( $group_hits, $res['units'] ),
		);
	}
	// Best types first: order groups by their top hit position.
	$order = array();
	foreach ( $res['hits'] as $i => $h ) {
		if ( ! isset( $order[ $h['type'] ] ) ) {
			$order[ $h['type'] ] = $i;
		}
	}
	usort(
		$groups,
		function ( $a, $b ) use ( $order ) {
			return ( $order[ $a['type'] ] ?? 999 ) <=> ( $order[ $b['type'] ] ?? 999 );
		}
	);

	$data = array(
		'query'   => $q,
		'norm'    => $norm,
		'mode'    => $res['mode'],
		'total'   => $total,
		'groups'  => $groups,
		'suggest' => $total ? array() : psb_search_popular(),
	);
	set_transient( $key, $data, 10 * MINUTE_IN_SECONDS );
	return psb_search_response( $data );
}

/** REST response with cache headers. */
function psb_search_response( $data ) {
	$r = new WP_REST_Response( $data, 200 );
	$r->header( 'Cache-Control', 'public, max-age=120' );
	$r->header( 'X-Robots-Tag', 'noindex' );
	return $r;
}

/** Popular searches / quick chips. */
function psb_search_popular() {
	return apply_filters( 'psb_search_popular', array( 'تابلو فشار ضعیف', 'فشار متوسط', 'کمپکت', 'تأییدیه توانیر', 'تایپ تست', 'استعلام قیمت' ) );
}

/* -------------------------------------------------------------------------
 * Search results page support
 * ---------------------------------------------------------------------- */

// The theme renders results from the engine; keep WP's own main search query minimal.
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->is_search() ) {
			$q->set( 'posts_per_page', 1 );
			$q->set( 'no_found_rows', true );
			$q->set( 'fields', 'ids' );
		}
	}
);

// Search result pages are never indexed.
add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( is_search() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
			unset( $robots['index'], $robots['max-image-preview'] );
		}
		return $robots;
	},
	99
);
add_filter(
	'rank_math/frontend/robots',
	function ( $robots ) {
		if ( is_search() ) {
			$robots['index']  = 'noindex';
			$robots['follow'] = 'follow';
		}
		return $robots;
	},
	99
);
