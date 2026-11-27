<?php
/**
 * «رضایت مشتریان» — customer satisfaction letters (CPT customer_letter, owned by the inner-templates agent).
 * Renders only when the CPT has published posts and template-parts/letters/letters-strip.php exists.
 */
if ( ! post_type_exists( 'customer_letter' ) || ! locate_template( 'template-parts/letters/letters-strip.php' ) ) {
	return;
}
$psb_count = (int) ( wp_count_posts( 'customer_letter' )->publish ?? 0 );
if ( ! $psb_count ) {
	return;
}
// Spec §4.3: stay hidden until real letters replace the sample/placeholder posts.
$psb_real = array_filter(
	get_posts( array( 'post_type' => 'customer_letter', 'posts_per_page' => 20, 'no_found_rows' => true ) ),
	function ( $p ) {
		return false === mb_strpos( $p->post_title, 'نمونه' ) && false === mb_strpos( $p->post_excerpt . ' ' . $p->post_content, 'آزمایشی' );
	}
);
if ( ! $psb_real ) {
	return;
}
?>
<section class="bg-white py-section" aria-labelledby="home-letters-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			psb_pf_head(
				'home_letters',
				(int) get_option( 'page_on_front' ),
				array(
					'id'      => 'home-letters-title',
					'link'    => array(
						'url'   => psb_archive_url( 'customer_letter', '/customer-letters/' )
					),
				)
			)
		);
		get_template_part( 'template-parts/letters/letters-strip', null, array( 'count' => 6 ) );
		?>
	</div>
</section>
