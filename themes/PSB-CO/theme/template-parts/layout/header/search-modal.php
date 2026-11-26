<?php
/**
 * Instant site search (combobox dialog). Engine + REST endpoint: inc/search-route.php.
 *
 * Open: searchOpen = true (header button, tab bar, drawer), Ctrl/Cmd+K or "/".
 * Keys: ↑/↓ move, Enter opens (or goes to the results page), Esc closes.
 * Mobile: full-screen sheet sized to window.visualViewport so it stays above the keyboard.
 */
$psb_phone   = psb_primary_phone();
$psb_popular = psb_search_popular();
$psb_icons   = array();
foreach ( psb_search_types() as $psb_type => $psb_info ) {
	$psb_icons[ $psb_type ] = $psb_info[1];
}
$psb_cfg = array(
	'endpoint' => esc_url_raw( rest_url( 'psb/v1/search' ) ),
	'results'  => esc_url_raw( home_url( '/' ) ),
	'quote'    => esc_url_raw( psb_quote_url() ),
);
?>
<section id="searchModal" role="dialog" aria-modal="true" aria-labelledby="psb-search-label"
	x-show="searchOpen"
	x-cloak
	x-data="psbSearch(<?php echo esc_attr( wp_json_encode( $psb_cfg ) ); ?>)"
	x-init="init()"
	@keydown.window="hotkey($event)"
	x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
	x-transition:leave="transition duration-100 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
	@click.self="close()"
	class="search-sheet fixed inset-x-0 top-0 z-[55] flex items-start justify-center bg-dark/50 sm:p-6 sm:pt-[8vh]"
	:style="vh ? `height:${vh}px` : 'height:100dvh'">

	<div class="flex h-full w-full max-w-3xl flex-col overflow-hidden bg-white shadow-lift sm:h-auto sm:max-h-[80vh] sm:rounded-panel sm:border sm:border-line">
		<form role="search" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="flex shrink-0 items-center gap-2 border-b border-line p-3 sm:p-4" @submit="onSubmit($event)">
			<span class="text-slate-400"><?php echo psb_icon( 'search', 'size-5' ); // phpcs:ignore ?></span>
			<label id="psb-search-label" for="psb-search-input" class="sr-only">جستجو در سایت</label>
			<input id="psb-search-input" x-ref="q" type="search" name="s" autocomplete="off" enterkeyhint="search" spellcheck="false"
				role="combobox" aria-autocomplete="list" aria-controls="psb-search-list"
				:aria-expanded="flat.length > 0" :aria-activedescendant="active >= 0 ? 'psb-opt-' + active : null"
				x-model="query" @input="onInput()" @keydown="onKey($event)"
				placeholder="جستجوی محصول یا تأییدیه…"
				class="min-h-11 min-w-0 flex-1 bg-transparent text-base text-dark placeholder:text-slate-500 focus:outline-none">
			<svg x-show="loading" class="size-5 animate-spin text-slate-600" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".2"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
			<button type="button" x-show="query.length && !loading" @click="clear()" class="icon-btn" aria-label="پاک کردن عبارت">
				<?php echo psb_icon( 'close', 'size-4' ); // phpcs:ignore ?>
			</button>
			<button type="button" @click="close()" class="btn btn-ghost btn-sm shrink-0">بستن</button>
		</form>

		<div class="flex-1 overflow-y-auto overscroll-contain" x-ref="scroller">
			<p class="sr-only" aria-live="polite" x-text="status"></p>

			<!-- Empty state -->
			<div x-show="query.trim().length < 2" class="grid gap-6 p-4 sm:p-6">
				<div x-show="recent.length">
					<div class="mb-2 flex items-center justify-between">
						<p class="text-xs font-semibold text-slate-600">جستجوهای اخیر</p>
						<button type="button" class="text-xs text-slate-600 hover:text-primary-700" @click="clearRecent()">پاک کردن</button>
					</div>
					<div class="flex flex-wrap gap-2">
						<template x-for="r in recent" :key="r">
							<button type="button" class="chip" @click="pick(r)" x-text="r"></button>
						</template>
					</div>
				</div>
				<div>
					<p class="mb-2 text-xs font-bold text-slate-500">جستجوهای پرتکرار</p>
					<div class="flex flex-wrap gap-2">
						<?php foreach ( $psb_popular as $psb_term ) : ?>
							<button type="button" class="chip" @click="pick(<?php echo esc_attr( wp_json_encode( $psb_term ) ); ?>)"><?php echo esc_html( $psb_term ); ?></button>
						<?php endforeach; ?>
					</div>
				</div>
				<a class="flex items-center gap-3 rounded-card border border-line bg-slate-50 p-4 transition-colors hover:border-line-strong" href="<?php echo esc_url( psb_quote_url() ); ?>">
					<span class="icon-tile"><?php echo psb_icon( 'quote' ); // phpcs:ignore ?></span>
					<span class="flex-1"><strong class="block text-sm text-dark">استعلام قیمت</strong><span class="text-xs text-slate-600">مشخصات پروژه را ارسال کنید تا کارشناسان فروش با شما تماس بگیرند</span></span>
					<?php echo psb_icon( 'arrow', 'btn-arrow size-4 text-primary-700' ); // phpcs:ignore ?>
				</a>
			</div>

			<!-- Results (combobox listbox) -->
			<div x-show="query.trim().length >= 2 && groups.length" role="listbox" id="psb-search-list" aria-label="نتایج جستجو" class="p-2 sm:p-3">
				<template x-for="g in groups" :key="g.type">
					<div role="group" :aria-labelledby="'psb-g-' + g.type" class="mb-2">
						<div class="flex items-center justify-between px-3 pb-1 pt-3">
							<p class="text-xs font-semibold text-slate-600" :id="'psb-g-' + g.type"><span x-text="g.label"></span> <span class="text-slate-600" x-text="'(' + faNum(g.count) + ')'"></span></p>
							<a x-show="g.count > g.items.length" :href="g.more" class="text-xs font-semibold text-primary-700 hover:underline" tabindex="-1">همه</a>
						</div>
						<template x-for="it in g.items" :key="it.id">
							<a role="option" :id="'psb-opt-' + it.idx" :href="it.url" :aria-selected="active === it.idx"
								@mouseenter="active = it.idx" @click="remember()"
								class="flex items-start gap-3 rounded-control px-3 py-2.5 outline-none transition-colors aria-selected:bg-slate-100"
								tabindex="-1">
								<span class="icon-tile !size-10 shrink-0 overflow-hidden !p-0">
									<template x-if="it.thumb"><img :src="it.thumb" alt="" width="40" height="40" loading="eager" decoding="async" class="size-full object-cover"></template>
									<template x-if="!it.thumb"><svg class="size-5" aria-hidden="true"><use :href="'#i-' + (icons[it.type] || 'doc')"></use></svg></template>
								</span>
								<span class="min-w-0 flex-1">
									<span class="search-hl block truncate text-sm font-bold text-dark" x-html="it.title"></span>
									<span x-show="it.meta" class="block truncate text-xs font-medium text-slate-600" x-text="it.meta"></span>
									<span class="search-hl mt-0.5 line-clamp-2 text-xs leading-6 text-slate-600" x-html="it.snippet"></span>
								</span>
							</a>
						</template>
					</div>
				</template>
				<a :href="resultsUrl()" class="mx-1 mt-1 flex items-center justify-between rounded-control border border-line px-3 py-3 text-sm font-semibold text-foreground hover:bg-slate-50">
					<span x-text="'مشاهده همه ' + faNum(total) + ' نتیجه برای «' + query.trim() + '»'"></span>
					<?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?>
				</a>
			</div>

			<!-- No results -->
			<div x-show="query.trim().length >= 2 && !loading && searched && !groups.length" class="p-6 text-center">
				<p class="text-base font-bold text-dark">نتیجه‌ای برای «<span x-text="query.trim()"></span>» پیدا نشد</p>
				<p class="mt-1 text-sm text-slate-500">املای عبارت را بررسی کنید یا یکی از این جستجوها را امتحان کنید:</p>
				<div class="mt-4 flex flex-wrap justify-center gap-2">
					<template x-for="s in suggest" :key="s">
						<button type="button" class="chip" @click="pick(s)" x-text="s"></button>
					</template>
				</div>
				<div class="mt-6 flex flex-wrap justify-center gap-3">
					<a class="btn btn-primary" href="<?php echo esc_url( psb_quote_url() ); ?>">ثبت درخواست استعلام قیمت</a>
					<?php if ( $psb_phone ) : ?>
						<a class="btn btn-secondary" href="<?php echo esc_url( $psb_phone['href'] ); ?>"><?php echo psb_icon( 'phone' ); // phpcs:ignore ?><span class="ltr-num"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></span></a>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<p class="hidden shrink-0 items-center gap-4 border-t border-line bg-slate-50 px-4 py-2 text-[0.7rem] text-slate-500 sm:flex">
			<span><kbd class="rounded border border-line bg-white px-1.5 font-mono">↑↓</kbd> حرکت</span>
			<span><kbd class="rounded border border-line bg-white px-1.5 font-mono">Enter</kbd> باز کردن</span>
			<span><kbd class="rounded border border-line bg-white px-1.5 font-mono">Esc</kbd> بستن</span>
			<span class="ms-auto"><kbd class="rounded border border-line bg-white px-1.5 font-mono" dir="ltr">Ctrl K</kbd> یا <kbd class="rounded border border-line bg-white px-1.5 font-mono">/</kbd> برای جستجو</span>
		</p>
	</div>
</section>
<script>
window.psbSearchIcons = <?php echo wp_json_encode( $psb_icons, JSON_HEX_TAG | JSON_HEX_AMP ); ?>;
</script>
