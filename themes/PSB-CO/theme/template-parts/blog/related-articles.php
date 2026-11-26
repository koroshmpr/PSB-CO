<?php
/**
 * Related articles section (posts). Picks posts by explicit IDs, by category, or by a search phrase.
 *
 * $args: ids (int[]), category (int), search (string), exclude (int[]), count (default 3), title, id, class
 */
$psb_count = (int) ( $args['count'] ?? 3 );
$psb_q     = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => $psb_count,
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
	'post__not_in'        => array_map( 'intval', (array) ( $args['exclude'] ?? array() ) ),
);
if ( ! empty( $args['ids'] ) ) {
	$psb_q['post__in'] = array_map( 'intval', (array) $args['ids'] );
	$psb_q['orderby']  = 'post__in';
} elseif ( ! empty( $args['category'] ) ) {
	$psb_q['cat'] = (int) $args['category'];
} elseif ( ! empty( $args['search'] ) ) {
	$psb_q['s'] = (string) $args['search'];
}
$psb_posts = new WP_Query( $psb_q );
if ( ! $psb_posts->have_posts() && ! empty( $args['search'] ) && empty( $args['strict'] ) ) {
	unset( $psb_q['s'] );
	$psb_posts = new WP_Query( $psb_q );
}
if ( ! $psb_posts->have_posts() ) {
	return;
}
$psb_hid = $args['id'] ?? 'related-articles-title';
?>
<section class="border-t border-line py-section-sm <?php echo esc_attr( $args['class'] ?? '' ); ?>" aria-labelledby="<?php echo esc_attr( $psb_hid ); ?>">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			array(
				'id'    => $psb_hid,
				'title' => $args['title'] ?? 'مقالات مرتبط',
				'link'  => array(
					'url'   => get_permalink( (int) get_option( 'page_for_posts' ) ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/blog/' ),
					'label' => 'همه مقالات',
				),
			)
		);
		?>
		<div class="rail [--rail-item:82%] sm:[--rail-item:46%] lg:grid lg:grid-flow-row lg:grid-cols-3 lg:gap-6 lg:overflow-visible">
			<?php
			while ( $psb_posts->have_posts() ) :
				$psb_posts->the_post();
				get_template_part( 'template-parts/blog/post-card', null, array( 'post' => get_the_ID() ) );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
