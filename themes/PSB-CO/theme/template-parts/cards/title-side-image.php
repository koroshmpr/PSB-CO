<?php
/**
 * Legacy card name kept for compatibility — renders cards/card.php with layout "side".
 * Accepts the same $args as cards/card.php.
 */
$psb_args = is_array( $args ?? null ) ? $args : array();
$psb_cats = get_the_category();
if ( empty( $psb_args['eyebrow'] ) && ! empty( $psb_cats ) ) {
	$psb_args['eyebrow'] = $psb_cats[0]->name;
}
$psb_args['layout'] = 'side';
get_template_part( 'template-parts/cards/card', null, $psb_args );
