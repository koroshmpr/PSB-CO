<?php
/**
 * AI visibility — content collection, document builders and the cache.
 *
 * Cache model (object-cache safe, no autoload bloat):
 * - psb_ai_state  (non-autoloaded) holds a revision counter. Invalidation only
 *   bumps the counter — cheap, and the previous document stays available.
 * - psb_ai_doc_<key> (non-autoloaded) holds the last built document with the
 *   revision it was built for. A request with a matching revision is served
 *   as-is; a stale one is rebuilt by exactly one request (simple lock) while
 *   concurrent requests keep getting the stale copy, so crawlers never wait on
 *   a cold build after the first one. A scheduled warm-up rebuilds shortly after
 *   every invalidation anyway.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_AI_STATE_OPTION = 'psb_ai_state';

/** Documents that are cached (key => endpoint path). */
function psb_ai_cached_docs() {
	return array(
		'llms'      => 'llms.txt',
		'llms_full' => 'llms-full.txt',
	);
}

/* -------------------------------------------------------------------------
 * State / cache
 * ---------------------------------------------------------------------- */

function psb_ai_state() {
	$state = get_option( PSB_AI_STATE_OPTION, array() );
	$state = is_array( $state ) ? $state : array();
	return array_merge(
		array(
			'rev'         => 1,
			'invalidated' => 0,
			'reason'      => '',
		),
		$state
	);
}

function psb_ai_doc_option( $key ) {
	return 'psb_ai_doc_' . $key;
}

/**
 * Stored document record (or null).
 */
function psb_ai_doc_record( $key ) {
	$rec = get_option( psb_ai_doc_option( $key ), null );
	return ( is_array( $rec ) && isset( $rec['body'] ) ) ? $rec : null;
}

/**
 * Get a cached document, building it when missing or stale.
 *
 * @return array{body:string, generated:int, items:int, etag:string, rev:int, size:int}
 */
function psb_ai_get_doc( $key, $force = false ) {
	$state = psb_ai_state();
	$rec   = $force ? null : psb_ai_doc_record( $key );

	if ( $rec && (int) $rec['rev'] === (int) $state['rev'] ) {
		return $rec;
	}

	$locked = psb_ai_lock( $key );
	if ( $rec && ! $locked ) {
		// Another request is rebuilding — serve the stale copy.
		return $rec;
	}

	$rec = psb_ai_build_and_store( $key, $state );
	if ( $locked ) {
		psb_ai_unlock( $key );
	}
	return $rec;
}

function psb_ai_lock( $key ) {
	$name = 'psb_ai_lock_' . $key;
	if ( add_option( $name, time(), '', 'no' ) ) {
		return true;
	}
	$since = (int) get_option( $name );
	if ( $since && time() - $since > 120 ) {
		update_option( $name, time(), false );
		return true;
	}
	return false;
}

function psb_ai_unlock( $key ) {
	delete_option( 'psb_ai_lock_' . $key );
}

function psb_ai_build_and_store( $key, $state = null ) {
	$state = $state ?: psb_ai_state();

	if ( function_exists( 'set_time_limit' ) && 'llms_full' === $key ) {
		@set_time_limit( 60 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	switch ( $key ) {
		case 'llms_full':
			$built = psb_ai_build_llms_full();
			break;
		case 'llms':
		default:
			$built = psb_ai_build_llms();
			break;
	}

	$rec = array(
		'body'      => $built['body'],
		'items'     => (int) $built['items'],
		'truncated' => ! empty( $built['truncated'] ),
		'generated' => time(),
		'etag'      => md5( $built['body'] ),
		'size'      => strlen( $built['body'] ),
		'rev'       => (int) $state['rev'],
	);

	// Non-autoloaded: never loaded on normal page views.
	update_option( psb_ai_doc_option( $key ), $rec, false );

	return $rec;
}

/**
 * Rebuild every cached document now.
 */
function psb_ai_rebuild_all() {
	foreach ( array_keys( psb_ai_cached_docs() ) as $key ) {
		psb_ai_get_doc( $key, true );
	}
}

/* -------------------------------------------------------------------------
 * Content collection
 * ---------------------------------------------------------------------- */

/**
 * Enabled sections whose post type exists.
 *
 * @return array<string, array{title:string, post_type:string}>
 */
function psb_ai_active_sections() {
	$enabled = psb_ai_setting( 'sections', array() );
	$out     = array();
	foreach ( psb_ai_sections() as $key => $section ) {
		if ( empty( $enabled[ $key ] ) || ! post_type_exists( $section['post_type'] ) ) {
			continue;
		}
		$out[ $key ] = $section;
	}
	return $out;
}

/** Post types that may be exposed (for the .md endpoint and invalidation). */
function psb_ai_included_post_types() {
	$types = array();
	foreach ( psb_ai_active_sections() as $section ) {
		$types[] = $section['post_type'];
	}
	return array_values( array_unique( $types ) );
}

function psb_ai_excluded_ids() {
	return psb_ai_parse_ids( psb_ai_setting( 'exclude_ids', '' ) );
}

/**
 * Whether a single post may be exposed to AI documents.
 */
function psb_ai_post_is_exposable( $post ) {
	$post = get_post( $post );
	if ( ! $post || 'publish' !== $post->post_status || '' !== $post->post_password ) {
		return false;
	}
	if ( in_array( (int) $post->ID, psb_ai_excluded_ids(), true ) ) {
		return false;
	}
	// Respect Rank Math "noindex" on the item.
	$robots = get_post_meta( $post->ID, 'rank_math_robots', true );
	if ( is_array( $robots ) && in_array( 'noindex', $robots, true ) ) {
		return false;
	}
	if ( 'product' === $post->post_type && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $post->ID );
		if ( $product && method_exists( $product, 'get_catalog_visibility' ) && 'hidden' === $product->get_catalog_visibility() ) {
			return false;
		}
	}
	return true;
}

/**
 * Posts of one section.
 *
 * @return WP_Post[]
 */
function psb_ai_section_posts( $key ) {
	$sections = psb_ai_sections();
	if ( ! isset( $sections[ $key ] ) ) {
		return array();
	}
	$type    = $sections[ $key ]['post_type'];
	$exclude = psb_ai_excluded_ids();

	if ( 'pages' === $key ) {
		$ids   = psb_ai_parse_ids( psb_ai_setting( 'key_pages', '' ) );
		$front = (int) get_option( 'page_on_front' );
		if ( $front ) {
			array_unshift( $ids, $front );
		}
		$ids = array_values( array_unique( $ids ) );
		if ( ! $ids ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count( $ids ),
				'no_found_rows'  => true,
			)
		);
	} else {
		$args = array(
			'post_type'      => $type,
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'post__not_in'   => $exclude,
			'has_password'   => false,
			'no_found_rows'  => true,
		);
		if ( 'post' === $type ) {
			$max = (int) psb_ai_setting( 'max_posts', 20 );
			if ( $max < 1 ) {
				return array();
			}
			$args['posts_per_page'] = $max;
			$args['orderby']        = 'date';
			$args['order']          = 'DESC';
		}
		$posts = get_posts( $args );

		if ( 'post' === $type ) {
			// Sticky (most important) articles first.
			$sticky = array_map( 'intval', (array) get_option( 'sticky_posts', array() ) );
			if ( $sticky ) {
				usort(
					$posts,
					static function ( $a, $b ) use ( $sticky ) {
						$sa = in_array( (int) $a->ID, $sticky, true ) ? 0 : 1;
						$sb = in_array( (int) $b->ID, $sticky, true ) ? 0 : 1;
						if ( $sa !== $sb ) {
							return $sa - $sb;
						}
						return strcmp( $b->post_date, $a->post_date );
					}
				);
			}
		}
	}

	return array_values( array_filter( $posts, 'psb_ai_post_is_exposable' ) );
}

/* -------------------------------------------------------------------------
 * Text helpers
 * ---------------------------------------------------------------------- */

function psb_ai_clean_line( $text, $max = 220 ) {
	$text = strip_shortcodes( (string) $text );
	$text = wp_strip_all_tags( $text );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = str_replace( "\xC2\xA0", ' ', $text );
	$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
	if ( $max && function_exists( 'mb_strlen' ) && mb_strlen( $text ) > $max ) {
		$cut  = mb_substr( $text, 0, $max );
		$pos  = mb_strrpos( $cut, ' ' );
		$cut  = false !== $pos && $pos > $max * 0.6 ? mb_substr( $cut, 0, $pos ) : $cut;
		$text = preg_replace( '/[\s،,.:;\-]+$/u', '', $cut ) . '…';
	}
	return $text;
}

function psb_ai_title( $post ) {
	return psb_ai_clean_line( get_the_title( $post ), 0 );
}

/**
 * One-line description: Rank Math meta description → excerpt → content.
 */
function psb_ai_description( $post ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	$desc = (string) get_post_meta( $post->ID, 'rank_math_description', true );
	if ( '' !== $desc && false !== strpos( $desc, '%' ) ) {
		if ( class_exists( '\RankMath\Helper' ) && method_exists( '\RankMath\Helper', 'replace_vars' ) ) {
			try {
				$desc = (string) \RankMath\Helper::replace_vars( $desc, $post );
			} catch ( \Throwable $e ) {
				$desc = '';
			}
		}
		$desc = preg_replace( '/%[a-z0-9_()\-]+%/i', '', $desc );
	}
	$desc = psb_ai_clean_line( $desc );

	if ( '' === $desc ) {
		$desc = psb_ai_clean_line( $post->post_excerpt );
	}
	if ( '' === $desc ) {
		$desc = psb_ai_clean_line( preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', '', $post->post_content ) );
	}
	if ( '' === $desc && 'page' === $post->post_type ) {
		$desc = psb_ai_clean_line( psb_ai_acf_text( $post->ID, 600 ) );
	}
	return $desc;
}

/**
 * Flattened textual ACF values of a post (for ACF-built pages such as About).
 */
function psb_ai_acf_text( $post_id, $limit = 8000 ) {
	if ( ! function_exists( 'get_fields' ) ) {
		return '';
	}
	try {
		$fields = get_fields( $post_id );
	} catch ( \Throwable $e ) {
		return '';
	}
	if ( ! is_array( $fields ) ) {
		return '';
	}
	$lines = array();
	psb_ai_acf_walk( $fields, $lines );
	$text = implode( "\n\n", array_unique( $lines ) );
	if ( function_exists( 'mb_substr' ) && mb_strlen( $text ) > $limit ) {
		$text = mb_substr( $text, 0, $limit ) . '…';
	}
	return $text;
}

function psb_ai_acf_walk( $value, array &$lines, $key = '' ) {
	if ( is_array( $value ) ) {
		// Skip media / link arrays.
		if ( isset( $value['mime_type'] ) || isset( $value['sizes'] ) || ( isset( $value['url'] ) && isset( $value['target'] ) ) ) {
			return;
		}
		foreach ( $value as $k => $v ) {
			psb_ai_acf_walk( $v, $lines, is_string( $k ) ? $k : $key );
		}
		return;
	}
	if ( ! is_string( $value ) || preg_match( '/(script|map|url|link|image|icon|logo|color|class|id$)/i', (string) $key ) ) {
		return;
	}
	if ( preg_match( '#^(https?:)?//#i', trim( $value ) ) || preg_match( '#<(iframe|script)#i', $value ) ) {
		return;
	}
	$text = psb_ai_clean_line( $value, 0 );
	if ( function_exists( 'mb_strlen' ) ? mb_strlen( $text ) >= 15 : strlen( $text ) >= 15 ) {
		$lines[] = $text;
	}
}

function psb_ai_md_link( $title, $url, $desc = '' ) {
	$line = '- [' . psb_ai_md_escape_label( $title ) . '](' . psb_ai_md_escape_url( $url ) . ')';
	if ( '' !== $desc ) {
		$line .= ': ' . $desc;
	}
	return $line;
}

/** Item markdown URL (only when that endpoint is on). */
function psb_ai_item_md_url( $post_id ) {
	return home_url( '/llms/' . (int) $post_id . '.md' );
}

function psb_ai_sitemap_url() {
	if ( class_exists( 'RankMath' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return home_url( '/sitemap_index.xml' );
	}
	return home_url( '/wp-sitemap.xml' );
}

/**
 * Company contact lines from the ACF options page (site-setting).
 *
 * @return string[]
 */
function psb_ai_contact_lines() {
	if ( ! psb_ai_setting( 'contact', 1 ) || ! function_exists( 'get_field' ) ) {
		return array();
	}
	$lines = array();
	try {
		$addresses = get_field( 'contact', 'option' );
		if ( is_array( $addresses ) ) {
			foreach ( $addresses as $row ) {
				$addr = psb_ai_clean_line( $row['address'] ?? '', 0 );
				if ( '' !== $addr ) {
					$label   = psb_ai_clean_line( $row['title'] ?? '', 0 );
					$lines[] = ( '' !== $label ? $label . ': ' : 'نشانی: ' ) . $addr;
				}
			}
		}
		$phones = get_field( 'phones', 'option' );
		if ( is_array( $phones ) ) {
			foreach ( $phones as $row ) {
				if ( empty( $row['show-on-footer'] ) ) {
					continue;
				}
				$num = strtr( (string) ( $row['number'] ?? '' ), array_combine( array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ), array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ) ) );
				$num = preg_replace( '/[^0-9+]/', '', $num );
				if ( '' !== $num ) {
					$label   = psb_ai_clean_line( $row['title'] ?? '', 0 );
					$lines[] = ( '' !== $label ? 'تلفن ' . $label : 'تلفن' ) . ': ' . $num;
				}
			}
		}
		$emails = get_field( 'emails', 'option' );
		if ( is_array( $emails ) ) {
			foreach ( $emails as $row ) {
				$email = sanitize_email( trim( (string) ( $row['email'] ?? '' ) ) );
				if ( $email ) {
					$lines[] = 'ایمیل: ' . $email;
				}
			}
		}
	} catch ( \Throwable $e ) {
		return $lines;
	}
	return $lines;
}

/* -------------------------------------------------------------------------
 * Builders
 * ---------------------------------------------------------------------- */

function psb_ai_header_block() {
	$name    = psb_ai_setting( 'site_name', '' );
	$name    = '' !== $name ? $name : get_bloginfo( 'name' );
	$summary = psb_ai_clean_line( psb_ai_setting( 'summary', '' ), 0 );
	if ( '' === $summary ) {
		$summary = psb_ai_clean_line( get_bloginfo( 'description' ), 0 );
	}

	$out = '# ' . $name . "\n\n";
	if ( '' !== $summary ) {
		$out .= '> ' . $summary . "\n\n";
	}
	$about = trim( (string) psb_ai_setting( 'about', '' ) );
	if ( '' !== $about ) {
		$out .= $about . "\n\n";
	}
	$out .= 'وب‌سایت رسمی: ' . home_url( '/' ) . "\n\n";

	$contact = psb_ai_contact_lines();
	if ( $contact ) {
		$out .= "اطلاعات تماس:\n\n";
		foreach ( $contact as $line ) {
			$out .= '- ' . $line . "\n";
		}
		$out .= "\n";
	}
	return $out;
}

/**
 * /llms.txt (llmstxt.org format).
 */
function psb_ai_build_llms() {
	$out   = psb_ai_header_block();
	$items = 0;

	foreach ( psb_ai_active_sections() as $key => $section ) {
		$posts = psb_ai_section_posts( $key );
		if ( ! $posts ) {
			continue;
		}
		$out .= '## ' . $section['title'] . "\n\n";
		foreach ( $posts as $post ) {
			$out .= psb_ai_md_link( psb_ai_title( $post ), get_permalink( $post ), psb_ai_description( $post ) ) . "\n";
			++$items;
		}
		$out .= "\n";
	}

	// Optional: secondary resources an agent may skip.
	$optional = array();
	if ( psb_ai_endpoint_enabled( 'llms_full' ) ) {
		$optional[] = psb_ai_md_link( 'محتوای کامل سایت (llms-full.txt)', home_url( '/llms-full.txt' ), 'متن کامل همه صفحات کلیدی در قالب Markdown در یک فایل' );
	}
	$optional[] = psb_ai_md_link( 'نقشه سایت XML', psb_ai_sitemap_url(), 'فهرست کامل نشانی‌های قابل ایندکس سایت' );

	foreach ( array( 'product_cat' => 'دسته‌بندی محصولات', 'category' => 'دسته‌بندی مقالات' ) as $tax => $label ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => true,
				'number'     => 30,
			)
		);
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $term ) {
			if ( 'uncategorized' === $term->slug ) {
				continue;
			}
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$optional[] = psb_ai_md_link( $label . ': ' . psb_ai_clean_line( $term->name, 0 ), $link, psb_ai_clean_line( $term->description ) );
		}
	}

	$out .= "## Optional\n\n" . implode( "\n", $optional ) . "\n";

	return array(
		'body'  => $out,
		'items' => $items,
	);
}

/**
 * Markdown body of one item (without its own heading).
 */
function psb_ai_item_body( $post ) {
	$post  = get_post( $post );
	$parts = array();

	if ( 'product' === $post->post_type ) {
		if ( '' !== trim( $post->post_excerpt ) ) {
			$parts[] = psb_ai_html_to_markdown( $post->post_excerpt );
		}
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post->ID );
			if ( $product && method_exists( $product, 'get_attributes' ) ) {
				$rows = array();
				foreach ( $product->get_attributes() as $attr ) {
					if ( ! is_object( $attr ) || ! method_exists( $attr, 'get_visible' ) || ! $attr->get_visible() ) {
						continue;
					}
					$label = wc_attribute_label( $attr->get_name() );
					if ( $attr->is_taxonomy() ) {
						$values = wc_get_product_terms( $post->ID, $attr->get_name(), array( 'fields' => 'names' ) );
					} else {
						$values = $attr->get_options();
					}
					$values = array_filter( array_map( static fn( $v ) => psb_ai_clean_line( $v, 0 ), (array) $values ) );
					if ( $values ) {
						$rows[] = '| ' . str_replace( '|', '\|', psb_ai_clean_line( $label, 0 ) ) . ' | ' . str_replace( '|', '\|', implode( '، ', $values ) ) . ' |';
					}
				}
				if ( $rows ) {
					$parts[] = "| مشخصه | مقدار |\n| --- | --- |\n" . implode( "\n", $rows );
				}
			}
		}
	}

	$content = psb_ai_html_to_markdown( $post->post_content, 3 );
	if ( '' !== $content ) {
		$parts[] = $content;
	}

	$plain_len = function_exists( 'mb_strlen' ) ? mb_strlen( implode( '', $parts ) ) : strlen( implode( '', $parts ) );
	if ( $plain_len < 300 ) {
		$acf = psb_ai_acf_text( $post->ID );
		if ( '' !== $acf ) {
			$parts[] = $acf;
		}
	}

	if ( ! $parts ) {
		$desc = psb_ai_description( $post );
		if ( '' !== $desc ) {
			$parts[] = $desc;
		}
	}

	return trim( implode( "\n\n", $parts ) );
}

/**
 * Per-item metadata lines.
 */
function psb_ai_item_meta( $post, $section_title = '' ) {
	$lines   = array();
	$lines[] = '- نشانی: ' . get_permalink( $post );
	if ( '' !== $section_title ) {
		$lines[] = '- بخش: ' . $section_title;
	}
	$lines[] = '- آخرین به‌روزرسانی: ' . get_post_modified_time( 'Y-m-d', true, $post );
	$desc    = psb_ai_description( $post );
	if ( '' !== $desc ) {
		$lines[] = '- خلاصه: ' . $desc;
	}
	return implode( "\n", $lines );
}

/**
 * /llms-full.txt — full cleaned content, capped in size.
 */
function psb_ai_build_llms_full() {
	$cap       = max( 100, (int) psb_ai_setting( 'full_max_kb', 1500 ) ) * 1024;
	$out       = psb_ai_header_block();
	$out      .= 'این فایل به‌صورت خودکار از محتوای منتشرشده سایت ساخته می‌شود. فهرست خلاصه: ' . home_url( '/llms.txt' ) . "\n\n";
	$items     = 0;
	$truncated = false;

	// Main pages first (about / contact give the best context), then the rest.
	$sections = psb_ai_active_sections();
	if ( isset( $sections['pages'] ) ) {
		$sections = array( 'pages' => $sections['pages'] ) + $sections;
	}

	foreach ( $sections as $key => $section ) {
		if ( $truncated ) {
			break;
		}
		$posts = psb_ai_section_posts( $key );
		if ( ! $posts ) {
			continue;
		}
		$block = "\n# " . $section['title'] . "\n\n";
		foreach ( $posts as $post ) {
			$chunk  = '## ' . psb_ai_title( $post ) . "\n\n";
			$chunk .= psb_ai_item_meta( $post, $section['title'] ) . "\n\n";
			$body   = psb_ai_item_body( $post );
			if ( '' !== $body ) {
				$chunk .= $body . "\n\n";
			}
			$chunk .= "---\n\n";

			if ( strlen( $out ) + strlen( $block ) + strlen( $chunk ) > $cap ) {
				$truncated = true;
				break;
			}
			$block .= $chunk;
			++$items;
		}
		$out .= $block;
	}

	if ( $truncated ) {
		$out .= "\n> توجه: این فایل به دلیل محدودیت حجم کوتاه شده است. برای فهرست کامل صفحات به " . home_url( '/llms.txt' ) . " و نقشه سایت مراجعه کنید.\n";
	}

	return array(
		'body'      => $out,
		'items'     => $items,
		'truncated' => $truncated,
	);
}

/**
 * /llms/<id>.md — one item as Markdown (not cached; cheap).
 */
function psb_ai_build_item_md( $post ) {
	$post    = get_post( $post );
	$section = '';
	foreach ( psb_ai_sections() as $s ) {
		if ( $s['post_type'] === $post->post_type ) {
			$section = $s['title'];
			break;
		}
	}
	$out  = '# ' . psb_ai_title( $post ) . "\n\n";
	$out .= psb_ai_item_meta( $post, $section ) . "\n\n";
	$out .= psb_ai_item_body( $post ) . "\n";
	return $out;
}

/**
 * /ai.txt (Spawning-style), derived from the training-bot toggles.
 */
function psb_ai_build_ai_txt() {
	$bots     = psb_ai_setting( 'bots', array() );
	$training = 0;
	$blocked  = 0;
	foreach ( psb_ai_bots() as $name => $bot ) {
		if ( 'training' !== $bot['kind'] ) {
			continue;
		}
		++$training;
		if ( 'disallow' === ( $bots[ $name ] ?? 'allow' ) ) {
			++$blocked;
		}
	}
	$allow_training = $blocked < $training; // At least some training allowed.

	$out  = "# ai.txt — AI usage permissions for " . home_url( '/' ) . "\n";
	$out .= "# Detailed per-crawler rules: " . home_url( '/robots.txt' ) . "\n";
	$out .= "# Site summary for language models: " . home_url( '/llms.txt' ) . "\n\n";
	$out .= "User-Agent: *\n";
	if ( $allow_training ) {
		$out .= "Allow: /\n";
	} else {
		$out .= "Disallow: /\n";
	}
	return $out;
}
