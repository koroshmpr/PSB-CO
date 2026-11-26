<?php
/**
 * Blog magazine layout (blog home, category, tag and date archives). Uses the main query.
 *
 * Page 1 of the blog home: hero + search, topic chips, featured pillar, series rail, latest grid,
 * most read, editor's picks. Archives and later pages: hero, chips, grid.
 * Pagination is progressively enhanced into a «بیشتر» (load more) button.
 *
 * $args: mode ('home'|'category'|'archive')
 */
global $wp_query;
$psb_mode  = $args['mode'] ?? 'home';
$psb_paged = max( 1, (int) get_query_var( 'paged' ) );
$psb_first = 'home' === $psb_mode && 1 === $psb_paged;
$psb_blog  = (int) get_option( 'page_for_posts' );
$psb_burl  = $psb_blog ? get_permalink( $psb_blog ) : home_url( '/blog/' );
$psb_term  = ( is_category() || is_tag() ) ? get_queried_object() : null;

if ( 'home' === $psb_mode ) {
	$psb_title = 'مقالات آموزشی تابلو برق';
	$psb_lead  = 'مقالات فنی درباره طراحی، ساخت، آزمون و انتخاب تابلوهای برق فشار متوسط و فشار ضعیف؛ تهیه‌شده توسط تیم فنی ' . psb_brand_name() . '.';
} else {
	$psb_title = wp_strip_all_tags( get_the_archive_title() );
	$psb_lead  = $psb_term && $psb_term->description ? wp_strip_all_tags( $psb_term->description ) : 'مقالات فنی ' . psb_brand_name() . ' در موضوع ' . $psb_title . '.';
}
if ( $psb_paged > 1 ) {
	$psb_title .= ' — صفحه ' . psb_fa_num( $psb_paged );
}

// Topic chips: categories with posts (no "Uncategorized").
$psb_topics = get_categories(
	array(
		'hide_empty' => true,
		'exclude'    => array( (int) get_option( 'default_category' ) ),
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 12,
	)
);
$psb_series = function_exists( 'psb_series_all' ) ? psb_series_all() : array();
?>
<section class="page-hero page-hero--light">
	<div class="container">
		<?php get_template_part( 'template-parts/global/breadcrumb' ); ?>
		<div class="mt-6 grid items-end gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,24rem)]">
			<div>
				<span class="eyebrow">مجله فنی تابلو برق</span>
				<h1 class="page-hero-title !mt-2"><?php echo esc_html( $psb_title ); ?></h1>
				<p class="page-hero-lead"><?php echo esc_html( $psb_lead ); ?></p>
			</div>
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex gap-2">
				<label for="blog-search" class="sr-only">جستجو در مقالات</label>
				<input id="blog-search" class="input" type="search" name="s" placeholder="جستجو در مقالات…" value="<?php echo esc_attr( get_search_query() ); ?>">
				<input type="hidden" name="post_type" value="post">
				<button class="btn btn-primary btn-icon shrink-0" type="submit" aria-label="جستجو"><?php echo psb_icon( 'search' ); // phpcs:ignore ?></button>
			</form>
		</div>
	</div>
</section>

<?php if ( count( $psb_topics ) > 0 || $psb_series ) : ?>
	<nav class="sticky top-0 z-20 border-b border-line bg-white/95 backdrop-blur-md lg:static" aria-label="موضوعات مقالات">
		<ul class="container flex gap-2 overflow-x-auto py-3" role="list" style="scrollbar-width:none">
			<li><a class="chip shrink-0" href="<?php echo esc_url( $psb_burl ); ?>" <?php echo 'home' === $psb_mode ? 'aria-current="page" aria-pressed="true"' : ''; ?>>همه مقالات</a></li>
			<?php foreach ( $psb_topics as $psb_t ) : ?>
				<?php $psb_on = $psb_term && (int) $psb_term->term_id === (int) $psb_t->term_id; ?>
				<li><a class="chip shrink-0" href="<?php echo esc_url( get_category_link( $psb_t ) ); ?>" <?php echo $psb_on ? 'aria-current="page" aria-pressed="true"' : ''; ?>><?php echo esc_html( $psb_t->name ); ?></a></li>
			<?php endforeach; ?>
			<?php foreach ( $psb_series as $psb_s ) : ?>
				<li><a class="chip shrink-0" href="<?php echo esc_url( $psb_s['url'] ); ?>"><?php echo psb_icon( $psb_s['icon'], 'size-3.5' ); // phpcs:ignore ?><?php echo esc_html( $psb_s['name'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</nav>
<?php endif; ?>

<?php
$psb_featured = array();
if ( $psb_first && have_posts() ) :
	// Featured: pillar of the largest series, else the first sticky post, else the latest post.
	$psb_lead_id = $psb_series ? (int) $psb_series[0]['pillar'] : 0;
	$psb_sticky  = array_map( 'intval', (array) get_option( 'sticky_posts' ) );
	if ( ! $psb_lead_id && $psb_sticky ) {
		$psb_lead_id = $psb_sticky[0];
	}
	if ( ! $psb_lead_id ) {
		$psb_lead_id = (int) $wp_query->posts[0]->ID;
	}
	$psb_side = get_posts(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'numberposts'  => 2,
			'post__not_in' => array( $psb_lead_id ),
		)
	);
	$psb_featured = array_merge( array( $psb_lead_id ), wp_list_pluck( $psb_side, 'ID' ) );
	?>
	<section class="container py-section-sm" aria-labelledby="mag-featured-title">
		<h2 id="mag-featured-title" class="sr-only">مقالات برگزیده</h2>
		<div class="grid gap-gutter lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
			<?php get_template_part( 'template-parts/blog/post-card', null, array( 'post' => $psb_lead_id, 'eager' => true, 'class' => 'lg:row-span-2', 'excerpt' => true ) ); ?>
			<?php foreach ( $psb_side as $psb_p ) : ?>
				<?php get_template_part( 'template-parts/blog/post-card', null, array( 'post' => $psb_p->ID, 'layout' => 'side', 'excerpt' => false ) ); ?>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>

<?php if ( $psb_first && $psb_series ) : ?>
	<section class="border-y border-line bg-white py-section-sm" aria-labelledby="mag-series-title">
		<div class="container">
			<?php
			get_template_part(
				'template-parts/global/section-head',
				null,
				array(
					'id'      => 'mag-series-title',
					'eyebrow' => 'مسیرهای یادگیری',
					'title'   => 'سری مقالات',
					'lead'    => 'هر سری، یک موضوع را از مقاله پایه تا جزئیات فنی به ترتیب پیش می‌برد. پیشرفت مطالعه روی همین دستگاه ذخیره می‌شود.',
				)
			);
			?>
			<div class="rail [--rail-item:82%] sm:[--rail-item:46%] lg:grid lg:grid-flow-row lg:grid-cols-4 lg:gap-6 lg:overflow-visible">
				<?php
				foreach ( $psb_series as $psb_s ) {
					get_template_part( 'template-parts/blog/series-card', null, array( 'info' => $psb_s ) );
				}
				?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php
$psb_rest = array_filter(
	(array) $wp_query->posts,
	function ( $p ) use ( $psb_featured ) {
		return ! in_array( (int) $p->ID, $psb_featured, true );
	}
);
if ( $psb_rest || ! have_posts() || $psb_paged < (int) $wp_query->max_num_pages ) :
	?>
<section class="container py-section-sm" aria-labelledby="mag-latest-title">
	<h2 id="mag-latest-title" class="<?php echo $psb_first ? 'section-title mb-8' : 'sr-only'; ?>"><?php echo $psb_first ? 'تازه‌ترین مقالات' : 'فهرست مقالات'; ?></h2>
	<?php if ( have_posts() ) : ?>
		<div class="grid gap-gutter sm:grid-cols-2 lg:grid-cols-3" data-post-grid>
			<?php
			while ( have_posts() ) :
				the_post();
				if ( in_array( get_the_ID(), $psb_featured, true ) ) {
					continue;
				}
				get_template_part( 'template-parts/blog/post-card', null, array( 'post' => get_the_ID(), 'eager' => ! $psb_first && 0 === (int) $wp_query->current_post ) );
			endwhile;
			?>
		</div>
		<div data-pagination><?php psb_pagination(); ?></div>
		<?php if ( $psb_paged < (int) $wp_query->max_num_pages ) : ?>
			<div class="mt-10 flex justify-center">
				<button type="button" class="btn btn-outline btn-lg" data-load-more="<?php echo esc_url( get_pagenum_link( $psb_paged + 1 ) ); ?>" hidden>مقالات بیشتر</button>
			</div>
			<script>
			(function(){var b=document.querySelector('[data-load-more]');if(!b||!window.fetch||!window.DOMParser)return;var pg=document.querySelector('[data-pagination]');if(pg)pg.hidden=true;b.hidden=false;
			b.addEventListener('click',function(){var url=b.getAttribute('data-load-more');b.disabled=true;b.textContent='در حال بارگذاری…';
			fetch(url,{credentials:'same-origin'}).then(function(r){return r.text();}).then(function(t){var d=new DOMParser().parseFromString(t,'text/html'),g=d.querySelector('[data-post-grid]'),grid=document.querySelector('[data-post-grid]');if(g&&grid){Array.prototype.slice.call(g.children).forEach(function(c){c.querySelectorAll('img[loading="eager"]').forEach(function(i){i.loading='lazy';});grid.appendChild(c);});}
			var nb=d.querySelector('[data-load-more]');if(nb){b.setAttribute('data-load-more',nb.getAttribute('data-load-more'));b.disabled=false;b.textContent='مقالات بیشتر';}else{b.parentNode.remove();}
			}).catch(function(){window.location.href=url;});});})();
			</script>
		<?php endif; ?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/certificates/empty-state', null, array( 'icon' => 'doc', 'title' => 'مقاله‌ای در این بخش منتشر نشده است', 'actions' => array( array( $psb_burl, 'همه مقالات', 'btn-outline' ) ) ) ); ?>
	<?php endif; ?>
</section>
<?php endif; ?>

<?php
if ( $psb_first ) :
	$psb_popular = get_posts(
		array(
			'post_type'    => 'post',
			'post_status'  => 'publish',
			'numberposts'  => 4,
			'meta_key'     => 'post_views', // phpcs:ignore
			'orderby'      => 'meta_value_num',
			'order'        => 'DESC',
			'post__not_in' => $psb_featured,
		)
	);
	$psb_sticky = array_values( array_diff( array_map( 'intval', (array) get_option( 'sticky_posts' ) ), $psb_featured ) );
	$psb_picks  = $psb_sticky ? get_posts(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'post__in'            => $psb_sticky,
			'numberposts'         => 4,
			'ignore_sticky_posts' => true,
		)
	) : array();
	if ( $psb_popular || $psb_picks ) :
		?>
		<section class="border-t border-line bg-white py-section-sm" aria-label="پربازدید و پیشنهاد سردبیر">
			<div class="container grid gap-10 <?php echo ( $psb_popular && $psb_picks ) ? 'lg:grid-cols-2' : 'max-w-content'; ?>">
				<?php
				foreach ( array(
					array( 'پربازدیدترین مقالات', $psb_popular, 'mag-popular-title' ),
					array( 'پیشنهاد سردبیر', $psb_picks, 'mag-picks-title' ),
				) as $psb_list ) :
					if ( ! $psb_list[1] ) {
						continue;
					}
					?>
					<div>
						<h2 id="<?php echo esc_attr( $psb_list[2] ); ?>" class="mb-5 text-h3 font-bold text-dark"><?php echo esc_html( $psb_list[0] ); ?></h2>
						<ol class="flex flex-col divide-y divide-line rounded-card border border-line bg-white" role="list">
							<?php foreach ( $psb_list[1] as $psb_i => $psb_p ) : ?>
								<li>
									<a class="flex min-h-16 items-center gap-4 p-4 hover:bg-slate-50" href="<?php echo esc_url( get_permalink( $psb_p ) ); ?>">
										<span class="w-8 shrink-0 text-2xl font-bold text-slate-300 tabular-nums" aria-hidden="true"><?php echo esc_html( psb_fa_num( $psb_i + 1 ) ); ?></span>
										<span class="min-w-0 flex-1">
											<span class="block font-bold leading-7 text-dark"><?php echo esc_html( get_the_title( $psb_p ) ); ?></span>
											<span class="text-xs text-slate-600"><?php echo esc_html( psb_fa_num( psb_reading_minutes( $psb_p ) ) ); ?> دقیقه مطالعه</span>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ol>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	endif;
endif;
