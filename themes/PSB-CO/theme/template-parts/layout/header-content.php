<?php
/**
 * Sticky site header (spec v2 §4.9). CSS-responsive (page-cache safe).
 * ≥ 1100px: full logo · mega-menu nav · search · phone block (≥ 1280) · «استعلام قیمت».
 * < 1100px: menu (drawer, inline-start) · logo (centred) · search · phone icon (md+).
 * Fixed bar over a constant-height spacer; html.is-compact (set by javascript/modules/header-compact.js
 * from an IntersectionObserver sentinel) shrinks the bar 84→64px (60→52 mobile) without moving content.
 */
$psb_phone = psb_primary_phone();
?>
<div class="site-header-spacer" aria-hidden="true"></div>
<header id="header" class="site-header" :class="{ 'is-scrolled': scrolled }"
	x-data="{ open: null, t: null,
		enter(id) { clearTimeout(this.t); if (this.open === id) return; this.t = setTimeout(() => this.open = id, 150); },
		leave() { clearTimeout(this.t); this.t = setTimeout(() => this.open = null, 300); },
		toggle(id, kbd) { clearTimeout(this.t); this.open = this.open === id ? null : id; if (kbd && this.open === id) this.$nextTick(() => document.querySelector('#mm-panel-' + id + ' a')?.focus()); },
		panelTab(e, id) {
			/* Tab out of a panel continues with the next top-level item; Shift+Tab off the first link returns to its chevron. */
			const links = [...e.currentTarget.querySelectorAll('a[href]')].filter(a => a.offsetParent !== null);
			if (!links.length) return;
			const btn = document.getElementById('mm-btn-' + id);
			if (e.shiftKey && document.activeElement === links[0]) { e.preventDefault(); btn?.focus(); }
			else if (!e.shiftKey && document.activeElement === links[links.length - 1]) {
				e.preventDefault(); this.open = null;
				const tops = [...document.querySelectorAll('.site-nav .mm-top')]; const li = btn?.closest('.mm-item');
				const next = li?.nextElementSibling?.querySelector('.mm-top');
				(next || document.querySelector('.header-actions button, .header-actions a'))?.focus();
			}
		},
		close(focus) { const id = this.open; this.open = null; if (focus && id) this.$nextTick(() => document.getElementById('mm-btn-' + id)?.focus()); } }"
	@keydown.escape.window="open && close(true)"
	@click.outside="open = null"
	@focusout="if (open && !$el.contains($event.relatedTarget)) open = null">
	<div class="site-header-inner">
		<?php // < 1100px: menu at the inline-start (right in RTL, under the thumb) · logo centred · search at the end. ?>
		<button type="button" class="icon-btn header-menu-btn nav:hidden" @click="menuOpen = true" aria-label="باز کردن منو" aria-controls="mobileMenu" aria-haspopup="dialog" :aria-expanded="menuOpen">
			<?php echo psb_icon( 'menu' ); // phpcs:ignore ?>
		</button>
		<?php
		// One link, two images: full lockup ≥ 1100px, PSB mark below and in the compact (scrolled) header —
		// at 44px the full logo would be < 120px wide (brand minimum), so compact swaps to the mark.
		get_template_part( 'template-parts/global/logo', null, array( 'variant' => 'swap' ) );
		get_template_part( 'template-parts/layout/header/mega-menu', null, array( 'part' => 'nav' ) );
		?>

		<div class="header-actions">
			<?php if ( ! is_search() ) : ?>
				<button type="button" class="icon-btn" @click="searchOpen = true" aria-label="جستجو در سایت" aria-haspopup="dialog" :aria-expanded="searchOpen">
					<?php echo psb_icon( 'search' ); // phpcs:ignore ?>
				</button>
			<?php endif; ?>

			<?php if ( $psb_phone ) : ?>
				<a class="header-phone" href="<?php echo esc_url( $psb_phone['href'] ); ?>">
					<small>مشاوره فنی و فروش</small>
					<b dir="ltr"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></b>
				</a>
				<a class="icon-btn max-md:hidden" href="<?php echo esc_url( $psb_phone['href'] ); ?>" aria-label="<?php echo esc_attr( 'تماس تلفنی: ' . psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?>" title="<?php echo esc_attr( 'مشاوره فنی و فروش: ' . psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?>">
					<?php echo psb_icon( 'phone' ); // phpcs:ignore ?>
				</a>
			<?php endif; ?>

			<?php $psb_on_quote = is_page( 'request-quote' ) || is_page_template( 'page-request-quote.php' ); ?>
			<a class="btn <?php echo $psb_on_quote ? 'btn-secondary' : 'btn-primary'; ?> hidden nav:inline-flex" href="<?php echo esc_url( psb_quote_url() ); ?>" <?php echo $psb_on_quote ? 'aria-current="page"' : ''; ?>>استعلام قیمت</a>

		</div>
	</div>
	<?php get_template_part( 'template-parts/layout/header/mega-menu', null, array( 'part' => 'panels' ) ); ?>
	<div class="mm-scrim max-nav:hidden" x-show="open" x-cloak aria-hidden="true"
		x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
		x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
</header>
