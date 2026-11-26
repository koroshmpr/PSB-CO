<?php
/**
 * Service / facility card (CPT services).
 *
 * $args: post (ID), eager, tag
 */
$psb_id   = (int) ( $args['post'] ?? get_the_ID() );
$psb_desc = trim( (string) psb_field( 'services_description', '', $psb_id ) );
$psb_feat = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) psb_field( 'technical_features', '', $psb_id ) ) ) );
get_template_part(
	'template-parts/cards/card',
	null,
	array(
		'post'    => $psb_id,
		'eyebrow' => $psb_feat ? psb_fa_num( count( $psb_feat ) ) . ' قابلیت فنی' : 'امکانات کارخانه',
		'excerpt' => $psb_desc ? wp_trim_words( $psb_desc, 24, '…' ) : wp_trim_words( get_the_excerpt( $psb_id ), 24, '…' ),
		'badges'  => psb_extract_standards( psb_field( 'standards_compliance', '', $psb_id ) . ' ' . implode( ' ', $psb_feat ), 3 ),
		'cta'     => 'مشاهده جزئیات',
		'ratio'   => '16 / 10',
		'tag'     => $args['tag'] ?? 'h3',
		'eager'   => ! empty( $args['eager'] ),
	)
);
