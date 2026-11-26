<?php
/**
 * AI visibility — settings, defaults and the AI-bot catalogue.
 *
 * Settings live in one small autoloaded option (psb_ai_settings) because the
 * front end needs the bot list on every request for the cheap UA check.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_AI_OPTION = 'psb_ai_settings';

/**
 * Known AI crawlers / agents.
 *
 * kind: search = AI search / answer-engine index, user = fetches on behalf of a
 * user's live question, training = model-training crawler.
 * ua: substring used for the activity log (false = robots.txt token only, never
 * sent as a User-Agent).
 *
 * @return array<string, array{vendor:string, kind:string, ua:string|false}>
 */
function psb_ai_bots() {
	return array(
		'GPTBot'             => array( 'vendor' => 'OpenAI', 'kind' => 'training', 'ua' => 'GPTBot' ),
		'OAI-SearchBot'      => array( 'vendor' => 'OpenAI', 'kind' => 'search', 'ua' => 'OAI-SearchBot' ),
		'ChatGPT-User'       => array( 'vendor' => 'OpenAI', 'kind' => 'user', 'ua' => 'ChatGPT-User' ),
		'ClaudeBot'          => array( 'vendor' => 'Anthropic', 'kind' => 'training', 'ua' => 'ClaudeBot' ),
		'Claude-SearchBot'   => array( 'vendor' => 'Anthropic', 'kind' => 'search', 'ua' => 'Claude-SearchBot' ),
		'Claude-User'        => array( 'vendor' => 'Anthropic', 'kind' => 'user', 'ua' => 'Claude-User' ),
		'anthropic-ai'       => array( 'vendor' => 'Anthropic', 'kind' => 'training', 'ua' => 'anthropic-ai' ),
		'PerplexityBot'      => array( 'vendor' => 'Perplexity', 'kind' => 'search', 'ua' => 'PerplexityBot' ),
		'Perplexity-User'    => array( 'vendor' => 'Perplexity', 'kind' => 'user', 'ua' => 'Perplexity-User' ),
		'Google-Extended'    => array( 'vendor' => 'Google', 'kind' => 'training', 'ua' => false ),
		'Applebot-Extended'  => array( 'vendor' => 'Apple', 'kind' => 'training', 'ua' => false ),
		'CCBot'              => array( 'vendor' => 'Common Crawl', 'kind' => 'training', 'ua' => 'CCBot' ),
		'Bytespider'         => array( 'vendor' => 'ByteDance', 'kind' => 'training', 'ua' => 'Bytespider' ),
		'meta-externalagent' => array( 'vendor' => 'Meta', 'kind' => 'training', 'ua' => 'meta-externalagent' ),
		'cohere-ai'          => array( 'vendor' => 'Cohere', 'kind' => 'training', 'ua' => 'cohere-ai' ),
		'Amazonbot'          => array( 'vendor' => 'Amazon', 'kind' => 'search', 'ua' => 'Amazonbot' ),
		'DuckAssistBot'      => array( 'vendor' => 'DuckDuckGo', 'kind' => 'search', 'ua' => 'DuckAssistBot' ),
		'YouBot'             => array( 'vendor' => 'You.com', 'kind' => 'search', 'ua' => 'YouBot' ),
		'MistralAI-User'     => array( 'vendor' => 'Mistral', 'kind' => 'user', 'ua' => 'MistralAI-User' ),
	);
}

function psb_ai_kind_label( $kind ) {
	$labels = array(
		'search'   => 'جستجو / موتور پاسخ',
		'user'     => 'درخواست کاربر',
		'training' => 'آموزش مدل',
	);
	return $labels[ $kind ] ?? $kind;
}

/**
 * Content sections of llms.txt, in output order.
 *
 * @return array<string, array{title:string, post_type:string}>
 */
function psb_ai_sections() {
	return array(
		'products'     => array( 'title' => 'محصولات', 'post_type' => 'product' ),
		'services'     => array( 'title' => 'خدمات', 'post_type' => 'services' ),
		'confirmation' => array( 'title' => 'تأییدیه‌ها', 'post_type' => 'confirmation' ),
		'type-test'    => array( 'title' => 'تایپ تست‌ها', 'post_type' => 'type-test' ),
		'certificates' => array( 'title' => 'گواهینامه‌ها', 'post_type' => 'certificates' ),
		'portfolio'    => array( 'title' => 'نمونه کارها', 'post_type' => 'portfolio' ),
		'posts'        => array( 'title' => 'مقالات', 'post_type' => 'post' ),
		'pages'        => array( 'title' => 'صفحات اصلی', 'post_type' => 'page' ),
	);
}

function psb_ai_defaults() {
	$bots = array();
	foreach ( psb_ai_bots() as $name => $bot ) {
		$bots[ $name ] = 'allow';
	}

	return array(
		'endpoints'    => array(
			'llms'      => 1,
			'llms_full' => 1,
			'md'        => 1,
			'ai_txt'    => 1,
			'robots'    => 1,
		),
		'site_name'    => 'پارس سوئیچ بورد',
		'summary'      => 'پارس سوئیچ بورد (Pars Switch Board) تولیدکننده ایرانی تابلوهای برق فشار ضعیف و فشار متوسط است که از سال ۱۳۵۵ و با نزدیک به نیم قرن سابقه در زمینه طراحی، تولید و مونتاژ تابلو برق فعالیت می‌کند.',
		'about'        => 'محصولات شرکت شامل انواع تابلوهای برق فشار ضعیف، فشار متوسط، کشویی، کمپکت، تابلوهای اندازه‌گیری (کنتوری) و تابلوهای فرمان و کنترل است که بر اساس استانداردهای بین‌المللی طراحی و ساخته می‌شوند. دفتر مرکزی و کارخانه شرکت در تهران قرار دارند. این فایل فهرست صفحات کلیدی وب‌سایت رسمی شرکت برای استفاده مدل‌های زبانی و موتورهای پاسخ است.',
		'sections'     => array_fill_keys( array_keys( psb_ai_sections() ), 1 ),
		'max_posts'    => 20,
		'key_pages'    => '179,1312,1299,1296,1382',
		'exclude_ids'  => '',
		'full_max_kb'  => 1500,
		'contact'      => 1,
		'bots'         => $bots,
		'log_enabled'  => 1,
	);
}

/**
 * Merged settings (static-cached per request).
 */
function psb_ai_settings( $refresh = false ) {
	static $settings = null;
	if ( null === $settings || $refresh ) {
		$saved    = get_option( PSB_AI_OPTION, array() );
		$saved    = is_array( $saved ) ? $saved : array();
		$defaults = psb_ai_defaults();
		$settings = array_merge( $defaults, $saved );
		foreach ( array( 'endpoints', 'sections', 'bots' ) as $group ) {
			$settings[ $group ] = array_merge( $defaults[ $group ], is_array( $saved[ $group ] ?? null ) ? $saved[ $group ] : array() );
		}
	}
	return $settings;
}

function psb_ai_setting( $key, $default = null ) {
	$s = psb_ai_settings();
	return $s[ $key ] ?? $default;
}

function psb_ai_endpoint_enabled( $key ) {
	$e = psb_ai_setting( 'endpoints', array() );
	return ! empty( $e[ $key ] );
}

/**
 * Parse a comma / space separated ID list.
 *
 * @return int[]
 */
function psb_ai_parse_ids( $value ) {
	$ids = preg_split( '/[\s,،]+/u', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

/**
 * Sanitize the settings form.
 */
function psb_ai_sanitize_settings( $input ) {
	$input    = is_array( $input ) ? wp_unslash( $input ) : array();
	$defaults = psb_ai_defaults();
	$out      = array();

	$out['endpoints'] = array();
	foreach ( $defaults['endpoints'] as $key => $unused ) {
		$out['endpoints'][ $key ] = empty( $input['endpoints'][ $key ] ) ? 0 : 1;
	}

	$out['sections'] = array();
	foreach ( $defaults['sections'] as $key => $unused ) {
		$out['sections'][ $key ] = empty( $input['sections'][ $key ] ) ? 0 : 1;
	}

	$out['bots'] = array();
	foreach ( $defaults['bots'] as $name => $unused ) {
		$v                    = $input['bots'][ $name ] ?? 'allow';
		$out['bots'][ $name ] = 'disallow' === $v ? 'disallow' : 'allow';
	}

	$out['site_name']   = sanitize_text_field( $input['site_name'] ?? '' );
	$out['summary']     = sanitize_textarea_field( $input['summary'] ?? '' );
	$out['about']       = sanitize_textarea_field( $input['about'] ?? '' );
	$out['max_posts']   = max( 0, min( 200, absint( $input['max_posts'] ?? 20 ) ) );
	$out['key_pages']   = implode( ',', psb_ai_parse_ids( $input['key_pages'] ?? '' ) );
	$out['exclude_ids'] = implode( ',', psb_ai_parse_ids( $input['exclude_ids'] ?? '' ) );
	$out['full_max_kb'] = max( 100, min( 5000, absint( $input['full_max_kb'] ?? 1500 ) ) );
	$out['contact']     = empty( $input['contact'] ) ? 0 : 1;
	$out['log_enabled'] = empty( $input['log_enabled'] ) ? 0 : 1;

	if ( '' === $out['site_name'] ) {
		$out['site_name'] = $defaults['site_name'];
	}

	return $out;
}
