<?php
/**
 * End-of-article series navigation: big «مقاله بعدی» card (+ previous part).
 *
 * $args: pos (psb_series_position array)
 */
$psb_pos = $args['pos'] ?? null;
if ( ! $psb_pos || ( ! $psb_pos['next'] && ! $psb_pos['prev'] ) ) {
	return;
}
$psb_p = $psb_pos['info']['palette'];
?>
<nav class="mt-12 grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]" aria-label="قسمت‌های سری">
	<?php if ( $psb_pos['prev'] ) : ?>
		<a class="card card-hover justify-center p-5" href="<?php echo esc_url( get_permalink( $psb_pos['prev'] ) ); ?>" rel="prev">
			<span class="text-xs font-bold text-slate-500">قسمت قبلی · <?php echo esc_html( psb_fa_num( $psb_pos['number'] - 1 ) ); ?></span>
			<span class="mt-1 font-bold leading-7 text-dark"><?php echo esc_html( get_the_title( $psb_pos['prev'] ) ); ?></span>
		</a>
	<?php else : ?>
		<span class="hidden sm:block" aria-hidden="true"></span>
	<?php endif; ?>
	<?php if ( $psb_pos['next'] ) : ?>
		<a class="card card-hover flex-row items-center gap-4 p-5 sm:p-6" href="<?php echo esc_url( get_permalink( $psb_pos['next'] ) ); ?>" rel="next">
			<?php if ( has_post_thumbnail( $psb_pos['next'] ) ) : ?>
				<span class="hidden w-28 shrink-0 overflow-hidden rounded-control sm:block"><?php echo psb_image( get_post_thumbnail_id( $psb_pos['next'] ), 'medium', array( 'alt' => '', 'sizes' => '7rem', 'class' => 'aspect-[4/3] size-full object-cover' ) ); // phpcs:ignore ?></span>
			<?php endif; ?>
			<span class="min-w-0 flex-1">
				<span class="text-xs font-bold <?php echo esc_attr( $psb_p['text'] ); ?>">مقاله بعدی · قسمت <?php echo esc_html( psb_fa_num( $psb_pos['number'] + 1 ) ); ?> از <?php echo esc_html( psb_fa_num( $psb_pos['total'] ) ); ?></span>
				<span class="mt-1 block text-lg font-bold leading-8 text-dark"><?php echo esc_html( get_the_title( $psb_pos['next'] ) ); ?></span>
			</span>
			<?php echo psb_icon( 'arrow', 'btn-arrow size-6 shrink-0 text-foreground' ); // phpcs:ignore ?>
		</a>
	<?php else : ?>
		<a class="card card-hover flex-row items-center gap-4 p-5 sm:p-6" href="<?php echo esc_url( $psb_pos['info']['url'] ); ?>">
			<span class="min-w-0 flex-1"><span class="text-xs font-bold text-emerald-700">پایان سری</span><span class="mt-1 block font-bold text-dark">بازگشت به فهرست سری <?php echo esc_html( $psb_pos['info']['name'] ); ?></span></span>
			<?php echo psb_icon( 'arrow', 'btn-arrow size-6 shrink-0 text-foreground' ); // phpcs:ignore ?>
		</a>
	<?php endif; ?>
</nav>
