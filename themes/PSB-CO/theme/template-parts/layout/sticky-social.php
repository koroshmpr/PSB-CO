<?php
/**
 * Mobile bottom navigation (< 768px), minimal and icon-first:
 * a white floating pill with 5 monochrome line icons (خانه · محصولات · استعلام · اعتبارنامه‌ها · منو)
 * and ONE separate round call button outside the pill (inline-end) that opens the call sheet.
 * Active item = light grey circle behind a graphite icon. Labels are visually hidden (aria + title).
 * Printed by footer.php when psb_show_tabbar() is true (body.has-tabbar pads the page by --tabbar-offset).
 * File name kept for backwards compatibility.
 */
$psb_section = psb_nav_current_section(); // same mapping as the header (one active item)
$psb_phone   = psb_primary_phone();
$psb_wa      = '';
$psb_bale    = '';
foreach ( psb_socials() as $psb_s ) {
	if ( 'whatsapp' === $psb_s['name'] ) {
		$psb_wa = $psb_s['url'];
	} elseif ( 'bale' === $psb_s['name'] && false !== strpos( $psb_s['url'], 'ble.ir/' ) ) {
		$psb_bale = $psb_s['url'];
	}
}
$psb_is_quote = is_page( 'request-quote' ) || is_page_template( 'page-request-quote.php' );
$psb_tabs     = array(
	array( home_url( '/' ), 'خانه', 'home', 'home' === $psb_section ),
	array( psb_shop_url(), 'محصولات', 'cabinet', 'products' === $psb_section ),
	array( psb_quote_url(), 'استعلام قیمت', 'quote', $psb_is_quote ),
	array( psb_archive_url( 'confirmation' ), 'اعتبارنامه‌ها', 'shield', 'credentials' === $psb_section ),
);
?>
<div x-data="{ sheet: false, y0: 0, dy: 0 }" @keydown.escape.window="if (sheet) sheet = false">
	<div class="app-dock">
		<nav class="app-tabbar" aria-label="ناوبری اصلی موبایل">
			<?php foreach ( $psb_tabs as $psb_tab ) : ?>
				<a class="app-tab" href="<?php echo esc_url( $psb_tab[0] ); ?>" aria-label="<?php echo esc_attr( $psb_tab[1] ); ?>" title="<?php echo esc_attr( $psb_tab[1] ); ?>" <?php echo $psb_tab[3] ? 'aria-current="page"' : ''; ?>>
					<span class="app-tab-ico"><?php echo psb_icon( $psb_tab[2] ); // phpcs:ignore ?></span>
				</a>
			<?php endforeach; ?>
			<button type="button" class="app-tab" @click="menuOpen = true" aria-label="منو" title="منو" aria-controls="mobileMenu" aria-haspopup="dialog" :aria-expanded="menuOpen">
				<span class="app-tab-ico"><?php echo psb_icon( 'menu' ); // phpcs:ignore ?></span>
			</button>
		</nav>
		<button type="button" class="app-call" x-ref="callBtn" @click="sheet = true" aria-label="تماس با شرکت" title="تماس با شرکت" aria-haspopup="dialog" aria-controls="call-sheet" :aria-expanded="sheet">
			<?php echo psb_icon( 'phone', 'size-[22px]' ); // phpcs:ignore ?>
		</button>
	</div>

	<div class="call-sheet-backdrop md:hidden" x-show="sheet" x-cloak @click="sheet = false"
		x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
		x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
	<div id="call-sheet" class="call-sheet md:hidden" role="dialog" aria-modal="true" aria-labelledby="call-sheet-title"
		x-show="sheet" x-cloak
		x-init="$watch('sheet', v => { document.body.classList.toggle('overflow-hidden', v); if (v) { $nextTick(() => $el.querySelector('a')?.focus()); } else { $nextTick(() => $refs.callBtn?.focus()); } })"
		:style="dy > 0 ? `transform: translateY(${dy}px)` : ''"
		@touchstart.passive="y0 = $event.touches[0].clientY; dy = 0"
		@touchmove.passive="dy = Math.max(0, $event.touches[0].clientY - y0)"
		@touchend="if (dy > 80) sheet = false; dy = 0"
		x-transition:enter="transition duration-[240ms] ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
		x-transition:leave="transition duration-[180ms] ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full">
		<span class="call-sheet-grip" aria-hidden="true"></span>
		<div class="flex items-center justify-between">
			<h2 id="call-sheet-title" class="text-base font-bold text-foreground">ارتباط با <?php echo esc_html( psb_brand_name() ); ?></h2>
			<button type="button" class="icon-btn" @click="sheet = false" aria-label="بستن"><?php echo psb_icon( 'close' ); // phpcs:ignore ?></button>
		</div>
		<ul class="mt-2 grid">
			<?php if ( $psb_phone ) : ?>
				<li><a class="call-row" href="<?php echo esc_url( $psb_phone['href'] ); ?>"><?php echo psb_icon( 'phone', 'call-row-ico' ); // phpcs:ignore ?><span class="flex-1">تماس با دفتر مرکزی</span><span class="text-sm text-slate-600" dir="ltr"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></span></a></li>
			<?php endif; ?>
			<?php if ( $psb_wa ) : ?>
				<li><a class="call-row" href="<?php echo esc_url( $psb_wa ); ?>" target="_blank" rel="noopener"><?php echo psb_icon( 'whatsapp', 'call-row-ico' ); // phpcs:ignore ?><span class="flex-1">واتساپ واحد فروش</span></a></li>
			<?php endif; ?>
			<?php if ( $psb_bale ) : ?>
				<li><a class="call-row" href="<?php echo esc_url( $psb_bale ); ?>" target="_blank" rel="noopener"><?php echo psb_icon( 'chat', 'call-row-ico' ); // phpcs:ignore ?><span class="flex-1">پیام در بله</span></a></li>
			<?php endif; ?>
			<li><a class="call-row" href="<?php echo esc_url( psb_contact_url() ); ?>"><?php echo psb_icon( 'doc', 'call-row-ico' ); // phpcs:ignore ?><span class="flex-1">فرم تماس</span><?php echo psb_icon( 'arrow', 'btn-arrow size-4 text-slate-400' ); // phpcs:ignore ?></a></li>
		</ul>
	</div>
</div>
