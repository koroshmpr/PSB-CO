<?php
/**
 * Article card: category, title, excerpt, date + reading time (no author link).
 *
 * $args: post (ID), layout (stack|side), eager, tag, class, excerpt (bool, default true)
 */
$psb_id   = (int) ( $args['post'] ?? get_the_ID() );
$psb_cats = get_the_category( $psb_id );
$psb_meta = array( psb_post_date( $psb_id ), psb_fa_num( psb_reading_minutes( $psb_id ) ) . ' دقیقه مطالعه' );
get_template_part(
	'template-parts/cards/card',
	null,
	array(
		'post'    => $psb_id,
		'layout'  => $args['layout'] ?? 'stack',
		'eyebrow' => $psb_cats ? $psb_cats[0]->name : '',
		'meta'    => array_filter( $psb_meta ),
		'excerpt' => ( $args['excerpt'] ?? true ) ? wp_trim_words( get_the_excerpt( $psb_id ), 22, '…' ) : false,
		'cta'     => 'ادامه مطلب',
		'ratio'   => '16 / 10',
		'tag'     => $args['tag'] ?? 'h3',
		'eager'   => ! empty( $args['eager'] ),
		'class'   => $args['class'] ?? '',
	)
);
