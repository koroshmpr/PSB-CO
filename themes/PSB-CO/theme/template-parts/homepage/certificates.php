<?php
/**
 * Certificates & honours (CPT "certificates"). Hidden while empty.
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
			array(
				'id'      => 'home-certs-title',
				'eyebrow' => 'گواهینامه‌ها',
				'title'   => 'گواهینامه‌ها و افتخارات',
				'link'    => array(
					'url'   => psb_archive_url( 'certificates' ),
					'label' => 'همه گواهینامه‌ها',
				),
			)
		);
		?>
		<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
			<?php
			while ( $psb_q->have_posts() ) :
				$psb_q->the_post();
				$psb_std  = psb_field( 'standard_number', '', get_the_ID() );
				$psb_body = psb_field( 'issuing_body', '', get_the_ID() );
				get_template_part(
					'template-parts/cards/card',
					null,
					array(
						'eyebrow' => $psb_body,
						'excerpt' => false,
						'badges'  => array( $psb_std ),
						'ratio'   => '3 / 4',
						'size'    => 'medium',
						'cta'     => 'مشاهده گواهینامه',
					)
				);
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
