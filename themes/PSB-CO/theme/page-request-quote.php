<?php
/**
 * Template Name: استعلام قیمت
 *
 * Quote request (RFQ) page: intro + trust facts, multi-step wizard (inc/rfq), process, FAQ.
 * Facts used here come only from the site / owner-confirmed list (SCRATCH/company-facts.md).
 *
 * @package PSB-CO
 */

add_filter( 'psb_show_cta_band', '__return_false' );
get_header();

$psb_brand   = function_exists( 'psb_brand_name' ) ? psb_brand_name() : 'پارس سوئیچ بورد';
$psb_founded = defined( 'PSB_FOUNDED_JALALI' ) ? PSB_FOUNDED_JALALI : 1355;
$psb_years   = function_exists( 'psb_years_active' ) ? psb_years_active() : 50;
$psb_fa      = function_exists( 'psb_rfq_fa' ) ? 'psb_rfq_fa' : static function ( $v ) {
	return $v;
};

// Hero facts: plain text in a ruled row (spec §6.7 — no pills).
$psb_facts = array(
	sprintf( 'تأسیس %s', $psb_fa( $psb_founded ) ),
	'در فهرست توانیر',
	'آزمون نوعی EPIL',
	'گارانتی ' . psb_fa_num( PSB_WARRANTY_MONTHS ) . ' ماه',
);

get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'title'   => get_the_title() ? get_the_title() : 'استعلام قیمت تابلو برق',
		'eyebrow' => 'استعلام قیمت و ثبت سفارش',
		'lead'    => 'نوع تابلو، مشخصات فنی یا نقشه تک‌خطی پروژه را ارسال کنید تا کارشناسان فروش و مهندسی ' . $psb_brand . ' پیشنهاد فنی و قیمت را برای شما آماده کنند. ثبت درخواست چند دقیقه طول می‌کشد و همه فیلدهای فنی اختیاری است.',
		'facts'   => $psb_facts,
	)
);

$psb_trust = array(
	sprintf( 'نزدیک به %s سال طراحی و تولید تابلو برق، از سال %s', $psb_fa( $psb_years ), $psb_fa( $psb_founded ) ),
	'تأییدیه توانیر و شرکت‌های توزیع نیروی برق استان تهران و تهران بزرگ برای تابلوهای فشار ضعیف، فشار متوسط، کمپکت و کنتوری',
	'تأییدیه تابلو فشار ضعیف از شرکت ملی مهندسی و ساختمان نفت ایران',
	'گزارش‌های آزمون نوعی آزمایشگاه EPIL: درجه حفاظت IP54 و IP65 و تابلو ایزوله بیمارستانی',
	'مجوز ساخت تابلوهای فشار متوسط SYStem6 از SAREL ایتالیا',
	'دارای گواهینامه ISO 9001',
	'آزمون‌های روتین در آزمایشگاه برق کارخانه، با امکان حضور نماینده خریدار',
	'گارانتی ' . psb_fa_num( PSB_WARRANTY_MONTHS ) . ' ماه و ' . psb_fa_num( PSB_SERVICE_YEARS ) . ' سال خدمات پس از فروش',
);

$psb_phones = function_exists( 'psb_phones' ) ? psb_phones() : array();
$psb_sales  = function_exists( 'psb_rfq_opt' ) ? psb_rfq_opt( 'sales_phone' ) : '';
$psb_wa     = function_exists( 'psb_rfq_opt' ) ? psb_rfq_opt( 'sales_whatsapp' ) : '';
?>

<section class="rfq-band" aria-label="فرم استعلام قیمت">
	<div class="container rfq-page">
		<div class="rfq-main">
			<?php
			if ( function_exists( 'psb_rfq_wizard' ) ) {
				echo psb_rfq_wizard(); // phpcs:ignore -- template output.
			} else {
				echo '<p>فرم استعلام در حال حاضر در دسترس نیست. لطفاً با واحد فروش تماس بگیرید.</p>';
			}
			?>
		</div>

		<aside class="rfq-aside" aria-label="اطلاعات تکمیلی">
			<div class="rfq-box">
				<h2>چرا <?php echo esc_html( $psb_brand ); ?>؟</h2>
				<ul class="rfq-trust">
					<?php
					$psb_trust_icons = array( 'calendar', 'seal', 'seal', 'report', 'award', 'iso', 'flask', 'warranty' );
					foreach ( $psb_trust as $psb_ti => $psb_t ) :
						?>
						<li class="has-icon"><?php echo psb_icon( $psb_trust_icons[ $psb_ti ] ?? 'check', 'mt-0.5 size-5 shrink-0 text-slate-600' ); // phpcs:ignore ?><span><?php echo function_exists( 'psb_lat' ) ? psb_lat( $psb_t ) : esc_html( $psb_t ); // phpcs:ignore -- escaped by psb_lat(). ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="rfq-box">
				<h2>روند استعلام تا تحویل</h2>
				<ol class="rfq-process">
					<li><span><strong>ثبت درخواست</strong>نوع تابلو، مشخصات و مدارک فنی</span></li>
					<li><span><strong>بررسی فنی</strong>تماس کارشناس برای تکمیل اطلاعات</span></li>
					<li><span><strong>پیشنهاد فنی و پیش‌فاکتور</strong>مطابق مشخصات کارفرما یا مشاور</span></li>
					<li><span><strong>تأیید نقشه‌های جانمایی و ثبت سفارش</strong></span></li>
					<li><span><strong>تولید و آزمون</strong>معمولاً ۱۵ تا ۴۵ روز کاری، بسته به نوع و تعداد تابلوها</span></li>
					<li><span><strong>ارسال</strong>به همه شهرهای کشور</span></li>
				</ol>
			</div>

			<div class="rfq-box rfq-contact">
				<h2>تماس مستقیم با واحد فروش</h2>
				<?php
				$psb_printed = 0;
				foreach ( array_slice( (array) $psb_phones, 0, 3 ) as $psb_p ) {
					if ( empty( $psb_p['href'] ) ) {
						continue;
					}
					$psb_printed++;
					printf(
						'<a href="%s"><span>%s</span><b dir="ltr">%s</b></a>',
						esc_url( $psb_p['href'] ),
						esc_html( ! empty( $psb_p['title'] ) ? $psb_p['title'] : 'تلفن' ),
						esc_html( $psb_fa( function_exists( 'psb_format_phone' ) ? psb_format_phone( $psb_p['number'] ) : $psb_p['number'] ) )
					);
				}
				if ( ! $psb_printed && $psb_sales && function_exists( 'psb_rfq_tel' ) ) {
					printf( '<a href="%s"><span>واحد فروش</span><b dir="ltr">%s</b></a>', esc_url( psb_rfq_tel( $psb_sales ) ), esc_html( $psb_fa( $psb_sales ) ) );
				}
				if ( $psb_wa && function_exists( 'psb_rfq_intl' ) ) {
					printf( '<a href="%s" target="_blank" rel="noopener"><span>واتساپ</span><b dir="ltr">%s</b></a>', esc_url( 'https://wa.me/' . psb_rfq_intl( $psb_wa ) ), esc_html( $psb_fa( $psb_wa ) ) );
				}
				?>
			</div>
		</aside>
	</div>
</section>

<?php
while ( have_posts() ) :
	the_post();
	if ( '' !== trim( (string) get_the_content() ) ) :
		?>
		<section class="container rfq-section">
			<div class="<?php echo esc_attr( defined( 'BLUEBOX_TYPOGRAPHY_CLASSES' ) ? BLUEBOX_TYPOGRAPHY_CLASSES : '' ); ?>">
				<?php the_content(); ?>
			</div>
		</section>
		<?php
	endif;
endwhile;

$psb_faq = array(
	array(
		'question' => 'برای دریافت قیمت دقیق چه مدارکی لازم است؟',
		'answer'   => 'دقیق‌ترین مبنا نقشه تک‌خطی (Single Line Diagram)، جدول مقادیر (BOQ) و مشخصات فنی مشاور یا کارفرماست. اگر این مدارک را ندارید، نوع تابلو، جریان نامی و کاربری را بنویسید یا تصویر تابلوی موجود را ارسال کنید؛ کارشناسان فروش برای تکمیل اطلاعات تماس می‌گیرند.',
	),
	array(
		'question' => 'آیا تکمیل فرم بدون آشنایی فنی امکان‌پذیر است؟',
		'answer'   => 'بله. همه فیلدهای فنی اختیاری‌اند و برای هر کدام گزینه «نمی‌دانم / طبق نقشه» وجود دارد. فقط نام و شماره موبایل الزامی است.',
	),
	array(
		'question' => 'پیشنهاد قیمت چه زمانی ارسال می‌شود؟',
		'answer'   => 'پس از ثبت درخواست، کارشناس فروش برای بررسی و تکمیل اطلاعات با شما تماس می‌گیرد. زمان آماده‌شدن پیشنهاد فنی و پیش‌فاکتور به پیچیدگی پروژه و کامل بودن مدارک بستگی دارد. با کد پیگیری می‌توانید وضعیت درخواست را از واحد فروش بپرسید.',
	),
	array(
		'question' => 'زمان ساخت تابلو چقدر است؟',
		'answer'   => 'زمان تولید بسته به نوع و تعداد تابلوها معمولاً ۱۵ تا ۴۵ روز کاری است و از تأیید نقشه‌های جانمایی و ثبت سفارش محاسبه می‌شود. زمان دقیق هر پروژه در پیش‌فاکتور اعلام می‌شود.',
	),
	array(
		'question' => 'آیا انتخاب برند تجهیزات امکان‌پذیر است؟',
		'answer'   => 'بله. برند کلیدها و تجهیزات مطابق مشخصات کارفرما یا مشاور انتخاب می‌شود. در فرم، برند مورد نظر را علامت‌گذاری کنید یا «بدون ترجیح» را انتخاب کنید تا گزینه‌های مناسب پیشنهاد شود.',
	),
	array(
		'question' => 'آیا تابلوها پیش از تحویل آزمایش می‌شوند؟',
		'answer'   => 'بله. آزمون‌های روتین در آزمایشگاه برق کارخانه انجام می‌شود و نماینده خریدار می‌تواند در آزمون‌ها حضور داشته باشد. برگه‌های آزمون امضاشده به خریدار تحویل داده می‌شود.',
	),
	array(
		'question' => 'آیا نصب و راه‌اندازی در محل پروژه نیز انجام می‌شود؟',
		'answer'   => 'بله. تابلوها در کارخانه ساخته و آزمایش می‌شوند و به همه شهرهای کشور ارسال می‌شوند؛ در صورت نیاز، نصب و راه‌اندازی در محل پروژه هم توسط کارشناسان شرکت انجام می‌شود. در فرم، گزینه «خدمات مورد نیاز» را مشخص کنید.',
	),
	array(
		'question' => 'آیا اطلاعات و مدارک ارسالی محرمانه می‌ماند؟',
		'answer'   => 'بله. فایل‌ها در فضای غیرعمومی سرور ذخیره می‌شوند و فقط کارشناسان فروش و مهندسی برای تهیه پیشنهاد به آن‌ها دسترسی دارند.',
	),
);
?>
<section class="container rfq-section" aria-labelledby="rfq-faq-title">
	<div class="section-head">
		<span class="eyebrow">پرسش‌های رایج</span>
		<h2 id="rfq-faq-title" class="section-title">پرسش‌های رایج درباره استعلام قیمت</h2>
	</div>
	<?php get_template_part( 'template-parts/global/faq-list', null, array( 'items' => $psb_faq ) ); ?>
</section>

<?php
get_footer();
