<?php
/**
 * Template Name: سوابق اجرایی
 *
 * Project references: light page head, stats, highlights, filterable list (SSR first 30 + JSON for the JS),
 * breakdown by sector/province (+ decade timeline when enough rows are dated). The CTA band is printed by footer.php.
 * Data: inc/project-records (CPT project_record). Schema and meta come from inc/seo.php / Rank Math.
 *
 * @package PSB-CO
 */

get_header();

if ( ! function_exists( 'psb_pr_stats' ) ) {
	// Module not loaded: render the page content only.
	get_template_part( 'template-parts/global/page-hero' );
	echo '<div class="container py-section"><div class="article-prose">';
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	echo '</div></div>';
	get_footer();
	return;
}

psb_pr_enqueue_style();
$psb_pr_stats = psb_pr_stats();
$psb_pr_from  = $psb_pr_stats['span_from'];
$psb_pr_lead  = $psb_pr_from
	? sprintf( 'فهرست پروژه‌هایی که از سال %s تابلوهای برق آن‌ها توسط پارس سوئیچ بورد طراحی و ساخته و در بسیاری موارد نصب و راه‌اندازی شده است؛ از پالایشگاه و پتروشیمی تا بیمارستان، صنایع غذایی و مجتمع‌های ساختمانی.', psb_pr_fa( $psb_pr_from ) )
	: 'فهرست پروژه‌هایی که تابلوهای برق آن‌ها توسط پارس سوئیچ بورد طراحی، ساخته و نصب و راه‌اندازی شده است.';
?>
<div class="pr-page">
	<?php
	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'title'   => apply_filters( 'psb_pr_page_title', 'سوابق اجرایی پارس سوئیچ بورد' ),
			'eyebrow' => 'سوابق اجرایی',
			'lead'    => $psb_pr_lead,
		)
	);

	if ( $psb_pr_stats['projects'] ) {
		get_template_part( 'template-parts/project-records/stats', null, array( 'stats' => $psb_pr_stats ) );
		get_template_part(
			'template-parts/project-records/highlights',
			null,
			array(
				'count' => 6,
				'class'   => 'bg-background',
				'eyebrow' => 'نمونه پروژه‌ها',
				'title' => 'پروژه‌های شاخص',
				'lead'  => 'نمونه‌ای از پروژه‌ها در حوزه‌های مختلف؛ فهرست کامل در ادامه آمده است.',
				'link'  => array( 'url' => '#pr-list', 'label' => 'فهرست کامل' ),
			)
		);
		get_template_part( 'template-parts/project-records/list' );
		get_template_part( 'template-parts/project-records/breakdown', null, array( 'stats' => $psb_pr_stats ) );
	}

	while ( have_posts() ) {
		the_post();
		if ( '' !== trim( get_the_content() ) ) {
			echo '<section class="py-section"><div class="container"><div class="article-prose">';
			the_content();
			echo '</div></div></section>';
		}
	}
	?>
</div>
<?php
get_footer();
