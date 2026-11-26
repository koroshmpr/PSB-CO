<?php
/**
 * Inner-page header (spec v2 §6.1) — the page's only H1.
 *
 * $args:
 * - title    string (default: current page / archive title)
 * - eyebrow  string
 * - lead     string
 * - variant  'light' (default: full-bleed #F4F5F7, hairline, breadcrumb, H1, lead)
 *            | 'split' (6/6 with a real photo; needs 'image')
 *            | 'photo' (about only: dark full-bleed photo header; needs 'image')
 *            ('dark' is accepted for backwards compatibility and renders as 'light')
 * - image    attachment ID or ACF image array
 * - media    ready HTML for the split column (overrides image; lets 'split' work without an attachment)
 * - facts    array of short strings shown as a ruled meta row (e.g. «۳ گروه محصول»)
 * - actions  HTML (already escaped) rendered under the lead
 * - class    extra classes
 */
if ( isset( $args['title'] ) ) {
	$psb_title = $args['title'];
} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
	$psb_title = woocommerce_page_title( false );
} elseif ( is_archive() ) {
	$psb_title = wp_strip_all_tags( get_the_archive_title() );
} elseif ( is_search() ) {
	$psb_title = 'نتایج جستجو برای «' . get_search_query() . '»';
} else {
	$psb_title = get_the_title();
}
$psb_image   = $args['image'] ?? 0;
$psb_image   = is_array( $psb_image ) ? (int) ( $psb_image['ID'] ?? 0 ) : (int) $psb_image;
$psb_variant = $args['variant'] ?? 'light';
$psb_media   = (string) ( $args['media'] ?? '' ); // Ready HTML for the split column (e.g. psb_single_image() output).
if ( ! in_array( $psb_variant, array( 'light', 'split', 'photo' ), true ) || ( 'light' !== $psb_variant && ! $psb_image && ! ( 'split' === $psb_variant && $psb_media ) ) ) {
	$psb_variant = 'light';
}
$psb_photo = 'photo' === $psb_variant;
$psb_split = 'split' === $psb_variant;
?>
<section class="page-hero <?php echo $psb_photo ? 'page-hero--photo on-dark' : ''; ?> <?php echo esc_attr( $args['class'] ?? '' ); ?>">
	<?php if ( $psb_photo ) : ?>
		<?php echo psb_image( $psb_image, 'full', array( 'class' => 'absolute inset-0 -z-20 size-full object-cover [filter:saturate(.85)_contrast(1.04)]', 'alt' => '', 'sizes' => '100vw', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
		<span class="hero-overlay absolute inset-0 -z-10" aria-hidden="true"></span>
	<?php endif; ?>
	<div class="container <?php echo $psb_split ? 'grid items-center gap-8 lg:grid-cols-2 lg:gap-12' : 'w-full'; ?>">
		<div>
			<?php get_template_part( 'template-parts/global/breadcrumb', null, array( 'class' => $psb_photo ? 'breadcrumb--invert' : '' ) ); ?>
			<?php if ( ! empty( $args['eyebrow'] ) ) : ?>
				<span class="eyebrow mt-4 md:mt-5"><?php echo esc_html( $args['eyebrow'] ); ?></span>
			<?php endif; ?>
			<h1 class="page-hero-title"><?php echo function_exists( 'psb_lat_html' ) ? psb_lat_html( $psb_title ) : psb_lat( $psb_title ); // phpcs:ignore -- escaped by the helper ?></h1>
			<?php if ( ! empty( $args['lead'] ) ) : ?>
				<p class="page-hero-lead"><?php echo esc_html( $args['lead'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $args['facts'] ) ) : ?>
				<p class="page-hero-facts mt-5"><?php echo esc_html( implode( ' | ', (array) $args['facts'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $args['actions'] ) ) : ?>
				<div class="mt-6 flex flex-wrap gap-3"><?php echo $args['actions']; // phpcs:ignore ?></div>
			<?php endif; ?>
		</div>
		<?php if ( $psb_split ) : ?>
			<div>
				<?php echo $psb_media ? $psb_media : psb_image( $psb_image, 'large', array( 'class' => 'aspect-[4/3] w-full rounded-card object-cover', 'alt' => '', 'sizes' => '(min-width: 1024px) 600px, 100vw', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
	</div>
</section>
