<?php
/**
 * Default page: light page hero (single H1 + breadcrumb), article prose, FAQ.
 * WooCommerce cart / checkout / account pages keep a compact heading (their own layouts).
 *
 * @package bluebox
 */

get_header();

global $woo_active;
$psb_is_wc_page = $woo_active && function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );

while ( have_posts() ) :
	the_post();
	if ( $psb_is_wc_page ) :
		?>
		<div class="py-8">
			<h1 class="text-h2 font-bold text-dark"><?php the_title(); ?></h1>
			<div class="mt-6"><?php the_content(); ?></div>
		</div>
		<?php
		continue;
	endif;

	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'variant' => 'light',
			'lead'    => has_excerpt() ? wp_strip_all_tags( get_the_excerpt() ) : '',
		)
	);
	?>
	<section class="container py-section">
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="media-frame mx-auto mb-10 max-w-content">
				<?php echo psb_image( get_post_thumbnail_id(), 'large', array( 'alt' => get_the_title(), 'sizes' => '(min-width: 1024px) 60rem, 92vw', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
			</figure>
		<?php endif; ?>
		<div class="article-prose mx-auto">
			<?php
			the_content();
			wp_link_pages(
				array(
					'before' => '<nav class="mt-8 flex gap-2" aria-label="صفحات">',
					'after'  => '</nav>',
				)
			);
			?>
		</div>
		<?php
		get_template_part(
			'template-parts/global/faq-list',
			null,
			array(
				'title' => 'پرسش‌های متداول',
				'class' => 'mx-auto mt-12 max-w-prose',
			)
		);
		?>
	</section>
	<?php
endwhile;

get_footer();
