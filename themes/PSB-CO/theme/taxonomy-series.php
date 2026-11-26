<?php
/**
 * Series archive (/series/<slug>/): hero with description, parts count, total reading time,
 * pillar first, ordered list as a course outline (read parts ticked from localStorage).
 *
 * @package bluebox
 */

get_header();

$psb_s = psb_series_info( get_queried_object() );
if ( $psb_s ) :
	psb_series_script();
	$psb_p = $psb_s['palette'];
	?>
	<section class="page-hero page-hero--light">
		<div class="container">
			<?php get_template_part( 'template-parts/global/breadcrumb' ); ?>
			<div class="mt-6 flex items-start gap-4">
				<span class="hidden size-14 shrink-0 items-center justify-center rounded-card border sm:inline-flex <?php echo esc_attr( $psb_p['border'] . ' ' . $psb_p['soft'] . ' ' . $psb_p['text'] ); ?>"><?php echo psb_icon( $psb_s['icon'], 'size-7' ); // phpcs:ignore ?></span>
				<div class="min-w-0">
					<span class="eyebrow">سری مقالات</span>
					<h1 class="page-hero-title !mt-2"><?php echo esc_html( $psb_s['name'] ); ?></h1>
					<?php if ( $psb_s['description'] ) : ?>
						<p class="page-hero-lead"><?php echo esc_html( $psb_s['description'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<dl class="mt-8 flex flex-wrap gap-3 text-sm">
				<div class="rounded-control border border-line bg-white px-4 py-2"><dt class="sr-only">تعداد قسمت‌ها</dt><dd><strong class="text-dark"><?php echo esc_html( psb_fa_num( $psb_s['count'] ) ); ?></strong> قسمت</dd></div>
				<div class="rounded-control border border-line bg-white px-4 py-2"><dt class="sr-only">زمان مطالعه</dt><dd><strong class="text-dark"><?php echo esc_html( psb_fa_num( $psb_s['minutes'] ) ); ?></strong> دقیقه مطالعه</dd></div>
				<div class="flex min-w-48 items-center gap-3 rounded-control border border-line bg-white px-4 py-2"><dt class="text-slate-500">پیشرفت شما</dt><dd class="h-1.5 flex-1 overflow-hidden rounded-[2px] bg-slate-100"><span class="block h-full w-0 <?php echo esc_attr( $psb_p['bg'] ); ?>" data-series-progress="<?php echo esc_attr( implode( ',', $psb_s['posts'] ) ); ?>"></span></dd></div>
			</dl>
			<?php if ( $psb_s['pillar'] ) : ?>
				<div class="mt-6"><a class="btn btn-primary" href="<?php echo esc_url( get_permalink( $psb_s['pillar'] ) ); ?>">شروع از مقاله پایه <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></a></div>
			<?php endif; ?>
		</div>
	</section>

	<section class="container py-section" aria-labelledby="series-outline-title">
		<h2 id="series-outline-title" class="section-title mb-8">فهرست قسمت‌ها</h2>
		<?php
		$psb_order = $psb_s['posts'];
		if ( $psb_s['pillar'] ) {
			$psb_order = array_values( array_unique( array_merge( array( (int) $psb_s['pillar'] ), $psb_order ) ) );
		}
		?>
		<ol class="mx-auto flex max-w-content flex-col gap-4" role="list">
			<?php foreach ( $psb_order as $psb_i => $psb_id ) : ?>
				<?php $psb_is_pillar = (int) $psb_id === (int) $psb_s['pillar']; ?>
				<li data-series-item="<?php echo (int) $psb_id; ?>">
					<article class="card card-hover flex-row items-stretch <?php echo $psb_is_pillar ? esc_attr( $psb_p['border'] ) : ''; ?>">
						<div class="flex w-16 shrink-0 flex-col items-center justify-center gap-1 border-e border-line bg-slate-50 sm:w-20">
							<span class="series-num inline-flex size-9 items-center justify-center rounded-control border border-line bg-white text-sm font-bold text-slate-600">
								<span class="series-num-n"><?php echo esc_html( psb_fa_num( $psb_i + 1 ) ); ?></span>
								<?php echo psb_icon( 'check', 'series-num-check hidden size-4 text-emerald-600' ); // phpcs:ignore ?>
							</span>
						</div>
						<div class="card-body">
							<?php if ( $psb_is_pillar ) : ?>
								<span class="card-eyebrow <?php echo esc_attr( $psb_p['text'] ); ?>">مقاله پایه سری</span>
							<?php endif; ?>
							<h3 class="card-title"><a class="card-link" href="<?php echo esc_url( get_permalink( $psb_id ) ); ?>"><?php echo psb_lat_html( get_the_title( $psb_id ) ); // phpcs:ignore ?></a></h3>
							<p class="card-text"><?php echo esc_html( wp_trim_words( get_the_excerpt( $psb_id ), 26, '…' ) ); ?></p>
							<p class="card-meta"><?php echo esc_html( psb_fa_num( psb_reading_minutes( $psb_id ) ) ); ?> دقیقه مطالعه</p>
						</div>
					</article>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>
	<?php
	$psb_others = array_filter(
		psb_series_all(),
		function ( $x ) use ( $psb_s ) {
			return $x['id'] !== $psb_s['id'];
		}
	);
	if ( $psb_others ) :
		?>
		<section class="border-t border-line bg-white py-section-sm" aria-labelledby="series-others-title">
			<div class="container">
				<?php get_template_part( 'template-parts/global/section-head', null, array( 'id' => 'series-others-title', 'title' => 'سری‌های دیگر' ) ); ?>
				<div class="rail [--rail-item:82%] sm:[--rail-item:46%] lg:grid lg:grid-flow-row lg:grid-cols-3 lg:gap-6 lg:overflow-visible">
					<?php
					foreach ( $psb_others as $psb_o ) {
						get_template_part( 'template-parts/blog/series-card', null, array( 'info' => $psb_o ) );
					}
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php
else :
	get_template_part( 'template-parts/blog/magazine', null, array( 'mode' => 'archive' ) );
endif;

get_footer();
