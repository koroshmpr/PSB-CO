<?php
/**
 * Single approval / vendor-list letter (CPT confirmation).
 *
 * @package bluebox
 */

get_header();
while ( have_posts() ) :
	the_post();
	$psb_d = psb_confirmation_data( get_the_ID() );
	get_template_part(
		'template-parts/certificates/cred-single',
		null,
		array(
			'type'       => 'confirmation',
			'eyebrow'    => 'تأییدیه و وندورلیست',
			'meta'       => array( $psb_d['issuer'], $psb_d['number'] ? psb_fa_num( $psb_d['number'] ) : '' ),
			'rows'       => $psb_d['rows'],
			'doc'        => $psb_d['doc'],
			'doc_label'  => 'نامه تأییدیه',
			'table'      => 'مشخصات تأییدیه',
			'more_title' => 'سایر تأییدیه‌ها',
		)
	);
endwhile;
get_footer();
