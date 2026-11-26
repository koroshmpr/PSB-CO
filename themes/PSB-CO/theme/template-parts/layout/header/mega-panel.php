<?php
/**
 * Mega-menu panel content. $args['top'] = top-level node from psb_nav_tree().
 * Panels are full-width (anchored to the fixed header) with an inner container; see layout.css .mm-panel.
 *  products    : category list (icons + counts) · product cards of the hovered category · quote promo
 *  credentials : icon rows (archives + key documents) · highlight card (Tavanir)
 *  about       : icon rows (menu children) · highlight card (phone, lead time)
 */
$psb_top   = $args['top'] ?? array();
$psb_panel = $psb_top['panel'] ?? '';
$psb_kids  = $psb_top['children'] ?? array();

/** One icon row: icon + title + one-line description. */
$psb_row = function ( $url, $icon, $title, $desc = '', $count = 0 ) {
	?>
	<a class="mm-row" href="<?php echo esc_url( $url ); ?>">
		<?php echo psb_icon( $icon ?: 'doc', 'mm-row-ico' ); // phpcs:ignore ?>
		<span class="min-w-0">
			<span class="mm-row-title"><?php echo psb_lat( $title ); // phpcs:ignore ?><?php echo $count ? ' <span class="text-xs font-medium text-slate-500">(' . esc_html( psb_fa_num( $count ) ) . ')</span>' : ''; // phpcs:ignore ?></span>
			<?php if ( $desc ) : ?>
				<span class="mm-row-desc line-clamp-1"><?php echo psb_lat( $desc ); // phpcs:ignore ?></span>
			<?php endif; ?>
		</span>
	</a>
	<?php
};

if ( 'products' === $psb_panel ) :
	// One source with the drawer and /shop/: non-empty categories, counted with psb_cat_count().
	$psb_cats    = psb_nav_product_categories();
	$psb_default = $psb_cats ? (int) $psb_cats[0]['term']->term_id : 0;
	$psb_img     = function ( $p, $slot, $ratio, $cls ) {
		$attr = array( 'class' => $cls, 'alt' => '', 'sizes' => $slot . 'px', 'loading' => 'eager', 'fetchpriority' => 'low' );
		if ( function_exists( 'psb_best_image' ) ) {
			return psb_best_image( $p, $slot, $ratio, array( 'size' => $slot > 300 ? 'medium_large' : 'medium', 'attr' => $attr ) );
		}
		return has_post_thumbnail( $p ) ? psb_image( get_post_thumbnail_id( $p ), 'medium', $attr ) : '';
	};
	?>
	<div class="grid grid-cols-12 gap-6" x-data="{ cat: <?php echo (int) $psb_default; ?> }">
		<?php if ( $psb_cats ) : ?>
			<div class="col-span-3 border-e border-line pe-4">
				<p class="mm-head">دسته‌بندی محصولات</p>
				<ul class="grid gap-0.5" role="list">
					<?php foreach ( $psb_cats as $psb_c ) : ?>
						<?php $psb_tid = (int) $psb_c['term']->term_id; ?>
						<li>
							<a class="mm-cat" href="<?php echo esc_url( $psb_c['url'] ); ?>"
								:class="cat === <?php echo $psb_tid; // phpcs:ignore ?> && 'is-on'"
								@mouseenter="cat = <?php echo $psb_tid; // phpcs:ignore ?>" @focus="cat = <?php echo $psb_tid; // phpcs:ignore ?>">
								<?php echo psb_icon( $psb_c['icon'], 'size-5 shrink-0 text-slate-600' ); // phpcs:ignore ?>
								<span class="flex-1"><?php echo psb_lat( $psb_c['name'] ); // phpcs:ignore ?></span>
								<span class="text-xs text-slate-600"><?php echo esc_html( psb_fa_num( $psb_c['count'] ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<div class="<?php echo $psb_cats ? 'col-span-6' : 'col-span-9'; ?> min-w-0">
			<?php foreach ( $psb_cats as $psb_c ) : ?>
				<?php
				$psb_tid = (int) $psb_c['term']->term_id;
				$psb_ps  = $psb_c['products'];
				$psb_few = count( $psb_ps ) <= 2;
				?>
				<div x-show="cat === <?php echo $psb_tid; // phpcs:ignore ?>" <?php echo $psb_tid === $psb_default ? '' : 'x-cloak'; ?>>
					<?php if ( $psb_few ) : ?>
						<?php // 1–2 products: large horizontal cards (16:9 image, title, 2-line text, link) — no empty grid. ?>
						<ul class="grid gap-4" role="list">
							<?php foreach ( $psb_ps as $psb_p ) : ?>
								<li>
									<a class="group grid grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] items-center gap-5 rounded-card p-2 transition-colors hover:bg-slate-50" href="<?php echo esc_url( get_permalink( $psb_p ) ); ?>">
										<span class="relative block aspect-[16/9] overflow-hidden rounded-control bg-slate-100">
											<?php echo $psb_img( $psb_p, 360, '16 / 9', 'size-full object-cover transition-transform duration-300 group-hover:scale-[1.03]' ); // phpcs:ignore ?>
										</span>
										<span class="min-w-0">
											<span class="block text-base font-bold leading-7 text-foreground group-hover:text-primary-700"><?php echo psb_lat( get_the_title( $psb_p ) ); // phpcs:ignore ?></span>
											<span class="mt-1 line-clamp-2 text-[0.8125rem] leading-6 text-slate-600"><?php echo psb_lat( psb_nav_teaser( $psb_p, 140 ) ); // phpcs:ignore ?></span>
											<span class="link-arrow mt-3 text-sm">مشاهده محصول <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></span>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<ul class="grid grid-cols-3 gap-4" role="list">
							<?php foreach ( $psb_ps as $psb_p ) : ?>
								<li>
									<a class="group block" href="<?php echo esc_url( get_permalink( $psb_p ) ); ?>">
										<span class="relative block aspect-[4/3] overflow-hidden rounded-control bg-slate-100">
											<?php echo $psb_img( $psb_p, 200, '4 / 3', 'size-full object-cover transition-transform duration-300 group-hover:scale-[1.03]' ); // phpcs:ignore ?>
										</span>
										<span class="mt-2 block text-sm font-semibold leading-6 text-foreground group-hover:text-primary-700"><?php echo psb_lat( get_the_title( $psb_p ) ); // phpcs:ignore ?></span>
										<span class="line-clamp-1 text-xs leading-5 text-slate-600"><?php echo psb_lat( psb_nav_teaser( $psb_p, 60 ) ); // phpcs:ignore ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<a class="link-arrow mt-4 text-sm" href="<?php echo esc_url( $psb_c['url'] ); ?>"><?php echo esc_html( 'همه محصولات ' . $psb_c['name'] ); ?> <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></a>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="col-span-3 rounded-card bg-slate-50 p-5">
			<?php echo psb_icon( 'drawing', 'size-7 text-slate-700' ); // phpcs:ignore ?>
			<p class="mt-3 text-base font-bold text-foreground">ارسال نقشه تک‌خطی</p>
			<p class="mt-1 text-[0.8125rem] leading-6 text-slate-600">نقشه یا فهرست تجهیزات را ارسال کنید تا کارشناس فنی پیشنهاد قیمت ارائه کند.</p>
			<a class="btn btn-primary btn-sm mt-4" href="<?php echo esc_url( psb_quote_url() ); ?>">استعلام قیمت پروژه</a>
			<a class="link-arrow mt-4 flex w-fit" href="<?php echo esc_url( $psb_top['url'] ? $psb_top['url'] : psb_shop_url() ); ?>">همه محصولات <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></a>
		</div>
	</div>

<?php
elseif ( 'credentials' === $psb_panel ) :
	$psb_rows = array();
	foreach ( array(
		'confirmation'    => array( 'تأییدیه‌ها و وندورلیست', 'shield', 'توانیر، شرکت‌های توزیع برق و شرکت ملی مهندسی و ساختمان نفت' ),
		'type-test'       => array( 'گزارش‌های آزمون نوعی', 'report', 'آزمایشگاه EPIL و لیسانس SAREL' ),
		'certificates'    => array( 'گواهینامه‌ها', 'iso', 'گواهینامه‌های سیستم مدیریت کیفیت' ),
		'customer_letter' => array( 'رضایت‌نامه‌ها', 'letter', 'نامه‌های حسن انجام کار کارفرمایان' ),
	) as $psb_pt => $psb_info ) {
		$psb_n = psb_nav_count( $psb_pt );
		if ( $psb_n ) {
			$psb_rows[] = array( psb_archive_url( $psb_pt, 'customer_letter' === $psb_pt ? '/customer-letters/' : '' ), $psb_info[1], $psb_info[0], $psb_info[2], $psb_n );
		}
	}
	// Key documents as rows (most recent approvals and type tests).
	$psb_docs = array();
	foreach ( array( 'confirmation' => 'seal', 'type-test' => 'report' ) as $psb_pt => $psb_icon ) {
		foreach ( get_posts( array( 'post_type' => $psb_pt, 'posts_per_page' => 3, 'no_found_rows' => true ) ) as $psb_p ) {
			$psb_docs[] = array( get_permalink( $psb_p ), $psb_icon, psb_brand_fix( get_the_title( $psb_p ) ), (string) ( psb_field( 'issuing_organization', '', $psb_p->ID ) ?: psb_field( 'issuing_body', '', $psb_p->ID ) ) );
		}
	}
	$psb_feature = null;
	foreach ( get_posts( array( 'post_type' => 'confirmation', 'posts_per_page' => 12, 'no_found_rows' => true ) ) as $psb_c ) {
		if ( false !== mb_strpos( $psb_c->post_title, 'توانیر' ) ) {
			$psb_feature = $psb_c;
			break;
		}
	}
	$psb_file = $psb_feature ? psb_field( 'letter_file', null, $psb_feature->ID ) : null;
	?>
	<div class="grid grid-cols-12 gap-6">
		<div class="col-span-9 grid grid-cols-2 content-start gap-x-6 gap-y-1">
			<?php foreach ( $psb_rows as $psb_r ) : ?>
				<?php $psb_row( $psb_r[0], $psb_r[1], $psb_r[2], $psb_r[3], $psb_r[4] ); ?>
			<?php endforeach; ?>
			<?php if ( $psb_docs ) : ?>
				<p class="mm-head col-span-2 mt-3 border-t border-line pt-4">اسناد شاخص</p>
				<?php foreach ( $psb_docs as $psb_d ) : ?>
					<?php $psb_row( $psb_d[0], $psb_d[1], $psb_d[2], $psb_d[3] ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="col-span-3 rounded-card bg-slate-50 p-5">
			<?php echo psb_icon( 'seal', 'size-7 text-slate-700' ); // phpcs:ignore ?>
			<p class="mt-3 text-base font-bold text-foreground">در فهرست سازندگان تأییدشده توانیر</p>
			<p class="mt-1 text-[0.8125rem] leading-6 text-slate-600">اصل تأییدیه‌ها و گزارش‌های آزمون در سایت قابل مشاهده است.</p>
			<?php if ( $psb_feature ) : ?>
				<a class="link-arrow mt-4 flex w-fit" href="<?php echo esc_url( get_permalink( $psb_feature ) ); ?>">مشاهده تأییدیه توانیر <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></a>
			<?php endif; ?>
			<?php if ( is_array( $psb_file ) && ! empty( $psb_file['url'] ) ) : ?>
				<a class="register-doc mt-3" href="<?php echo esc_url( $psb_file['url'] ); ?>" target="_blank" rel="noopener"><?php echo psb_icon( 'doc' ); // phpcs:ignore ?>دانلود فهرست توانیر</a>
			<?php endif; ?>
		</div>
	</div>

<?php
elseif ( 'about' === $psb_panel ) :
	$psb_phone = psb_primary_phone();
	$psb_descs = array(
		'about'    => 'تاریخچه، تأییدیه‌ها و توان تولید',
		'services' => 'ماشین‌آلات CNC، خط رنگ و آزمایشگاه‌ها',
		'projects' => 'فهرست پروژه‌های اجراشده',
		'contact'  => 'نشانی دفتر مرکزی، کارخانه و شماره‌ها',
	);
	?>
	<div class="grid grid-cols-12 gap-6">
		<div class="col-span-8 grid grid-cols-2 content-start gap-x-6 gap-y-1">
			<?php foreach ( $psb_kids as $psb_kid ) : ?>
				<?php
				$psb_sec = psb_nav_url_section( $psb_kid['url'] );
				$psb_row( $psb_kid['url'], $psb_kid['icon'] ?: psb_nav_section_icon( $psb_kid['url'] ), $psb_kid['title'], $psb_kid['desc'] ? $psb_kid['desc'] : ( $psb_descs[ $psb_sec ] ?? '' ) );
				?>
			<?php endforeach; ?>
		</div>
		<div class="col-span-4 rounded-card bg-slate-50 p-5">
			<?php echo psb_icon( 'building', 'size-7 text-slate-700' ); // phpcs:ignore ?>
			<p class="mt-3 text-base font-bold text-foreground"><?php echo esc_html( psb_fa_num( psb_years_active() ) . ' سال تابلوسازی' ); ?></p>
			<p class="text-[0.8125rem] text-slate-600"><?php echo esc_html( 'تأسیس ' . psb_fa_num( PSB_FOUNDED_JALALI ) ); ?></p>
			<dl class="mt-3 grid gap-3 text-sm">
				<?php if ( $psb_phone ) : ?>
					<div><dt class="flex items-center gap-2 text-slate-600"><?php echo psb_icon( 'phone', 'size-4' ); // phpcs:ignore ?>تلفن</dt><dd class="mt-1"><a class="text-base font-bold text-foreground" dir="ltr" href="<?php echo esc_url( $psb_phone['href'] ); ?>"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></a></dd></div>
				<?php endif; ?>
				<div><dt class="flex items-center gap-2 text-slate-600"><?php echo psb_icon( 'clock', 'size-4' ); // phpcs:ignore ?>زمان ساخت</dt><dd class="mt-1 font-semibold text-foreground">۱۵ تا ۴۵ روز کاری پس از تأیید نقشه‌ها</dd></div>
			</dl>
		</div>
	</div>
<?php endif; ?>
