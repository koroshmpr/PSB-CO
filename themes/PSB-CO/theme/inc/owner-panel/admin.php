<?php
/**
 * Owner panel — WordPress entry points: settings page (Settings → «پنل مدیریت و اپ»), admin-bar link,
 * dashboard widget, speculation-rules exclusion.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	static function () {
		add_options_page( 'پنل مدیریت موبایل و اپ', 'پنل مدیریت و اپ', 'manage_options', PSB_SETTINGS_PAGE, 'psb_panel_settings_page' );
	}
);

add_action(
	'admin_init',
	static function () {
		register_setting(
			PSB_SETTINGS_GROUP,
			PSB_PANEL_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'psb_panel_sanitize_settings',
				'default'           => psb_panel_defaults(),
			)
		);
		add_settings_section(
			'psb_panel',
			'پنل مدیریت موبایل',
			static function () {
				echo '<p>آدرس پنل: <a href="' . esc_url( psb_panel_url() ) . '"><code>' . esc_html( psb_panel_url() ) . '</code></a> — روی گوشی باز کنید و «افزودن به صفحه اصلی» را بزنید تا به‌صورت اپ جداگانه «PSB Panel» نصب شود.</p>';
			},
			PSB_SETTINGS_PAGE
		);
		$s = psb_panel_settings();
		add_settings_field(
			'psb_panel_days',
			'ماندگاری ورود (روز)',
			static function () use ( $s ) {
				printf( '<input type="number" min="1" max="365" class="small-text" name="%1$s[session_days]" value="%2$d"> ', esc_attr( PSB_PANEL_OPTION ), (int) $s['session_days'] );
				echo '<p class="description">برای کاربرانی که به پنل دسترسی دارند و با «مرا به خاطر بسپار» وارد می‌شوند. بقیه کاربران همان ۱۴ روز پیش‌فرض وردپرس را دارند. هرچه بیشتر باشد، گوشیِ گم‌شده یا باز مدت بیشتری به سایت دسترسی دارد؛ در این حالت از دکمه «خروج از همه دستگاه‌ها» در پنل یا صفحه پروفایل استفاده کنید.</p>';
			},
			PSB_SETTINGS_PAGE,
			'psb_panel'
		);
		add_settings_field(
			'psb_panel_refresh',
			'تمدید خودکار (روز)',
			static function () use ( $s ) {
				printf( '<input type="number" min="0" max="365" class="small-text" name="%1$s[refresh_days]" value="%2$d"> ', esc_attr( PSB_PANEL_OPTION ), (int) $s['refresh_days'] );
				echo '<p class="description">اگر کمتر از این تعداد روز به پایان ورود مانده باشد، با باز کردن پنل بی‌صدا تمدید می‌شود. ۰ = بدون تمدید.</p>';
			},
			PSB_SETTINGS_PAGE,
			'psb_panel'
		);
		add_settings_field(
			'psb_panel_roles',
			'نقش‌های دارای دسترسی',
			static function () use ( $s ) {
				$eligible = psb_panel_eligible_roles();
				foreach ( wp_roles()->get_names() as $key => $name ) {
					if ( ! in_array( $key, $eligible, true ) ) {
						continue; // Customers/subscribers can never get the panel (security review L7).
					}
					$is_admin = 'administrator' === $key;
					printf(
						'<label style="display:inline-block;margin:0 0 6px 16px"><input type="checkbox" name="%1$s[roles][]" value="%2$s" %3$s %4$s> %5$s</label>',
						esc_attr( PSB_PANEL_OPTION ),
						esc_attr( $key ),
						checked( $is_admin || in_array( $key, $s['roles'], true ), true, false ),
						disabled( $is_admin, true, false ),
						esc_html( translate_user_role( $name ) )
					);
				}
				echo '<p class="description">مدیر کل همیشه دسترسی دارد. فقط نقش‌هایی که اجازه ویرایش نوشته (edit_posts) دارند قابل انتخاب‌اند. هر بخش پنل علاوه بر این، مجوز خودش را هم بررسی می‌کند (مثلاً انتشار نوشته یا مدیریت دیدگاه).</p>';
			},
			PSB_SETTINGS_PAGE,
			'psb_panel'
		);
		add_settings_field(
			'psb_panel_accent',
			'رنگ اصلی پنل',
			static function () use ( $s ) {
				printf( '<input type="color" name="%1$s[accent]" value="%2$s">', esc_attr( PSB_PANEL_OPTION ), esc_attr( $s['accent'] ) );
			},
			PSB_SETTINGS_PAGE,
			'psb_panel'
		);
	}
);

function psb_panel_sanitize_settings( $in ) {
	$in    = is_array( $in ) ? $in : array();
	$roles = array( 'administrator' );
	$valid = psb_panel_eligible_roles();
	foreach ( (array) ( $in['roles'] ?? array() ) as $r ) {
		$r = sanitize_key( $r );
		if ( in_array( $r, $valid, true ) ) {
			$roles[] = $r;
		}
	}
	$days = min( 365, max( 1, absint( $in['session_days'] ?? 180 ) ) );
	return array(
		'session_days' => $days,
		'refresh_days' => min( $days, absint( $in['refresh_days'] ?? 30 ) ),
		'roles'        => array_values( array_unique( $roles ) ),
		'accent'       => sanitize_hex_color( $in['accent'] ?? '' ) ? sanitize_hex_color( $in['accent'] ) : '#1A3DA3',
	);
}

function psb_panel_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap"><h1>پنل مدیریت موبایل و اپ سایت</h1>';
	echo '<form method="post" action="options.php">';
	settings_fields( PSB_SETTINGS_GROUP );
	do_settings_sections( PSB_SETTINGS_PAGE );
	submit_button();
	echo '</form>';
	echo '<hr><h2>نشست‌های من</h2>';
	$info = psb_panel_session_info();
	printf(
		'<p>تعداد نشست‌های فعال شما: <strong>%1$s</strong>. نشست فعلی تا <strong>%2$s</strong> معتبر است. برای خروج از دستگاه‌های دیگر از دکمه «خروج از همه جلسه‌های دیگر» در <a href="%3$s">پروفایل</a> یا بخش تنظیمات پنل استفاده کنید.</p>',
		esc_html( number_format_i18n( $info['count'] ) ),
		esc_html( $info['expires'] ? psb_panel_jdate( $info['expires'] ) : '—' ),
		esc_url( admin_url( 'profile.php' ) )
	);
	echo '<p>راهنمای کامل: فایل <code>themes/PSB-CO/PWA-PANEL.md</code>.</p></div>';
}

add_action(
	'admin_bar_menu',
	static function ( $bar ) {
		if ( ! psb_panel_can() ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'psb-panel',
				'title' => '<span class="ab-icon dashicons dashicons-smartphone" aria-hidden="true" style="top:2px"></span><span class="ab-label">پنل موبایل</span>',
				'href'  => psb_panel_url(),
				'meta'  => array( 'title' => 'پنل مدیریت موبایل (درخواست‌ها، محتوا، فرم‌ها)' ),
			)
		);
	},
	70
);

add_action(
	'wp_dashboard_setup',
	static function () {
		if ( ! psb_panel_can() ) {
			return;
		}
		wp_add_dashboard_widget(
			'psb_panel_widget',
			'پنل مدیریت موبایل',
			static function () {
				echo '<p style="margin-top:0">درخواست‌های استعلام، انتشار محتوا، دیدگاه‌ها و وضعیت سایت در یک اپ سبک روی گوشی؛ قابل نصب روی صفحه اصلی.</p>';
				if ( psb_panel_rfq_can() ) {
					$c = psb_panel_rfq_counts();
					printf( '<p>استعلام جدید: <strong>%1$s</strong> · امروز: <strong>%2$s</strong> · ۷ روز: <strong>%3$s</strong></p>', esc_html( number_format_i18n( $c['new'] ) ), esc_html( number_format_i18n( $c['today'] ) ), esc_html( number_format_i18n( $c['week'] ) ) );
				}
				echo '<p style="margin:0"><a class="button button-primary" href="' . esc_url( psb_panel_url() ) . '">باز کردن پنل</a></p>';
			}
		);
	}
);

// Never prefetch/prerender the panel from public pages.
add_filter(
	'wp_speculation_rules_href_exclude_paths',
	static function ( $paths ) {
		$paths[] = '/' . PSB_PANEL_SLUG . '/*';
		return $paths;
	}
);
