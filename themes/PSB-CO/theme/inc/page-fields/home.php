<?php
/**
 * Front page fields (group «صفحه اصلی», key of the original DB group). One tab per home section,
 * in page order. Read by template-parts/homepage/*.php.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Section-head specs (eyebrow, title, lead, link label) for one prefix. */
function psb_pf_head_specs( $prefix, $eyebrow, $title, $lead = '', $link = '' ) {
	$specs = array(
		array( 'text', "{$prefix}_eyebrow", 'برچسب بالای عنوان', $eyebrow, array( 'width' => 35 ) ),
		array( 'text', "{$prefix}_title", 'عنوان بخش', $title, array( 'width' => 65 ) ),
		array( 'textarea', "{$prefix}_lead", 'متن کوتاه زیر عنوان', $lead, array( 'rows' => 2 ) ),
	);
	if ( '' !== $link ) {
		$specs[] = array( 'text', "{$prefix}_link", 'متن لینک «مشاهده همه»', $link, array( 'width' => 50 ) );
	}
	return $specs;
}

$psb_home_icon = array( 'choices' => psb_pf_icon_choices(), 'width' => 20 );

psb_pf_register(
	'group_63c2710475bbb',
	'محتوای صفحه اصلی',
	array( array( array( 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ) ) ),
	array_merge(
		array(
			array( 'tab', 'بالای صفحه' ),
			array( 'message', '', 'راهنما', '', array( 'key' => 'field_psbpf_help_home' ) ),
			array( 'image', 'home_hero_image', 'عکس بالای صفحه (دسکتاپ)', '', array( 'width' => 50, 'help' => 'افقی، حداقل ۱۶۰۰ پیکسل عرض. سمت چپ عکس زیر متن تیره می‌شود؛ موضوع اصلی بهتر است در سمت چپ کادر باشد.' ) ),
			array( 'image', 'home_hero_image_mobile', 'عکس بالای صفحه (موبایل)', '', array( 'width' => 50, 'help' => 'اختیاری؛ عمودی با نسبت ۴ به ۵ (مثلاً ۷۲۰ × ۹۰۰). اگر خالی باشد، عکس دسکتاپ استفاده می‌شود.' ) ),
			array( 'text', 'home_hero_eyebrow', 'برچسب بالای عنوان', 'شرکت تولیدی و صنعتی {brand} · از {founded}' ),
			array( 'text', 'home_hero_title', 'عنوان اصلی صفحه (H1)', 'طراحی، ساخت و نصب تابلوهای برق فشار ضعیف و فشار متوسط' ),
			array( 'textarea', 'home_hero_lead', 'متن معرفی', 'ارائه‌دهنده راهکارهای توزیع و کنترل انرژی الکتریکی برای صنایع، شبکه‌های توزیع، نیروگاه‌ها و پروژه‌های ساختمانی؛ دارای تأییدیه توانیر، گواهینامه ISO 9001، {warranty} ماه گارانتی و {service} سال خدمات پس از فروش.' ),
			array( 'text', 'home_hero_btn1', 'متن دکمه اصلی (استعلام قیمت)', 'درخواست استعلام قیمت', array( 'width' => 33 ) ),
			array( 'text', 'home_hero_btn2', 'متن دکمه دوم', 'مشاهده محصولات', array( 'width' => 33 ) ),
			array( 'url', 'home_hero_btn2_url', 'لینک دکمه دوم', '', array( 'width' => 34, 'help' => 'خالی = صفحه محصولات' ) ),
			array( 'text', 'home_hero_phone', 'متن کنار شماره تماس', 'یا تماس مستقیم:', array( 'help' => 'شماره از «تنظیمات سایت» خوانده می‌شود. خالی = عدم نمایش.' ) ),
			array(
				'repeater',
				'home_hero_stats',
				'آمار زیر بالای صفحه',
				array(
					array( 'value' => '{years}', 'unit' => 'سال', 'label' => 'سابقه ساخت تابلو برق، تأسیس {founded}', 'icon' => 'calendar' ),
					array( 'value' => '{approvals}', 'unit' => 'تأییدیه', 'label' => 'توانیر، توزیع برق تهران و شرکت ملی مهندسی و ساختمان نفت', 'icon' => 'seal' ),
					array( 'value' => '{tests}', 'unit' => 'گزارش', 'label' => 'آزمون نوعی و لیسانس از EPIL و SAREL', 'icon' => 'report' ),
					array( 'value' => '{warranty}', 'unit' => 'ماه', 'label' => 'گارانتی؛ و {service} سال خدمات پس از فروش', 'icon' => 'warranty' ),
				),
				array(
					'help'   => 'ردیفی که عدد آن صفر یا خالی باشد نمایش داده نمی‌شود.',
					'button' => 'افزودن آمار',
					'sub'    => array(
						array( 'text', 'value', 'عدد', '', array( 'width' => 20 ) ),
						array( 'text', 'unit', 'واحد', '', array( 'width' => 15 ) ),
						array( 'text', 'label', 'توضیح', '', array( 'width' => 45 ) ),
						array( 'select', 'icon', 'آیکون', '', $psb_home_icon ),
					),
				),
			),

			array( 'tab', 'مراجع تأیید' ),
			array( 'text', 'home_issuers_label', 'متن کنار لوگوها', 'در فهرست سازندگان تأییدشده و آزمون‌شده نزد:', array( 'help' => 'لوگوها از تأییدیه‌ها و گزارش‌های آزمون منتشرشده خوانده می‌شوند.' ) ),

			array( 'tab', 'محصولات' ),
		),
		psb_pf_head_specs( 'home_products', 'محصولات', 'سه خط تولید، یک استاندارد ساخت', 'هر تابلو بر اساس نقشه تک‌خطی و مشخصات فنی پروژه طراحی می‌شود و پیش از تحویل، آزمون روتین کارخانه‌ای را می‌گذراند.', 'همه محصولات' ),
		array( array( 'tab', 'تأییدیه‌ها' ) ),
		psb_pf_head_specs( 'home_approvals', 'تأییدیه‌ها و آزمون‌های نوعی', 'اسنادی که در مناقصه به آن استناد می‌کنید', 'نام {brand} در فهرست سازندگان تأییدشده توانیر، شرکت‌های توزیع برق تهران و شرکت ملی مهندسی و ساختمان نفت ثبت است. اصل هر سند در سایت قابل مشاهده است.', 'همه تأییدیه‌ها' ),
		array(
			array( 'text', 'home_approvals_link2', 'متن لینک گزارش‌های آزمون', 'گزارش‌های آزمون نوعی', array( 'width' => 50 ) ),
			array( 'tab', 'گواهینامه‌ها' ),
		),
		psb_pf_head_specs( 'home_certificates', 'گواهینامه‌ها', 'گواهینامه‌ها و افتخارات', '', 'همه گواهینامه‌ها' ),
		array( array( 'tab', 'کارخانه و آزمایشگاه' ) ),
		psb_pf_head_specs( 'home_factory', 'کارخانه و آزمایشگاه', 'از ورق خام تا آزمون نهایی، در یک مجموعه', 'برش و خم CNC، رنگ پودری الکترواستاتیک، مونتاژ و آزمون روتین در کارخانه {brand} در تهران انجام می‌شود.', 'بازدید از خطوط تولید' ),
		array(
			array( 'message', '', 'کاشی‌ها', 'سه کاشی عکس به صفحه خدمات مربوط لینک می‌شوند. اگر عکس خالی بماند، تصویر شاخص همان خدمت نمایش داده می‌شود.' ),
			array( 'image', 'home_tile_cnc_image', 'کاشی بزرگ (ورق‌کاری) — عکس', '', array( 'width' => 30, 'help' => 'افقی ۱۶ به ۹' ) ),
			array( 'text', 'home_tile_cnc_eyebrow', 'برچسب', 'ورق‌کاری', array( 'width' => 20 ) ),
			array( 'text', 'home_tile_cnc_title', 'عنوان', 'برش، پانچ و خم CNC', array( 'width' => 50 ) ),
			array( 'textarea', 'home_tile_cnc_text', 'توضیح', 'قیچی، پرس‌برک و پانچ CNC ساخت AMADA ژاپن؛ از نقشه‌های ساخت واحد مهندسی تا قطعه آماده رنگ.', array( 'rows' => 2 ) ),
			array( 'image', 'home_tile_assembly_image', 'کاشی دوم (مونتاژ) — عکس', '', array( 'width' => 30, 'help' => 'افقی ۱۶ به ۹' ) ),
			array( 'text', 'home_tile_assembly_eyebrow', 'برچسب', 'مونتاژ', array( 'width' => 20 ) ),
			array( 'text', 'home_tile_assembly_title', 'عنوان', 'مونتاژ، شینه‌بندی و سیم‌بندی', array( 'width' => 50 ) ),
			array( 'textarea', 'home_tile_assembly_text', 'توضیح', '', array( 'rows' => 2 ) ),
			array( 'image', 'home_tile_lab_image', 'کاشی سوم (آزمایشگاه) — عکس', '', array( 'width' => 30, 'help' => 'افقی ۱۶ به ۹' ) ),
			array( 'text', 'home_tile_lab_eyebrow', 'برچسب', 'آزمایشگاه', array( 'width' => 20 ) ),
			array( 'text', 'home_tile_lab_title', 'عنوان', 'آزمون روتین در حضور خریدار', array( 'width' => 50 ) ),
			array( 'textarea', 'home_tile_lab_text', 'توضیح', 'تزریق جریان اولیه تا ۴۰۰۰ آمپر، میکرواهم‌متر و منابع تغذیه متغیر؛ برگه‌های آزمون امضاشده تحویل خریدار می‌شود.', array( 'rows' => 2 ) ),
			array(
				'repeater',
				'home_factory_facts',
				'ردیف ارقام زیر کاشی‌ها',
				array(
					array( 'value' => 'AMADA', 'label' => 'ماشین‌آلات CNC ورق‌کاری', 'icon' => 'cog' ),
					array( 'value' => 'Gema', 'label' => 'خط رنگ پودری الکترواستاتیک', 'icon' => 'factory' ),
					array( 'value' => '۴۰۰۰ آمپر', 'label' => 'تزریق جریان اولیه', 'icon' => 'bolt' ),
					array( 'value' => '۱۵ تا ۴۵ روز', 'label' => 'زمان ساخت پس از تأیید نقشه‌ها', 'icon' => 'clock' ),
				),
				array(
					'button' => 'افزودن ردیف',
					'sub'    => array(
						array( 'text', 'value', 'مقدار', '', array( 'width' => 30 ) ),
						array( 'text', 'label', 'توضیح', '', array( 'width' => 50 ) ),
						array( 'select', 'icon', 'آیکون', '', $psb_home_icon ),
					),
				),
			),
			array( 'tab', 'پروژه‌ها' ),
		),
		psb_pf_head_specs( 'home_projects', 'پروژه‌ها', 'پروژه‌های اجراشده', '', 'همه پروژه‌ها' ),
		array( array( 'tab', 'رضایت‌نامه‌ها' ) ),
		psb_pf_head_specs( 'home_letters', 'رضایت‌نامه‌ها و حسن انجام کار', 'رضایت مشتریان', '', 'همه رضایت‌نامه‌ها' ),
		array(
			array( 'tab', 'درباره شرکت' ),
			array(
				'group',
				'about_us',
				'بخش درباره شرکت',
				'',
				array(
					'key' => 'field_6748e4b44691d',
					'sub' => array(
						array( 'text', 'eyebrow', 'برچسب بالای عنوان', 'درباره {brand}', array( 'width' => 35 ) ),
						array( 'text', 'heading', 'عنوان', 'نزدیک به {years} سال تابلوسازی', array( 'width' => 65 ) ),
						array( 'wysiwyg', 'content', 'متن', '', array( 'key' => 'field_6748e4d84691f' ) ),
						array( 'image', 'image', 'عکس', '', array( 'key' => 'field_6748e55446924', 'width' => 40, 'help' => 'عمودی ۴ به ۵ (مثلاً ۹۶۰ × ۱۲۰۰). در موبایل به‌صورت افقی برش می‌خورد؛ موضوع اصلی وسط کادر باشد.' ) ),
						array( 'text', 'image_alt', 'توضیح عکس (برای موتورهای جستجو)', 'سیم‌بندی و مونتاژ تجهیزات داخل تابلو برق ساخت {brand}', array( 'width' => 60 ) ),
						array( 'text', 'caption', 'برچسب روی عکس', 'کارخانه {brand} — از {founded}', array( 'width' => 60 ) ),
						array( 'link', 'btn_link', 'دکمه', '', array( 'key' => 'field_6748e51446922', 'width' => 40, 'help' => 'متن دکمه همان «متن لینک» است. خالی = «درباره شرکت» به صفحه درباره ما.' ) ),
					),
				),
			),
			array(
				'repeater',
				'home_timeline',
				'خط زمانی',
				array(
					array( 'year' => '{founded}', 'title' => 'تأسیس شرکت {brand}' ),
					array( 'year' => '۱۳۶۴', 'title' => 'پروانه بهره‌برداری از وزارت صنایع' ),
					array( 'year' => '۱۳۸۲', 'title' => 'آزمون تابلوهای ۲۴ کیلوولت متال‌کلد در EPIL' ),
					array( 'year' => '۱۳۸۵', 'title' => 'تأیید کامل در ارزیابی سازندگان تابلو برق' ),
					array( 'year' => '۱۳۹۲', 'title' => 'ثبت در وندورلیست شرکت ملی مهندسی و ساختمان نفت' ),
					array( 'year' => '۱۳۹۳', 'title' => 'گزارش‌های آزمون نوعی EPIL و لیسانس SAREL' ),
					array( 'year' => '۱۳۹۷', 'title' => 'آزمون نوعی تابلو ایزوله بیمارستانی' ),
					array( 'year' => '۱۳۹۸–۱۳۹۹', 'title' => 'تأییدیه‌های شرکت‌های توزیع برق و توانیر' ),
					array( 'year' => '{year}', 'title' => '{years} سال تابلوسازی' ),
				),
				array(
					'help'   => 'آخرین ردیف به‌عنوان «امروز» برجسته می‌شود.',
					'button' => 'افزودن سال',
					'sub'    => array(
						array( 'text', 'year', 'سال', '', array( 'width' => 25 ) ),
						array( 'text', 'title', 'رویداد', '', array( 'width' => 75 ) ),
					),
				),
			),
			array( 'tab', 'مقالات' ),
		),
		psb_pf_head_specs( 'home_blog', 'مقالات', 'مقالات آموزشی تابلو برق', '', 'همه مقالات' ),
		array( array( 'tab', 'پرسش‌های متداول' ) ),
		psb_pf_head_specs( 'home_faq', 'پرسش‌های متداول', 'پاسخ پرسش‌های پرتکرار' ),
		array( array( 'message', '', 'پرسش‌ها', 'خود پرسش و پاسخ‌ها در کادر «سوالات متداول» پایین همین صفحه ویرایش می‌شوند.' ) )
	)
);
unset( $psb_home_icon );
