<?php
/**
 * About (spec v2 §5.7): alt background, 6/6. Photo 4:5 (attachment 1364 or ACF about image),
 * ACF paragraph, verified timeline (company-facts.md), secondary «درباره ما».
 */
$psb_about = psb_field( 'about_us', null, (int) get_option( 'page_on_front' ) ?: null );
$psb_about = is_array( $psb_about ) ? $psb_about : array();
$psb_img   = wp_attachment_is_image( 1364 ) ? 1364 : ( is_array( $psb_about['image'] ?? null ) ? (int) ( $psb_about['image']['ID'] ?? 0 ) : (int) ( $psb_about['image'] ?? 0 ) );
$psb_link  = is_array( $psb_about['btn_link'] ?? null ) ? ( $psb_about['btn_link']['url'] ?? '' ) : '';
$psb_link  = $psb_link ? $psb_link : home_url( '/about-us/' );
$psb_brand = psb_brand_name();
$psb_now   = psb_jalali_year();
$psb_steps = apply_filters(
	'psb_home_timeline',
	array(
		array( psb_fa_num( PSB_FOUNDED_JALALI ), 'تأسیس شرکت ' . $psb_brand ),
		array( '۱۳۶۴', 'پروانه بهره‌برداری از وزارت صنایع' ),
		array( '۱۳۸۲', 'آزمون تابلوهای ۲۴ کیلوولت متال‌کلد در EPIL' ),
		array( '۱۳۸۵', 'تأیید کامل در ارزیابی سازندگان تابلو برق' ),
		array( '۱۳۹۲', 'ثبت در وندورلیست شرکت ملی مهندسی و ساختمان نفت' ),
		array( '۱۳۹۳', 'گزارش‌های آزمون نوعی EPIL و لیسانس SAREL' ),
		array( '۱۳۹۷', 'آزمون نوعی تابلو ایزوله بیمارستانی' ),
		array( '۱۳۹۸–۱۳۹۹', 'تأییدیه‌های شرکت‌های توزیع برق و توانیر' ),
		array( psb_fa_num( $psb_now ), psb_fa_num( psb_years_active() ) . ' سال تابلوسازی' ),
	)
);
?>
<section class="bg-background py-section" aria-labelledby="home-about-title">
	<div class="container grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
		<div data-reveal>
			<span class="eyebrow">درباره <?php echo esc_html( $psb_brand ); ?></span>
			<h2 id="home-about-title" class="section-title mt-3"><?php echo esc_html( 'نزدیک به ' . psb_fa_num( psb_years_active() ) . ' سال تابلوسازی' ); ?></h2>
			<?php if ( ! empty( $psb_about['content'] ) ) : ?>
				<div class="mt-4 max-w-[38rem] text-[1.0625rem] leading-[1.9] text-slate-600 [&_p]:mb-3">
					<?php echo wp_kses_post( psb_brand_fix( $psb_about['content'] ) ); ?>
				</div>
			<?php endif; ?>

			<ol class="timeline mt-8">
				<?php foreach ( $psb_steps as $psb_i => $psb_s ) : ?>
					<li class="timeline-item <?php echo count( $psb_steps ) - 1 === $psb_i ? 'is-current' : ''; ?>">
						<span class="timeline-year"><?php echo esc_html( $psb_s[0] ); ?></span>
						<span class="timeline-title"><?php echo wp_kses( preg_replace( '/\b(EPIL|SAREL)\b/', '<span class="lat">$1</span>', esc_html( $psb_s[1] ) ), array( 'span' => array( 'class' => true ) ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ol>

			<a class="btn btn-secondary mt-8" href="<?php echo esc_url( $psb_link ); ?>">درباره شرکت</a>
		</div>

		<?php if ( $psb_img ) : ?>
			<div class="mx-auto w-full max-w-md lg:max-w-none" data-reveal>
				<?php echo psb_image( $psb_img, 'large', array( 'class' => 'aspect-[4/5] w-full rounded-card object-cover', 'alt' => get_post_meta( $psb_img, '_wp_attachment_image_alt', true ) ?: 'کارگاه ' . $psb_brand, 'sizes' => '(min-width: 1024px) 600px, 90vw' ) ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>
	</div>
</section>
