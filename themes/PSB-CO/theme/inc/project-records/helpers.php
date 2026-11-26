<?php
/**
 * Project records — vocabularies (sectors, panel types, provinces) and text normalisation.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Text normalisation
 * ---------------------------------------------------------------------- */

/**
 * Clean Persian text for storage/display: Arabic ي/ك/ة/ۀ → Persian, Arabic-Indic digits → Persian digits,
 * strip tatweel and control chars, collapse whitespace. Latin digits are kept (they live in Latin codes).
 *
 * @param string $text Raw text.
 * @return string
 */
function psb_pr_clean( $text ) {
	$text = (string) $text;
	if ( '' === $text ) {
		return '';
	}
	if ( ! preg_match( '//u', $text ) ) {
		$text = psb_pr_to_utf8( $text );
	}
	$text = strtr(
		$text,
		array(
			'ي' => 'ی',
			'ى' => 'ی',
			'ئ' => 'ئ',
			'ك' => 'ک',
			'ـ' => '',
			'٠' => '۰',
			'١' => '۱',
			'٢' => '۲',
			'٣' => '۳',
			'٤' => '۴',
			'٥' => '۵',
			'٦' => '۶',
			'٧' => '۷',
			'٨' => '۸',
			'٩' => '۹',
			"\xC2\xA0" => ' ',
			"\xE2\x80\x8F" => '',
			"\xE2\x80\x8E" => '',
			"\xEF\xBB\xBF" => '',
		)
	);
	$text = preg_replace( '/[\x{064B}-\x{0652}\x{0670}]/u', '', $text ); // Harakat.
	$text = preg_replace( '/\x{200C}{2,}/u', "\u{200C}", $text );
	$text = preg_replace( '/\s*\x{200C}\s*/u', "\u{200C}", $text );
	$text = preg_replace( '/[ \t\r\n]+/u', ' ', $text );
	return trim( $text );
}

/**
 * Best-effort conversion of a legacy-encoded string (Windows-1256 from old Excel exports) to UTF-8.
 *
 * @param string $text Bytes.
 * @return string
 */
function psb_pr_to_utf8( $text ) {
	if ( preg_match( '//u', $text ) ) {
		return $text;
	}
	if ( function_exists( 'mb_convert_encoding' ) ) {
		$enc = @mb_convert_encoding( $text, 'UTF-8', 'Windows-1256' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( $enc && preg_match( '//u', $enc ) ) {
			return $enc;
		}
	}
	if ( function_exists( 'iconv' ) ) {
		$enc = @iconv( 'Windows-1256', 'UTF-8//IGNORE', $text ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( $enc ) {
			return $enc;
		}
	}
	return wp_check_invalid_utf8( $text, true );
}

/** Persian/Arabic digits → Latin digits. */
function psb_pr_latin_digits( $text ) {
	return strtr(
		(string) $text,
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		)
	);
}

/** Latin digits → Persian digits (display). */
function psb_pr_fa( $value ) {
	return function_exists( 'psb_fa_num' ) ? psb_fa_num( $value ) : strtr( (string) $value, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
}

/** Persian number with thousands separator (٬). */
function psb_pr_fa_int( $n ) {
	return psb_pr_fa( number_format( (int) $n, 0, '.', '٬' ) );
}

/**
 * Matching key: cleaned, Latin digits, lower case, no ZWNJ/space/punctuation. Used for de-duplication,
 * header detection, alias matching and search. Mirrors norm() in js/project-records.js.
 *
 * @param string $text Text.
 * @return string
 */
function psb_pr_key( $text ) {
	$t = psb_pr_latin_digits( psb_pr_clean( $text ) );
	$t = function_exists( 'mb_strtolower' ) ? mb_strtolower( $t, 'UTF-8' ) : strtolower( $t );
	$t = strtr( $t, array( 'آ' => 'ا', 'أ' => 'ا', 'إ' => 'ا', 'ۀ' => 'ه', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ی' ) );
	return (string) preg_replace( '/[^\p{L}\p{N}]+/u', '', $t );
}

/**
 * Parse a Jalali year from free text («۱۳۷۲», «1372/05/01», «72», «۱۳۹۸-۱۳۹۹» → first year).
 * Gregorian years (19xx/20xx) are converted approximately (−621). Returns 0 when unknown.
 *
 * @param string $text Raw value.
 * @return int
 */
function psb_pr_parse_year( $text ) {
	$t = psb_pr_latin_digits( psb_pr_clean( $text ) );
	if ( preg_match( '/\b(1[2-4]\d\d)\b/', $t, $m ) ) {
		$y = (int) $m[1];
	} elseif ( preg_match( '/\b((?:19|20)\d\d)\b/', $t, $m ) ) {
		$y = (int) $m[1] - 621;
	} elseif ( preg_match( '/^\s*([3-9]\d)\s*$/', $t, $m ) ) {
		$y = 1300 + (int) $m[1];
	} else {
		return 0;
	}
	$max = function_exists( 'psb_jalali_year' ) ? psb_jalali_year() : 1410;
	return ( $y >= 1300 && $y <= $max + 1 ) ? $y : 0;
}

/** Truthy cell («y», «yes», «بله», «1», «✓», «x»). */
function psb_pr_truthy( $text ) {
	$k = psb_pr_key( $text );
	return in_array( $k, array( 'y', 'yes', 'true', '1', 'بله', 'اری', 'اره', 'اري', 'x', 'اوکی', 'اوك', 'دارد', 'هست', 'اری' ), true ) || false !== strpos( (string) $text, '✓' );
}

/* -------------------------------------------------------------------------
 * Vocabularies
 * ---------------------------------------------------------------------- */

/**
 * Sector terms (taxonomy project_sector), in display order. slug => [ name, aliases[], anonymous label, keywords[] ].
 * The importer maps sector cells through the aliases; empty cells may be inferred from title keywords.
 *
 * @return array
 */
function psb_pr_sectors() {
	$sectors = array(
		'power'          => array(
			'name'     => 'آب، برق و نیروگاه',
			'aliases'  => array( 'آب و برق', 'آب و برق (تأسیسات زیربنایی)', 'توزیع برق', 'نیروگاه', 'برق', 'انرژی', 'تأسیسات زیربنایی', 'آب و فاضلاب' ),
			'anon'     => 'یک مجموعه تأسیسات آب و برق',
			'keywords' => array( 'نیروگاه', 'برق منطقه', 'توزیع نیروی برق', 'توزیع برق', 'توانیر', 'آب و فاضلاب', 'آبرسانی', 'تصفیه خانه آب' ),
		),
		'oil-gas'        => array(
			'name'     => 'نفت، گاز و پتروشیمی',
			'aliases'  => array( 'نفت و گاز و پتروشیمی', 'نفت و گاز', 'نفت', 'گاز', 'پتروشیمی', 'پالایشگاه', 'پالایش', 'oil & gas' ),
			'anon'     => 'یک مجموعه صنعت نفت، گاز و پتروشیمی',
			'keywords' => array( 'پتروشیمی', 'پالایشگاه', 'پالایش', 'نفت', 'گاز', 'تقویت فشار', 'پارس جنوبی', 'عسلویه' ),
		),
		'industrial'     => array(
			'name'     => 'صنعتی و تولیدی',
			'aliases'  => array( 'صنعتی', 'صنعت', 'تولیدی', 'کارخانه', 'industrial' ),
			'anon'     => 'یک واحد صنعتی',
			'keywords' => array( 'کارخانه', 'کارخانجات', 'صنایع', 'تولیدی', 'نساجی', 'ریسندگی', 'خودرو', 'لاستیک', 'شیشه', 'کاغذ', 'دارو' ),
		),
		'food'           => array(
			'name'     => 'صنایع غذایی و کشاورزی',
			'aliases'  => array( 'غذایی', 'کشاورزی', 'صنایع غذایی' ),
			'anon'     => 'یک واحد صنایع غذایی',
			'keywords' => array( 'شیر پاستوریزه', 'لبنیات', 'گوشت', 'نوشابه', 'قند', 'شکر', 'روغن نباتی', 'آرد', 'نان', 'کشت و صنعت', 'دامپروری', 'مرغداری', 'کشتارگاه', 'سردخانه', 'چای', 'غذایی' ),
		),
		'mining'         => array(
			'name'     => 'معدن، فلزات و مصالح',
			'aliases'  => array( 'معدن', 'فلزات', 'فولاد', 'مصالح', 'سیمان', 'معدنی' ),
			'anon'     => 'یک مجموعه معدنی و فلزی',
			'keywords' => array( 'معدن', 'معدنی', 'فولاد', 'ذوب آهن', 'صنایع مس', 'مس سرچشمه', 'آلومینیوم', 'سیمان', 'آهک', 'نورد', 'ریخته', 'آجر', 'کاشی' ),
		),
		'building'       => array(
			'name'     => 'ساختمان، تجاری و اداری',
			'aliases'  => array( 'ساختمانی، تجاری، اداری و فرهنگی', 'ساختمان و تجاری', 'ساختمانی', 'تجاری', 'اداری', 'مسکونی', 'فرهنگی' ),
			'anon'     => 'یک مجموعه ساختمانی',
			'keywords' => array( 'مجتمع مسکونی', 'آپارتمان', 'برج', 'ساختمان', 'هتل', 'بانک', 'وزارت', 'اداره', 'دانشگاه', 'مدرسه', 'پاساژ', 'تجاری', 'مسجد', 'ورزشگاه', 'سینما', 'موزه', 'خانه سازی', 'مسکن' ),
		),
		'healthcare'     => array(
			'name'     => 'بیمارستانی و درمانی',
			'aliases'  => array( 'درمانی و بهداشتی', 'بیمارستان', 'بیمارستانی', 'درمانی', 'بهداشتی' ),
			'anon'     => 'یک مرکز درمانی',
			'keywords' => array( 'بیمارستان', 'درمانگاه', 'کلینیک', 'رادیولوژی', 'بهداشت', 'زایشگاه' ),
		),
		'infrastructure' => array(
			'name'     => 'حمل‌ونقل و زیرساخت',
			'aliases'  => array( 'زیرساخت/حمل‌ونقل', 'حمل و نقل و زیرساخت', 'زیرساخت', 'حمل‌ونقل', 'حمل و نقل' ),
			'anon'     => 'یک پروژه زیرساختی',
			'keywords' => array( 'بندر', 'فرودگاه', 'راه آهن', 'مترو', 'منطقه آزاد', 'کشتیرانی', 'اسکله', 'پایانه', 'تونل', 'مخابرات', 'رادیو', 'تلویزیون' ),
		),
		'government'     => array(
			'name'     => 'دولتی و عمومی',
			'aliases'  => array( 'دولتی', 'عمومی' ),
			'anon'     => 'یک مجموعه دولتی',
			'keywords' => array(),
		),
		'other'          => array(
			'name'     => 'سایر',
			'aliases'  => array( 'other', 'متفرقه', 'غیره' ),
			'anon'     => 'یک کارفرمای خصوصی',
			'keywords' => array(),
		),
	);
	return apply_filters( 'psb_pr_sectors', $sectors );
}

/**
 * Panel / scope types. key => [ label, aliases[] ]. Stored as a meta array of keys (`_pr_types`).
 * Order = display order of the filter chips (before sorting by count).
 *
 * @return array
 */
function psb_pr_types() {
	$types = array(
		'mv'         => array( 'فشار متوسط', array( 'فشار متوسط', 'فشارمتوسط', 'فشار قوی', 'mv', '20kv', '۲۰ کیلوولت', '33kv', 'کیلوولت' ) ),
		'lv'         => array( 'فشار ضعیف و توزیع', array( 'فشار ضعیف', 'فشارضعیف', 'توزیع', 'lv', 'تابلو اصلی' ) ),
		'mcc'        => array( 'MCC', array( 'mcc', 'موتور کنترل سنتر' ) ),
		'control'    => array( 'فرمان و کنترل', array( 'فرمان', 'کنترل', 'plc', 'اتوماسیون' ) ),
		'capacitor'  => array( 'بانک خازن', array( 'خازن', 'اصلاح ضریب قدرت', 'kvar' ) ),
		'ats'        => array( 'اضطراری و ATS', array( 'اضطراری', 'ژنراتور', 'ats', 'تعویض کننده' ) ),
		'rmu'        => array( 'کمپکت / RMU', array( 'rmu', 'کمپکت', 'رینگ مین' ) ),
		'meter'      => array( 'کنتوری', array( 'کنتور', 'اندازه گیری', 'اندازه‌گیری' ) ),
		'hospital'   => array( 'ایزوله بیمارستانی', array( 'ایزوله', 'it system', 'it-system' ) ),
		'drawout'    => array( 'کشویی', array( 'کشویی', 'withdrawable', 'draw out' ) ),
		'lighting'   => array( 'روشنایی', array( 'روشنایی', 'روشنائی' ) ),
		'busduct'    => array( 'باس‌داکت', array( 'باس داکت', 'باس‌داکت', 'busduct', 'bus duct' ) ),
		'marine'     => array( 'دریایی', array( 'دریایی', 'شناور', 'lloyd', 'کشتی' ) ),
		'avr'        => array( 'AVR', array( 'avr', 'تثبیت ولتاژ' ) ),
		'instrument' => array( 'ابزاردقیق', array( 'ابزاردقیق', 'ابزار دقیق', 'instrument' ) ),
		'tray'       => array( 'سینی کابل', array( 'سینی کابل', 'cable tray' ) ),
		'install'    => array( 'نصب و راه‌اندازی', array( 'نصب', 'راه اندازی', 'راه‌اندازی', 'epc' ) ),
		'design'     => array( 'طراحی و مهندسی', array( 'طراحی', 'مهندسی' ) ),
	);
	return apply_filters( 'psb_pr_types', $types );
}

/** key => label. */
function psb_pr_type_labels() {
	$out = array();
	foreach ( psb_pr_types() as $k => $t ) {
		$out[ $k ] = $t[0];
	}
	return $out;
}

/**
 * Detect panel-type keys in a free-text cell («فشار متوسط؛ فشار ضعیف/توزیع؛ MCC»).
 *
 * @param string $text    Cell text.
 * @param array  $unknown Filled with tokens that matched nothing.
 * @return string[] keys
 */
function psb_pr_match_types( $text, &$unknown = array() ) {
	$text = psb_pr_clean( $text );
	if ( '' === $text ) {
		return array();
	}
	$found = array();
	$hay   = ' ' . psb_pr_key_spaced( $text ) . ' ';
	foreach ( psb_pr_types() as $key => $t ) {
		foreach ( $t[1] as $alias ) {
			$a = psb_pr_key_spaced( $alias );
			if ( '' !== $a && false !== strpos( $hay, $a ) ) {
				$found[ $key ] = true;
				break;
			}
		}
	}
	// Report tokens that matched nothing (helps the admin extend the vocabulary).
	foreach ( preg_split( '/[؛;،,\/|+—\-]+/u', $text ) as $tok ) {
		$tok = trim( $tok );
		if ( '' === $tok ) {
			continue;
		}
		$tk  = ' ' . psb_pr_key_spaced( $tok ) . ' ';
		$hit = false;
		foreach ( psb_pr_types() as $t ) {
			foreach ( $t[1] as $alias ) {
				$a = psb_pr_key_spaced( $alias );
				if ( '' !== $a && false !== strpos( $tk, $a ) ) {
					$hit = true;
					break 2;
				}
			}
		}
		if ( ! $hit ) {
			$unknown[] = $tok;
		}
	}
	return array_keys( $found );
}

/** Like psb_pr_key() but keeps single spaces between words (for substring matching of phrases). */
function psb_pr_key_spaced( $text ) {
	$t = psb_pr_latin_digits( psb_pr_clean( $text ) );
	$t = function_exists( 'mb_strtolower' ) ? mb_strtolower( $t, 'UTF-8' ) : strtolower( $t );
	$t = strtr( $t, array( 'آ' => 'ا', 'أ' => 'ا', 'إ' => 'ا', 'ۀ' => 'ه', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ی', "\u{200C}" => ' ' ) );
	$t = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $t );
	return trim( (string) $t );
}

/**
 * Map a sector cell to a sector slug ('' when empty / unknown).
 *
 * @param string $text Cell.
 * @return string
 */
function psb_pr_match_sector( $text ) {
	$k = psb_pr_key( $text );
	if ( '' === $k ) {
		return '';
	}
	foreach ( psb_pr_sectors() as $slug => $s ) {
		if ( psb_pr_key( $s['name'] ) === $k || psb_pr_key( $slug ) === $k ) {
			return $slug;
		}
		foreach ( $s['aliases'] as $a ) {
			if ( psb_pr_key( $a ) === $k ) {
				return $slug;
			}
		}
	}
	// Partial: first alias contained in the cell.
	foreach ( psb_pr_sectors() as $slug => $s ) {
		foreach ( array_merge( array( $s['name'] ), $s['aliases'] ) as $a ) {
			$ak = psb_pr_key( $a );
			if ( mb_strlen( $ak ) >= 3 && false !== strpos( $k, $ak ) ) {
				return $slug;
			}
		}
	}
	return '';
}

/**
 * Guess a sector from the project title/client (used only when the sector cell is empty).
 *
 * @param string $text Title + client.
 * @return string slug or ''
 */
function psb_pr_infer_sector( $text ) {
	$hay = ' ' . psb_pr_key_spaced( $text ) . ' ';
	foreach ( array( 'healthcare', 'oil-gas', 'power', 'food', 'mining', 'infrastructure', 'building', 'industrial' ) as $slug ) {
		$s = psb_pr_sectors()[ $slug ] ?? null;
		if ( ! $s ) {
			continue;
		}
		foreach ( $s['keywords'] as $kw ) {
			$k = psb_pr_key_spaced( $kw );
			if ( '' !== $k && false !== strpos( $hay, ' ' . $k ) ) {
				return $slug;
			}
		}
	}
	return '';
}

/** Iranian provinces (display order = alphabetical in Persian). */
function psb_pr_provinces() {
	return array( 'آذربایجان شرقی', 'آذربایجان غربی', 'اردبیل', 'اصفهان', 'البرز', 'ایلام', 'بوشهر', 'تهران', 'چهارمحال و بختیاری', 'خراسان جنوبی', 'خراسان رضوی', 'خراسان شمالی', 'خوزستان', 'زنجان', 'سمنان', 'سیستان و بلوچستان', 'فارس', 'قزوین', 'قم', 'کردستان', 'کرمان', 'کرمانشاه', 'کهگیلویه و بویراحمد', 'گلستان', 'گیلان', 'لرستان', 'مازندران', 'مرکزی', 'هرمزگان', 'همدان', 'یزد' );
}

/** Label used for projects outside Iran. */
function psb_pr_abroad_label() {
	return 'خارج از کشور';
}

/**
 * City / area → province. Keys are psb_pr_key()-normalised. Extend with the `psb_pr_city_map` filter.
 *
 * @return array
 */
function psb_pr_city_map() {
	static $map = null;
	if ( null !== $map ) {
		return $map;
	}
	$raw = array(
		'آذربایجان شرقی'      => 'تبریز مراغه آذرشهر|آذر شهر سونگون مرند میانه اهر بناب سهند سراب هشترود عجب‌شیر|عجبشیر ملکان شبستر جلفا',
		'آذربایجان غربی'      => 'ارومیه خوی مهاباد میاندوآب بوکان سلماس نقده پیرانشهر ماکو تکاب',
		'اردبیل'             => 'اردبیل پارس آباد|پارس‌آباد مشگین شهر|مشکین شهر خلخال گرمی نمین سرعین',
		'اصفهان'             => 'اصفهان کاشان نائین|نایین گلپایگان نجف آباد|نجف‌آباد خمینی شهر شاهین شهر مبارکه فولاد شهر|فولادشهر زرین شهر نطنز آران و بیدگل خوانسار اردستان شهرضا سمیرم فلاورجان',
		'البرز'              => 'کرج هشتگرد نظرآباد|نظر آباد طالقان فردیس اشتهارد ماهدشت',
		'ایلام'              => 'ایلام دهلران مهران آبدانان ایوان دره شهر',
		'بوشهر'              => 'بوشهر برازجان جم کنگان گنگ عسلویه پارس جنوبی|پارس‌جنوبی دیر دیلم گناوه خورموج تنگستان',
		'تهران'              => 'تهران شمیرانات|شمیران ری|شهر ری اسلامشهر|اسلام شهر ورامین پاکدشت دماوند فیروزکوه لواسان پردیس رودهن بومهن شهریار رباط کریم قدس شهر قدس ملارد پرند شمس آباد|شمس‌آباد چیتگر شهرک غرب کهریزک بهارستان',
		'چهارمحال و بختیاری' => 'شهرکرد بروجن فارسان لردگان',
		'خراسان جنوبی'       => 'بیرجند طبس قائن فردوس نهبندان',
		'خراسان رضوی'        => 'مشهد نیشابور سبزوار تربت حیدریه|تربت‌حیدریه تربت جام قوچان کاشمر گناباد چناران سرخس درگز',
		'خراسان شمالی'       => 'بجنورد شیروان اسفراین|اسفرائن جاجرم آشخانه',
		'خوزستان'            => 'اهواز آبادان خرمشهر ماهشهر|بندر ماهشهر بندر امام|بندر امام خمینی دزفول اندیمشک شوشتر شوش مسجد سلیمان|مسجدسلیمان بهبهان ایذه هویزه سوسنگرد رامهرمز امیدیه هفتکل لالی رامشیر',
		'زنجان'              => 'زنجان ابهر خرمدره قیدار',
		'سمنان'              => 'سمنان شاهرود دامغان گرمسار',
		'سیستان و بلوچستان'  => 'زاهدان زابل چابهار|چاه بهار ایرانشهر خاش سراوان کنارک نیکشهر',
		'فارس'               => 'شیراز مرودشت فسا اقلید آباده جهرم لار کازرون داراب فیروزآباد|فیروز آباد نی ریز|نیریز سپیدان استهبان',
		'قزوین'              => 'قزوین تاکستان البرز صنعتی|شهر صنعتی البرز بوئین زهرا|بویین زهرا آبیک الوند',
		'قم'                 => 'قم',
		'کردستان'            => 'سنندج سقز مریوان بانه بیجار قروه کامیاران دیواندره',
		'کرمان'              => 'کرمان رفسنجان سیرجان کهنوج بم جیرفت زرند شهربابک سرچشمه بافت راور',
		'کرمانشاه'           => 'کرمانشاه باختران سر پل ذهاب|سرپل ذهاب اسلام آباد غرب|اسلام‌آباد غرب کنگاور هرسین صحنه جوانرود قصر شیرین',
		'کهگیلویه و بویراحمد' => 'یاسوج دوگنبدان|گچساران دهدشت',
		'گلستان'             => 'گرگان کردکوی گنبد کاووس|گنبد بندر ترکمن آق قلا علی آباد کتول بندرگز',
		'گیلان'              => 'رشت انزلی|بندر انزلی لاهیجان لنگرود آستارا املش رودسر رودبار منجیل صومعه سرا|صومعه‌سرا تالش فومن آستانه اشرفیه',
		'لرستان'             => 'خرم آباد|خرم‌آباد بروجرد دورود الیگودرز کوهدشت ازنا نورآباد',
		'مازندران'           => 'ساری آمل بابل قائم شهر|قائم‌شهر|قائمشهر چالوس رامسر نوشهر تنکابن بابلسر بهشهر نکا محمودآباد|محمود آباد نور جویبار',
		'مرکزی'              => 'اراک ساوه محلات خمین دلیجان تفرش شازند آشتیان',
		'هرمزگان'            => 'بندرعباس|بندر عباس قشم کیش جاسک بندر لنگه|بندرلنگه میناب بستک پارسیان',
		'همدان'              => 'همدان ملایر نهاوند تویسرکان اسدآباد|اسد آباد کبودرآهنگ بهار',
		'یزد'                => 'یزد اردکان بافق میبد مهریز تفت ابرکوه|ابرکوه',
	);
	$map = array();
	foreach ( $raw as $prov => $list ) {
		$map[ psb_pr_key( $prov ) ] = $prov;
		// Items are separated by spaces; «|» joins variants; multi-word names are listed with a space inside a variant group.
		foreach ( psb_pr_city_tokens( $list ) as $city ) {
			$map[ psb_pr_key( $city ) ] = $prov;
		}
	}
	$map = array_filter( $map );
	foreach ( array( 'عراق', 'افغانستان', 'آفریقا', 'افریقا', 'ترکمنستان', 'تاجیکستان', 'ارمنستان', 'آذربایجان جمهوری', 'سوریه', 'لبنان', 'عمان', 'قطر', 'امارات', 'دبی', 'کویت', 'خارج از کشور', 'خارج' ) as $c ) {
		$map[ psb_pr_key( $c ) ] = psb_pr_abroad_label();
	}
	$map = apply_filters( 'psb_pr_city_map', $map );
	return $map;
}

/**
 * Split the city list. Multi-word names are given as variants joined by «|» (each variant may contain spaces);
 * single words are space separated. We greedily prefer known multi-word names.
 *
 * @param string $list Space separated list.
 * @return string[]
 */
function psb_pr_city_tokens( $list ) {
	$multi = array( 'آذر شهر', 'پارس آباد', 'مشگین شهر', 'مشکین شهر', 'نجف آباد', 'خمینی شهر', 'شاهین شهر', 'فولاد شهر', 'زرین شهر', 'آران و بیدگل', 'نظر آباد', 'دره شهر', 'پارس جنوبی', 'شهر ری', 'اسلام شهر', 'رباط کریم', 'قدس شهر', 'شهر قدس', 'شمس آباد', 'شهرک غرب', 'تربت حیدریه', 'تربت جام', 'بندر ماهشهر', 'بندر امام', 'بندر امام خمینی', 'مسجد سلیمان', 'چاه بهار', 'فیروز آباد', 'نی ریز', 'البرز صنعتی', 'شهر صنعتی البرز', 'بوئین زهرا', 'بویین زهرا', 'سر پل ذهاب', 'سرپل ذهاب', 'اسلام آباد غرب', 'قصر شیرین', 'گنبد کاووس', 'بندر ترکمن', 'آق قلا', 'علی آباد کتول', 'بندر انزلی', 'صومعه سرا', 'آستانه اشرفیه', 'خرم آباد', 'قائم شهر', 'محمود آباد', 'بندر عباس', 'بندر لنگه', 'اسد آباد', 'جاده قدیم کرج' );
	$out   = array();
	foreach ( explode( '|', $list ) as $chunk ) {
		$chunk = ' ' . trim( $chunk ) . ' ';
		foreach ( $multi as $m ) {
			if ( false !== strpos( $chunk, ' ' . $m . ' ' ) ) {
				$out[] = $m;
				$chunk = str_replace( ' ' . $m . ' ', ' ', $chunk );
			}
		}
		foreach ( preg_split( '/\s+/u', trim( $chunk ) ) as $w ) {
			if ( '' !== $w ) {
				$out[] = $w;
			}
		}
	}
	return $out;
}

/**
 * Resolve a free-text location («تهران، شهرک غرب», «قم و ساوه», «اندیمشک») to [ province, city ].
 * Province is '' when no part is recognised; city keeps the cleaned original text.
 *
 * @param string $text Location cell.
 * @return array{0:string,1:string}
 */
function psb_pr_resolve_location( $text ) {
	$text = psb_pr_clean( $text );
	if ( '' === $text ) {
		return array( '', '' );
	}
	$map   = psb_pr_city_map();
	$parts = preg_split( '/\s*(?:[،,؛;\/\-–—(]|\sو\s)\s*/u', $text );
	foreach ( $parts as $p ) {
		$p = trim( $p, " )\t" );
		if ( '' === $p ) {
			continue;
		}
		$k = psb_pr_key( $p );
		if ( isset( $map[ $k ] ) ) {
			return array( $map[ $k ], $text );
		}
		// «بندر X», «شهرستان X», «استان X», «شهرک صنعتی X».
		$k2 = psb_pr_key( preg_replace( '/^(?:استان|شهرستان|شهر|بندر|شهرک صنعتی|منطقه)\s+/u', '', $p ) );
		if ( isset( $map[ $k2 ] ) ) {
			return array( $map[ $k2 ], $text );
		}
	}
	// Whole string starts with a known name («تهران جاده قدیم کرج»).
	foreach ( $map as $k => $prov ) {
		if ( mb_strlen( $k ) >= 3 && 0 === strpos( psb_pr_key( $text ), $k ) ) {
			return array( $prov, $text );
		}
	}
	return array( '', $text );
}

/* -------------------------------------------------------------------------
 * Icons (sector glyphs, 24px stroke icons, same style as psb_icon()).
 * ---------------------------------------------------------------------- */

/**
 * Inline SVG icon for a sector slug.
 *
 * @param string $slug  Sector slug.
 * @param string $class Classes.
 * @return string
 */
function psb_pr_sector_icon( $slug, $class = 'pr-ico' ) {
	$paths = array(
		'power'          => '<path d="M13 2 5 13h6l-1 9 8-12h-6z"/>',
		'oil-gas'        => '<path d="M12 3c3 4 6 7.2 6 11a6 6 0 0 1-12 0c0-3.8 3-7 6-11z"/><path d="M12 18a2.5 2.5 0 0 1-2.5-2.5c0-1.5 1.2-2.8 2.5-4.5 1.3 1.7 2.5 3 2.5 4.5A2.5 2.5 0 0 1 12 18z"/>',
		'industrial'     => '<path d="M3 21V10l6 3V10l6 3V5h4v16z"/><path d="M7 17h2M12 17h2M17 17h1"/>',
		'food'           => '<path d="M12 21V9"/><path d="M12 13c-3 0-5-2-5-5 3 0 5 2 5 5zM12 13c3 0 5-2 5-5-3 0-5 2-5 5zM12 9c-2 0-3.5-1.5-3.5-4C10.5 5 12 6.5 12 9zM12 9c2 0 3.5-1.5 3.5-4-2 0-3.5 1.5-3.5 4z"/>',
		'mining'         => '<path d="M3 20 10 8l3 5 2-3 6 10z"/><path d="M8.5 11 11 15"/>',
		'building'       => '<path d="M4 21V5l8-2v18M12 8l8 2v11M3 21h18"/><path d="M7.5 8h1M7.5 12h1M7.5 16h1M15.5 13h1M15.5 17h1"/>',
		'healthcare'     => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M12 8v8M8 12h8"/>',
		'infrastructure' => '<path d="M3 18h18M5 18V9M19 18V9M3 9h18"/><path d="M5 9c2.5 4 11.5 4 14 0M9 18v-5M15 18v-5"/>',
		'government'     => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z"/>',
		'other'          => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
	);
	$p = $paths[ $slug ] ?? $paths['other'];
	return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
}

/**
 * Escape text and wrap Latin tokens containing digits (20KV, 400V/210KVAR, 250 kVA, IP54) in .lat,
 * so Peyda FaNum does not render them with Persian digits.
 *
 * @param string $text Plain text.
 * @return string Safe HTML.
 */
function psb_pr_lat( $text ) {
	$html = esc_html( (string) $text );
	return (string) preg_replace_callback(
		'/(?<![\p{L}\p{N}])([A-Za-z][A-Za-z\-\/]*\s?[0-9][0-9A-Za-z.\/\-~]*|[0-9۰-۹][0-9۰-۹.,\/\-~]*\s?[A-Za-z][A-Za-z0-9\/\-.]*)(?![\p{L}\p{N}])/u',
		static function ( $m ) {
			// «۲۵۰ kVA» → «250 kVA» as one isolated LTR token (spec §2: Latin digits inside Latin tokens).
			return '<span class="lat">' . psb_pr_latin_digits( $m[1] ) . '</span>';
		},
		$html
	);
}


/**
 * Owner policy (2026-11-26): projects for military/defence/aerospace/security bodies, prisons and
 * nuclear sites must never appear on the site. Used by the importer to drop such rows.
 */
function psb_pr_is_restricted( $text ) {
	$t = str_replace( array( 'ي', 'ك' ), array( 'ی', 'ک' ), (string) $text );
	$words = apply_filters(
		'psb_pr_restricted_words',
		array( 'هوافضا', 'هوا فضا', 'صنایع دفاع', 'وزارت دفاع', 'دفاعی', 'سپاه', 'پادگان', 'ارتش', 'نزاجا', 'یا مهدی', 'سپاهان لایه', 'سپاهان مسیل', 'الغدیر', 'جولائی', 'جولایی', 'شهید درویش', 'هلکوپتر', 'هلیکوپتر', 'هواپیماسازی', 'هواپیما سازی', 'پایگاه هشتم', 'پایگاه شکاری', 'ندامتگاه', 'زندان', 'انرژی اتمی', 'هسته‌ای', 'هسته ای', 'شهید باقری', 'فجر 3', 'فجر ۳', 'نیروی هوائی', 'نیروی هوایی', 'نظامی', 'موشک', 'مهمات', 'پهنا', 'قرارگاه', 'بسیج', 'انتظامی', 'ناجا' )
	);
	foreach ( $words as $w ) {
		if ( '' !== $w && false !== mb_strpos( $t, $w ) ) {
			return true;
		}
	}
	return false;
}
