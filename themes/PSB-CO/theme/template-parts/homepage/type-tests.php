<?php
/**
 * Type-test reports (CPT "type-test") — technical cards with standard badges.
 */
$psb_q = new WP_Query(
	array(
		'post_type'      => 'type-test',
		'post_status'    => 'publish',
		'posts_per_page' => 4,
		'no_found_rows'  => true,
	)
);
if ( ! $psb_q->have_posts() ) {
	return;
}
?>
<section class="border-y border-line bg-white py-section" aria-labelledby="home-tests-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			array(
				'id'      => 'home-tests-title',
				'eyebrow' => 'تایپ‌تست',
				'title'   => 'گزارش‌های آزمون نوعی (Type Test)',
				'lead'    => 'نتایج آزمون‌های نوعی تابلوها در آزمایشگاه‌های مرجع، همراه با استاندارد و مرجع صدور.',
				'link'    => array(
					'url'   => psb_archive_url( 'type-test' ),
					'label' => 'همه گزارش‌ها',
				),
			)
		);
		?>
		<div class="rail [--rail-item:82%] sm:[--rail-item:46%] lg:grid lg:grid-flow-row lg:grid-cols-4 lg:gap-6 lg:overflow-visible">
			<?php
			while ( $psb_q->have_posts() ) :
				$psb_q->the_post();
				$psb_id    = get_the_ID();
				$psb_std   = psb_field( 'standard_number', '', $psb_id );
				$psb_body  = psb_field( 'issuing_body', '', $psb_id );
				$psb_equip = psb_field( 'tested_equipment', '', $psb_id );
				$psb_file  = psb_field( 'type_test_file', null, $psb_id ) ?: psb_field( 'certificate_file', null, $psb_id );
				?>
				<article class="card card-hover" data-reveal>
					<div class="card-body">
						<span class="icon-tile" aria-hidden="true"><?php echo psb_icon( 'flask' ); // phpcs:ignore ?></span>
						<h3 class="card-title mt-2 !text-base">
							<a class="card-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>
						<?php if ( $psb_std ) : ?>
							<div><span class="badge badge-standard"><?php echo esc_html( $psb_std ); ?></span></div>
						<?php endif; ?>
						<dl class="spec-list mt-1">
							<?php if ( $psb_body ) : ?>
								<div><dt>مرجع آزمون</dt><dd><?php echo esc_html( $psb_body ); ?></dd></div>
							<?php endif; ?>
							<?php if ( $psb_equip ) : ?>
								<div><dt>تجهیز</dt><dd><?php echo esc_html( $psb_equip ); ?></dd></div>
							<?php endif; ?>
						</dl>
					</div>
					<div class="card-footer">
						<span class="link-arrow">مشاهده گزارش <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></span>
						<?php if ( is_array( $psb_file ) && ! empty( $psb_file['url'] ) ) : ?>
							<a class="relative z-10 text-xs font-bold text-slate-500 hover:text-primary" href="<?php echo esc_url( $psb_file['url'] ); ?>" target="_blank" rel="noopener">فایل گزارش</a>
						<?php endif; ?>
					</div>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
