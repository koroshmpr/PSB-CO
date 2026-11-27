<?php
/**
 * Products (spec v2 §5.3): alt background, 3 product cards (4:3 photo, kicker, H3, 2-line text,
 * 2 spec rows, «مشخصات فنی» link + secondary «استعلام قیمت»). Mobile: scroll-snap rail (84%).
 * Spec rows are verified facts per product line (filter: psb_home_product_specs).
 */
$psb_q = new WP_Query(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'no_found_rows'  => true,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),
	)
);
if ( ! $psb_q->have_posts() ) {
	return;
}

/** Verified facts per product line, matched by title (company-facts.md / spec §5.3). */
$psb_specs_for = function ( $title ) {
	$rows = array();
	if ( false !== mb_strpos( $title, 'متوسط' ) ) {
		$rows = array(
			array( 'انواع', 'سنتی، کمپکت، ثابت و کشویی', '' ),
			array( 'سیستم', '', array( 'SAREL SYStem6' ) ),
		);
		$pos  = '50% 50%';
	} elseif ( false !== mb_strpos( $title, 'ضعیف' ) ) {
		$rows = array(
			array( 'استاندارد ساخت', '', array( 'IEC 61439' ) ),
			array( 'درجه حفاظت آزمون‌شده', '', array( 'IP54', 'IP65' ) ),
		);
		$pos  = '50% 40%';
	} elseif ( false !== mb_strpos( $title, 'فرمان' ) || false !== mb_strpos( $title, 'کنترل' ) ) {
		$rows = array(
			array( 'کاربرد', 'کنترل و پروسس، نیروگاهی', '' ),
			array( 'نوع', 'PLC و MCC', '' ),
		);
		$pos  = '50% 30%';
	} else {
		$pos = '50% 50%';
	}
	return apply_filters( 'psb_home_product_specs', array( $rows, $pos ), $title );
};
?>
<section class="bg-background py-section" aria-labelledby="home-products-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			psb_pf_head(
				'home_products',
				(int) get_option( 'page_on_front' ),
				array(
					'id'      => 'home-products-title',
					'link'    => array(
						'url'   => psb_shop_url()
					),
				)
			)
		);
		?>
		<div class="-mx-[var(--spacing-gutter)] flex snap-x snap-mandatory gap-4 overflow-x-auto px-[var(--spacing-gutter)] [scrollbar-width:none] md:mx-0 md:grid md:grid-cols-3 md:gap-6 md:overflow-visible md:px-0">
			<?php
			while ( $psb_q->have_posts() ) :
				$psb_q->the_post();
				$psb_id    = get_the_ID();
				$psb_title = get_the_title();
				$psb_cats  = get_the_terms( $psb_id, 'product_cat' );
				$psb_cat   = ( $psb_cats && ! is_wp_error( $psb_cats ) ) ? $psb_cats[0]->name : 'تابلو برق';
				$psb_desc  = wp_trim_words( wp_strip_all_tags( has_excerpt() ? get_the_excerpt() : get_the_content() ), 26, '…' );
				list( $psb_rows, $psb_pos ) = $psb_specs_for( $psb_title );
				?>
				<article class="card card-hover w-[84%] shrink-0 snap-start md:w-auto" data-reveal>
					<div class="card-media" style="--card-ratio:4/3">
						<?php
						$psb_img_attr = array( 'alt' => $psb_title, 'sizes' => '(min-width: 768px) 33vw, 84vw', 'style' => 'object-position:' . $psb_pos );
						if ( function_exists( 'psb_best_image' ) ) {
							echo psb_best_image( $psb_id, 400, '4 / 3', array( 'size' => 'medium_large', 'attr' => $psb_img_attr ) ); // phpcs:ignore -- low-res → matching illustration
						} elseif ( has_post_thumbnail() ) {
							echo psb_image( get_post_thumbnail_id(), 'medium_large', $psb_img_attr ); // phpcs:ignore
						}
						?>
					</div>
					<div class="card-body">
						<span class="card-eyebrow"><?php echo esc_html( $psb_cat ); ?></span>
						<h3 class="card-title"><a class="card-link" href="<?php the_permalink(); ?>"><?php echo psb_lat( $psb_title ); // phpcs:ignore ?></a></h3>
						<?php if ( $psb_desc ) : ?>
							<p class="card-text"><?php echo psb_lat( $psb_desc ); // phpcs:ignore ?></p>
						<?php endif; ?>
						<?php if ( $psb_rows ) : ?>
							<dl class="spec-list mt-1">
								<?php foreach ( $psb_rows as $psb_row ) : ?>
									<div>
										<dt><?php echo esc_html( $psb_row[0] ); ?></dt>
										<dd>
											<?php if ( $psb_row[2] ) : ?>
												<span class="flex flex-wrap justify-end gap-1.5">
													<?php foreach ( $psb_row[2] as $psb_chip ) : ?>
														<span class="badge-standard badge"><?php echo esc_html( $psb_chip ); ?></span>
													<?php endforeach; ?>
												</span>
											<?php else : ?>
												<?php echo esc_html( $psb_row[1] ); ?>
											<?php endif; ?>
										</dd>
									</div>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>
					</div>
					<div class="card-footer">
						<span class="link-arrow">مشخصات فنی <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></span>
						<a class="btn btn-secondary btn-sm relative z-10" href="<?php echo esc_url( psb_quote_url( get_post_field( 'post_name', $psb_id ) ) ); ?>">استعلام قیمت</a>
					</div>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
