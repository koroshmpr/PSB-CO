<?php
/**
 * Projects (spec v2 §5.6): white, project cards (3:2 photo, kicker = sector, H3, 3-fact hairline
 * meta from ACF: year · voltage · IP/standard, «جزئیات پروژه»). Client logo, if any, 32px in the body.
 */
$psb_q = new WP_Query(
	array(
		'post_type'      => 'portfolio',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'no_found_rows'  => true,
	)
);
if ( ! $psb_q->have_posts() ) {
	return;
}
$psb_term_name = function ( $id, $field ) {
	$v = psb_field( $field, null, $id );
	if ( is_array( $v ) ) {
		$v = reset( $v );
	}
	if ( is_object( $v ) && isset( $v->name ) ) {
		return $v->name;
	}
	if ( is_numeric( $v ) ) {
		$t = get_term( (int) $v );
		return ( $t && ! is_wp_error( $t ) ) ? $t->name : '';
	}
	$terms = taxonomy_exists( $field ) ? get_the_terms( $id, $field ) : false;
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
};
$psb_cols = 2 === $psb_q->post_count ? 'md:grid-cols-2' : 'md:grid-cols-3';
?>
<section class="bg-white py-section" aria-labelledby="home-projects-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			array(
				'id'      => 'home-projects-title',
				'eyebrow' => 'پروژه‌ها',
				'title'   => 'پروژه‌های اجراشده',
				'link'    => array(
					'url'   => psb_archive_url( 'portfolio', '/portfolios/' ),
					'label' => 'همه پروژه‌ها',
				),
			)
		);
		?>
		<div class="grid gap-6 <?php echo esc_attr( $psb_cols ); ?>">
			<?php
			while ( $psb_q->have_posts() ) :
				$psb_q->the_post();
				$psb_id     = get_the_ID();
				$psb_sector = $psb_term_name( $psb_id, 'industry_sector' );
				$psb_volt   = $psb_term_name( $psb_id, 'voltage_level' );
				$psb_year   = (string) psb_field( 'execution_year', '', $psb_id );
				$psb_ip     = (string) psb_field( 'ip_rating', '', $psb_id );
				$psb_std    = (string) psb_field( 'standards_compliance', '', $psb_id );
				$psb_logo   = psb_field( 'brand-logo', null, $psb_id );
				$psb_facts  = array_filter(
					array(
						$psb_year ? array( 'سال اجرا', psb_fa_num( $psb_year ), false ) : null,
						$psb_volt ? array( 'سطح ولتاژ', $psb_volt, false ) : null,
						$psb_ip ? array( 'درجه حفاظت', $psb_ip, true ) : ( $psb_std ? array( 'استاندارد', $psb_std, true ) : null ),
					)
				);
				?>
				<article class="card card-hover" data-reveal>
					<div class="card-media" style="--card-ratio:3/2">
						<?php
						$psb_img_attr = array( 'alt' => get_the_title(), 'sizes' => '(min-width: 768px) 50vw, 100vw' );
						if ( function_exists( 'psb_best_image' ) ) {
							echo psb_best_image( $psb_id, 600, '3 / 2', array( 'size' => 'large', 'attr' => $psb_img_attr ) ); // phpcs:ignore -- low-res → matching illustration
						} elseif ( has_post_thumbnail() ) {
							echo psb_image( get_post_thumbnail_id(), 'large', $psb_img_attr ); // phpcs:ignore
						}
						?>
					</div>
					<div class="card-body">
						<div class="flex items-start justify-between gap-4">
							<div class="grid gap-2">
								<?php if ( $psb_sector ) : ?>
									<span class="card-eyebrow"><?php echo esc_html( $psb_sector ); ?></span>
								<?php endif; ?>
								<h3 class="card-title"><a class="card-link" href="<?php the_permalink(); ?>"><?php echo psb_lat( get_the_title() ); // phpcs:ignore ?></a></h3>
							</div>
							<?php if ( is_array( $psb_logo ) && ! empty( $psb_logo['ID'] ) ) : ?>
								<?php echo psb_image( $psb_logo['ID'], 'thumbnail', array( 'class' => 'h-8 w-auto shrink-0 opacity-85 grayscale mix-blend-multiply', 'alt' => '', 'srcset' => false, 'sizes' => false ) ); // phpcs:ignore ?>
							<?php endif; ?>
						</div>
						<?php if ( $psb_facts ) : ?>
							<dl class="spec-list mt-1">
								<?php foreach ( $psb_facts as $psb_f ) : ?>
									<div><dt><?php echo esc_html( $psb_f[0] ); ?></dt><dd class="<?php echo $psb_f[2] ? 'lat' : ''; ?>"><?php echo esc_html( $psb_f[1] ); ?></dd></div>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>
					</div>
					<div class="card-footer">
						<span class="link-arrow">جزئیات پروژه <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></span>
					</div>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
