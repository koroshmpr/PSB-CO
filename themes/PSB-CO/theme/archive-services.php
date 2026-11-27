<?php
/**
 * Template Name: خدمات و امکانات تولید
 *
 * Archive: services & factory facilities (CPT services).
 * Also usable as a page template (page «خدمات», id 1299): then the page body is
 * shown under the hero and the services are queried explicitly.
 *
 * @package bluebox
 */

get_header();

get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'eyebrow' => 'کارخانه و آزمایشگاه',
		'title'   => 'خدمات و امکانات تولید',
		'lead'    => 'ساخت بدنه با ماشین‌آلات CNC، خط رنگ پودری الکترواستاتیک، آزمایشگاه تست رنگ و آزمایشگاه تست برق؛ همه مراحل ساخت تابلو در کارخانه ' . psb_brand_name() . ' انجام و کنترل می‌شود.',
	)
);
?>
<?php
$psb_is_page = is_page();
if ( $psb_is_page ) {
	$psb_page_id = get_queried_object_id();
	$psb_body    = $psb_page_id ? apply_filters( 'the_content', get_post_field( 'post_content', $psb_page_id ) ) : '';
	if ( '' !== trim( wp_strip_all_tags( $psb_body ) ) ) :
		?>
		<section class="container pt-section" aria-label="معرفی خدمات">
			<div class="inner-grid">
				<div class="inner-main">
					<div class="article-prose"><?php echo $psb_body; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content filtered ?></div>
				</div>
				<?php get_template_part( 'template-parts/services/services-aside' ); ?>
			</div>
		</section>
		<?php
	endif;
	// Main query is the page itself: query the services explicitly.
	query_posts( // phpcs:ignore WordPress.WP.DiscouragedFunctions -- restored below
		array(
			'post_type'      => 'services',
			'post_status'    => 'publish',
			'posts_per_page' => 24,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'paged'          => max( 1, (int) get_query_var( 'paged' ) ),
		)
	);
}
?>
<?php
// One services section (owner, 2026-11-27): the six production steps with their full data (same module
// and data as the home page), then the services outside the production flow (after delivery).
$psb_flow_ids = array();
if ( function_exists( 'psb_production_steps' ) ) {
	foreach ( psb_production_steps() as $psb_st ) {
		if ( $psb_st['post'] instanceof WP_Post ) {
			$psb_flow_ids[] = (int) $psb_st['post']->ID;
		}
	}
}
$psb_others = array();
while ( have_posts() ) {
	the_post();
	if ( ! in_array( get_the_ID(), $psb_flow_ids, true ) ) {
		$psb_others[] = get_the_ID();
	}
}
if ( $psb_is_page ) {
	wp_reset_query();
}
?>
<section class="py-section" aria-labelledby="services-flow-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			array(
				'id'      => 'services-flow-title',
				'eyebrow' => 'فرایند ساخت',
				'title'   => 'از نقشه تا تحویل تابلو',
				'lead'    => 'شش مرحله‌ای که هر تابلو در کارخانه ' . psb_brand_name() . ' طی می‌کند؛ برای جزئیات هر مرحله روی کارت آن بزنید.',
			)
		);
		?>
		<?php if ( function_exists( 'psb_production_steps' ) && psb_production_steps() ) : ?>
			<?php psb_production_flow( 'light' ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/certificates/empty-state', null, array( 'icon' => 'factory', 'title' => 'اطلاعات امکانات به‌زودی منتشر می‌شود' ) ); ?>
		<?php endif; ?>
	</div>
</section>

<?php if ( $psb_others ) : ?>
	<section class="border-t border-line bg-white py-section-sm" aria-labelledby="services-after-title">
		<div class="container">
			<?php
			get_template_part(
				'template-parts/global/section-head',
				null,
				array(
					'id'      => 'services-after-title',
					'eyebrow' => 'پس از ساخت',
					'title'   => 'نصب، راه‌اندازی و پشتیبانی',
				)
			);
			?>
			<div class="grid gap-gutter sm:grid-cols-2 lg:grid-cols-3">
				<?php foreach ( $psb_others as $psb_oid ) : ?>
					<?php get_template_part( 'template-parts/services/service-card', null, array( 'post' => $psb_oid ) ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
<?php
get_footer();
