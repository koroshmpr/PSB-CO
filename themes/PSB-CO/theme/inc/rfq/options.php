<?php
/**
 * RFQ — settings model: defaults, editable option lists, field definitions.
 *
 * Options:
 * - psb_rfq_settings  (autoload: yes) form lists, field visibility, texts, limits. No secrets.
 * - psb_rfq_channels  (autoload: no)  notification channels incl. tokens / secrets.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Type groups (fixed keys: they drive which technical fields are shown). */
function psb_rfq_groups() {
	return array(
		'lv'       => 'تابلو فشار ضعیف',
		'mv'       => 'تابلو فشار متوسط',
		'rmu'      => 'کمپکت / RMU',
		'meter'    => 'تابلو کنتوری',
		'control'  => 'فرمان و کنترل',
		'hospital' => 'ایزوله بیمارستانی',
		'other'    => 'سایر',
	);
}

/** Request statuses (fixed keys). */
function psb_rfq_statuses() {
	return array(
		'new'    => array( 'label' => 'جدید', 'color' => '#d97706' ),
		'review' => array( 'label' => 'در حال بررسی', 'color' => '#0284c7' ),
		'quoted' => array( 'label' => 'پیش‌فاکتور ارسال شد', 'color' => '#7c3aed' ),
		'won'    => array( 'label' => 'برنده', 'color' => '#059669' ),
		'lost'   => array( 'label' => 'از دست رفته', 'color' => '#64748b' ),
	);
}

/** Preferred contact channels (fixed keys). */
function psb_rfq_contact_channels() {
	return array(
		'call'     => 'تماس تلفنی',
		'whatsapp' => 'واتساپ',
		'telegram' => 'تلگرام',
		'bale'     => 'بله',
		'email'    => 'ایمیل',
	);
}

/** Iranian provinces. */
function psb_rfq_provinces() {
	return array( 'تهران', 'البرز', 'اصفهان', 'خراسان رضوی', 'فارس', 'خوزستان', 'آذربایجان شرقی', 'آذربایجان غربی', 'اردبیل', 'ایلام', 'بوشهر', 'چهارمحال و بختیاری', 'خراسان جنوبی', 'خراسان شمالی', 'زنجان', 'سمنان', 'سیستان و بلوچستان', 'قزوین', 'قم', 'کردستان', 'کرمان', 'کرمانشاه', 'کهگیلویه و بویراحمد', 'گلستان', 'گیلان', 'لرستان', 'مازندران', 'مرکزی', 'هرمزگان', 'همدان', 'یزد', 'خارج از کشور' );
}

/** Label for the "I don't know" option added to every technical select. */
function psb_rfq_unknown_label() {
	return 'نمی‌دانم / طبق نقشه';
}

/**
 * Editable lists: name => array( title, help, default lines ).
 * Line format: key|label  (types: key|label|group|short description).
 */
function psb_rfq_list_defs() {
	return array(
		'types'         => array(
			'انواع تابلو',
			'هر خط: کلید|عنوان|گروه|توضیح کوتاه — گروه یکی از: lv, mv, rmu, meter, control, hospital, other (تعیین می‌کند کدام فیلدهای فنی نمایش داده شود).',
			"lv_mdb|تابلو اصلی (MDB)|lv|ورودی اصلی و توزیع از ترانس یا ژنراتور\nlv_smdb|تابلو نیمه‌اصلی و توزیع (SMDB / DB)|lv|توزیع طبقات، واحدها و بارها\nlv_mcc|تابلو MCC|lv|مرکز کنترل موتورها\nlv_capacitor|بانک خازنی|lv|اصلاح ضریب قدرت (ثابت یا اتوماتیک)\nlv_ats|تابلو ATS / چنج‌اور|lv|انتقال خودکار بین برق شهر و ژنراتور\nlv_lighting|تابلو روشنایی|lv|روشنایی داخلی، محوطه و اضطراری\nmv_fixed|سلول فشار متوسط ثابت|mv|سلول‌های ورودی، خروجی، ترانس و اندازه‌گیری\nmv_withdrawable|سلول فشار متوسط کشویی|mv|سلول با دژنکتور کشویی\nrmu|تابلو کمپکت / RMU|rmu|رینگ‌مین یونیت و پست‌های کمپکت\nmeter|تابلو کنتوری|meter|تابلو لوازم اندازه‌گیری مطابق ضوابط توزیع برق\ncontrol|تابلو فرمان و کنترل|control|فرمان، اتوماسیون و PLC\nhospital|تابلو ایزوله بیمارستانی|hospital|سیستم IT پزشکی برای اتاق عمل و ICU\nother|سایر / مطمئن نیستم|other|توضیح دهید یا نقشه را بارگذاری کنید",
		),
		'voltages_lv'   => array( 'ولتاژ نامی — فشار ضعیف', 'هر خط: کلید|عنوان', "400v|۴۰۰ ولت (سه‌فاز)\n230v|۲۳۰ ولت (تکفاز)\n690v|۶۹۰ ولت\n24vdc|ولتاژ فرمان DC (۲۴ / ۱۱۰ ولت)" ),
		'voltages_mv'   => array( 'ولتاژ نامی — فشار متوسط', 'هر خط: کلید|عنوان', "20kv|۲۰ کیلوولت\n33kv|۳۳ کیلوولت\n11kv|۱۱ کیلوولت\n6kv|۶٫۳ کیلوولت" ),
		'icw_lv'        => array( 'جریان اتصال کوتاه — فشار ضعیف', 'هر خط: کلید|عنوان', "25|25 kA\n36|36 kA\n50|50 kA\n65|65 kA\n80|80 kA\n100|100 kA" ),
		'icw_mv'        => array( 'جریان اتصال کوتاه — فشار متوسط', 'هر خط: کلید|عنوان', "16|16 kA / 1s\n20|20 kA / 1s\n25|25 kA / 1s\n31|31.5 kA / 1s" ),
		'ip'            => array( 'درجه حفاظت (IP)', 'هر خط: کلید|عنوان', "ip31|IP31\nip42|IP42\nip54|IP54\nip55|IP55\nip65|IP65" ),
		'forms'         => array( 'فرم جداسازی داخلی', 'هر خط: کلید|عنوان', "f1|Form 1\nf2a|Form 2a\nf2b|Form 2b\nf3a|Form 3a\nf3b|Form 3b\nf4a|Form 4a\nf4b|Form 4b" ),
		'brands'        => array( 'برند تجهیزات', 'هر خط: کلید|عنوان', "abb|ABB\nschneider|Schneider\nsiemens|Siemens\nls|LS\nhyundai|Hyundai\niranian|ساخت داخل\nany|بدون ترجیح" ),
		'hospital_kva'  => array( 'توان ترانس ایزوله بیمارستانی', 'هر خط: کلید|عنوان', "3|3.15 kVA\n5|5 kVA\n6|6.3 kVA\n8|8 kVA\n10|10 kVA" ),
		'project_types' => array( 'نوع پروژه / کارفرما', 'هر خط: کلید|عنوان', "industrial|صنعتی و کارخانه\nbuilding|ساختمانی و مسکونی\npower|نیروگاه\noilgas|نفت، گاز و پتروشیمی\nhospital|بیمارستان و مراکز درمانی\ndistribution|توزیع برق (توانیر / شرکت‌های توزیع)\ncommercial|تجاری و اداری\nother|سایر" ),
		'deadlines'     => array( 'زمان نیاز', 'هر خط: کلید|عنوان', "urgent|فوری (کمتر از ۲ هفته)\nm1|حدود ۱ ماه\nm3|۱ تا ۳ ماه\nm6|۳ تا ۶ ماه\ntender|در مرحله برآورد یا مناقصه" ),
	);
}

/** Default (non-secret) settings. */
function psb_rfq_default_settings() {
	$lists = array();
	foreach ( psb_rfq_list_defs() as $name => $def ) {
		$lists[ $name ] = $def[2];
	}
	return array(
		'lists'           => $lists,
		'fields'          => array(),
		'max_files'       => 5,
		'max_mb'          => 20,
		'ext'             => 'pdf,dwg,dxf,jpg,jpeg,png,xlsx,xls,docx,zip',
		'rate_limit'      => 5,
		'min_seconds'     => 4,
		// On for new installs (security review M2). Saved settings keep their stored value: the settings
		// form always stores `challenge`, and psb_rfq_settings() merges saved values over these defaults.
		'challenge'       => 1,
		'sms_daily_cap'   => 50,   // customer SMS confirmations per day, site-wide (plus 1 per mobile per 24 h).
		'upload_ip_mb'    => 100,  // upload megabytes per IP per 24 h.
		'upload_total_mb' => 2048, // total size of the uploads psb-rfq folder.

		'prefix'          => 'PSB',
		'sales_phone'     => '02166707011',
		'sales_whatsapp'  => '09124763197',
		'response_time'   => 'کارشناسان فروش معمولاً ظرف یک روز کاری برای تکمیل اطلاعات و ارائه پیشنهاد با شما تماس می‌گیرند.',
		'success_title'   => 'درخواست شما ثبت شد',
		'success_message' => 'از اعتماد شما سپاسگزاریم. مشخصات و مدارک ارسالی شما در اختیار واحدهای فروش و مهندسی قرار گرفت.',
		'privacy_note'    => 'اطلاعات و مدارک شما محرمانه است و فقط برای تهیه پیشنهاد فنی و قیمت در اختیار کارشناسان فروش و مهندسی قرار می‌گیرد.',
	);
}

/** Merged settings (static cache; reset with psb_rfq_settings( true )). */
function psb_rfq_settings( $refresh = false ) {
	static $cache = null;
	if ( null === $cache || $refresh ) {
		$saved = get_option( 'psb_rfq_settings', array() );
		$saved = is_array( $saved ) ? $saved : array();
		$def   = psb_rfq_default_settings();
		$cache = array_merge( $def, $saved );
		$cache['lists']  = array_merge( $def['lists'], isset( $saved['lists'] ) && is_array( $saved['lists'] ) ? array_filter( $saved['lists'], 'strlen' ) : array() );
		$cache['fields'] = isset( $saved['fields'] ) && is_array( $saved['fields'] ) ? $saved['fields'] : array();
	}
	return $cache;
}

/** One setting value. */
function psb_rfq_opt( $key, $default = '' ) {
	$s = psb_rfq_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : $default;
}

/**
 * Parse an editable list into key => array( 'label', 'group', 'desc' ).
 */
function psb_rfq_list( $name ) {
	static $parsed = array();
	if ( isset( $parsed[ $name ] ) ) {
		return $parsed[ $name ];
	}
	$s   = psb_rfq_settings();
	$raw = isset( $s['lists'][ $name ] ) ? (string) $s['lists'][ $name ] : '';
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) === 1 ) {
			$label = $parts[0];
			$key   = sanitize_key( $label );
		} else {
			$key   = sanitize_key( $parts[0] );
			$label = $parts[1];
		}
		if ( '' === $key ) {
			$key = 'k' . substr( md5( $label ), 0, 6 );
		}
		$group = isset( $parts[2] ) ? sanitize_key( $parts[2] ) : '';
		if ( 'types' === $name && ! isset( psb_rfq_groups()[ $group ] ) ) {
			$group = 'other';
		}
		$out[ $key ] = array(
			'label' => wp_strip_all_tags( $label ),
			'group' => $group,
			'desc'  => isset( $parts[3] ) ? wp_strip_all_tags( $parts[3] ) : '',
		);
	}
	$parsed[ $name ] = $out;
	return $out;
}

/** key => label of a list. */
function psb_rfq_labels( $name ) {
	return wp_list_pluck( psb_rfq_list( $name ), 'label' );
}

/** Line-item types. */
function psb_rfq_types() {
	return psb_rfq_list( 'types' );
}

/** Label of a type key. */
function psb_rfq_type_label( $key ) {
	$t = psb_rfq_types();
	return isset( $t[ $key ] ) ? $t[ $key ]['label'] : (string) $key;
}

/**
 * Technical fields shown per line item in step 2.
 *
 * Each: label, type (select|radio|chips|text|textarea), and either
 *  - lists: group => list name (options differ per group), or
 *  - list:  one list for the listed groups, or
 *  - options: fixed key => label,
 * plus groups (default: all), unit, placeholder.
 */
function psb_rfq_spec_defs() {
	$all  = array_keys( psb_rfq_groups() );
	$lvish = array( 'lv', 'meter', 'control', 'hospital', 'other' );
	return array(
		'voltage'      => array(
			'label' => 'ولتاژ نامی',
			'type'  => 'select',
			'lists' => array_merge( array_fill_keys( $lvish, 'voltages_lv' ), array( 'mv' => 'voltages_mv', 'rmu' => 'voltages_mv' ) ),
		),
		'current'      => array(
			'label'       => 'جریان نامی شین / ورودی',
			'type'        => 'text',
			'unit'        => 'A',
			'placeholder' => 'مثلاً ۱۶۰۰',
			'groups'      => array( 'lv', 'mv', 'rmu', 'meter', 'control', 'other' ),
		),
		'icw'          => array(
			'label' => 'جریان اتصال کوتاه (Icw)',
			'type'  => 'select',
			'lists' => array( 'lv' => 'icw_lv', 'mv' => 'icw_mv', 'rmu' => 'icw_mv' ),
		),
		'kva'          => array(
			'label' => 'توان ترانس ایزوله',
			'type'  => 'select',
			'list'  => 'hospital_kva',
			'groups' => array( 'hospital' ),
		),
		'ip'           => array(
			'label'  => 'درجه حفاظت (IP)',
			'type'   => 'select',
			'list'   => 'ip',
			'groups' => $all,
		),
		'form'         => array(
			'label'  => 'فرم جداسازی',
			'type'   => 'select',
			'list'   => 'forms',
			'groups' => array( 'lv' ),
		),
		'install'      => array(
			'label'   => 'محل نصب',
			'type'    => 'radio',
			'options' => array( 'indoor' => 'داخلی (Indoor)', 'outdoor' => 'خارجی (Outdoor)' ),
			'groups'  => $all,
		),
		'construction' => array(
			'label'   => 'نوع ساخت',
			'type'    => 'radio',
			'options' => array( 'fixed' => 'ثابت', 'withdrawable' => 'کشویی' ),
			'groups'  => array( 'lv', 'control' ),
		),
		'meters'       => array(
			'label'       => 'تعداد و نوع کنتور',
			'type'        => 'text',
			'placeholder' => 'مثلاً ۱۲ کنتور تکفاز + ۲ سه‌فاز دیماندی',
			'groups'      => array( 'meter' ),
		),
		'plc'          => array(
			'label'       => 'PLC / کنترلر یا سیستم کنترلی',
			'type'        => 'text',
			'placeholder' => 'برند و مدل یا بدون PLC',
			'groups'      => array( 'control' ),
		),
		'brand'        => array(
			'label'  => 'برند تجهیزات مورد نظر',
			'type'   => 'chips',
			'list'   => 'brands',
			'groups' => $all,
		),
		'ral'          => array(
			'label'       => 'رنگ (RAL)',
			'type'        => 'text',
			'placeholder' => 'مثلاً RAL 7035',
			'groups'      => $all,
		),
		'dims'         => array(
			'label'       => 'ابعاد / محدودیت فضا',
			'type'        => 'text',
			'placeholder' => 'مثلاً حداکثر ارتفاع ۲۲۰۰ میلی‌متر',
			'groups'      => $all,
		),
		'note'         => array(
			'label'       => 'توضیحات این ردیف',
			'type'        => 'textarea',
			'placeholder' => 'تعداد فیدرها، کلیدها، ورودی/خروجی کابل از بالا یا پایین و …',
			'groups'      => $all,
		),
	);
}

/**
 * Resolved options of a spec field for a group (incl. «نمی‌دانم» for select/radio).
 *
 * @return array|null key => label, null when the field does not apply to the group; array() for text fields.
 */
function psb_rfq_spec_options( $def, $group ) {
	if ( isset( $def['lists'] ) ) {
		if ( ! isset( $def['lists'][ $group ] ) ) {
			return null;
		}
		$opts = psb_rfq_labels( $def['lists'][ $group ] );
	} else {
		$groups = isset( $def['groups'] ) ? $def['groups'] : array_keys( psb_rfq_groups() );
		if ( ! in_array( $group, $groups, true ) ) {
			return null;
		}
		if ( isset( $def['list'] ) ) {
			$opts = psb_rfq_labels( $def['list'] );
		} elseif ( isset( $def['options'] ) ) {
			$opts = $def['options'];
		} else {
			return array();
		}
	}
	if ( in_array( $def['type'], array( 'select', 'radio' ), true ) ) {
		$opts['unknown'] = psb_rfq_unknown_label();
	}
	return $opts;
}

/**
 * Non-spec fields (steps project / contact / files).
 * locked = always required, cannot be hidden.
 */
function psb_rfq_field_defs() {
	return array(
		// Project.
		'project.name'        => array( 'step' => 'project', 'label' => 'نام پروژه', 'type' => 'text', 'max' => 120, 'placeholder' => 'مثلاً مجتمع تجاری … / فاز ۲ کارخانه …' ),
		'project.client_type' => array( 'step' => 'project', 'label' => 'نوع پروژه / کارفرما', 'type' => 'select', 'list' => 'project_types' ),
		'project.province'    => array( 'step' => 'project', 'label' => 'استان', 'type' => 'select', 'options' => array_combine( psb_rfq_provinces(), psb_rfq_provinces() ) ),
		'project.city'        => array( 'step' => 'project', 'label' => 'شهر محل پروژه', 'type' => 'text', 'max' => 60 ),
		'project.deadline'    => array( 'step' => 'project', 'label' => 'زمان نیاز', 'type' => 'radio', 'list' => 'deadlines' ),
		'project.delivery'    => array( 'step' => 'project', 'label' => 'محل تحویل (شهر)', 'type' => 'text', 'max' => 80, 'placeholder' => 'اگر با محل پروژه متفاوت است — ارسال به همه شهرها' ),
		'project.service'     => array( 'step' => 'project', 'label' => 'خدمات مورد نیاز', 'type' => 'radio', 'options' => array( 'delivery' => 'ساخت و ارسال تابلو', 'install' => 'ساخت، ارسال، نصب و راه‌اندازی', 'unknown' => 'هنوز مشخص نیست' ) ),
		'project.approval'    => array( 'step' => 'project', 'label' => 'تأییدیه یا وندور لیست مورد نیاز', 'type' => 'radio', 'options' => array( 'none' => 'ندارد', 'tavanir' => 'توانیر / شرکت توزیع برق', 'vendor' => 'وندور لیست کارفرما یا مشاور', 'unknown' => 'نمی‌دانم' ) ),
		'project.approval_note' => array( 'step' => 'project', 'label' => 'نام کارفرما، مشاور یا نهاد تأییدکننده', 'type' => 'text', 'max' => 160 ),
		'project.description' => array( 'step' => 'project', 'label' => 'توضیحات تکمیلی', 'type' => 'textarea', 'max' => 3000, 'placeholder' => 'هر نکته‌ای که به برآورد دقیق‌تر کمک می‌کند: شرایط محیطی، استانداردها، محدودیت حمل، الزامات کارفرما …' ),
		// Files.
		'files'               => array( 'step' => 'files', 'label' => 'مدارک فنی', 'type' => 'files' ),
		// Contact.
		'contact.name'        => array( 'step' => 'contact', 'label' => 'نام و نام خانوادگی', 'type' => 'text', 'max' => 80, 'locked' => true, 'autocomplete' => 'name' ),
		'contact.company'     => array( 'step' => 'contact', 'label' => 'شرکت / سازمان', 'type' => 'text', 'max' => 120, 'autocomplete' => 'organization' ),
		'contact.position'    => array( 'step' => 'contact', 'label' => 'سمت', 'type' => 'text', 'max' => 80, 'autocomplete' => 'organization-title', 'datalist' => array( 'مهندس طراح / مشاور', 'پیمانکار', 'کارفرما', 'کارشناس خرید / تدارکات', 'مدیر پروژه' ) ),
		'contact.mobile'      => array( 'step' => 'contact', 'label' => 'شماره موبایل', 'type' => 'tel', 'locked' => true, 'autocomplete' => 'tel', 'placeholder' => '۰۹۱۲ ۱۲۳ ۴۵۶۷' ),
		'contact.email'       => array( 'step' => 'contact', 'label' => 'ایمیل', 'type' => 'email', 'max' => 120, 'autocomplete' => 'email' ),
		'contact.channel'     => array( 'step' => 'contact', 'label' => 'راه ارتباط ترجیحی', 'type' => 'radio', 'options' => psb_rfq_contact_channels() ),
		'contact.bale'        => array( 'step' => 'contact', 'label' => 'آیدی بله (اختیاری)', 'type' => 'text', 'max' => 40, 'placeholder' => '@id — برای ارسال پیش‌فاکتور در بله' ),
		'contact.username'    => array( 'step' => 'contact', 'label' => 'آیدی تلگرام (اختیاری)', 'type' => 'text', 'max' => 40, 'placeholder' => '@username' ),
	);
}

/** Resolved options of a non-spec field. */
function psb_rfq_field_options( $def ) {
	if ( isset( $def['list'] ) ) {
		return psb_rfq_labels( $def['list'] );
	}
	return isset( $def['options'] ) ? $def['options'] : array();
}

/**
 * Visibility of a field: 'show' | 'required' | 'hide'.
 *
 * @param string $key 'spec.voltage' or 'project.city' …
 */
function psb_rfq_vis( $key ) {
	$defs = psb_rfq_field_defs();
	if ( isset( $defs[ $key ]['locked'] ) ) {
		return 'required';
	}
	$f = psb_rfq_opt( 'fields', array() );
	$v = isset( $f[ $key ] ) ? $f[ $key ] : 'show';
	return in_array( $v, array( 'show', 'required', 'hide' ), true ) ? $v : 'show';
}

/** Which wizard steps are active (a step with every field hidden is skipped). */
function psb_rfq_steps() {
	$steps = array( 'types' => 'نوع تابلو' );
	foreach ( array_keys( psb_rfq_spec_defs() ) as $k ) {
		if ( 'hide' !== psb_rfq_vis( 'spec.' . $k ) ) {
			$steps['specs'] = 'مشخصات فنی';
			break;
		}
	}
	if ( 'hide' !== psb_rfq_vis( 'files' ) ) {
		$steps['files'] = 'مدارک';
	}
	foreach ( psb_rfq_field_defs() as $k => $d ) {
		if ( 'project' === $d['step'] && 'hide' !== psb_rfq_vis( $k ) ) {
			$steps['project'] = 'پروژه';
			break;
		}
	}
	$steps['contact'] = 'اطلاعات تماس';
	$steps['review']  = 'بازبینی';
	return $steps;
}

/** Extensions that may ever be accepted, whatever the settings say (security review L3: allowlist, not blocklist). */
function psb_rfq_safe_ext() {
	return array( 'pdf', 'dwg', 'dxf', 'jpg', 'jpeg', 'png', 'webp', 'xlsx', 'xls', 'csv', 'docx', 'doc', 'zip', 'rar', '7z' );
}

/** Allowed upload extensions: the admin setting intersected with the fixed safe list. */
function psb_rfq_allowed_ext() {
	$ext = array_filter( array_map( 'sanitize_key', explode( ',', strtolower( (string) psb_rfq_opt( 'ext' ) ) ) ) );
	return array_values( array_unique( array_intersect( $ext, psb_rfq_safe_ext() ) ) );
}

/** Effective max upload size in bytes (setting, capped by the PHP/server limit). */
function psb_rfq_max_bytes() {
	$mb  = max( 1, (int) psb_rfq_opt( 'max_mb', 20 ) );
	$cfg = $mb * 1048576;
	$srv = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 0;
	return $srv > 0 ? min( $cfg, $srv ) : $cfg;
}

/* -------------------------------------------------------------------------
 * Channels (non-autoloaded, contains secrets)
 * ---------------------------------------------------------------------- */

function psb_rfq_default_channels() {
	return array(
		'bale'     => array( 'enabled' => 0, 'token' => '', 'chats' => '', 'parse' => 'html', 'files' => 1, 'base' => 'https://tapi.bale.ai' ),
		'telegram' => array( 'enabled' => 0, 'token' => '', 'chats' => '', 'parse' => 'html', 'files' => 0, 'base' => 'https://api.telegram.org', 'relay_header' => 'X-Relay-Secret', 'relay_secret' => '' ),
		'whatsapp' => array( 'enabled' => 0, 'base' => 'https://graph.facebook.com', 'version' => 'v21.0', 'phone_id' => '', 'token' => '', 'to' => '', 'template' => '', 'lang' => 'fa', 'relay_header' => 'X-Relay-Secret', 'relay_secret' => '' ),
		'webhook'  => array( 'enabled' => 0, 'url' => '', 'secret' => '', 'headers' => '' ),
		'email'    => array( 'enabled' => 1, 'to' => (string) get_option( 'admin_email' ), 'attach' => 0 ),
		'sms'      => array(
			'enabled'     => 0,
			'method'      => 'POST',
			'format'      => 'json',
			'url'         => '',
			'body'        => '{"to":"{mobile}","text":"{message}"}',
			'headers'     => "Authorization: Bearer {api_key}",
			'api_key'     => '',
			'message'     => '{name} گرامی، درخواست استعلام شما با کد {code} در پارس سوئیچ بورد ثبت شد. کارشناسان فروش به‌زودی با شما تماس خواهند گرفت.',
			'test_mobile' => '',
		),
		'files_max_mb' => 10,
	);
}

/** Secret fields: channel => keys; and their optional wp-config constant overrides. */
function psb_rfq_secret_fields() {
	return array(
		'bale'     => array( 'token' => 'PSB_RFQ_BALE_TOKEN' ),
		'telegram' => array( 'token' => 'PSB_RFQ_TELEGRAM_TOKEN', 'relay_secret' => 'PSB_RFQ_TELEGRAM_RELAY_SECRET' ),
		'whatsapp' => array( 'token' => 'PSB_RFQ_WHATSAPP_TOKEN', 'relay_secret' => 'PSB_RFQ_WHATSAPP_RELAY_SECRET' ),
		'webhook'  => array( 'secret' => 'PSB_RFQ_WEBHOOK_SECRET' ),
		'sms'      => array( 'api_key' => 'PSB_RFQ_SMS_API_KEY' ),
	);
}

/** Merged channel config (with wp-config constant overrides applied). */
function psb_rfq_channels() {
	$saved = get_option( 'psb_rfq_channels', array() );
	$saved = is_array( $saved ) ? $saved : array();
	$out   = array();
	foreach ( psb_rfq_default_channels() as $ch => $def ) {
		$out[ $ch ] = is_array( $def ) ? array_merge( $def, isset( $saved[ $ch ] ) && is_array( $saved[ $ch ] ) ? $saved[ $ch ] : array() ) : ( isset( $saved[ $ch ] ) ? $saved[ $ch ] : $def );
	}
	foreach ( psb_rfq_secret_fields() as $ch => $fields ) {
		foreach ( $fields as $f => $const ) {
			if ( defined( $const ) && constant( $const ) ) {
				$out[ $ch ][ $f ] = (string) constant( $const );
			}
		}
	}
	return $out;
}

/** Store channel config, never autoloaded. */
function psb_rfq_save_channels( $value ) {
	if ( false === get_option( 'psb_rfq_channels', false ) ) {
		add_option( 'psb_rfq_channels', $value, '', 'no' );
	} else {
		update_option( 'psb_rfq_channels', $value, false );
	}
}

/** Split a textarea of ids / numbers / emails into a clean list. */
function psb_rfq_lines( $value ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n,،]+/', (string) $value ) ), 'strlen' ) );
}

/** Settings page URL. */
function psb_rfq_settings_url( $page = 'psb-rfq-settings' ) {
	return admin_url( 'edit.php?post_type=' . PSB_RFQ_CPT . '&page=' . $page );
}
