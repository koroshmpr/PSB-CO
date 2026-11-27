<?php
/**
 * Certificates & honours (CPT "certificates"). Hidden while empty.
 * Mobile: horizontal scroll-snap rail, ~1.4 cards in view; lg+: 4 columns. Each scanned document sits
 * whole (object-contain) on a soft graphite-to-steel gradient mat, so any paper size looks framed.
 */
if ( ! post_type_exists( 'certificates' ) ) {
	return;
}
$psb_q = new WP_Query(
	array(
		'post_type'      => 'certificates',
		'post_status'    => 'publish',
		'posts_per_page' => 4,
		'no_found_rows'  => true,
	)
);
if ( ! $psb_q->have_posts() ) {
	return;
}
?>
<section class="py-section" aria-labelledby="home-certs-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			psb_pf_head(
				'home_certificates',
				(int) get_option( 'page_on_front' ),
				array(
					'id'      => 'home-certs-title',
					'link'    => array(
						'url'   => psb_archive_url( 'certificates' )
					),
				)
			)
		);
		?>
		<ul class="cert-rail" role="list">
			<?php
			while ( $psb_q->have_posts() ) :
				$psb_q->the_post();
				$psb_id    = get_the_ID();
				$psb_std   = trim( (string) psb_field( 'standard_number', '', $psb_id ) );
				$psb_body  = trim( (string) psb_field( 'issuing_body', '', $psb_id ) );
				$psb_thumb = (int) get_post_thumbnail_id( $psb_id );
				$psb_attr  = array(
					'alt'   => get_the_title(),
					'sizes' => '(min-width: 1024px) 260px, 60vw',
					'class' => 'cert-doc',
				);
				$psb_img   = ( $psb_thumb && ! ( function_exists( 'psb_image_blocked' ) && psb_image_blocked( $psb_thumb ) ) )
					? psb_image( $psb_thumb, 'medium_large', $psb_attr )
					: ( function_exists( 'psb_best_image' ) ? psb_best_image( $psb_id, 300, '4 / 5', array( 'attr' => $psb_attr ) ) : '' );
				?>
				<li class="cert-slide">
					<article class="cert-card card-hover">
						<div class="cert-frame"><?php echo $psb_img; // phpcs:ignore ?></div>
						<div class="cert-body">
							<?php if ( $psb_body ) : ?>
								<span class="cert-issuer"><?php echo esc_html( $psb_body ); ?></span>
							<?php endif; ?>
							<h3 class="cert-title"><a class="card-link" href="<?php the_permalink(); ?>"><?php echo psb_lat( get_the_title() ); // phpcs:ignore ?></a></h3>
							<?php if ( $psb_std ) : ?>
								<span class="cert-note"><?php echo psb_lat( $psb_std ); // phpcs:ignore ?></span>
							<?php endif; ?>
						</div>
						<div class="cert-foot"><span class="link-arrow">مشاهده گواهینامه <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></span></div>
					</article>
				</li>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		</ul>
	</div>
</section>
