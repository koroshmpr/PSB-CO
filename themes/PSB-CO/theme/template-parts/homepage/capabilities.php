<?php
/**
 * Factory & lab (spec v2 §5.5): the one dark band — the six-step PRODUCTION FLOW (inc/production-flow.php,
 * edited in «روند تولید»): one row with a pulsing journey line on desktop, a vertical numbered path on
 * mobile; then the ruled 4-fact row.
 */
$psb_fp = (int) get_option( 'page_on_front' );
if ( ! function_exists( 'psb_production_steps' ) || ! psb_production_steps() ) {
	return;
}
// «بازدید از خطوط تولید» goes to the services page (/service/), not the CPT archive.
$psb_services_page = get_page_by_path( 'service' );
$psb_services_url  = $psb_services_page ? get_permalink( $psb_services_page ) : psb_archive_url( 'services', '/service/' );

$psb_facts = array();
foreach ( psb_pf_rows( 'home_factory_facts', $psb_fp ) as $psb_row ) {
	if ( ! empty( $psb_row['value'] ) ) {
		$psb_facts[] = array( psb_pf_html( $psb_row['value'] ), $psb_row['label'] ?? '', ! empty( $psb_row['icon'] ) ? $psb_row['icon'] : 'check' );
	}
}
?>
<section class="on-dark bg-dark py-section text-white" aria-labelledby="home-factory-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			psb_pf_head(
				'home_factory',
				(int) get_option( 'page_on_front' ),
				array(
					'id'      => 'home-factory-title',
					'invert'  => true,
					'link'    => array(
						'url'   => $psb_services_url
					),
				)
			)
		);
		?>
		<?php psb_production_flow( 'dark' ); ?>

		<?php if ( $psb_facts ) : ?>
		<dl class="stats stats--invert mt-8 lg:mt-12">
			<?php foreach ( $psb_facts as $psb_f ) : ?>
				<div class="stat">
					<dt class="order-1 flex items-center gap-3 text-2xl font-bold leading-[1.3] text-white"><?php echo psb_icon( $psb_f[2] ?? 'check', 'size-6 shrink-0 text-slate-400' ); // phpcs:ignore ?><span><?php echo wp_kses( $psb_f[0], array( 'span' => array( 'class' => true ) ) ); ?></span></dt>
					<dd class="stat-label order-2 !text-[0.8125rem]"><?php echo psb_pf_html( $psb_f[1] ); // phpcs:ignore ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php endif; ?>
	</div>
</section>
