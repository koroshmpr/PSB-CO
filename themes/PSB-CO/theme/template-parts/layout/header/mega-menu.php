<?php
/**
 * Desktop mega-menu (≥ 1100px). Disclosure pattern:
 * - the top-level link navigates; the chevron button (aria-expanded/aria-controls) opens the panel
 * - hover opens after 150ms and closes after a 300ms grace; click / Enter / Space toggle
 * - Esc closes and returns focus to the chevron; a click outside closes
 * The Alpine state (open, enter, leave, toggle, close) lives on <header> (header-content.php).
 *
 * $args['part']: 'nav' (top-level items, inside the header bar) | 'panels' (rendered as direct
 * children of <header> so the bar's compact transform never repositions them).
 * Data: psb_nav_tree( 'menu-1' ) (inc/nav.php).
 */
$psb_tree = psb_nav_tree( 'menu-1' );
if ( ! $psb_tree ) {
	return;
}
$psb_part = $args['part'] ?? 'nav';
$psb_has  = function ( $top ) {
	return in_array( $top['panel'] ?? '', array( 'products', 'credentials', 'about' ), true );
};

if ( 'panels' === $psb_part ) :
	foreach ( $psb_tree as $psb_top ) :
		if ( ! $psb_has( $psb_top ) ) {
			continue;
		}
		$psb_id = (int) $psb_top['id'];
		?>
		<div class="mm-panel" id="mm-panel-<?php echo $psb_id; // phpcs:ignore ?>"
			x-show="open === <?php echo $psb_id; // phpcs:ignore ?>" x-cloak
			x-transition:enter="transition duration-[180ms] ease-out" x-transition:enter-start="opacity-0 -translate-y-1.5" x-transition:enter-end="opacity-100 translate-y-0"
			x-transition:leave="transition duration-[160ms] ease-in" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1.5"
			@mouseenter="enter(<?php echo $psb_id; // phpcs:ignore ?>)" @mouseleave="leave()"
			@keydown.tab="panelTab($event, <?php echo $psb_id; // phpcs:ignore ?>)">
			<div class="container py-7 xl:py-8">
				<?php get_template_part( 'template-parts/layout/header/mega-panel', null, array( 'top' => $psb_top ) ); ?>
			</div>
		</div>
		<?php
	endforeach;
	return;
endif;
?>
<nav class="site-nav" aria-label="منوی اصلی">
	<ul class="flex items-center">
		<?php foreach ( $psb_tree as $psb_top ) : ?>
			<?php
			$psb_id   = (int) $psb_top['id'];
			$psb_drop = $psb_has( $psb_top );
			?>
			<li class="mm-item <?php echo $psb_top['current'] ? 'is-current is-active' : ''; ?>"
				<?php if ( $psb_drop ) : ?>
					@mouseenter="enter(<?php echo $psb_id; // phpcs:ignore ?>)" @mouseleave="leave()"
				<?php endif; ?>>
				<?php if ( $psb_top['url'] ) : ?>
					<a class="mm-top" href="<?php echo esc_url( $psb_top['url'] ); ?>" <?php echo $psb_top['current'] ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $psb_top['title'] ); ?></a>
				<?php else : ?>
					<button type="button" class="mm-top" @click="toggle(<?php echo $psb_id; // phpcs:ignore ?>, $event.detail === 0)"><?php echo esc_html( $psb_top['title'] ); ?></button>
				<?php endif; ?>
				<?php if ( $psb_drop ) : ?>
					<button type="button" class="mm-toggle" id="mm-btn-<?php echo $psb_id; // phpcs:ignore ?>"
						aria-controls="mm-panel-<?php echo $psb_id; // phpcs:ignore ?>"
						:aria-expanded="open === <?php echo $psb_id; // phpcs:ignore ?>"
						@click="toggle(<?php echo $psb_id; // phpcs:ignore ?>, $event.detail === 0)"
						@keydown.arrow-down.prevent="open = <?php echo $psb_id; // phpcs:ignore ?>; $nextTick(() => document.querySelector('#mm-panel-<?php echo $psb_id; // phpcs:ignore ?> a')?.focus())">
						<span class="sr-only">زیرمنوی <?php echo esc_html( $psb_top['title'] ); ?></span>
					</button>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
