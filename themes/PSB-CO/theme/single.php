<?php
/**
 * Single article: light hero (H1, category, date, reading time, author name without link),
 * series header + pager + mobile "next" bar when the post belongs to a series, TOC, article prose,
 * FAQ, share, author box, quote CTA, related articles, comments.
 *
 * @package bluebox
 */

if ( function_exists( 'track_post_views' ) && have_posts() ) {
	track_post_views( get_queried_object_id() ); // Sets a cookie: must run before any output.
}

get_header();

while ( have_posts() ) :
	the_post();
	$psb_id   = get_the_ID();
	$psb_cats = get_the_category();
	$psb_pos  = function_exists( 'psb_series_position' ) ? psb_series_position( $psb_id ) : null;

	// Filter the body once: used for output and for the table of contents. Body H1s become H2 (one H1 per page).
	$psb_html = apply_filters( 'the_content', get_the_content() ); // phpcs:ignore
	$psb_html = preg_replace( array( '#<h1(\s|>)#i', '#</h1>#i' ), array( '<h2$1', '</h2>' ), $psb_html );
	$psb_toc  = psb_toc_items( $psb_html );
	$psb_html = psb_lat_content( $psb_html ); // Latin codes (IEC 60364-7-710) keep Latin digits.

	$psb_modified = get_the_modified_time( 'U' ) > get_the_time( 'U' ) + DAY_IN_SECONDS;
	?>
	<header class="page-hero page-hero--light">
		<div class="container max-w-content">
			<?php get_template_part( 'template-parts/global/breadcrumb' ); ?>
			<div class="mt-6 flex flex-wrap items-center gap-2">
				<?php if ( $psb_pos ) : ?>
					<a class="badge <?php echo esc_attr( $psb_pos['info']['palette']['soft'] . ' ' . $psb_pos['info']['palette']['text'] . ' ' . $psb_pos['info']['palette']['border'] ); ?>" href="<?php echo esc_url( $psb_pos['info']['url'] ); ?>">سری <?php echo esc_html( $psb_pos['info']['name'] ); ?> · قسمت <?php echo esc_html( psb_fa_num( $psb_pos['number'] ) ); ?></a>
				<?php elseif ( $psb_cats ) : ?>
					<a class="badge badge-primary" href="<?php echo esc_url( get_category_link( $psb_cats[0] ) ); ?>"><?php echo esc_html( $psb_cats[0]->name ); ?></a>
				<?php endif; ?>
			</div>
			<h1 class="page-hero-title !mt-3"><?php echo psb_lat_html( get_the_title() ); // phpcs:ignore -- escaped in helper ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="page-hero-lead"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt() ) ); ?></p>
			<?php endif; ?>
			<ul class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500" role="list">
				<li class="inline-flex items-center gap-1.5"><?php echo psb_icon( 'calendar', 'size-4' ); // phpcs:ignore ?><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( psb_post_date() ); ?></time></li>
				<?php if ( $psb_modified ) : ?>
					<li>به‌روزرسانی: <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( psb_fa_num( function_exists( 'shamsi_date' ) ? ltrim( shamsi_date( 'd F Y', get_the_modified_time( 'U' ) ), '0' ) : get_the_modified_date() ) ); ?></time></li>
				<?php endif; ?>
				<li class="inline-flex items-center gap-1.5"><?php echo psb_icon( 'doc', 'size-4' ); // phpcs:ignore ?><?php echo esc_html( psb_fa_num( psb_reading_minutes() ) ); ?> دقیقه مطالعه</li>
				<li>تیم فنی <?php echo esc_html( psb_brand_name() ); ?></li>
			</ul>
		</div>
	</header>

	<div class="container py-section-sm">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php
			$psb_cover_attr = array( 'alt' => get_the_title(), 'sizes' => '(min-width: 768px) 46rem, 100vw', 'loading' => 'eager', 'fetchpriority' => 'high', 'class' => 'aspect-[16/9] w-full object-cover', 'style' => 'object-position:50% 40%' );
			$psb_cover      = function_exists( 'psb_single_image' ) ? psb_single_image( get_post_thumbnail_id(), 736, '16 / 9', array( 'attr' => $psb_cover_attr ) ) : array( 'mode' => 'photo', 'html' => psb_image( get_post_thumbnail_id(), 'large', $psb_cover_attr ) );
			?>
			<?php if ( 'lowres' === $psb_cover['mode'] ) : // Too small for a 16:9 cover: natural size in a frame, never upscaled. ?>
				<figure class="ph-frame mx-auto mb-10 max-h-[32rem] max-w-prose">
					<?php echo str_replace( array( 'aspect-[16/9] w-full object-cover', 'object-position:50% 40%' ), array( 'max-h-[28rem]', '' ), $psb_cover['html'] ); // phpcs:ignore ?>
				</figure>
			<?php else : ?>
				<figure class="mx-auto mb-10 max-w-prose overflow-hidden rounded-card border border-line">
					<?php echo $psb_cover['html']; // phpcs:ignore ?>
				</figure>
			<?php endif; ?>
		<?php endif; ?>

		<div class="mx-auto grid max-w-prose gap-10 xl:max-w-none xl:grid-cols-[minmax(0,46rem)_18rem] xl:justify-center xl:gap-16">
			<aside class="order-last" aria-label="فهرست و استعلام">
				<div class="toc-stick flex flex-col gap-5">
					<?php get_template_part( 'template-parts/blog/single/toc', null, array( 'items' => $psb_toc, 'variant' => 'side' ) ); ?>
					<div class="panel">
						<p class="panel-title">استعلام قیمت تابلو برق</p>
						<p class="text-sm leading-7 text-slate-600">مشخصات یا نقشه تک‌خطی پروژه را ارسال کنید تا پیشنهاد فنی و قیمت ارائه شود.</p>
						<a class="btn btn-outline btn-block mt-4" href="<?php echo esc_url( psb_quote_url() ); ?>">ثبت درخواست استعلام</a>
					</div>
				</div>
			</aside>

			<article class="min-w-0" data-article="<?php echo (int) $psb_id; ?>">
				<?php get_template_part( 'template-parts/blog/series-header', null, array( 'pos' => $psb_pos ) ); ?>

				<?php get_template_part( 'template-parts/blog/single/toc', null, array( 'items' => $psb_toc, 'variant' => 'inline' ) ); ?>

				<div class="article-prose"><?php echo $psb_html; // phpcs:ignore -- filtered post content ?></div>

				<?php
				get_template_part(
					'template-parts/global/faq-list',
					null,
					array(
						'title' => 'پرسش‌های متداول',
						'class' => 'mt-12',
					)
				);
				get_template_part( 'template-parts/blog/series-pager', null, array( 'pos' => $psb_pos ) );
				get_template_part( 'template-parts/blog/single/share-button' );
				get_template_part( 'template-parts/blog/single/author-box' );
				?>
			</article>
		</div>
	</div>

	<?php
	$psb_related = array();
	if ( $psb_pos ) {
		$psb_related = array_values( array_diff( $psb_pos['info']['posts'], array( $psb_id, $psb_pos['next'], $psb_pos['prev'] ) ) );
	}
	get_template_part(
		'template-parts/blog/related-articles',
		null,
		array(
			'ids'      => array_slice( $psb_related, 0, 3 ),
			'category' => ( ! $psb_related && $psb_cats ) ? $psb_cats[0]->term_id : 0,
			'exclude'  => array( $psb_id ),
			'title'    => $psb_related ? 'قسمت‌های دیگر این سری' : 'مقالات مرتبط',
			'class'    => 'bg-white',
		)
	);

	if ( comments_open() || get_comments_number() ) :
		?>
		<section class="border-t border-line py-section-sm" aria-label="دیدگاه‌ها">
			<div class="container max-w-content"><?php comments_template(); ?></div>
		</section>
		<?php
	endif;

	get_template_part( 'template-parts/blog/series-bar', null, array( 'pos' => $psb_pos ) );
endwhile;

get_footer();
