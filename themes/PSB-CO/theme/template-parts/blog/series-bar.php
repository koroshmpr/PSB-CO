<?php
/**
 * Mobile sticky «بعدی: …» bar. Hidden until ~60% of the article is read; sits above the app tab bar.
 *
 * $args: pos (psb_series_position array)
 */
$psb_pos = $args['pos'] ?? null;
if ( ! $psb_pos || ! $psb_pos['next'] ) {
	return;
}
psb_series_script();
?>
<a class="series-bar" href="<?php echo esc_url( get_permalink( $psb_pos['next'] ) ); ?>" data-series-bar>
	<span class="shrink-0 text-xs font-bold text-primary-700">بعدی</span>
	<span class="min-w-0 flex-1 truncate text-sm font-bold text-dark"><?php echo esc_html( get_the_title( $psb_pos['next'] ) ); ?></span>
	<?php echo psb_icon( 'arrow', 'btn-arrow size-5 shrink-0 text-foreground' ); // phpcs:ignore ?>
</a>
