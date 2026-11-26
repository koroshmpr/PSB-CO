<?php
/**
 * Single certificate (CPT certificates).
 *
 * @package bluebox
 */

get_header();
while ( have_posts() ) :
	the_post();
	$psb_d = psb_certificate_data( get_the_ID() );
	get_template_part(
		'template-parts/certificates/cred-single',
		null,
		array(
			'type'       => 'certificates',
			'eyebrow'    => 'گواهینامه',
			'meta'       => array( $psb_d['issuer'] ),
			'rows'       => $psb_d['rows'],
			'standards'  => $psb_d['standards'],
			'doc'        => $psb_d['doc'],
			'doc_label'  => 'تصویر گواهینامه',
			'table'      => 'مشخصات گواهینامه',
			'more_title' => 'سایر گواهینامه‌ها',
		)
	);
endwhile;
get_footer();
