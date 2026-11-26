<?php
/**
 * Archive: type-test reports (CPT type-test) — register list grouped by laboratory.
 *
 * @package bluebox
 */

get_header();
get_template_part(
	'template-parts/certificates/cred-archive',
	null,
	array(
		'type'  => 'type-test',
		'title' => 'گزارش‌های آزمون نوعی (Type Test)',
		'lead'  => 'گزارش‌های آزمون نوعی و گواهی‌های فنی تابلوها از آزمایشگاه EPIL و سازنده طرح مرجع SAREL، همراه با استاندارد آزمون و تصویر سند.',
		'group' => 'lab',
		'empty' => array(
			'icon'    => 'flask',
			'title'   => 'گزارش‌های آزمون به‌زودی منتشر می‌شوند',
			'actions' => array( array( psb_archive_url( 'confirmation' ), 'مشاهده تأییدیه‌ها', 'btn-outline' ) ),
		),
	)
);
get_footer();
