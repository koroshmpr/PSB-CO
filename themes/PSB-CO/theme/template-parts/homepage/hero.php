<?php
/**
 * Home hero (spec v2 §5.1): full-bleed graphite photo, value-proposition H1, 2 CTAs + direct line,
 * ruled 4-stat trust row. Static (no slider / no reveal) — the photo is the LCP image
 * (eager, fetchpriority=high, preloaded in header.php with the same srcset/sizes).
 * Counts come from the CPTs; other facts are verified (SCRATCH/company-facts.md).
 */
$psb_image = psb_home_hero_image();
$psb_phone = psb_primary_phone();
$psb_count = function ( $type ) {
	return post_type_exists( $type ) ? (int) ( wp_count_posts( $type )->publish ?? 0 ) : 0;
};
$psb_approvals = $psb_count( 'confirmation' );
$psb_tests     = $psb_count( 'type-test' );

$psb_stats = array(
	array( psb_fa_num( psb_years_active() ), 'سال', 'سابقه ساخت تابلو برق، تأسیس ' . psb_fa_num( PSB_FOUNDED_JALALI ), 'calendar' ),
);
if ( $psb_approvals ) {
	$psb_stats[] = array( psb_fa_num( $psb_approvals ), 'تأییدیه', 'توانیر، توزیع برق تهران و شرکت ملی مهندسی و ساختمان نفت', 'seal' );
}
if ( $psb_tests ) {
	$psb_stats[] = array( psb_fa_num( $psb_tests ), 'گزارش', 'آزمون نوعی و لیسانس از EPIL و SAREL', 'report' );
}
if ( PSB_WARRANTY_MONTHS ) {
	$psb_stats[] = array( psb_fa_num( PSB_WARRANTY_MONTHS ), 'ماه', 'گارانتی؛ و ۱۰ سال خدمات پس از فروش', 'warranty' );
}
?>
<section class="relative isolate overflow-hidden bg-dark text-white on-dark" aria-labelledby="home-hero-title">
	<?php if ( $psb_image ) : ?>
		<?php
		echo psb_image( // phpcs:ignore
			$psb_image,
			'full',
			array(
				'class'         => 'absolute inset-0 -z-20 size-full object-cover object-[35%_50%] md:object-[30%_55%] [filter:saturate(.8)_contrast(1.05)]',
				'alt'           => '',
				'sizes'         => psb_hero_sizes(),
				'loading'       => 'eager',
				'fetchpriority' => 'high',
			)
		);
		?>
	<?php endif; ?>
	<div class="hero-overlay absolute inset-0 -z-10" aria-hidden="true"></div>

	<div class="container flex flex-col justify-between pt-10 md:min-h-[640px] md:pt-[5.5rem]">
		<div class="max-w-[40rem]">
			<p class="eyebrow">شرکت تولیدی و صنعتی <?php echo esc_html( psb_brand_name() ); ?> · از <?php echo esc_html( psb_fa_num( PSB_FOUNDED_JALALI ) ); ?></p>

			<h1 id="home-hero-title" class="mb-4 mt-3.5 text-display font-bold text-white md:mb-5 md:mt-[1.125rem]">
				طراحی، ساخت و نصب تابلوهای برق فشار ضعیف و فشار متوسط
			</h1>

			<p class="mb-7 max-w-[38rem] text-lead text-slate-200 md:mb-9">
				<?php
				echo wp_kses(
					sprintf(
						'ارائه‌دهنده راهکارهای توزیع و کنترل انرژی الکتریکی برای صنایع، شبکه‌های توزیع، نیروگاه‌ها و پروژه‌های ساختمانی؛ دارای تأییدیه توانیر، گواهینامه <span class="lat">ISO 9001</span>، %s ماه گارانتی و ۱۰ سال خدمات پس از فروش.',
						esc_html( psb_fa_num( PSB_WARRANTY_MONTHS ) )
					),
					array( 'span' => array( 'class' => true ) )
				);
				?>
			</p>

			<div class="flex flex-wrap items-center gap-3">
				<a class="btn btn-primary max-md:h-[52px] max-md:w-full" href="<?php echo esc_url( psb_quote_url() ); ?>">
					درخواست استعلام قیمت
					<?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?>
				</a>
				<a class="btn btn-on-dark max-md:h-[52px] max-md:w-full" href="<?php echo esc_url( psb_shop_url() ); ?>">مشاهده محصولات</a>
				<?php if ( $psb_phone ) : ?>
					<p class="text-sm text-slate-300 max-md:w-full max-md:pt-1 max-md:text-center md:ms-3">
						یا تماس مستقیم:
						<a class="font-semibold text-white" dir="ltr" href="<?php echo esc_url( $psb_phone['href'] ); ?>"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( count( $psb_stats ) > 1 ) : ?>
			<dl class="stats stats--invert mt-10 md:mt-16">
				<?php foreach ( $psb_stats as $psb_stat ) : ?>
					<div class="stat">
						<dt class="stat-value order-1 flex items-center gap-3"><?php echo psb_icon( $psb_stat[3] ?? 'check', 'size-7 shrink-0 text-slate-400' ); // phpcs:ignore ?><span><?php echo esc_html( $psb_stat[0] ); ?><small><?php echo esc_html( $psb_stat[1] ); ?></small></span></dt>
						<dd class="stat-label order-2"><?php echo esc_html( $psb_stat[2] ); ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>
</section>
