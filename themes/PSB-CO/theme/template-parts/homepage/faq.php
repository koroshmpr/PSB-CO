<?php
/**
 * Home FAQ (spec v2 §5.9): alt background, hairline accordion. Front page ACF "faq_list"; hidden when empty.
 */
$psb_items = psb_field( 'faq_list', array(), (int) get_option( 'page_on_front' ) ?: null );
if ( empty( $psb_items ) || ! is_array( $psb_items ) ) {
	return;
}
?>
<section class="bg-background py-section" aria-labelledby="home-faq-title">
	<div class="container grid gap-8 lg:grid-cols-12 lg:gap-16">
		<div class="lg:col-span-4">
			<?php $psb_head = psb_pf_head( 'home_faq', (int) get_option( 'page_on_front' ) ); ?>
			<?php if ( $psb_head['eyebrow'] ) : ?>
				<span class="eyebrow"><?php echo psb_pf_html( $psb_head['eyebrow'] ); // phpcs:ignore ?></span>
			<?php endif; ?>
			<h2 id="home-faq-title" class="section-title mt-3"><?php echo psb_pf_html( $psb_head['title'] ); // phpcs:ignore ?></h2>
			<?php if ( $psb_head['lead'] ) : ?>
				<p class="section-lead mt-4"><?php echo psb_pf_html( $psb_head['lead'] ); // phpcs:ignore ?></p>
			<?php endif; ?>
		</div>
		<div class="lg:col-span-8">
			<?php get_template_part( 'template-parts/global/faq-list', null, array( 'items' => $psb_items ) ); ?>
		</div>
	</div>
</section>
