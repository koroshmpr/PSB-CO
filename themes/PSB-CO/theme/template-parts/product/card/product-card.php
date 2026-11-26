<?php
/**
 * Catalogue product card (no price / cart). Wrapper around template-parts/cards/card.php:
 * image or type illustration (psb_best_image in card.php), category eyebrow, title, 2-line excerpt,
 * «مشاهده جزئیات» + a secondary «استعلام قیمت» to the wizard with ?product= and ?type=.
 *
 * $args: post (ID, default current), eager (bool), tag (h2|h3), class
 */
$psb_id = (int) ( $args['post'] ?? get_the_ID() );
if ( ! $psb_id ) {
	return;
}
$psb_cat     = psb_product_primary_cat( $psb_id );
$psb_excerpt = wp_strip_all_tags( get_post_field( 'post_excerpt', $psb_id ) );
if ( '' === trim( $psb_excerpt ) ) {
	$psb_excerpt = wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $psb_id ) ) );
}
get_template_part(
	'template-parts/cards/card',
	null,
	array(
		'post'    => $psb_id,
		'eyebrow' => $psb_cat ? $psb_cat->name : '',
		'excerpt' => wp_trim_words( $psb_excerpt, 22, '…' ),
		'cta'     => 'مشاهده جزئیات',
		'quote'   => psb_product_quote_url( $psb_id ),
		'ratio'   => '4 / 3',
		'tag'     => $args['tag'] ?? 'h3',
		'eager'   => ! empty( $args['eager'] ),
		'class'   => trim( 'card--product ' . ( $args['class'] ?? '' ) ),
	)
);
