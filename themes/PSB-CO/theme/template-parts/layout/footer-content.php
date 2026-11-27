<?php
/**
 * Site footer (spec v2 §4.10): graphite, grid 4 + 2 + 2 + 4.
 * Brand (white logo, description, ISO 9001 / warranty as plain facts) · محصولات · اعتبارنامه‌ها · contact.
 * All contact data from ACF options (Site Setting).
 */
$psb_brand     = psb_brand_name();
$psb_desc      = psb_brand_fix( psb_field( 'footer_description', 'شرکت ' . $psb_brand . '، سازنده تابلوهای برق فشار متوسط، فشار ضعیف و فرمان و کنترل.' ) );
$psb_phones    = psb_phones( true );
$psb_emails    = psb_emails();
$psb_addresses = psb_addresses();

// «محصولات» column: footer menu 3 when assigned, else the products themselves.
$psb_products = array();
foreach ( get_posts( array( 'post_type' => 'product', 'posts_per_page' => 6, 'orderby' => 'menu_order', 'order' => 'ASC', 'no_found_rows' => true ) ) as $psb_p ) {
	$psb_products[] = array( 'url' => get_permalink( $psb_p ), 'label' => get_the_title( $psb_p ) );
}
$psb_products[] = array( 'url' => psb_shop_url(), 'label' => 'همه محصولات' );

// «اعتبارنامه‌ها» column: archives that have content.
$psb_creds = array();
foreach ( array(
	'confirmation'    => 'تأییدیه‌ها و وندورلیست',
	'type-test'       => 'گزارش‌های آزمون نوعی',
	'certificates'    => 'گواهینامه‌ها',
	'customer_letter' => 'رضایت‌نامه‌ها',
) as $psb_pt => $psb_label ) {
	if ( psb_nav_count( $psb_pt ) ) {
		$psb_creds[] = array( 'url' => psb_archive_url( $psb_pt, 'customer_letter' === $psb_pt ? '/customer-letters/' : '' ), 'label' => $psb_label );
	}
}
$psb_creds[] = array( 'url' => home_url( '/about-us/' ), 'label' => 'درباره شرکت' );
$psb_creds[] = array( 'url' => psb_contact_url(), 'label' => 'تماس با شرکت' );
?>
<footer id="footer" class="site-footer on-dark" aria-labelledby="footer-heading">
	<h2 id="footer-heading" class="sr-only">اطلاعات <?php echo esc_html( $psb_brand ); ?></h2>

	<div class="container grid gap-10 lg:grid-cols-12 lg:gap-6">
		<!-- Brand -->
		<div class="lg:col-span-4">
			<?php get_template_part( 'template-parts/global/logo', null, array( 'variant' => 'white', 'height' => 72 ) ); ?>
			<p class="mt-6 max-w-sm leading-[1.9] text-slate-300"><?php echo esc_html( $psb_desc ); ?></p>
			<ul class="mt-5 grid gap-2">
				<?php if ( PSB_ISO_9001 ) : ?>
					<li class="footer-fact">دارای گواهینامه <span class="lat">ISO 9001</span></li>
				<?php endif; ?>
				<?php if ( PSB_WARRANTY_MONTHS ) : ?>
					<li class="footer-fact"><?php echo esc_html( psb_fa_num( PSB_WARRANTY_MONTHS ) . ' ماه گارانتی و ' . psb_fa_num( PSB_SERVICE_YEARS ) . ' سال خدمات پس از فروش' ); ?></li>
				<?php endif; ?>
				<li class="footer-fact"><?php echo esc_html( 'تأسیس ' . psb_fa_num( PSB_FOUNDED_JALALI ) ); ?></li>
			</ul>
		</div>

		<!-- Link columns -->
		<nav class="lg:col-span-2" aria-label="محصولات">
			<p class="footer-title">محصولات</p>
			<ul class="footer-links">
				<?php foreach ( $psb_products as $psb_l ) : ?>
					<li><a href="<?php echo esc_url( $psb_l['url'] ); ?>"><?php echo esc_html( $psb_l['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<nav class="lg:col-span-2" aria-label="اعتبارنامه‌ها">
			<p class="footer-title">اعتبارنامه‌ها</p>
			<ul class="footer-links">
				<?php foreach ( $psb_creds as $psb_l ) : ?>
					<li><a href="<?php echo esc_url( $psb_l['url'] ); ?>"><?php echo esc_html( $psb_l['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<!-- Contact -->
		<div class="lg:col-span-4">
			<p class="footer-title">ارتباط با شرکت</p>
			<div class="grid gap-5">
				<?php foreach ( $psb_addresses as $psb_address ) : ?>
					<div>
						<p class="flex items-center gap-2 text-sm font-semibold text-white"><?php echo psb_icon( false !== mb_strpos( (string) $psb_address['title'], 'کارخانه' ) ? 'factory' : 'map', 'size-[18px] shrink-0 text-slate-400' ); // phpcs:ignore ?><?php echo esc_html( $psb_address['title'] ); ?></p>
						<address class="mt-1 text-sm not-italic leading-7 text-slate-300"><?php echo esc_html( psb_fa_num( $psb_address['address'] ) ); ?></address>
						<?php if ( $psb_address['map_link'] ) : ?>
							<a class="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-400 hover:text-primary-300" href="<?php echo esc_url( $psb_address['map_link'] ); ?>" target="_blank" rel="noopener"><?php echo psb_icon( 'pin', 'size-4' ); // phpcs:ignore ?>مسیریابی</a>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>

				<?php if ( $psb_phones ) : ?>
					<dl class="grid gap-1.5">
						<?php foreach ( $psb_phones as $psb_p ) : ?>
							<div class="flex items-baseline justify-between gap-3 border-b border-white/10 pb-1.5">
								<dt class="flex items-center gap-2 text-sm text-slate-300"><?php echo psb_icon( false !== mb_strpos( (string) $psb_p['title'], 'فاکس' ) ? 'fax' : 'phone', 'size-4 shrink-0 text-slate-400' ); // phpcs:ignore ?><?php echo esc_html( $psb_p['title'] ); ?></dt>
								<dd><a class="text-base font-semibold text-white" dir="ltr" href="<?php echo esc_url( $psb_p['href'] ); ?>"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_p['number'] ) ) ); ?></a></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>

				<?php foreach ( $psb_emails as $psb_email ) : ?>
					<a class="flex w-fit items-center gap-2 text-sm text-slate-200 hover:text-white" href="mailto:<?php echo esc_attr( antispambot( $psb_email ) ); ?>"><?php echo psb_icon( 'mail', 'size-4 shrink-0 text-slate-400' ); // phpcs:ignore ?><span class="lat"><?php echo esc_html( antispambot( $psb_email ) ); ?></span></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<div class="container">
		<div class="footer-bottom flex flex-col-reverse items-center justify-between gap-4 sm:flex-row">
			<p><?php echo esc_html( sprintf( '© %s — تمامی حقوق برای %s محفوظ است.', psb_fa_num( psb_jalali_year() ), $psb_brand ) ); ?></p>
			<?php get_template_part( 'template-parts/global/social-links', null, array( 'wrap_class' => 'flex items-center gap-1' ) ); ?>
		</div>
	</div>
</footer>
