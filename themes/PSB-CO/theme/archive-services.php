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
			<div class="article-prose max-w-3xl"><?php echo $psb_body; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content filtered ?></div>
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
<section class="container py-section" aria-labelledby="services-list-title">
	<h2 id="services-list-title" class="sr-only">فهرست خدمات و امکانات</h2>
	<?php if ( have_posts() ) : ?>
		<div class="grid gap-gutter sm:grid-cols-2 lg:grid-cols-3">
			<?php
			global $wp_query;
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/services/service-card', null, array( 'post' => get_the_ID(), 'eager' => 0 === (int) $wp_query->current_post ) );
			endwhile;
			?>
		</div>
		<?php psb_pagination(); ?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/certificates/empty-state', null, array( 'icon' => 'factory', 'title' => 'اطلاعات امکانات به‌زودی منتشر می‌شود' ) ); ?>
	<?php endif; ?>
</section>
<?php
if ( $psb_is_page ) {
	wp_reset_query();
}
?>

<section class="border-t border-line bg-white py-section-sm" aria-labelledby="services-flow-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			array(
				'id'      => 'services-flow-title',
				'eyebrow' => 'فرایند ساخت',
				'title'   => 'از نقشه تا تحویل تابلو',
			)
		);
		$psb_steps = array(
			array( 'drawing', 'طراحی و مهندسی', 'تهیه نقشه‌های ساخت بر اساس نقشه تک‌خطی و مشخصات کارفرما.' ),
			array( 'cog', 'ساخت بدنه', 'برش، پانچ و خم‌کاری ورق با ماشین‌آلات CNC و پلیسه‌گیری لبه‌ها.' ),
			array( 'shield', 'رنگ و کنترل کیفیت', 'آماده‌سازی سطح و رنگ پودری الکترواستاتیک با آزمون‌های آزمایشگاه رنگ.' ),
			array( 'report', 'مونتاژ و آزمون', 'مونتاژ تجهیزات و آزمون روتین هر تابلو در آزمایشگاه تست برق، با امکان حضور خریدار.' ),
		);
		?>
		<ol class="grid gap-gutter sm:grid-cols-2 lg:grid-cols-4" role="list">
			<?php foreach ( $psb_steps as $psb_i => $psb_s ) : ?>
				<li class="panel">
					<span class="flex items-center justify-between" aria-hidden="true">
						<?php echo psb_icon( $psb_s[0], 'size-7 text-slate-700' ); // phpcs:ignore ?>
						<span class="text-2xl font-bold text-slate-300 tabular-nums"><?php echo esc_html( psb_fa_num( str_pad( (string) ( $psb_i + 1 ), 2, '0', STR_PAD_LEFT ) ) ); ?></span>
					</span>
					<h3 class="mt-3 text-base font-bold text-foreground"><?php echo esc_html( $psb_s[1] ); ?></h3>
					<p class="mt-2 text-sm leading-7 text-slate-600"><?php echo esc_html( $psb_s[2] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
<?php
get_footer();
