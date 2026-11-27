<?php
/**
 * «تنظیمات سایت» (ACF options page site-setting): only the fields the theme reads, in tabs.
 * Reuses the original DB group key and field keys, so saved values stay.
 *
 * Company facts (founding year, warranty, after-sales years, ISO 9001) are read early by
 * inc/design-helpers.php into PSB_FOUNDED_JALALI / PSB_WARRANTY_MONTHS / PSB_SERVICE_YEARS /
 * PSB_ISO_9001, and are the values of the text tokens {founded} {years} {warranty} {service}.
 *
 * Removed (not used by the theme): footer_logo; the whole «Shop Setting» group.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

$psb_social = array( // Same choices as before (template-parts/global/social-links.php has their icons).
	'instagram' => 'Instagram',
	'telegram'  => 'Telegram',
	'whatsapp'  => 'WhatsApp',
	'bale'      => 'Bale',
	'rubika'    => 'Rubika',
	'linkedin'  => 'LinkedIn',
	'youtube'   => 'YouTube',
	'aparat'    => 'Aparat',
);

psb_pf_register(
	'group_6a759dbaade00',
	'تنظیمات سایت',
	array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'site-setting' ) ) ),
	array(
		array( 'tab', 'اطلاعات شرکت' ),
		array( 'message', '', 'راهنما', 'این مقادیر در همه صفحه‌ها استفاده می‌شوند؛ از جمله به‌جای عبارت‌های خودکار <code dir="ltr">{founded}</code>، <code dir="ltr">{years}</code>، <code dir="ltr">{warranty}</code> و <code dir="ltr">{service}</code> در متن صفحه‌ها.', array( 'key' => 'field_psbpf_site_note' ) ),
		array( 'text', 'brand_name', 'نام شرکت', 'پارس سوئیچ بورد', array( 'key' => 'field_63beaed319e23', 'width' => 40, 'help' => 'عبارت خودکار {brand}' ) ),
		array( 'number', 'founded_year', 'سال تأسیس (شمسی)', 1355, array( 'width' => 20, 'help' => 'عبارت {founded}؛ «سال‌های فعالیت» {years} از همین محاسبه می‌شود.' ) ),
		array( 'number', 'warranty_months', 'گارانتی (ماه)', 18, array( 'width' => 20, 'help' => 'عبارت {warranty}' ) ),
		array( 'number', 'service_years', 'خدمات پس از فروش (سال)', 10, array( 'width' => 20, 'help' => 'عبارت {service}' ) ),
		array( 'true_false', 'iso_9001', 'دارای گواهینامه ISO 9001', 1, array( 'width' => 40, 'help' => 'اگر خاموش شود، «دارای گواهینامه ISO 9001» از فوتر حذف می‌شود.' ) ),
		array( 'image', 'site_logo', 'لوگوی کوچک', '', array( 'key' => 'field_63beaeff19e24', 'width' => 60, 'help' => 'در کادر «درباره شرکت» زیر مقاله‌ها. لوگوی اصلی سربرگ و فوتر فایل برداری قالب است.' ) ),

		array( 'tab', 'اطلاعات تماس' ),
		array(
			'repeater',
			'contact',
			'آدرس‌ها',
			array(),
			array(
				'key'    => 'field_6a75899f559ce',
				'layout' => 'block',
				'button' => 'افزودن آدرس',
				'help'   => 'ردیف اول دفتر مرکزی است. «نقشه» کد iframe از گوگل‌مپ (Share → Embed a map) است.',
				'sub'    => array(
					array( 'text', 'title', 'عنوان (مثلاً دفتر مرکزی، کارخانه)', '', array( 'key' => 'field_6a7589b4559cf', 'width' => 30 ) ),
					array( 'text', 'address', 'آدرس', '', array( 'key' => 'field_6a758a49559d2', 'width' => 70 ) ),
					array( 'textarea', 'map', 'کد نقشه (iframe)', '', array( 'key' => 'field_6a758a5d559d3', 'rows' => 2, 'width' => 60 ) ),
					array( 'url', 'map-link', 'لینک مسیریابی گوگل‌مپ', '', array( 'key' => 'field_6a758eee26f5d', 'width' => 40 ) ),
				),
			),
		),
		array(
			'repeater',
			'phones',
			'تلفن‌ها',
			array(),
			array(
				'key'    => 'field_6a759f7f64bf5',
				'button' => 'افزودن تلفن',
				'help'   => 'اولین شماره، شماره اصلی است (دکمه تماس سایت). فقط شماره‌های شرکت؛ شماره شخصی وارد نشود.',
				'sub'    => array(
					array( 'text', 'title', 'عنوان', '', array( 'key' => 'field_6a75a41f68b1f', 'width' => 35 ) ),
					array( 'text', 'number', 'شماره', '', array( 'key' => 'field_6a759f8d64bf6', 'width' => 40 ) ),
					array( 'true_false', 'show-on-footer', 'نمایش در فوتر', 0, array( 'key' => 'field_6a75a0095b8cb', 'width' => 25 ) ),
				),
			),
		),
		array(
			'repeater',
			'emails',
			'ایمیل‌ها',
			array(),
			array(
				'key'    => 'field_6a758a75559d4',
				'button' => 'افزودن ایمیل',
				'sub'    => array( array( 'email', 'email', 'ایمیل', '', array( 'key' => 'field_6a758a87559d5' ) ) ),
			),
		),

		array( 'tab', 'شبکه‌های اجتماعی' ),
		array(
			'repeater',
			'Social',
			'شبکه‌های اجتماعی',
			array(),
			array(
				'key'    => 'field_6a758b44d9338',
				'button' => 'افزودن شبکه',
				'sub'    => array(
					array( 'select', 'name', 'شبکه', '', array( 'key' => 'field_6a758b59d9339', 'width' => 30, 'choices' => $psb_social ) ),
					array( 'link', 'link', 'لینک', '', array( 'key' => 'field_6a758b83d933a', 'width' => 70 ) ),
				),
			),
		),

		array( 'tab', 'فوتر' ),
		array( 'textarea', 'footer_description', 'متن معرفی کوتاه در فوتر', '', array( 'key' => 'field_63e8966e39055', 'help' => 'خالی = «شرکت {brand}، سازنده تابلوهای برق فشار متوسط، فشار ضعیف و فرمان و کنترل.»' ) ),

		array( 'tab', 'کدهای اضافه' ),
		array( 'message', '', 'هشدار', 'فقط کد تأییدشده (مثلاً تأیید مالکیت سرچ کنسول یا آمار بازدید) قرار دهید؛ کد اشتباه می‌تواند کل سایت را از کار بیندازد.', array( 'key' => 'field_psbpf_site_scripts_note' ) ),
		array( 'textarea', 'header-scripts', 'کد داخل head', '', array( 'key' => 'field_6a758d6f3b852', 'rows' => 4 ) ),
		array( 'textarea', 'body-scripts', 'کد ابتدای body', '', array( 'key' => 'field_6a758d943b854', 'rows' => 4 ) ),
	)
);
unset( $psb_social );
