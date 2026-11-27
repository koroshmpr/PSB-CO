<?php
/**
 * Strip of customer letters (for the home page and other sections). Outputs only the cards;
 * the caller prints the section heading. Prints nothing when there are no published letters.
 *
 * $args: count (int, default 6), type (letter_type slug, optional), exclude (int[]), class
 */
if ( ! post_type_exists( 'customer_letter' ) ) {
	return;
}
$psb_q_args = array(
	'post_type'      => 'customer_letter',
	'post_status'    => 'publish',
	'posts_per_page' => max( 1, (int) ( $args['count'] ?? 6 ) ),
	'no_found_rows'  => true,
	'post__not_in'   => array_map( 'intval', (array) ( $args['exclude'] ?? array() ) ),
);
if ( ! empty( $args['type'] ) ) {
	$psb_q_args['tax_query'] = array( // phpcs:ignore
		array(
			'taxonomy' => 'letter_type',
			'field'    => 'slug',
			'terms'    => sanitize_title( $args['type'] ),
		),
	);
}
$psb_letters = new WP_Query( $psb_q_args );
if ( ! $psb_letters->have_posts() ) {
	return;
}
?>
<?php // Phones: one swipe row (~1.4 cards); desktop: one row of 4 (extra cards stay in the phone rail only). ?>
<div class="letter-rail <?php echo esc_attr( $args['class'] ?? '' ); ?>">
	<?php
	while ( $psb_letters->have_posts() ) :
		$psb_letters->the_post();
		echo '<div class="letter-slide">';
		get_template_part( 'template-parts/letters/letter-card', null, array( 'post' => get_the_ID() ) );
		echo '</div>';
	endwhile;
	wp_reset_postdata();
	?>
</div>
