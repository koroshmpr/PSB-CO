<?php
/**
 * Archive: certificates (CPT certificates) — ISO 9001 and future items. Graceful empty state.
 *
 * @package bluebox
 */

get_header();

get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'eyebrow' => 'اعتبارنامه‌ها',
		'title'   => 'گواهینامه‌ها',
		'lead'    => 'گواهینامه‌های سیستم مدیریت کیفیت و سایر گواهی‌های رسمی ' . psb_brand_name() . '.',
	)
);
get_template_part( 'template-parts/certificates/cred-nav', null, array( 'current' => 'certificates' ) );
?>
<section class="container py-section" aria-labelledby="cert-list-title">
	<?php if ( have_posts() ) : ?>
		<h2 id="cert-list-title" class="sr-only">فهرست گواهینامه‌ها</h2>
		<div class="grid gap-gutter sm:grid-cols-2 lg:grid-cols-3">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/certificates/certificate-card', null, array( 'post' => get_the_ID() ) );
			endwhile;
			?>
		</div>
		<?php psb_pagination(); ?>
	<?php else : ?>
		<?php
		get_template_part(
			'template-parts/certificates/empty-state',
			null,
			array(
				'icon'    => 'shield',
				'title'   => 'تصویر گواهینامه‌ها به‌زودی در این بخش قرار می‌گیرد',
				'text'    => 'تا آن زمان، تأییدیه‌های رسمی و گزارش‌های آزمون نوعی شرکت در بخش‌های مربوط قابل مشاهده است؛ برای دریافت مدارک، با واحد بازرگانی تماس بگیرید.',
				'actions' => array(
					array( psb_archive_url( 'confirmation' ), 'تأییدیه‌ها', 'btn-primary' ),
					array( psb_archive_url( 'type-test' ), 'گزارش‌های آزمون نوعی', 'btn-outline' ),
				),
			)
		);
		?>
	<?php endif; ?>
</section>
<?php
get_footer();
