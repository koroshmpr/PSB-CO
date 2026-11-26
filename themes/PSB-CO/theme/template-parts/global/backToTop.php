<?php
/**
 * Back-to-top floating button (appears after scrolling, above the mobile CTA bar).
 */
?>
<button type="button" class="fab" x-cloak
	x-show="lastScroll > window.innerHeight * 2 && !atBottom"
	x-transition.opacity
	@click="window.scrollTo({ top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); document.getElementById('main')?.focus({ preventScroll: true })"
	aria-label="بازگشت به بالای صفحه">
	<?php echo psb_icon( 'up', 'size-5' ); // phpcs:ignore ?>
</button>
