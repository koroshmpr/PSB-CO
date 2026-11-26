<?php
/**
 * Archive: approvals & vendor lists (CPT confirmation) — register list grouped by issuer.
 *
 * @package bluebox
 */

get_header();
get_template_part(
	'template-parts/certificates/cred-archive',
	null,
	array(
		'type'  => 'confirmation',
		'title' => 'تأییدیه‌ها و وندورلیست‌ها',
		'lead'  => 'نامه‌های تأیید صلاحیت و درج در فهرست سازندگان مورد تأیید شرکت توانیر، شرکت‌های توزیع نیروی برق و شرکت ملی مهندسی و ساختمان نفت ایران.',
		'group' => 'issuer',
		'empty' => array(
			'icon'    => 'award',
			'title'   => 'تأییدیه‌ها به‌زودی در این صفحه قرار می‌گیرند',
			'text'    => 'برای دریافت فهرست تأییدیه‌ها و مدارک صلاحیت، با واحد بازرگانی تماس بگیرید.',
			'actions' => array( array( psb_contact_url(), 'تماس با شرکت', 'btn-outline' ) ),
		),
	)
);
get_footer();
