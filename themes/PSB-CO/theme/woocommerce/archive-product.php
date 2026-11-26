<?php
/**
 * Product catalogue (shop page + product categories). B2B: no prices, ratings, sorting or cart.
 * Light head (H1 = «محصولات» or the category name) · category navigation for the hierarchy
 * «تابلو برق» → children (side list with counts and line icons on desktop, scrollable chips on mobile)
 * · equal-height product cards · pagination · «درباره این دسته» (category description as prose).
 *
 * @package bluebox
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;
$psb_term    = is_product_category() ? get_queried_object() : null;
$psb_default = (int) get_option( 'default_product_cat' );

// Category tree: top-level categories that have catalogue products, each with its children.
$psb_tops = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'parent'     => 0,
		'hide_empty' => false,
		'exclude'    => array_filter( array( $psb_default ) ),
	)
);
$psb_tree = array();
foreach ( is_wp_error( $psb_tops ) ? array() : $psb_tops as $psb_top ) {
	$psb_kids = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $psb_top->term_id,
			'hide_empty' => false,
			'orderby'    => 'term_order',
		)
	);
	$psb_kids = array_values(
		array_filter(
			is_wp_error( $psb_kids ) ? array() : $psb_kids,
			function ( $k ) {
				return psb_cat_count( $k ) > 0;
			}
		)
	);
	if ( psb_cat_count( $psb_top ) > 0 ) {
		$psb_tree[] = array(
			'term' => $psb_top,
			'kids' => $psb_kids,
		);
	}
}
$psb_total = 0;
foreach ( $psb_tree as $psb_node ) {
	$psb_total += psb_cat_count( $psb_node['term'] );
}

$psb_title = $psb_term ? $psb_term->name : 'محصولات';
$psb_desc  = $psb_term ? trim( (string) $psb_term->description ) : '';
$psb_lead  = $psb_desc ? wp_trim_words( wp_strip_all_tags( $psb_desc ), 34, '…' ) : 'طراحی و ساخت سفارشی تابلوهای برق فشار متوسط، فشار ضعیف و فرمان و کنترل بر اساس نقشه تک‌خطی و مشخصات فنی هر پروژه. قیمت هر تابلو پس از بررسی مشخصات پروژه اعلام می‌شود.';

get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'title'   => $psb_title,
		'eyebrow' => $psb_term ? 'محصولات' : 'کاتالوگ محصولات',
		'lead'    => $psb_lead,
	)
);

/** One navigation link (chip on mobile, list row on desktop). */
$psb_link = function ( $term, $label, $count, $icon, $active, $child = false ) {
	$url = $term ? get_term_link( $term ) : psb_shop_url();
	if ( is_wp_error( $url ) ) {
		return '';
	}
	return sprintf(
		'<a class="cat-link%1$s" href="%2$s"%3$s>%4$s<span class="cat-link-label">%5$s</span><span class="cat-link-count">%6$s</span></a>',
		$child ? ' cat-link--child' : '',
		esc_url( $url ),
		$active ? ' aria-current="page"' : '',
		psb_icon( $icon, 'size-5 shrink-0' ),
		esc_html( $label ),
		esc_html( psb_fa_num( $count ) )
	);
};
$psb_is = function ( $term ) use ( $psb_term ) {
	return $psb_term && (int) $psb_term->term_id === (int) $term->term_id;
};
?>
<section class="container py-section-sm lg:py-section" aria-labelledby="catalogue-title">
	<div class="grid gap-8 lg:grid-cols-12 lg:gap-6">
		<?php if ( $psb_tree ) : ?>
			<nav class="min-w-0 lg:col-span-3" aria-label="دسته‌بندی محصولات">
				<div class="cat-nav lg:sticky" style="top:calc(var(--header-h,4rem) + 1.5rem)">
					<p class="mb-3 hidden text-sm font-semibold text-slate-600 lg:block">دسته‌بندی محصولات</p>
					<ul class="cat-list" role="list">
						<li><?php echo $psb_link( null, 'همه محصولات', $psb_total, 'grid', ! $psb_term ); // phpcs:ignore ?></li>
						<?php foreach ( $psb_tree as $psb_node ) : ?>
							<?php if ( count( $psb_tree ) > 1 || ! $psb_node['kids'] ) : ?>
								<li><?php echo $psb_link( $psb_node['term'], $psb_node['term']->name, psb_cat_count( $psb_node['term'] ), psb_cat_icon( $psb_node['term'] ), $psb_is( $psb_node['term'] ) ); // phpcs:ignore ?></li>
							<?php endif; ?>
							<?php foreach ( $psb_node['kids'] as $psb_kid ) : ?>
								<li><?php echo $psb_link( $psb_kid, $psb_kid->name, psb_cat_count( $psb_kid ), psb_cat_icon( $psb_kid ), $psb_is( $psb_kid ), count( $psb_tree ) > 1 ); // phpcs:ignore ?></li>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</ul>
				</div>
			</nav>
		<?php endif; ?>

		<div class="min-w-0 <?php echo $psb_tree ? 'lg:col-span-9' : 'lg:col-span-12'; ?>">
			<div class="mb-6 flex items-baseline justify-between gap-4 border-b border-line pb-3">
				<h2 id="catalogue-title" class="text-base font-semibold text-foreground"><?php echo esc_html( $psb_term ? 'محصولات ' . $psb_term->name : 'همه محصولات' ); ?></h2>
				<p class="text-sm text-slate-600"><?php echo esc_html( psb_fa_num( (int) $wp_query->found_posts ) ); ?> محصول</p>
			</div>

			<?php if ( have_posts() ) : ?>
				<ul class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3" role="list">
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<li class="flex">
							<?php
							get_template_part(
								'template-parts/product/card/product-card',
								null,
								array(
									'post'  => get_the_ID(),
									'eager' => 0 === (int) $wp_query->current_post,
									'class' => 'w-full',
								)
							);
							?>
						</li>
					<?php endwhile; ?>
				</ul>
				<?php psb_pagination(); ?>
			<?php else : ?>
				<?php
				get_template_part(
					'template-parts/certificates/empty-state',
					null,
					array(
						'icon'    => $psb_term ? psb_cat_icon( $psb_term ) : 'grid',
						'title'   => 'محصولی در این دسته ثبت نشده است',
						'text'    => 'تابلوهای این گروه به‌صورت سفارشی ساخته می‌شوند. برای دریافت پیشنهاد فنی و قیمت، مشخصات پروژه را ارسال کنید.',
						'actions' => array(
							array( psb_quote_url(), 'ثبت درخواست استعلام', 'btn-primary' ),
							array( psb_shop_url(), 'همه محصولات', 'btn-outline' ),
						),
					)
				);
				?>
			<?php endif; ?>

			<?php if ( $psb_desc ) : ?>
				<section class="mt-16 border-t-2 border-foreground pt-6" aria-labelledby="cat-about-title">
					<h2 id="cat-about-title" class="text-h3 font-bold text-foreground">درباره <?php echo esc_html( $psb_term->name ); ?></h2>
					<div class="article-prose mt-4" data-clamp><?php echo psb_lat_content( wp_kses_post( wpautop( do_shortcode( $psb_desc ) ) ) ); // phpcs:ignore -- kses'd ?></div>
					<button type="button" class="btn btn-outline mt-4 hidden" data-clamp-toggle aria-expanded="false">ادامه مطلب</button>
					<script>
					(function(){var c=document.querySelector('[data-clamp]'),b=document.querySelector('[data-clamp-toggle]');if(!c||!b||c.scrollHeight<900)return;c.classList.add('is-clamped','is-clamped-sm');b.classList.remove('hidden');b.addEventListener('click',function(){c.classList.remove('is-clamped','is-clamped-sm');b.remove();});})();
					</script>
				</section>
			<?php endif; ?>

			<?php
			$psb_term_content = $psb_term ? psb_field( 'content', '', $psb_term->taxonomy . '_' . $psb_term->term_id ) : '';
			if ( $psb_term_content ) :
				?>
				<div class="article-prose mt-10"><?php echo wp_kses_post( $psb_term_content ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php
get_footer();
