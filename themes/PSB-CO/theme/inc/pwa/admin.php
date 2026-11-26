<?php
/**
 * PWA — settings section on «تنظیمات → پنل مدیریت و اپ» (page slug psb-panel, shared with the owner panel).
 * If the owner-panel module is not loaded, the page is registered here.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'PSB_SETTINGS_PAGE' ) ) {
	define( 'PSB_SETTINGS_PAGE', 'psb-panel' );
	define( 'PSB_SETTINGS_GROUP', 'psb_panel_group' );
}

add_action(
	'admin_init',
	static function () {
		register_setting(
			PSB_SETTINGS_GROUP,
			PSB_PWA_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'psb_pwa_sanitize_settings',
				'default'           => psb_pwa_defaults(),
			)
		);
		add_settings_section(
			'psb_pwa',
			'اپ سایت (PWA)',
			static function () {
				$s = psb_pwa_stats();
				echo '<p>فایل‌های <code>/manifest.webmanifest</code> و <code>/sw.js</code> به‌صورت پویا ساخته می‌شوند و با هر به‌روزرسانی قالب نسخه‌شان عوض می‌شود.</p>';
				printf(
					'<p>آمار نصب: پیشنهاد نصب %1$s · نصب پذیرفته %2$s · رد شده %3$s · نصب‌شده %4$s · راهنمای iOS %5$s · بازدید از اپ نصب‌شده (نشست) %6$s</p>',
					esc_html( number_format_i18n( $s['prompt'] ) ),
					esc_html( number_format_i18n( $s['accepted'] ) ),
					esc_html( number_format_i18n( $s['dismissed'] ) ),
					esc_html( number_format_i18n( $s['installed'] ) ),
					esc_html( number_format_i18n( $s['ios'] ) ),
					esc_html( number_format_i18n( $s['standalone'] ) )
				);
			},
			PSB_SETTINGS_PAGE
		);
		$s     = psb_pwa_settings();
		$check = static function ( $key, $label ) use ( $s ) {
			return static function () use ( $key, $label, $s ) {
				printf(
					'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label>',
					esc_attr( PSB_PWA_OPTION ),
					esc_attr( $key ),
					checked( ! empty( $s[ $key ] ), true, false ),
					esc_html( $label )
				);
			};
		};
		add_settings_field( 'psb_pwa_enabled', 'فعال بودن اپ', $check( 'enabled', 'مانیفست و سرویس‌ورکر فعال باشد (غیرفعال کردن = حذف خودکار سرویس‌ورکر و کش از گوشی بازدیدکنندگان)' ), PSB_SETTINGS_PAGE, 'psb_pwa' );
		add_settings_field( 'psb_pwa_prompt', 'پیشنهاد نصب', $check( 'prompt', 'نمایش برگه «نصب اپ» پس از دومین بازدید یا چند ثانیه ماندن در صفحه' ), PSB_SETTINGS_PAGE, 'psb_pwa' );
		add_settings_field( 'psb_pwa_ios', 'راهنمای iOS', $check( 'ios', 'نمایش راهنمای «Share → Add to Home Screen» در آیفون و آیپد' ), PSB_SETTINGS_PAGE, 'psb_pwa' );
		add_settings_field(
			'psb_pwa_delay',
			'تأخیر پیشنهاد نصب (ثانیه)',
			static function () use ( $s ) {
				printf( '<input type="number" min="5" max="600" step="1" class="small-text" name="%1$s[prompt_delay]" value="%2$d">', esc_attr( PSB_PWA_OPTION ), (int) $s['prompt_delay'] );
			},
			PSB_SETTINGS_PAGE,
			'psb_pwa'
		);
	}
);

function psb_pwa_sanitize_settings( $in ) {
	$in = is_array( $in ) ? $in : array();
	return array(
		'enabled'      => empty( $in['enabled'] ) ? 0 : 1,
		'prompt'       => empty( $in['prompt'] ) ? 0 : 1,
		'ios'          => empty( $in['ios'] ) ? 0 : 1,
		'prompt_delay' => min( 600, max( 5, isset( $in['prompt_delay'] ) ? absint( $in['prompt_delay'] ) : 30 ) ),
	);
}

// Stand-alone page when the owner panel module is not loaded.
add_action(
	'admin_menu',
	static function () {
		if ( function_exists( 'psb_panel_settings_page' ) ) {
			return;
		}
		add_options_page(
			'اپ سایت (PWA)',
			'اپ سایت (PWA)',
			'manage_options',
			PSB_SETTINGS_PAGE,
			static function () {
				echo '<div class="wrap"><h1>اپ سایت (PWA)</h1><form method="post" action="options.php">';
				settings_fields( PSB_SETTINGS_GROUP );
				do_settings_sections( PSB_SETTINGS_PAGE );
				submit_button();
				echo '</form></div>';
			}
		);
	},
	20
);
