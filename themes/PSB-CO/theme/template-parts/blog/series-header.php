<?php
/**
 * Series header card on a single post: «قسمت ۳ از ۶ — سری …», progress bar, collapsible list of parts
 * (current highlighted, read ones ticked via localStorage).
 *
 * $args: pos (psb_series_position array)
 */
$psb_pos = $args['pos'] ?? null;
if ( ! $psb_pos ) {
	return;
}
psb_series_script();
$psb_s   = $psb_pos['info'];
$psb_p   = $psb_s['palette'];
$psb_pct = (int) round( $psb_pos['number'] / max( 1, $psb_pos['total'] ) * 100 );
?>
<details class="series-box mb-8 rounded-card border bg-white <?php echo esc_attr( $psb_p['border'] ); ?>" data-series-current="<?php echo (int) get_the_ID(); ?>">
	<summary class="flex cursor-pointer list-none items-center gap-3 p-4 sm:p-5 [&::-webkit-details-marker]:hidden">
		<span class="inline-flex size-11 shrink-0 items-center justify-center rounded-control <?php echo esc_attr( $psb_p['soft'] . ' ' . $psb_p['text'] ); ?>"><?php echo psb_icon( $psb_s['icon'] ); // phpcs:ignore ?></span>
		<span class="min-w-0 flex-1">
			<span class="block text-xs font-bold <?php echo esc_attr( $psb_p['text'] ); ?>">قسمت <?php echo esc_html( psb_fa_num( $psb_pos['number'] ) ); ?> از <?php echo esc_html( psb_fa_num( $psb_pos['total'] ) ); ?></span>
			<span class="block truncate font-bold text-dark">سری <?php echo esc_html( $psb_s['name'] ); ?></span>
			<span class="mt-2 block h-1 overflow-hidden rounded-[2px] bg-slate-100" aria-hidden="true"><span class="block h-full <?php echo esc_attr( $psb_p['bg'] ); ?>" style="width:<?php echo (int) $psb_pct; ?>%"></span></span>
		</span>
		<span class="series-box-toggle text-xs font-bold text-slate-500">فهرست قسمت‌ها <?php echo psb_icon( 'chevron', 'inline size-4 -rotate-90 transition-transform' ); // phpcs:ignore ?></span>
	</summary>
	<ol class="border-t border-line px-2 py-2 sm:px-3" role="list">
		<?php foreach ( $psb_s['posts'] as $psb_i => $psb_id ) : ?>
			<?php $psb_cur = (int) $psb_id === (int) get_the_ID(); ?>
			<li data-series-item="<?php echo (int) $psb_id; ?>">
				<a class="group flex min-h-11 items-center gap-3 rounded-control px-2 py-2 text-sm <?php echo $psb_cur ? esc_attr( $psb_p['soft'] ) . ' font-bold text-dark' : 'text-slate-700 hover:bg-slate-50'; ?>" href="<?php echo esc_url( get_permalink( $psb_id ) ); ?>" <?php echo $psb_cur ? 'aria-current="page"' : ''; ?>>
					<span class="series-num inline-flex size-7 shrink-0 items-center justify-center rounded-control border border-line bg-white text-xs font-bold text-slate-500">
						<span class="series-num-n"><?php echo esc_html( psb_fa_num( $psb_i + 1 ) ); ?></span>
						<?php echo psb_icon( 'check', 'series-num-check hidden size-4 text-emerald-600' ); // phpcs:ignore ?>
					</span>
					<span class="min-w-0 flex-1"><?php echo esc_html( get_the_title( $psb_id ) ); ?></span>
					<span class="shrink-0 text-xs text-slate-500"><?php echo esc_html( psb_fa_num( psb_reading_minutes( $psb_id ) ) ); ?> دقیقه</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ol>
	<p class="border-t border-line px-5 py-3 text-xs"><a class="link-arrow" href="<?php echo esc_url( $psb_s['url'] ); ?>">صفحه سری <?php echo esc_html( $psb_s['name'] ); ?> <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></a></p>
</details>
