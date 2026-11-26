<?php
/**
 * Project card (CPT portfolio): client, location, year, IP / standard badges.
 *
 * $args: post (ID), eager, tag
 */
$psb_id     = (int) ( $args['post'] ?? get_the_ID() );
$psb_sector = psb_field_text( 'industry_sector', $psb_id );
if ( ! $psb_sector ) {
	$psb_sector = implode( '، ', psb_term_names( $psb_id, array( 'portfolio_categories' ) ) );
}
$psb_badges = array_filter( array_merge( array( psb_field_text( 'ip_rating', $psb_id ) ), psb_extract_standards( psb_field_text( 'standards_compliance', $psb_id ), 2 ) ) );
get_template_part(
	'template-parts/cards/card',
	null,
	array(
		'post'    => $psb_id,
		'eyebrow' => $psb_sector,
		'excerpt' => false,
		'specs'   => array(
			'کارفرما'   => psb_field_text( 'client_name', $psb_id ),
			'محل اجرا'  => psb_field_text( 'project_location', $psb_id ),
			'سال اجرا'  => psb_fa_num( psb_field_text( 'execution_year', $psb_id ) ),
			'سطح ولتاژ' => psb_field_text( 'voltage_level', $psb_id ),
		),
		'badges'  => array_unique( $psb_badges ),
		'cta'     => 'جزئیات پروژه',
		'ratio'   => '3 / 2',
		'tag'     => $args['tag'] ?? 'h3',
		'eager'   => ! empty( $args['eager'] ),
	)
);
