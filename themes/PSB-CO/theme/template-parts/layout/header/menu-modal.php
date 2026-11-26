<?php
/**
 * Mobile navigation drawer (role=dialog). Opened by the header menu button (menuOpen).
 * Esc / backdrop / close button close it; focus moves into the drawer and back to the trigger.
 */
$psb_phone = psb_primary_phone();
?>
<div id="mobileMenu"
	x-show="menuOpen"
	x-cloak
	class="nav:hidden"
	@keydown.escape.window="if (menuOpen) menuOpen = false"
	x-init="let opener = null; $watch('menuOpen', open => { if (open) { const a = document.activeElement; opener = a && a !== document.body ? a : null; $nextTick(() => $refs.drawerClose.focus()); } else if (opener && document.contains(opener) && opener.offsetParent !== null) { $nextTick(() => opener.focus()); } else { $nextTick(() => [...document.querySelectorAll('[aria-controls=mobileMenu]')].find(b => b.offsetParent !== null)?.focus()); } })">

	<div class="drawer-backdrop"
		x-show="menuOpen"
		x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
		x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
		@click="menuOpen = false"></div>

	<div class="drawer" role="dialog" aria-modal="true" aria-label="منوی سایت"
		x-show="menuOpen"
		x-transition:enter="transition-transform duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
		x-transition:leave="transition-transform duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
		@keydown.tab="
			const f = [...$el.querySelectorAll('a[href],button:not([disabled])')];
			if (!f.length) return;
			if ($event.shiftKey && document.activeElement === f[0]) { $event.preventDefault(); f[f.length - 1].focus(); }
			else if (!$event.shiftKey && document.activeElement === f[f.length - 1]) { $event.preventDefault(); f[0].focus(); }
		">

		<div class="drawer-head">
			<?php get_template_part( 'template-parts/global/logo', null, array( 'height' => 34 ) ); ?>
			<button type="button" x-ref="drawerClose" class="icon-btn" @click="menuOpen = false" aria-label="بستن منو">
				<?php echo psb_icon( 'close', 'size-5' ); // phpcs:ignore ?>
			</button>
		</div>

		<div class="shrink-0 px-4 pt-4">
			<button type="button" class="input flex h-12 items-center gap-2 overflow-hidden whitespace-nowrap text-start text-slate-500" @click="menuOpen = false; searchOpen = true">
				<?php echo psb_icon( 'search', 'size-4 shrink-0' ); // phpcs:ignore ?><span class="truncate">جستجو در سایت…</span>
			</button>
		</div>
		<nav class="drawer-body drawer-nav" aria-label="منوی موبایل">
			<?php
			$psb_tree = psb_nav_tree( has_nav_menu( 'menu-3' ) ? 'menu-3' : 'menu-1' );
			// «view all» label per section (natural Persian, not «همه + title»).
			$psb_all_label = array(
				'products'    => 'همه محصولات',
				'credentials' => 'همه اعتبارنامه‌ها',
				'services'    => 'صفحه کارخانه و خدمات',
				'projects'    => 'همه پروژه‌ها',
				'blog'        => 'همه مقالات',
				'about'       => 'معرفی شرکت',
			);
			$psb_here    = untrailingslashit( preg_replace( '/\?.*$/', '', psb_iri( home_url( add_query_arg( array() ) ) ) ) );
			$psb_marked  = false; // aria-current on the first matching link only
			$psb_current = function ( $url ) use ( $psb_here, &$psb_marked ) {
				if ( $psb_marked || ! $url || untrailingslashit( $url ) !== $psb_here ) {
					return '';
				}
				$psb_marked = true;
				return 'aria-current="page"';
			};
			?>
			<ul>
				<?php foreach ( $psb_tree as $psb_top ) : ?>
					<?php
					$psb_section  = psb_nav_url_section( $psb_top['url'] );
					$psb_row_icon = psb_icon( psb_nav_section_icon( $psb_top['url'] ), 'drawer-ico' );
					$psb_kids     = $psb_top['children'];
					if ( 'products' === ( $psb_top['panel'] ?? '' ) && function_exists( 'psb_nav_product_categories' ) ) {
						// Same catalogue source as the desktop mega panel (non-empty categories).
						$psb_kids = array();
						foreach ( psb_nav_product_categories() as $psb_c ) {
							$psb_kids[] = array( 'url' => $psb_c['url'], 'title' => $psb_c['name'], 'count' => $psb_c['count'] );
						}
					}
					?>
					<li class="<?php echo $psb_top['current'] ? 'is-active' : ''; ?>">
						<?php if ( empty( $psb_kids ) ) : ?>
							<a href="<?php echo esc_url( $psb_top['url'] ); ?>" <?php echo $psb_top['current'] ? $psb_current( $psb_top['url'] ) : ''; // phpcs:ignore ?>><span class="flex items-center gap-3"><?php echo $psb_row_icon; // phpcs:ignore ?><?php echo esc_html( $psb_top['title'] ); ?></span></a>
						<?php else : ?>
							<details class="drawer-acc" name="drawer-acc" <?php echo $psb_top['current'] ? 'open' : ''; ?>>
								<summary><span class="flex items-center gap-3"><?php echo $psb_row_icon; // phpcs:ignore ?><?php echo esc_html( $psb_top['title'] ); ?></span></summary>
								<ul class="sub-menu">
									<?php if ( $psb_top['url'] ) : ?>
										<li><a href="<?php echo esc_url( $psb_top['url'] ); ?>" class="!font-bold" <?php echo $psb_current( $psb_top['url'] ); // phpcs:ignore ?>><?php echo esc_html( $psb_all_label[ $psb_section ] ?? ( 'مشاهده صفحه ' . $psb_top['title'] ) ); ?></a></li>
									<?php endif; ?>
									<?php foreach ( $psb_kids as $psb_kid ) : ?>
										<?php
										if ( $psb_top['url'] && untrailingslashit( $psb_kid['url'] ) === untrailingslashit( $psb_top['url'] ) ) {
											continue;
										}
										?>
										<li>
											<a href="<?php echo esc_url( $psb_kid['url'] ); ?>" class="gap-3" <?php echo $psb_current( $psb_kid['url'] ); // phpcs:ignore ?>>
												<span class="flex-1"><?php echo psb_lat( $psb_kid['title'] ); // phpcs:ignore ?></span>
												<?php if ( ! empty( $psb_kid['count'] ) ) : ?>
													<span class="text-xs text-slate-600"><?php echo esc_html( psb_fa_num( $psb_kid['count'] ) ); ?></span>
												<?php endif; ?>
											</a>
										</li>
									<?php endforeach; ?>
								</ul>
							</details>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
		</nav>

		<div class="drawer-foot">
			<a class="btn btn-primary btn-block h-[52px]" href="<?php echo esc_url( psb_quote_url() ); ?>">استعلام قیمت</a>
			<?php
			$psb_wa = '';
			foreach ( psb_socials() as $psb_s ) {
				if ( 'whatsapp' === $psb_s['name'] ) {
					$psb_wa = $psb_s['url'];
				}
			}
			?>
			<div class="grid grid-cols-2 gap-2">
				<?php if ( $psb_phone ) : ?>
					<a class="btn btn-secondary btn-block" href="<?php echo esc_url( $psb_phone['href'] ); ?>"><?php echo psb_icon( 'phone' ); // phpcs:ignore ?>تماس</a>
				<?php endif; ?>
				<?php if ( $psb_wa ) : ?>
					<a class="btn btn-secondary btn-block" href="<?php echo esc_url( $psb_wa ); ?>" target="_blank" rel="noopener"><?php get_template_part( 'template-parts/svg/socials/whatsapp', null, array( 'size' => 16 ) ); ?>واتساپ</a>
				<?php endif; ?>
			</div>
		</div>
		</div>
	</div>
</div>
