<?php
/**
 * About page fields (template about-us.php; group «تنظیمات صفحه درباره ما», key of the original
 * DB group). One tab per section, in page order. The intro text is the page's own editor content.
 *
 * Timeline links may be a URL or «archive:<post type>» / «doc:<post type>:<slug>»; those are
 * resolved by psb_pf_url() (and written as real URLs when the rows are seeded).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Resolve a link value: URL, «archive:<type>» or «doc:<type>:<slug>» (falls back to the archive). */
function psb_pf_url( $value ) {
	$value = trim( (string) $value );
	if ( 0 === strpos( $value, 'archive:' ) ) {
		return psb_archive_url( substr( $value, 8 ) );
	}
	if ( 0 === strpos( $value, 'doc:' ) ) {
		list( , $type, $slug ) = array_pad( explode( ':', $value, 3 ), 3, '' );
		$p = get_page_by_path( $slug, OBJECT, $type );
		return ( $p && 'publish' === $p->post_status ) ? get_permalink( $p ) : psb_archive_url( $type );
	}
	return $value;
}

$psb_about_icon = array( 'choices' => psb_pf_icon_choices(), 'width' => 20, 'key' => 'field_value_icon' );

psb_pf_register(
	'group_about_page_fields',
	'محتوای صفحه درباره ما',
	array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'about-us.php' ) ) ),
	array_merge(
		array(
			array( 'tab', 'بالای صفحه' ),
			array( 'message', '', 'راهنما', '', array( 'key' => 'field_psbpf_help_about', 'note' => '<p>عنوان بالای صفحه همان «عنوان برگه» است و متن بخش «معرفی شرکت» در ویرایشگر اصلی برگه نوشته می‌شود.</p>' ) ),
			array( 'image', 'about_hero_image', 'عکس پس‌زمینه بالای صفحه', '', array( 'width' => 40, 'help' => 'افقی، حداقل ۱۶۰۰ پیکسل عرض. روی عکس لایه تیره قرار می‌گیرد. خالی = بالای صفحه ساده و روشن.' ) ),
			array( 'text', 'about_hero_badge', 'برچسب بالای عنوان', 'فعال در صنعت تابلوسازی برق از سال {founded}', array( 'width' => 60, 'key' => 'field_about_hero_badge' ) ),
			array( 'textarea', 'about_hero_subtitle', 'متن کوتاه زیر عنوان', '', array( 'key' => 'field_about_hero_subtitle' ) ),
			array( 'text', 'about_hero_btn1', 'متن دکمه استعلام قیمت', 'استعلام قیمت', array( 'width' => 50 ) ),
			array( 'text', 'about_hero_btn2', 'متن دکمه محصولات', 'محصولات', array( 'width' => 50 ) ),

			array( 'tab', 'آمار' ),
			array(
				'repeater',
				'about_stats',
				'آمار کلیدی',
				array(),
				array(
					'key'    => 'field_about_stats',
					'button' => 'افزودن آمار',
					'sub'    => array(
						array( 'text', 'number', 'عدد', '', array( 'width' => 30, 'key' => 'field_stat_number' ) ),
						array( 'text', 'label', 'عنوان', '', array( 'width' => 70, 'key' => 'field_stat_label' ) ),
					),
				),
			),

			array( 'tab', 'معرفی شرکت' ),
			array( 'text', 'about_intro_eyebrow', 'برچسب بالای عنوان', 'معرفی شرکت', array( 'width' => 35 ) ),
			array( 'text', 'about_intro_title', 'عنوان', '{brand}؛ {years} سال تابلوسازی', array( 'width' => 65 ) ),
			array( 'image', 'about_intro_image', 'عکس کنار متن', '', array( 'width' => 40, 'help' => 'عمودی ۴ به ۵. خالی = اولین عکس گالری کارخانه، یا عکس پیش‌فرض.' ) ),
			array( 'message', '', 'متن معرفی', 'متن این بخش در ویرایشگر اصلی برگه (بالای همین کادر) نوشته می‌شود.' ),

			array( 'tab', 'تاریخچه' ),
			array( 'text', 'about_timeline_eyebrow', 'برچسب بالای عنوان', 'مسیر شرکت', array( 'width' => 35 ) ),
			array( 'text', 'about_timeline_title', 'عنوان', 'از {founded} تا امروز', array( 'width' => 65 ) ),
			array( 'textarea', 'about_timeline_lead', 'متن کوتاه زیر عنوان', 'مهم‌ترین نقطه‌های مسیر شرکت، بر اساس اسنادی که در همین سایت منتشر شده است.', array( 'rows' => 2 ) ),
			array(
				'repeater',
				'about_timeline',
				'رویدادها',
				array(
					array( 'year' => '{founded}', 'title' => 'تأسیس شرکت', 'text' => 'آغاز فعالیت شرکت تولیدی و صنعتی پارس سوئیچ بورد در طراحی و ساخت تابلوهای برق.', 'link' => '' ),
					array( 'year' => '۱۳۶۴', 'title' => 'پروانه بهره‌برداری از وزارت صنایع', 'text' => 'صدور کارت شناسایی کارگاه، معادل پروانه بهره‌برداری، برای تولید تابلوهای برق فشار ضعیف.', 'link' => '' ),
					array( 'year' => '۱۳۶۸', 'title' => 'توسعه ظرفیت تولید', 'text' => 'اصلاح ظرفیت تولید به ۱۲۰۰ سلول در سال در یک شیفت، شامل تابلوهای فشار متوسط، فشار ضعیف و MCC.', 'link' => '' ),
					array( 'year' => '۱۳۸۰', 'title' => 'تأمین‌کننده نمونه صنایع شیر ایران', 'text' => 'انتخاب به‌عنوان تأمین‌کننده نمونه ماشین‌آلات و تجهیزات صنایع شیر ایران (پگاه).', 'link' => '' ),
					array( 'year' => '۱۳۸۲', 'title' => 'آزمون تابلوهای ۲۴ کیلوولت متال‌کلد', 'text' => 'قبولی در آزمون استقامت عایقی فرکانس قدرت تابلوهای ۲۴ کیلوولت در آزمایشگاه EPIL.', 'link' => 'archive:type-test' ),
					array( 'year' => '۱۳۸۳', 'title' => 'آزمون نوعی باس‌داکت ۴۰۰۰ آمپر', 'text' => 'گواهی آزمون نوعی باس‌داکت 4000 A / 400 V مطابق IEC 60439-1 و IEC 60439-2 از آزمایشگاه EPIL.', 'link' => 'archive:type-test' ),
					array( 'year' => '۱۳۸۵', 'title' => 'ارزیابی سازندگان تابلو برق', 'text' => 'کسب تأیید کامل در هر شش گروه، شامل تابلوهای فشار ضعیف و فشار متوسط ثابت و کشویی، در ارزیابی انجمن صنفی تولیدکنندگان تابلوهای برق ایران.', 'link' => '' ),
					array( 'year' => '۱۳۹۲', 'title' => 'ثبت در وندورلیست شرکت ملی مهندسی و ساختمان نفت ایران', 'text' => 'تابلوهای فشار ضعیف ثابت تا ۴۰۰ ولت.', 'link' => 'doc:confirmation:low-voltage-switchboard-approval-national-iranian-oil-engineering-and-construction-company' ),
					array( 'year' => '۱۳۹۳', 'title' => 'گزارش‌های آزمون EPIL و لیسانس SAREL', 'text' => 'آزمون درجه حفاظت IP54 و IP65 بدنه‌ها و حق ساخت تابلوهای فشار متوسط SYStem6.', 'link' => 'archive:type-test' ),
					array( 'year' => '۱۳۹۷', 'title' => 'آزمون تابلو ایزوله بیمارستانی', 'text' => 'آزمون ایمنی بر اساس IEC 60601-1 در آزمایشگاه EPIL با نتیجه قبول.', 'link' => 'archive:type-test' ),
					array( 'year' => '۱۳۹۸–۱۳۹۹', 'title' => 'تأییدیه‌های توانیر و شرکت‌های توزیع نیروی برق', 'text' => 'درج در فهرست سازندگان تأییدشده توانیر و تأییدیه‌های شرکت‌های توزیع نیروی برق استان تهران و تهران بزرگ.', 'link' => 'doc:confirmation:tavanir-approval' ),
					array( 'year' => '{year}', 'title' => '{years} سال فعالیت', 'text' => 'طراحی، ساخت، آزمون و نصب تابلوهای فشار متوسط، فشار ضعیف و فرمان و کنترل برای شبکه توزیع، صنایع و ساختمان‌ها.', 'link' => '' ),
				),
				array(
					'help'   => 'آخرین ردیف به‌عنوان «امروز» برجسته می‌شود. «لینک سند» اختیاری است و دکمه «مشاهده سند» می‌سازد.',
					'button' => 'افزودن رویداد',
					'layout' => 'block',
					'sub'    => array(
						array( 'text', 'year', 'سال', '', array( 'width' => 20 ) ),
						array( 'text', 'title', 'عنوان', '', array( 'width' => 80 ) ),
						array( 'textarea', 'text', 'توضیح', '', array( 'rows' => 2, 'width' => 70 ) ),
						array( 'text', 'link', 'لینک سند', '', array( 'width' => 30 ) ),
					),
				),
			),

			array( 'tab', 'ارزش‌ها' ),
			array( 'text', 'about_values_eyebrow', 'برچسب بالای عنوان', 'ارزش‌ها', array( 'width' => 35 ) ),
			array( 'text', 'about_values_title', 'عنوان', 'آنچه به آن متعهدیم', array( 'width' => 65 ) ),
			array(
				'repeater',
				'about_values',
				'ارزش‌ها و مأموریت',
				array(),
				array(
					'key'    => 'field_about_values',
					'button' => 'افزودن مورد',
					'layout' => 'block',
					'sub'    => array(
						array( 'select', 'icon', 'آیکون', '', $psb_about_icon ),
						array( 'text', 'title', 'عنوان', '', array( 'width' => 80, 'key' => 'field_value_title' ) ),
						array( 'textarea', 'description', 'توضیح', '', array( 'rows' => 2, 'key' => 'field_value_description' ) ),
					),
				),
			),

			array( 'tab', 'توانمندی‌ها' ),
			array( 'text', 'about_cap_eyebrow', 'برچسب بالای عنوان', 'توانمندی‌ها', array( 'width' => 35 ) ),
			array( 'text', 'about_cap_title', 'عنوان', 'محصولات و امکانات تولید', array( 'width' => 65 ) ),
			array( 'textarea', 'about_cap_lead', 'متن کوتاه زیر عنوان', 'گروه‌های تابلویی که در فهرست سازندگان توانیر برای شرکت درج شده و امکاناتی که همه مراحل ساخت در آن انجام می‌شود.', array( 'rows' => 2 ) ),
			array( 'text', 'about_cap_list_title', 'عنوان فهرست اول', 'گروه‌های درج‌شده در فهرست توانیر', array( 'width' => 50 ) ),
			array(
				'repeater',
				'about_cap_list',
				'موارد فهرست اول',
				array(
					array( 'item' => 'تابلو سنجش و توزیع' ),
					array( 'item' => 'تابلو فشار ضعیف ثابت' ),
					array( 'item' => 'تابلو فشار متوسط سنتی' ),
					array( 'item' => 'تابلو فشار متوسط کمپکت' ),
					array( 'item' => 'تابلو کنترل و پروسس' ),
					array( 'item' => 'تابلو حفاظت و کنترل نیروگاهی' ),
				),
				array(
					'button' => 'افزودن مورد',
					'sub'    => array( array( 'text', 'item', 'مورد', '' ) ),
				),
			),
			array( 'text', 'about_cap_products_title', 'عنوان ستون محصولات', 'محصولات', array( 'width' => 50, 'help' => 'فهرست از محصولات منتشرشده خوانده می‌شود.' ) ),
			array( 'text', 'about_cap_services_title', 'عنوان ستون خدمات', 'کارخانه و آزمایشگاه', array( 'width' => 50, 'help' => 'فهرست از خدمات منتشرشده خوانده می‌شود.' ) ),

			array( 'tab', 'اعتبارنامه‌ها' ),
			array( 'text', 'about_creds_eyebrow', 'برچسب بالای عنوان', 'اعتبارنامه‌ها', array( 'width' => 35 ) ),
			array( 'text', 'about_creds_title', 'عنوان', 'تأییدیه‌ها و گزارش‌های آزمون', array( 'width' => 65 ) ),
			array( 'text', 'about_creds_link', 'متن لینک «مشاهده همه»', 'همه تأییدیه‌ها', array( 'width' => 50 ) ),
			array( 'text', 'about_creds_note', 'متن زیر فهرست', 'سیستم مدیریت کیفیت: دارای گواهینامه ISO 9001', array( 'width' => 50 ) ),

			array( 'tab', 'مدیران' ),
			array( 'text', 'about_team_eyebrow', 'برچسب بالای عنوان', 'مدیریت', array( 'width' => 35 ) ),
			array( 'text', 'about_team_title', 'عنوان', 'مدیران شرکت', array( 'width' => 65 ) ),
			array(
				'repeater',
				'owners',
				'مدیران',
				array(),
				array(
					'key'    => 'field_about_owners',
					'button' => 'افزودن مدیر',
					'sub'    => array(
						array( 'image', 'image', 'عکس پرتره', '', array( 'width' => 20, 'key' => 'field_owner_image', 'help' => 'مربع' ) ),
						array( 'text', 'name', 'نام و نام خانوادگی', '', array( 'width' => 40, 'key' => 'field_owner_name' ) ),
						array( 'text', 'position', 'سمت', '', array( 'width' => 40, 'key' => 'field_owner_position' ) ),
					),
				),
			),

			array( 'tab', 'گالری کارخانه' ),
			array( 'text', 'about_gallery_title', 'عنوان', 'تصاویر کارخانه', array( 'width' => 50 ) ),
			array( 'gallery', 'about_factory_gallery', 'تصاویر', '', array( 'key' => 'field_about_factory_gallery' ) ),

			array( 'tab', 'پرسش‌های متداول' ),
			array( 'text', 'about_faq_title', 'عنوان', 'پرسش‌های متداول', array( 'width' => 50 ) ),
			array( 'message', '', 'پرسش‌ها', 'خود پرسش و پاسخ‌ها در کادر «سوالات متداول» پایین همین صفحه ویرایش می‌شوند.' ),
		)
	)
);
unset( $psb_about_icon );
