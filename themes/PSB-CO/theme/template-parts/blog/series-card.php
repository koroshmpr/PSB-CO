<?php
/**
 * Series card: accent colour, icon, name, description, parts count, total reading time,
 * progress bar (filled from localStorage by psb_series_script()).
 *
 * $args: info (psb_series_info array), tag (h2|h3), class
 */
$psb_s = $args['info'] ?? null;
if ( ! $psb_s ) {
	return;
}
psb_series_script();
$psb_p   = $psb_s['palette'];
$psb_tag = in_array( $args['tag'] ?? 'h3', array( 'h2', 'h3' ), true ) ? $args['tag'] ?? 'h3' : 'h3';
?>
<article class="card card-hover <?php echo esc_attr( $args['class'] ?? '' ); ?>" style="--series:<?php echo esc_attr( $psb_p['hex'] ); ?>">
	<div class="h-1.5 w-full <?php echo esc_attr( $psb_p['bg'] ); ?>" aria-hidden="true"></div>
	<div class="card-body">
		<div class="flex items-center justify-between gap-3">
			<span class="inline-flex size-11 items-center justify-center rounded-control border <?php echo esc_attr( $psb_p['border'] . ' ' . $psb_p['soft'] . ' ' . $psb_p['text'] ); ?>"><?php echo psb_icon( $psb_s['icon'] ); // phpcs:ignore ?></span>
			<span class="text-xs font-bold text-slate-500"><?php echo esc_html( psb_fa_num( $psb_s['count'] ) ); ?> قسمت · <?php echo esc_html( psb_fa_num( $psb_s['minutes'] ) ); ?> دقیقه</span>
		</div>
		<span class="card-eyebrow <?php echo esc_attr( $psb_p['text'] ); ?>">سری مقالات</span>
		<<?php echo $psb_tag; // phpcs:ignore ?> class="card-title"><a class="card-link" href="<?php echo esc_url( $psb_s['url'] ); ?>"><?php echo esc_html( $psb_s['name'] ); ?></a></<?php echo $psb_tag; // phpcs:ignore ?>>
		<?php if ( $psb_s['description'] ) : ?>
			<p class="card-text"><?php echo esc_html( $psb_s['description'] ); ?></p>
		<?php endif; ?>
	</div>
	<div class="card-footer flex-col !items-stretch !gap-2">
		<div class="flex items-center justify-between text-xs text-slate-500">
			<span>پیشرفت مطالعه</span>
			<span data-series-progress-label="<?php echo esc_attr( implode( ',', $psb_s['posts'] ) ); ?>">شروع نشده</span>
		</div>
		<div class="h-1.5 overflow-hidden rounded-[2px] bg-slate-100" aria-hidden="true">
			<div class="h-full w-0 transition-[width] duration-500 <?php echo esc_attr( $psb_p['bg'] ); ?>" data-series-progress="<?php echo esc_attr( implode( ',', $psb_s['posts'] ) ); ?>"></div>
		</div>
	</div>
</article>
