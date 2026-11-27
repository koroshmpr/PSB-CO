<?php
/**
 * Articles (spec v2 §5.8): white, 3 article cards (16:9, kicker = category, H3 ≤ 2 lines,
 * date · read time; no excerpt). Featured images flagged as AI renders are never shown —
 * those cards get the steel placeholder with the PSB mark. Filter: psb_blocked_image_ids.
 */
$psb_q = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => $args['count'] ?? 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
if ( ! $psb_q->have_posts() ) {
	return;
}
$psb_blocked = (array) apply_filters( 'psb_blocked_image_ids', array( 1777, 1841, 1752, 1842, 1703, 1775, 1776, 1838, 1750, 1837, 1694, 1705, 1836, 1388, 1336 ) );
$psb_blog    = (int) get_option( 'page_for_posts' );
?>
<section class="bg-white py-section" aria-labelledby="home-blog-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			psb_pf_head(
				'home_blog',
				(int) get_option( 'page_on_front' ),
				array(
					'id'      => 'home-blog-title',
					'link'    => array(
						'url'   => $psb_blog ? get_permalink( $psb_blog ) : home_url( '/blog/' )
					),
				)
			)
		);
		?>
		<div class="-mx-[var(--spacing-gutter)] flex snap-x snap-mandatory gap-4 overflow-x-auto px-[var(--spacing-gutter)] [scrollbar-width:none] md:mx-0 md:grid md:grid-cols-3 md:gap-6 md:overflow-visible md:px-0">
			<?php
			while ( $psb_q->have_posts() ) :
				$psb_q->the_post();
				$psb_cats  = get_the_category();
				$psb_thumb = get_post_thumbnail_id();
				$psb_ok    = $psb_thumb && ! in_array( (int) $psb_thumb, array_map( 'intval', $psb_blocked ), true );
				$psb_words = count( preg_split( '/\s+/u', wp_strip_all_tags( get_the_content() ), -1, PREG_SPLIT_NO_EMPTY ) );
				$psb_mins  = max( 1, (int) ceil( $psb_words / 200 ) );
				?>
				<article class="card card-hover w-[84%] shrink-0 snap-start md:w-auto" data-reveal>
					<?php
					$psb_bimg = '';
					if ( function_exists( 'psb_best_image' ) ) {
						// Sharp photo or the topic illustration («تصویر نمایشی»).
						$psb_bimg = psb_best_image( $psb_ok ? $psb_thumb : 0, 400, '16 / 9', array( 'key' => psb_placeholder_key( get_the_ID() ), 'attr' => array( 'alt' => get_the_title(), 'sizes' => '(min-width: 768px) 33vw, 84vw' ) ) );
					} elseif ( $psb_ok ) {
						$psb_bimg = psb_image( $psb_thumb, 'medium_large', array( 'alt' => get_the_title(), 'sizes' => '(min-width: 768px) 33vw, 84vw' ) );
					}
					?>
					<div class="card-media <?php echo $psb_bimg ? '' : 'media-placeholder'; ?>" style="--card-ratio:16/9">
						<?php echo $psb_bimg; // phpcs:ignore ?>
					</div>
					<div class="card-body">
						<?php if ( $psb_cats ) : ?>
							<span class="card-eyebrow"><?php echo esc_html( $psb_cats[0]->name ); ?></span>
						<?php endif; ?>
						<h3 class="card-title line-clamp-2"><a class="card-link" href="<?php the_permalink(); ?>"><?php echo psb_lat( get_the_title() ); // phpcs:ignore ?></a></h3>
						<p class="card-meta mt-auto"><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( psb_fa_num( $psb_mins ) . ' دقیقه مطالعه' ); ?></p>
					</div>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
