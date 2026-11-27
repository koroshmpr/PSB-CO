<?php
/**
 * Navigation data layer for the desktop mega-menu and the mobile drawer.
 *
 * Structure comes from Appearance → Menus (location "menu-1", drawer: "menu-3" or "menu-1").
 * Each item is enriched from its target object (thumbnail, one-line description, count), so the
 * owner only edits the WP menu. A menu item's own "Description" field (Screen Options →
 * Description) overrides the automatic description.
 *
 * Panels are typed automatically:
 *  - products     children point at WooCommerce products → image cards + quote links + side column
 *  - credentials  children point at approvals / type tests → adds certificates & customer letters
 *                 archives when they have published posts, each with a count
 *  - about        children include about-us / contact-us → adds /project-references/ (if the page
 *                 exists) and the services archive
 *  - list         anything else (services, projects …) → thumbnail list
 *
 * @package bluebox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Resolve a menu item to its target post / archive. */
function psb_nav_target( $item ) {
	$target = array(
		'post_id' => 0,
		'type'    => '',
		'archive' => '',
	);
	if ( 'post_type' === $item->type && $item->object_id ) {
		$target['post_id'] = (int) $item->object_id;
	} elseif ( 'post_type_archive' === $item->type ) {
		$target['archive'] = $item->object;
	} elseif ( 'custom' === $item->type && $item->url && '#' !== $item->url ) {
		$path = trim( (string) wp_parse_url( $item->url, PHP_URL_PATH ), '/' );
		foreach ( array_keys( psb_search_types() ) as $pt ) {
			$obj  = get_post_type_object( $pt );
			$slug = ( $obj && ! empty( $obj->rewrite['slug'] ) ) ? trim( $obj->rewrite['slug'], '/' ) : $pt;
			if ( $obj && $obj->has_archive && ( $path === $slug || ( is_string( $obj->has_archive ) && $path === trim( $obj->has_archive, '/' ) ) ) ) {
				$target['archive'] = $pt;
				break;
			}
		}
		if ( ! $target['archive'] ) {
			$target['post_id'] = (int) url_to_postid( $item->url );
		}
	}
	if ( $target['post_id'] ) {
		$target['type'] = (string) get_post_type( $target['post_id'] );
	}
	return $target;
}

/** Published count for a post type (0 when not registered). */
function psb_nav_count( $pt ) {
	return post_type_exists( $pt ) ? (int) ( wp_count_posts( $pt )->publish ?? 0 ) : 0;
}

/** One-line description for a post. */
function psb_nav_post_desc( $post_id ) {
	$type = get_post_type( $post_id );
	$desc = '';
	if ( 'services' === $type ) {
		$desc = (string) psb_field( 'services_description', '', $post_id );
	} elseif ( 'portfolio' === $type ) {
		$desc = (string) psb_field( 'client_name', '', $post_id ) ?: (string) psb_field( 'project_location', '', $post_id );
	}
	if ( '' === trim( $desc ) ) {
		$post = get_post( $post_id );
		$desc = $post ? ( $post->post_excerpt ? $post->post_excerpt : wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ) : '';
	}
	return wp_trim_words( psb_brand_fix( $desc ), 12, '…' );
}

/** Icon key for an archive / post type. */
function psb_nav_icon( $pt ) {
	$types = psb_search_types();
	return $types[ $pt ][1] ?? 'doc';
}

/**
 * Build the enriched menu tree for a theme location.
 *
 * @param string $location Theme location.
 * @return array[] top-level items: id, title, url, current, desc, icon, thumb, children[], panel
 */
function psb_nav_tree( $location = 'menu-1' ) {
	static $cache = array();
	if ( isset( $cache[ $location ] ) ) {
		return $cache[ $location ];
	}
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		$cache[ $location ] = array();
		return array();
	}
	$items = wp_get_nav_menu_items( $locations[ $location ] );
	if ( ! $items ) {
		$cache[ $location ] = array();
		return array();
	}
	_wp_menu_item_classes_by_context( $items ); // current-menu-item / ancestor flags.

	$by_id = array();
	foreach ( $items as $item ) {
		// Some contexts (e.g. the WooCommerce shop archive) blank a page item's URL; rebuild it.
		if ( ! $item->url && 'post_type' === $item->type && $item->object_id ) {
			$item->url = (string) get_permalink( (int) $item->object_id );
		}
		$t       = psb_nav_target( $item );
		$classes = (array) $item->classes;
		$node    = array(
			'id'       => (int) $item->ID,
			'parent'   => (int) $item->menu_item_parent,
			'title'    => $item->title,
			'url'      => ( $item->url && '#' !== $item->url ) ? psb_iri( $item->url ) : '',
			'current'  => in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ) || in_array( 'current-menu-parent', $classes, true ),
			'desc'     => trim( (string) $item->description ),
			'icon'     => 'doc',
			'thumb'    => 0,
			'count'    => 0,
			'type'     => $t['type'] ? $t['type'] : $t['archive'],
			'post_id'  => $t['post_id'],
			'archive'  => $t['archive'],
			'children' => array(),
		);
		if ( $t['post_id'] ) {
			$node['thumb'] = (int) get_post_thumbnail_id( $t['post_id'] );
			$node['icon']  = 'page' === $t['type'] ? 'doc' : psb_nav_icon( $t['type'] );
			if ( '' === $node['desc'] && 'page' !== $t['type'] ) {
				$node['desc'] = psb_nav_post_desc( $t['post_id'] );
			}
		} elseif ( $t['archive'] ) {
			$node['icon']  = psb_nav_icon( $t['archive'] );
			$node['count'] = psb_nav_count( $t['archive'] );
		}
		$by_id[ $node['id'] ] = $node;
	}

	// Attach children (two levels).
	$tree = array();
	foreach ( $by_id as $id => $node ) {
		if ( $node['parent'] && isset( $by_id[ $node['parent'] ] ) ) {
			$by_id[ $node['parent'] ]['children'][] = &$by_id[ $id ];
		}
	}
	foreach ( $by_id as $id => $node ) {
		if ( ! $node['parent'] ) {
			$tree[] = &$by_id[ $id ];
		}
	}

	foreach ( $tree as &$top ) {
		$top['panel'] = psb_nav_panel_type( $top );
		psb_nav_enrich( $top );
	}
	unset( $top );

	psb_nav_mark_active( $tree );

	$cache[ $location ] = $tree;
	return $tree;
}

/** Decide the panel layout for a top-level item. */
function psb_nav_panel_type( $top ) {
	if ( empty( $top['children'] ) ) {
		return '';
	}
	$types = wp_list_pluck( $top['children'], 'type' );
	if ( in_array( 'product', $types, true ) ) {
		return 'products';
	}
	if ( array_intersect( $types, array( 'confirmation', 'type-test', 'certificates', 'customer_letter' ) ) ) {
		return 'credentials';
	}
	foreach ( $top['children'] as $child ) {
		$path = trim( (string) wp_parse_url( $child['url'], PHP_URL_PATH ), '/' );
		if ( in_array( $path, array( 'about-us', 'contact-us' ), true ) ) {
			return 'about';
		}
	}
	if ( in_array( 'services', $types, true ) ) {
		return 'services';
	}
	if ( in_array( 'portfolio', $types, true ) ) {
		return 'projects';
	}
	return 'list'; // Desktop: no dropdown (spec §4.9); the drawer still lists the children.
}

/** Auto-enrich panels with items whose targets exist. */
function psb_nav_enrich( &$top ) {
	$urls = array_map(
		function ( $c ) {
			return untrailingslashit( (string) $c['url'] );
		},
		$top['children']
	);
	$add  = function ( $url, $title, $icon, $count = 0, $desc = '' ) use ( &$top, &$urls ) {
		$url = $url ? psb_iri( $url ) : ''; // Compare decoded: menu URLs are already decoded by psb_iri().
		if ( ! $url || in_array( untrailingslashit( $url ), $urls, true ) ) {
			return;
		}
		$urls[]            = untrailingslashit( $url );
		$top['children'][] = array(
			'id'       => 0,
			'title'    => $title,
			'url'      => $url,
			'current'  => false,
			'desc'     => $desc,
			'icon'     => $icon,
			'thumb'    => 0,
			'count'    => $count,
			'type'     => '',
			'children' => array(),
		);
	};

	if ( 'credentials' === $top['panel'] ) {
		foreach ( array(
			'confirmation'    => array( 'تأییدیه‌ها و وندورلیست‌ها', 'shield' ),
			'type-test'       => array( 'گزارش‌های آزمون نوعی', 'flask' ),
			'certificates'    => array( 'گواهینامه‌ها', 'award' ),
			'customer_letter' => array( 'رضایت‌نامه‌ها', 'doc' ),
		) as $pt => $info ) {
			$n = psb_nav_count( $pt );
			if ( $n ) {
				$add( psb_archive_url( $pt, 'customer_letter' === $pt ? '/customer-letters/' : '' ), $info[0], $info[1], $n );
			}
		}
		// Fill counts / icons for existing archive children.
		foreach ( $top['children'] as &$c ) {
			if ( ! empty( $c['archive'] ) ) {
				$c['count'] = psb_nav_count( $c['archive'] );
				$c['icon']  = psb_nav_icon( $c['archive'] );
			}
			if ( ! $c['desc'] && $c['count'] ) {
				$c['desc'] = psb_fa_num( $c['count'] ) . ' مدرک منتشرشده';
			}
		}
		unset( $c );
	}

	if ( 'about' === $top['panel'] ) {
		$refs = get_page_by_path( 'project-references' );
		if ( $refs && 'publish' === $refs->post_status ) {
			$add( get_permalink( $refs ), 'سوابق اجرایی', 'briefcase', 0, 'فهرست پروژه‌های اجراشده' );
		}
		// Services are a top-level item («کارخانه و خدمات»), so the about panel no longer repeats them.

		foreach ( $top['children'] as &$c ) {
			$path = trim( (string) wp_parse_url( $c['url'], PHP_URL_PATH ), '/' );
			if ( 'about-us' === $path ) {
				$c['icon'] = 'building';
				$c['desc'] = $c['desc'] ? $c['desc'] : 'تاریخچه و معرفی شرکت از ' . psb_fa_num( PSB_FOUNDED_JALALI );
			} elseif ( in_array( $path, array( 'service', 'services' ), true ) ) {
				$c['icon'] = 'factory';
			} elseif ( 'project-references' === $path ) {
				$c['icon'] = 'briefcase';
			} elseif ( 'contact-us' === $path ) {
				$c['icon'] = 'phone';
				$c['desc'] = $c['desc'] ? $c['desc'] : 'نشانی دفتر مرکزی، کارخانه و شماره‌ها';
			}
		}
		unset( $c );
	}

	if ( 'services' === $top['panel'] ) {
		// Every published facility (the menu may list only some), each with a matching line icon.
		foreach ( get_posts( array( 'post_type' => 'services', 'post_status' => 'publish', 'numberposts' => 12, 'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ) ) ) as $p ) {
			$add( get_permalink( $p ), get_the_title( $p ), 'factory', 0, psb_nav_post_desc( $p->ID ) );
		}
		foreach ( $top['children'] as &$c ) {
			$t         = (string) $c['title'];
			$c['icon'] = preg_match( '/CNC|ماشین/u', $t ) ? 'cog' : ( preg_match( '/تست برق|برق/u', $t ) ? 'bolt' : ( preg_match( '/تست رنگ|آزمایشگاه/u', $t ) ? 'flask' : ( preg_match( '/رنگ/u', $t ) ? 'factory' : ( preg_match( '/پس از فروش|خدمات/u', $t ) ? 'wrench' : 'factory' ) ) ) );
			if ( ! $c['desc'] && ! empty( $c['post_id'] ) ) {
				$c['desc'] = psb_nav_post_desc( $c['post_id'] );
			}
		}
		unset( $c );
	}

	if ( 'projects' === $top['panel'] ) {
		foreach ( $top['children'] as &$c ) {
			$c['icon'] = 'building';
			if ( ! $c['desc'] && ! empty( $c['post_id'] ) ) {
				$c['desc'] = psb_nav_post_desc( $c['post_id'] );
			}
		}
		unset( $c );
		$refs = get_page_by_path( 'project-references' );
		if ( $refs && 'publish' === $refs->post_status ) {
			$n = function_exists( 'psb_pr_stats' ) ? (int) ( psb_pr_stats()['projects'] ?? 0 ) : 0;
			$add( get_permalink( $refs ), 'سوابق اجرایی', 'briefcase', $n, 'فهرست پروژه‌های اجراشده به تفکیک حوزه' );
		}
	}

	if ( 'list' === $top['panel'] && $top['url'] ) {
		// Make sure the panel always offers the parent destination.
		$add( $top['url'], 'مشاهده همه', 'arrow' );
	}
}

/** Post categories for the products panel side column («سری مقالات»). */
function psb_nav_article_series( $limit = 4 ) {
	$cats = get_categories(
		array(
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => $limit + 1,
			'exclude'    => array( (int) get_option( 'default_category' ) ),
		)
	);
	return array_slice( is_array( $cats ) ? $cats : array(), 0, $limit );
}


/** Site section of a URL path (first segment), used to map menu items to page contexts. */
function psb_nav_url_section( $url ) {
	$path = trim( (string) wp_parse_url( (string) $url, PHP_URL_PATH ), '/' );
	$home = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
	if ( $home && 0 === strpos( $path, $home ) ) {
		$path = trim( substr( $path, strlen( $home ) ), '/' );
	}
	$seg = strtolower( rawurldecode( strtok( $path, '/' ) ?: '' ) );
	$map = array(
		''                   => 'home',
		'shop'               => 'products',
		'product'            => 'products',
		'product-category'   => 'products',
		'confirmation'       => 'credentials',
		'type-test'          => 'credentials',
		'certificates'       => 'credentials',
		'customer-letters'   => 'credentials',
		'service'            => 'services',
		'services'           => 'services',
		'portfolios'         => 'projects',
		'portfolio'          => 'projects',
		'project-references' => 'projects',
		'blog'               => 'blog',
		'category'           => 'blog',
		'series'             => 'blog',
		'about-us'           => 'about',
		'contact-us'         => 'contact',
	);
	return $map[ $seg ] ?? '';
}

/** Section of the current request. */
function psb_nav_current_section() {
	if ( is_front_page() ) {
		return 'home';
	}
	$creds = array( 'confirmation', 'type-test', 'certificates', 'customer_letter' );
	if ( ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) || is_singular( 'product' ) || is_tax( array( 'product_cat', 'product_tag' ) ) || is_page( 'shop' ) ) {
		return 'products';
	}
	if ( is_singular( $creds ) || is_post_type_archive( $creds ) || is_tax( array( 'certificate_category', 'type_test_category' ) ) || is_page( array( 'customer-letters' ) ) ) {
		return 'credentials';
	}
	if ( is_singular( 'services' ) || is_post_type_archive( 'services' ) || is_tax( array( 'services_categories', 'services_tag' ) ) || is_page( 'service' ) ) {
		return 'services';
	}
	if ( is_singular( array( 'portfolio', 'project_record' ) ) || is_post_type_archive( array( 'portfolio', 'project_record' ) ) || is_tax( array( 'portfolio_categories', 'portfolio_tag' ) ) || is_page( array( 'portfolio', 'project-references' ) ) ) {
		return 'projects';
	}
	if ( is_singular( 'post' ) || is_home() || is_category() || is_tag() || is_tax( 'series' ) || is_page( 'blog' ) ) {
		return 'blog';
	}
	if ( is_page( 'about-us' ) ) {
		return 'about';
	}
	if ( is_page( 'contact-us' ) ) {
		return 'contact';
	}
	return '';
}

/**
 * Exactly one active top-level item: (1) the item whose own URL is the current page,
 * (2) else the item mapped to the current section, (3) else the parent of a current child.
 */
function psb_nav_mark_active( &$tree ) {
	$here    = untrailingslashit( psb_iri( home_url( add_query_arg( array() ) ) ) );
	$here    = preg_replace( '/\?.*$/', '', $here );
	$section = psb_nav_current_section();
	$active  = -1;

	foreach ( $tree as $i => $top ) {
		if ( $top['url'] && untrailingslashit( $top['url'] ) === $here ) {
			$active = $i;
			break;
		}
	}
	if ( $active < 0 && $section ) {
		foreach ( $tree as $i => $top ) {
			if ( $top['url'] && psb_nav_url_section( $top['url'] ) === $section ) {
				$active = $i;
				break;
			}
		}
	}
	if ( $active < 0 ) {
		foreach ( $tree as $i => $top ) {
			foreach ( $top['children'] as $c ) {
				if ( ! empty( $c['current'] ) ) {
					$active = $i;
					break 2;
				}
			}
		}
	}
	foreach ( $tree as $i => &$top ) {
		$top['current'] = ( $i === $active );
	}
	unset( $top );
}


/** Line icon for a top-level menu item (drawer rows), by site section of its URL. */
function psb_nav_section_icon( $url ) {
	$map = array(
		'home'        => 'home',
		'products'    => 'cabinet',
		'credentials' => 'shield',
		'services'    => 'factory',
		'projects'    => 'briefcase',
		'blog'        => 'doc',
		'about'       => 'building',
		'contact'     => 'phone',
	);
	return $map[ psb_nav_url_section( $url ) ] ?? 'doc';
}

/**
 * Product catalogue for the menus (desktop mega panel + mobile drawer), from ONE source:
 * sub-categories of the top «تابلو برق» category (or all categories with products), counted with the
 * shop's psb_cat_count() / psb_catalogue_statuses() so menus and /shop/ always agree. Empty
 * categories are dropped. Each entry: term, name, url, icon, count, products (WP_Post[] ≤ 6).
 */
function psb_nav_product_categories() {
	static $cats = null;
	if ( null !== $cats ) {
		return $cats;
	}
	$cats = array();
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return $cats;
	}
	$all = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $all ) || ! $all ) {
		return $cats;
	}
	$root = 0;
	foreach ( $all as $t ) {
		if ( $t->parent ) {
			$root = (int) $t->parent;
			break;
		}
	}
	$statuses = function_exists( 'psb_catalogue_statuses' ) ? psb_catalogue_statuses() : array( 'publish' );
	$icons    = array( 'medium' => 'mv-cell', 'compact' => 'rmu', 'rmu' => 'rmu', 'meter' => 'meter', 'control' => 'plc', 'hospital' => 'hospital', 'low' => 'cabinet' );
	foreach ( $all as $t ) {
		if ( $root ? (int) $t->parent !== $root : (int) $t->parent !== 0 ) {
			continue;
		}
		$count = function_exists( 'psb_cat_count' ) ? psb_cat_count( $t ) : (int) $t->count;
		if ( $count < 1 ) {
			continue;
		}
		$icon = 'cabinet';
		foreach ( $icons as $needle => $i ) {
			if ( false !== strpos( (string) $t->slug, $needle ) ) {
				$icon = $i;
				break;
			}
		}
		$link   = get_term_link( $t );
		$cats[] = array(
			'term'     => $t,
			'name'     => $t->name,
			'url'      => is_wp_error( $link ) ? psb_shop_url() : $link,
			'icon'     => $icon,
			'count'    => $count,
			'products' => get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => $statuses,
					'posts_per_page' => 6,
					'no_found_rows'  => true,
					'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
					'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'terms' => $t->term_id ) ), // phpcs:ignore
				)
			),
		);
	}
	usort(
		$cats,
		function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		}
	);
	return $cats;
}

/** One-line product teaser: plain text, character-trimmed, no doubled ellipsis. */
function psb_nav_teaser( $post, $chars = 90 ) {
	$text = wp_strip_all_tags( strip_shortcodes( $post->post_excerpt ? $post->post_excerpt : $post->post_content ) );
	$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );
	$text = preg_replace( '/(\.{3}|…)+$/u', '', $text );
	if ( mb_strlen( $text ) > $chars ) {
		$cut  = mb_substr( $text, 0, $chars );
		$sp   = mb_strrpos( $cut, ' ' );
		$text = rtrim( $sp ? mb_substr( $cut, 0, $sp ) : $cut, ' ،,؛:(-' ) . '…';
	}
	return $text;
}
