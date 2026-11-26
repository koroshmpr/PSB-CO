<?php
/**
 * About (spec v2 §5.7): alt background, 6/6. Theme photo set «about» (panel assembly, live 1921/1922):
 * 4:5 and sticky beside the timeline at lg+, 16:9 between the intro and the timeline below lg.
 * ACF paragraph, verified timeline (company-facts.md) with scroll progress (javascript/modules/timeline.js),
 * secondary «درباره شرکت».
 */
$psb_about = psb_field( 'about_us', null, (int) get_option( 'page_on_front' ) ?: null );
$psb_about = is_array( $psb_about ) ? $psb_about : array();
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
	<div class="container grid gap-8 lg:grid-cols-2 lg:grid-rows-[auto_1fr] lg:gap-x-16 lg:gap-y-0">
		<div data-reveal>
			<span class="eyebrow">درباره <?php echo esc_html( $psb_brand ); ?></span>
			<h2 id="home-about-title" class="section-title mt-3"><?php echo esc_html( 'نزدیک به ' . psb_fa_num( psb_years_active() ) . ' سال تابلوسازی' ); ?></h2>
			<?php if ( ! empty( $psb_about['content'] ) ) : ?>
				<div class="mt-4 max-w-[38rem] text-[1.0625rem] leading-[1.9] text-slate-600 [&_p]:mb-3">
					<?php echo wp_kses_post( psb_brand_fix( $psb_about['content'] ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<figure class="about-photo lg:col-start-2 lg:row-span-2 lg:row-start-1" data-reveal>
			<?php
			echo psb_photo_picture( // phpcs:ignore
				'about',
				array(
					'class' => 'aspect-video w-full object-cover object-[50%_60%] lg:aspect-[4/5] lg:object-center',
					'alt'   => 'سیم‌بندی و مونتاژ تجهیزات داخل تابلو برق ساخت ' . $psb_brand,
				)
			);
			?>
			<figcaption class="about-photo-cap"><?php echo esc_html( 'کارخانه ' . $psb_brand . ' — از ' . psb_fa_num( PSB_FOUNDED_JALALI ) ); ?></figcaption>
		</figure>

		<div class="lg:col-start-1 lg:row-start-2">
			<ol class="timeline lg:mt-8" data-timeline>
				<?php foreach ( $psb_steps as $psb_i => $psb_s ) : ?>
					<li class="timeline-item <?php echo count( $psb_steps ) - 1 === $psb_i ? 'is-current' : ''; ?>">
						<span class="timeline-year"><?php echo esc_html( $psb_s[0] ); ?></span>
						<span class="timeline-title"><?php echo wp_kses( preg_replace( '/\b(EPIL|SAREL)\b/', '<span class="lat">$1</span>', esc_html( $psb_s[1] ) ), array( 'span' => array( 'class' => true ) ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ol>

			<a class="btn btn-secondary mt-8" href="<?php echo esc_url( $psb_link ); ?>">درباره شرکت</a>
		</div>
	</div>
</section>
