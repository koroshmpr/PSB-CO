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
			<span class="eyebrow">پرسش‌های متداول</span>
			<h2 id="home-faq-title" class="section-title mt-3">پاسخ پرسش‌های پرتکرار</h2>
		</div>
		<div class="lg:col-span-8">
			<?php get_template_part( 'template-parts/global/faq-list', null, array( 'items' => $psb_items ) ); ?>
		</div>
	</div>
</section>
