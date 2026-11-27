<?php
/**
 * Breadcrumb (.breadcrumb). Markup only — BreadcrumbList schema is output by inc/seo.php.
 * Covers: singular (post / CPT / product / page with parents), category, tax, CPT archive, search, 404.
 *
 * $args:
 * - class   extra classes ('breadcrumb--invert' on dark heroes)
 * Below md the trail is replaced by one «back» pill to the direct parent (.crumb-back); the full trail
 * stays in the markup for desktop (BreadcrumbList JSON-LD is printed separately by inc/seo.php).
 */
if ( is_front_page() ) {
	return;
}

$psb_items = array(
	array(
		'name' => 'خانه',
		'url'  => home_url( '/' ),
	),
);
$psb_add   = function ( $name, $url = '' ) use ( &$psb_items ) {
	$psb_items[] = array(
		'name' => wp_strip_all_tags( (string) $name ),
		'url'  => $url,
	);
};

if ( is_singular() ) {
	$psb_post = get_post();
	$psb_type = get_post_type( $psb_post );

	if ( 'post' === $psb_type ) {
		$psb_blog = (int) get_option( 'page_for_posts' );
		$psb_add( $psb_blog ? get_the_title( $psb_blog ) : 'مقالات', $psb_blog ? get_permalink( $psb_blog ) : home_url( '/blog/' ) );
		$psb_cats = get_the_category();
		if ( ! empty( $psb_cats ) ) {
			$psb_add( $psb_cats[0]->name, get_category_link( $psb_cats[0]->term_id ) );
		}
	} elseif ( 'page' === $psb_type ) {
		foreach ( array_reverse( get_post_ancestors( $psb_post ) ) as $psb_parent ) {
			$psb_add( get_the_title( $psb_parent ), get_permalink( $psb_parent ) );
		}
	} elseif ( 'product' === $psb_type ) {
		$psb_add( 'محصولات', psb_shop_url() );
		// خانه › محصولات › <category> › <product> (most specific category; parent category skipped when it is the only root).
		$psb_pcat = function_exists( 'psb_product_primary_cat' ) ? psb_product_primary_cat( $psb_post->ID ) : null;
		if ( $psb_pcat ) {
			$psb_pcat_link = get_term_link( $psb_pcat );
			if ( ! is_wp_error( $psb_pcat_link ) ) {
				$psb_add( $psb_pcat->name, $psb_pcat_link );
			}
		}
	} else {
		$psb_obj = get_post_type_object( $psb_type );
		if ( $psb_obj ) {
			$psb_add( $psb_obj->labels->name, (string) get_post_type_archive_link( $psb_type ) );
		}
	}
	$psb_add( get_the_title( $psb_post ) );
} elseif ( is_home() ) {
	$psb_blog = (int) get_option( 'page_for_posts' );
	$psb_add( $psb_blog ? get_the_title( $psb_blog ) : 'مقالات' );
} elseif ( is_category() || is_tag() || is_tax() ) {
	$psb_term = get_queried_object();
	if ( $psb_term && ! empty( $psb_term->taxonomy ) ) {
		if ( in_array( $psb_term->taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
			$psb_add( 'محصولات', psb_shop_url() );
		} elseif ( in_array( $psb_term->taxonomy, array( 'category', 'post_tag', 'series' ), true ) ) {
			$psb_blog = (int) get_option( 'page_for_posts' );
			if ( $psb_blog ) {
				$psb_add( get_the_title( $psb_blog ), get_permalink( $psb_blog ) );
			}
		}
		if ( ! empty( $psb_term->parent ) ) {
			foreach ( array_reverse( get_ancestors( $psb_term->term_id, $psb_term->taxonomy ) ) as $psb_anc ) {
				$psb_anc_term = get_term( $psb_anc, $psb_term->taxonomy );
				$psb_anc_link = ( $psb_anc_term && ! is_wp_error( $psb_anc_term ) ) ? get_term_link( $psb_anc_term ) : '';
				if ( $psb_anc_link && ! is_wp_error( $psb_anc_link ) ) {
					$psb_add( $psb_anc_term->name, $psb_anc_link );
				}
			}
		}
		$psb_add( $psb_term->name );
	}
} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
	$psb_add( 'محصولات' );
} elseif ( is_post_type_archive() ) {
	$psb_add( post_type_archive_title( '', false ) );
} elseif ( is_search() ) {
	$psb_add( 'نتایج جستجو برای «' . get_search_query() . '»' );
} elseif ( is_404() ) {
	$psb_add( 'صفحه پیدا نشد' );
} elseif ( is_author() ) {
	$psb_add( get_the_author() );
}

$psb_last = count( $psb_items ) - 1;
// Mobile: one app-like «back» pill to the direct parent (category / archive / home) instead of the trail.
$psb_back = null;
for ( $psb_b = $psb_last - 1; $psb_b >= 0; $psb_b-- ) {
	if ( ! empty( $psb_items[ $psb_b ]['url'] ) ) {
		$psb_back = $psb_items[ $psb_b ];
		break;
	}
}
?>
<nav class="breadcrumb <?php echo esc_attr( $args['class'] ?? '' ); ?>" aria-label="مسیر صفحه">
	<?php if ( $psb_back ) : ?>
		<a class="crumb-back" href="<?php echo esc_url( $psb_back['url'] ); ?>" aria-label="<?php echo esc_attr( 'بازگشت به ' . $psb_back['name'] ); ?>">
			<?php echo psb_icon( 'chevron', 'size-4 shrink-0 -scale-x-100' ); // phpcs:ignore ?>
			<span class="truncate"><?php echo function_exists( 'psb_lat' ) ? psb_lat( $psb_back['name'] ) : esc_html( $psb_back['name'] ); // phpcs:ignore ?></span>
		</a>
	<?php endif; ?>
	<ol>
		<?php foreach ( $psb_items as $psb_i => $psb_item ) : ?>
			<li>
				<?php if ( $psb_i === $psb_last || empty( $psb_item['url'] ) ) : ?>
					<span <?php echo $psb_i === $psb_last ? 'aria-current="page"' : ''; ?>><?php echo function_exists( 'psb_lat' ) ? psb_lat( $psb_item['name'] ) : esc_html( $psb_item['name'] ); // phpcs:ignore -- escaped in helper ?></span>
				<?php else : ?>
					<a href="<?php echo esc_url( $psb_item['url'] ); ?>"><?php echo function_exists( 'psb_lat' ) ? psb_lat( $psb_item['name'] ) : esc_html( $psb_item['name'] ); // phpcs:ignore -- escaped in helper ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</nav>
