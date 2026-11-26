<?php
/**
 * Template Name: Compare Products
 *
 * Product comparison is not part of the B2B catalogue. This template stays for pages that still
 * use it and points visitors to the catalogue and the quote form.
 *
 * @package bluebox
 */

get_header();
get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'variant' => 'light',
		'lead'    => 'تابلوها بر اساس مشخصات هر پروژه ساخته می‌شوند؛ برای انتخاب نوع تابلو، کاتالوگ را ببینید یا با کارشناسان فنی مشورت کنید.',
	)
);
?>
<section class="container py-section">
	<?php
	get_template_part(
		'template-parts/certificates/empty-state',
		null,
		array(
			'icon'    => 'grid',
			'title'   => 'مقایسه محصولات',
			'text'    => 'برای مقایسه گزینه‌های فنی (نوع تابلو، درجه حفاظت، ساختار) مشخصات پروژه را ارسال کنید تا پیشنهاد فنی دریافت کنید.',
			'actions' => array(
				array( psb_shop_url(), 'مشاهده محصولات', 'btn-primary' ),
				array( psb_quote_url(), 'ثبت درخواست استعلام', 'btn-outline' ),
			),
		)
	);
	?>
</section>
<?php
get_footer();
