<?php
/**
 * Services page sidebar (page «خدمات»): facts at a glance, factory visit / FAT + quote, facilities list.
 * Facts come from Site Setting (years, warranty, after-sales, ISO) and the front page «ردیف ارقام زیر
 * کاشی‌ها» rows (AMADA, Gema, current injection, build time), so they are edited in one place.
 *
 * @package PSB-CO
 */

$psb_front = (int) get_option( 'page_on_front' );
$psb_facts = array(
	array( 'calendar', 'سابقه', psb_fa_num( psb_years_active() ) . ' سال، از ' . psb_fa_num( PSB_FOUNDED_JALALI ) ),
	array( 'warranty', 'گارانتی', psb_fa_num( PSB_WARRANTY_MONTHS ) . ' ماه' ),
	array( 'wrench', 'خدمات پس از فروش', psb_fa_num( PSB_SERVICE_YEARS ) . ' سال' ),
);
if ( PSB_ISO_9001 ) {
	$psb_facts[] = array( 'iso', 'سیستم کیفیت', 'ISO 9001' );
}
if ( function_exists( 'psb_pf_rows' ) ) {
	foreach ( psb_pf_rows( 'home_factory_facts', $psb_front ) as $psb_row ) {
		if ( ! empty( $psb_row['value'] ) && ! empty( $psb_row['label'] ) ) {
			$psb_facts[] = array( ! empty( $psb_row['icon'] ) ? $psb_row['icon'] : 'check', $psb_row['label'], $psb_row['value'] );
		}
	}
}
$psb_phone    = psb_primary_phone();
$psb_services = get_posts(
	array(
		'post_type'   => 'services',
		'post_status' => 'publish',
		'numberposts' => 12,
		'orderby'     => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	)
);
?>
<aside class="inner-aside" aria-label="اطلاعات کاربردی">
	<div class="panel">
		<p class="panel-title">در یک نگاه</p>
		<dl class="grid gap-3">
			<?php foreach ( $psb_facts as $psb_f ) : ?>
				<div class="flex items-start gap-3">
					<span class="grid size-9 shrink-0 place-items-center rounded-control border border-line bg-slate-50 text-slate-700" aria-hidden="true"><?php echo psb_icon( $psb_f[0], 'size-[18px]' ); // phpcs:ignore ?></span>
					<span class="flex min-w-0 flex-col">
						<dt class="text-xs text-slate-600"><?php echo function_exists( 'psb_pf_html' ) ? psb_pf_html( $psb_f[1] ) : esc_html( $psb_f[1] ); // phpcs:ignore ?></dt>
						<dd class="text-sm font-bold text-foreground"><?php echo function_exists( 'psb_pf_html' ) ? psb_pf_html( $psb_f[2] ) : esc_html( $psb_f[2] ); // phpcs:ignore ?></dd>
					</span>
				</div>
			<?php endforeach; ?>
		</dl>
	</div>

	<div class="panel">
		<p class="panel-title">بازدید از کارخانه یا حضور در آزمون</p>
		<p class="text-sm leading-7 text-slate-600">برای هماهنگی بازدید از خطوط تولید، حضور در آزمون‌های تحویل (<span class="lat">FAT</span>) یا دریافت پیشنهاد فنی، با واحد بازرگانی تماس بگیرید.</p>
		<div class="mt-4 grid gap-2">
			<a class="btn btn-primary btn-block" href="<?php echo esc_url( psb_quote_url() ); ?>">ثبت درخواست استعلام</a>
			<a class="btn btn-ghost btn-block" href="<?php echo esc_url( psb_contact_url() ); ?>">تماس با شرکت</a>
		</div>
		<?php if ( $psb_phone ) : ?>
			<p class="mt-4 flex items-center justify-between gap-2 border-t border-line pt-4 text-sm text-slate-600">
				<span>تماس مستقیم</span>
				<a class="font-bold text-foreground" dir="ltr" href="<?php echo esc_url( $psb_phone['href'] ); ?>"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></a>
			</p>
		<?php endif; ?>
	</div>

	<?php if ( $psb_services ) : ?>
		<nav class="panel" aria-label="فهرست امکانات">
			<p class="panel-title">امکانات و خطوط تولید</p>
			<ul class="flex flex-col gap-1" role="list">
				<?php foreach ( $psb_services as $psb_o ) : ?>
					<li><a class="flex min-h-11 items-center justify-between gap-2 rounded-control px-2 text-sm font-bold text-slate-700 hover:bg-slate-50 hover:text-primary-700" href="<?php echo esc_url( get_permalink( $psb_o ) ); ?>"><?php echo esc_html( get_the_title( $psb_o ) ); ?><?php echo psb_icon( 'chevron', 'size-4 text-slate-500' ); // phpcs:ignore ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>
</aside>
