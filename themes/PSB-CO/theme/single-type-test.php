<?php
/**
 * Single type-test report (CPT type-test).
 * Fields that exist on the site: issuing_body, standard_number, validity_date, certificate_file
 * (type_test_file / tested_equipment / test_parameters are rendered too when filled).
 *
 * @package bluebox
 */

get_header();
while ( have_posts() ) :
	the_post();
	$psb_d = psb_type_test_data( get_the_ID() );
	get_template_part(
		'template-parts/certificates/cred-single',
		null,
		array(
			'type'       => 'type-test',
			'eyebrow'    => 'گزارش آزمون نوعی',
			'meta'       => array( $psb_d['issuer'], psb_doc_date_text( $psb_d['date'] ) ),
			'rows'       => $psb_d['rows'],
			'standards'  => $psb_d['standards'],
			'doc'        => $psb_d['doc'],
			'doc_label'  => 'گزارش آزمون',
			'table'      => 'اطلاعات گزارش آزمون',
			'more_title' => 'سایر گزارش‌های آزمون نوعی',
		)
	);
endwhile;
get_footer();
