<?php
/**
 * AI visibility — admin dashboard (top-level menu «هوش مصنوعی و llms.txt»).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_AI_PAGE = 'psb-ai-visibility';

add_action(
	'admin_menu',
	static function () {
		add_menu_page(
			'هوش مصنوعی و llms.txt',
			'هوش مصنوعی و llms.txt',
			'manage_options',
			PSB_AI_PAGE,
			'psb_ai_admin_render',
			'dashicons-superhero',
			59
		);
	}
);

add_action( 'admin_post_psb_ai_save', 'psb_ai_admin_save' );
add_action( 'admin_post_psb_ai_rebuild', 'psb_ai_admin_rebuild' );
add_action( 'admin_post_psb_ai_check', 'psb_ai_admin_check' );
add_action( 'admin_post_psb_ai_clear_log', 'psb_ai_admin_clear_log' );

function psb_ai_admin_url( $tab = 'status', $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => PSB_AI_PAGE, 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
}

function psb_ai_admin_guard( $action ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to do that.' ), 403 );
	}
	check_admin_referer( $action );
}

function psb_ai_admin_save() {
	psb_ai_admin_guard( 'psb_ai_save' );
	$clean = psb_ai_sanitize_settings( $_POST['psb_ai'] ?? array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized in psb_ai_sanitize_settings().
	update_option( PSB_AI_OPTION, $clean, true );
	psb_ai_settings( true );
	psb_ai_invalidate( 'settings' );
	wp_safe_redirect( psb_ai_admin_url( 'settings', array( 'psb_ai_msg' => 'saved' ) ) );
	exit;
}

function psb_ai_admin_rebuild() {
	psb_ai_admin_guard( 'psb_ai_rebuild' );
	// Bump the revision first so the rebuilt copies are "fresh".
	$state                = psb_ai_state();
	$state['rev']         = (int) $state['rev'] + 1;
	$state['invalidated'] = time();
	$state['reason']      = 'manual';
	update_option( PSB_AI_STATE_OPTION, $state, false );
	psb_ai_rebuild_all();
	wp_safe_redirect( psb_ai_admin_url( 'status', array( 'psb_ai_msg' => 'rebuilt' ) ) );
	exit;
}

function psb_ai_admin_clear_log() {
	psb_ai_admin_guard( 'psb_ai_clear_log' );
	delete_option( PSB_AI_LOG_OPTION );
	wp_safe_redirect( psb_ai_admin_url( 'activity', array( 'psb_ai_msg' => 'cleared' ) ) );
	exit;
}

/**
 * Fetch the site's own public files and store a short report.
 */
function psb_ai_admin_check() {
	psb_ai_admin_guard( 'psb_ai_check' );

	$targets = array(
		'/llms.txt'      => 'GET',
		'/llms-full.txt' => 'HEAD',
		'/robots.txt'    => 'GET',
		'/ai.txt'        => 'GET',
	);
	$report  = array();

	foreach ( $targets as $path => $method ) {
		$url   = home_url( $path );
		$start = microtime( true );
		$args  = array(
			'timeout'             => 5,
			'redirection'         => 2,
			'sslverify'           => apply_filters( 'https_local_ssl_verify', false ),
			'user-agent'          => 'PSB-AI-Check/' . PSB_AI_VERSION,
			'limit_response_size' => 262144,
			'headers'             => array( 'Cache-Control' => 'no-cache' ),
		);
		$res   = 'HEAD' === $method ? wp_remote_head( $url, $args ) : wp_remote_get( $url, $args );
		$row   = array(
			'url'  => $url,
			'ms'   => (int) round( ( microtime( true ) - $start ) * 1000 ),
			'code' => 0,
			'type' => '',
			'size' => 0,
			'note' => '',
		);

		if ( is_wp_error( $res ) ) {
			$row['note'] = $res->get_error_message();
			// A failed loopback must not trip the theme's outbound circuit breaker for our own host.
			if ( function_exists( 'psb_http_breaker_key' ) ) {
				$k = psb_http_breaker_key( $url );
				if ( $k ) {
					delete_transient( $k );
				}
			}
		} else {
			$row['code'] = (int) wp_remote_retrieve_response_code( $res );
			$row['type'] = (string) wp_remote_retrieve_header( $res, 'content-type' );
			$body        = (string) wp_remote_retrieve_body( $res );
			$row['size'] = 'HEAD' === $method ? (int) wp_remote_retrieve_header( $res, 'content-length' ) : strlen( $body );
			if ( '/robots.txt' === $path ) {
				$row['note'] = false !== strpos( $body, '# BEGIN PSB AI' ) ? 'قوانین ربات‌های هوش مصنوعی موجود است' : 'قوانین افزوده‌شده یافت نشد (شاید robots.txt فیزیکی وجود دارد)';
			} elseif ( '/llms.txt' === $path && 200 === $row['code'] ) {
				$row['note'] = 0 === strpos( ltrim( $body ), '# ' ) ? 'ساختار llms.txt معتبر است' : 'خط اول باید با «# » شروع شود';
			}
		}
		$report[ $path ] = $row;
	}

	set_transient( 'psb_ai_check_' . get_current_user_id(), array( 'time' => time(), 'rows' => $report ), 15 * MINUTE_IN_SECONDS );
	wp_safe_redirect( psb_ai_admin_url( 'status', array( 'psb_ai_msg' => 'checked' ) ) );
	exit;
}

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

add_action(
	'admin_head',
	static function () {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, PSB_AI_PAGE ) ) {
			return;
		}
		?>
		<style>
			.psb-ai{max-width:1200px}
			.psb-ai .psb-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin:16px 0}
			.psb-ai .psb-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px}
			.psb-ai .psb-card h3{margin:0 0 8px;font-size:15px}
			.psb-ai .psb-card dl{display:grid;grid-template-columns:auto 1fr;gap:4px 12px;margin:8px 0 0}
			.psb-ai .psb-card dt{color:#646970}
			.psb-ai .psb-card dd{margin:0}
			.psb-ai .psb-url{direction:ltr;display:inline-block;word-break:break-all}
			.psb-ai .psb-badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;line-height:1.6}
			.psb-ai .psb-ok{background:#e7f6ec;color:#00691f}
			.psb-ai .psb-warn{background:#fcf3e1;color:#8a5a00}
			.psb-ai .psb-bad{background:#fbe9e9;color:#a00}
			.psb-ai .psb-off{background:#f0f0f1;color:#50575e}
			.psb-ai textarea.psb-preview{width:100%;min-height:360px;direction:ltr;text-align:left;font-family:Menlo,Consolas,monospace;font-size:12px;line-height:1.6;unicode-bidi:plaintext}
			.psb-ai .psb-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:12px 0}
			.psb-ai .psb-actions form{margin:0}
			.psb-ai .psb-chart{display:flex;align-items:flex-end;gap:3px;height:160px;padding:8px;background:#fff;border:1px solid #dcdcde;border-radius:8px;direction:ltr}
			.psb-ai .psb-bar{flex:1;min-width:6px;background:#2271b1;border-radius:3px 3px 0 0;position:relative}
			.psb-ai .psb-bar.is-zero{background:#dcdcde;height:2px!important}
			.psb-ai .psb-chart-labels{display:flex;justify-content:space-between;direction:ltr;color:#646970;font-size:11px;margin-top:4px}
			.psb-ai .psb-mini{display:inline-block;height:8px;background:#2271b1;border-radius:4px;vertical-align:middle;min-width:2px}
			.psb-ai .form-table td .description{margin-top:4px}
			.psb-ai .psb-grid2{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px 16px}
			.psb-ai table.psb-bots td,.psb-ai table.psb-bots th{vertical-align:middle}
			.psb-ai .ltr{direction:ltr;unicode-bidi:embed}
		</style>
		<?php
	}
);

function psb_ai_fmt_time( $ts ) {
	$ts = (int) $ts;
	if ( ! $ts ) {
		return '—';
	}
	return wp_date( 'Y/m/d H:i', $ts ) . ' (' . sprintf( '%s پیش', human_time_diff( $ts, time() ) ) . ')';
}

function psb_ai_admin_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'status'; // phpcs:ignore WordPress.Security.NonceVerification
	$tabs = array(
		'status'   => 'وضعیت',
		'settings' => 'تنظیمات',
		'activity' => 'فعالیت خزنده‌ها',
	);
	if ( ! isset( $tabs[ $tab ] ) ) {
		$tab = 'status';
	}

	$msgs = array(
		'saved'   => 'تنظیمات ذخیره شد. فایل‌ها در درخواست بعدی بازسازی می‌شوند.',
		'rebuilt' => 'همه فایل‌ها بازسازی شدند.',
		'checked' => 'بررسی انجام شد.',
		'cleared' => 'گزارش فعالیت پاک شد.',
	);
	$msg  = isset( $_GET['psb_ai_msg'] ) ? sanitize_key( wp_unslash( $_GET['psb_ai_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<div class="wrap psb-ai">
		<h1>هوش مصنوعی و llms.txt</h1>
		<p>دیده‌شدن سایت در موتورهای پاسخ هوش مصنوعی (ChatGPT، Claude، Perplexity، Gemini و…) — فایل‌های llms.txt، قوانین robots.txt و گزارش بازدید خزنده‌ها.</p>

		<?php if ( isset( $msgs[ $msg ] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $msgs[ $msg ] ); ?></p></div>
		<?php endif; ?>

		<?php if ( '0' === (string) get_option( 'blog_public' ) ) : ?>
			<div class="notice notice-warning"><p>
				گزینه «از موتورهای جستجو درخواست شود که این سایت را ایندکس نکنند» در <a href="<?php echo esc_url( admin_url( 'options-reading.php' ) ); ?>">تنظیمات خواندن</a> فعال است. فایل‌های llms.txt همچنان در دسترس‌اند، اما صفحات سایت noindex هستند و در نتایج جستجو و پاسخ‌های هوش مصنوعی دیده نمی‌شوند.
			</p></div>
		<?php endif; ?>

		<?php foreach ( array( 'llms.txt', 'llms-full.txt', 'ai.txt', 'robots.txt' ) as $physical ) : ?>
			<?php if ( file_exists( ABSPATH . $physical ) ) : ?>
				<div class="notice notice-error"><p>
					فایل فیزیکی <code><?php echo esc_html( $physical ); ?></code> در ریشه سایت وجود دارد و سرور آن را به‌جای نسخه پویا نمایش می‌دهد. برای فعال شدن نسخه خودکار، آن فایل را حذف یا تغییر نام دهید.
				</p></div>
			<?php endif; ?>
		<?php endforeach; ?>

		<nav class="nav-tab-wrapper">
			<?php foreach ( $tabs as $key => $label ) : ?>
				<a href="<?php echo esc_url( psb_ai_admin_url( $key ) ); ?>" class="nav-tab <?php echo $key === $tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>

		<?php
		if ( 'settings' === $tab ) {
			psb_ai_admin_tab_settings();
		} elseif ( 'activity' === $tab ) {
			psb_ai_admin_tab_activity();
		} else {
			psb_ai_admin_tab_status();
		}
		?>
	</div>
	<?php
}

function psb_ai_admin_button( $action, $label, $class = 'button' ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
		<?php wp_nonce_field( $action ); ?>
		<button type="submit" class="<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></button>
	</form>
	<?php
}

function psb_ai_admin_tab_status() {
	$state = psb_ai_state();
	$docs  = array(
		'llms'      => array( 'label' => 'llms.txt', 'path' => '/llms.txt', 'endpoint' => 'llms' ),
		'llms_full' => array( 'label' => 'llms-full.txt', 'path' => '/llms-full.txt', 'endpoint' => 'llms_full' ),
	);
	?>
	<div class="psb-actions">
		<?php psb_ai_admin_button( 'psb_ai_rebuild', 'بازسازی فوری', 'button button-primary' ); ?>
		<?php psb_ai_admin_button( 'psb_ai_check', 'بررسی (دریافت فایل‌ها از سایت)' ); ?>
		<span class="description">آخرین تغییر محتوا: <?php echo esc_html( psb_ai_fmt_time( $state['invalidated'] ) ); ?><?php echo $state['reason'] ? ' — <span class="ltr">' . esc_html( $state['reason'] ) . '</span>' : ''; ?></span>
	</div>

	<div class="psb-cards">
		<?php
		foreach ( $docs as $key => $doc ) :
			$rec     = psb_ai_doc_record( $key );
			$enabled = psb_ai_endpoint_enabled( $doc['endpoint'] );
			if ( ! $enabled ) {
				$badge = array( 'psb-off', 'غیرفعال' );
			} elseif ( ! $rec ) {
				$badge = array( 'psb-warn', 'هنوز ساخته نشده' );
			} elseif ( (int) $rec['rev'] === (int) $state['rev'] ) {
				$badge = array( 'psb-ok', 'به‌روز' );
			} else {
				$badge = array( 'psb-warn', 'در صف بازسازی' );
			}
			?>
			<div class="psb-card">
				<h3><?php echo esc_html( $doc['label'] ); ?> <span class="psb-badge <?php echo esc_attr( $badge[0] ); ?>"><?php echo esc_html( $badge[1] ); ?></span></h3>
				<a class="psb-url" href="<?php echo esc_url( home_url( $doc['path'] ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( home_url( $doc['path'] ) ); ?></a>
				<dl>
					<dt>آخرین ساخت</dt><dd><?php echo esc_html( $rec ? psb_ai_fmt_time( $rec['generated'] ) : '—' ); ?></dd>
					<dt>حجم</dt><dd class="ltr"><?php echo esc_html( $rec ? size_format( (int) $rec['size'], 1 ) : '—' ); ?></dd>
					<dt>تعداد آیتم</dt><dd><?php echo esc_html( $rec ? number_format_i18n( (int) $rec['items'] ) : '—' ); ?></dd>
					<?php if ( $rec && ! empty( $rec['truncated'] ) ) : ?>
						<dt>توجه</dt><dd><span class="psb-badge psb-warn">به دلیل سقف حجم کوتاه شده</span></dd>
					<?php endif; ?>
				</dl>
			</div>
		<?php endforeach; ?>

		<div class="psb-card">
			<h3>سایر نشانی‌ها</h3>
			<dl>
				<dt>robots.txt</dt>
				<dd><a class="psb-url" href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" rel="noopener">/robots.txt</a> <?php echo psb_ai_endpoint_enabled( 'robots' ) ? '<span class="psb-badge psb-ok">قوانین فعال</span>' : '<span class="psb-badge psb-off">غیرفعال</span>'; ?></dd>
				<dt>ai.txt</dt>
				<dd><a class="psb-url" href="<?php echo esc_url( home_url( '/ai.txt' ) ); ?>" target="_blank" rel="noopener">/ai.txt</a> <?php echo psb_ai_endpoint_enabled( 'ai_txt' ) ? '<span class="psb-badge psb-ok">فعال</span>' : '<span class="psb-badge psb-off">غیرفعال</span>'; ?></dd>
				<dt>Markdown هر صفحه</dt>
				<dd><span class="psb-url">/llms/&lt;ID&gt;.md</span> <?php echo psb_ai_endpoint_enabled( 'md' ) ? '<span class="psb-badge psb-ok">فعال</span>' : '<span class="psb-badge psb-off">غیرفعال</span>'; ?></dd>
				<dt>نقشه سایت</dt>
				<dd><a class="psb-url" href="<?php echo esc_url( psb_ai_sitemap_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( psb_ai_sitemap_url(), PHP_URL_PATH ) ); ?></a></dd>
			</dl>
		</div>
	</div>

	<?php psb_ai_admin_check_report(); ?>

	<h2>پیش‌نمایش llms.txt</h2>
	<?php
	$rec = psb_ai_endpoint_enabled( 'llms' ) ? psb_ai_get_doc( 'llms' ) : null;
	if ( $rec ) :
		?>
		<textarea class="psb-preview" readonly dir="auto"><?php echo esc_textarea( $rec['body'] ); ?></textarea>
	<?php else : ?>
		<p>نقطه پایانی llms.txt غیرفعال است.</p>
	<?php endif; ?>
	<?php
}

function psb_ai_admin_check_report() {
	$check = get_transient( 'psb_ai_check_' . get_current_user_id() );
	if ( ! is_array( $check ) || empty( $check['rows'] ) ) {
		return;
	}
	?>
	<h2>نتیجه بررسی <small>(<?php echo esc_html( psb_ai_fmt_time( $check['time'] ) ); ?>)</small></h2>
	<table class="widefat striped">
		<thead><tr><th>نشانی</th><th>وضعیت HTTP</th><th>Content-Type</th><th>حجم</th><th>زمان</th><th>توضیح</th></tr></thead>
		<tbody>
		<?php
		foreach ( $check['rows'] as $row ) :
			$code  = (int) $row['code'];
			$class = 200 === $code ? 'psb-ok' : ( $code ? 'psb-bad' : 'psb-warn' );
			?>
			<tr>
				<td class="ltr"><?php echo esc_html( $row['url'] ); ?></td>
				<td><span class="psb-badge <?php echo esc_attr( $class ); ?>"><?php echo esc_html( $code ? (string) $code : 'خطا' ); ?></span></td>
				<td class="ltr"><?php echo esc_html( $row['type'] ); ?></td>
				<td class="ltr"><?php echo esc_html( $row['size'] ? size_format( (int) $row['size'], 1 ) : '—' ); ?></td>
				<td class="ltr"><?php echo esc_html( (int) $row['ms'] . ' ms' ); ?></td>
				<td><?php echo esc_html( $row['note'] ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">درخواست از خود سرور ارسال می‌شود؛ اگر میزبان اجازه اتصال به خودش را ندهد، خطای اتصال نمایش داده می‌شود ولی فایل‌ها برای بازدیدکنندگان بیرونی سالم هستند. در این حالت نشانی‌ها را مستقیم در مرورگر باز کنید.</p>
	<?php
}

function psb_ai_admin_tab_settings() {
	$s         = psb_ai_settings();
	$endpoints = array(
		'llms'      => 'llms.txt (فهرست خلاصه سایت)',
		'llms_full' => 'llms-full.txt (محتوای کامل)',
		'md'        => 'نسخه Markdown هر صفحه: /llms/&lt;ID&gt;.md',
		'ai_txt'    => 'ai.txt (خلاصه مجوز استفاده هوش مصنوعی)',
		'robots'    => 'افزودن قوانین خزنده‌های هوش مصنوعی به robots.txt',
	);
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="psb_ai_save">
		<?php wp_nonce_field( 'psb_ai_save' ); ?>

		<h2>نقاط پایانی</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">فعال‌سازی</th>
				<td>
					<?php foreach ( $endpoints as $key => $label ) : ?>
						<label style="display:block;margin-bottom:6px">
							<input type="checkbox" name="psb_ai[endpoints][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $s['endpoints'][ $key ] ) ); ?>>
							<?php echo wp_kses( $label, array() ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
		</table>

		<h2>محتوا</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="psb-ai-name">نام سایت (عنوان H1)</label></th>
				<td><input id="psb-ai-name" type="text" class="regular-text" name="psb_ai[site_name]" value="<?php echo esc_attr( $s['site_name'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="psb-ai-summary">خلاصه (بلوک نقل‌قول)</label></th>
				<td>
					<textarea id="psb-ai-summary" class="large-text" rows="3" name="psb_ai[summary]"><?php echo esc_textarea( $s['summary'] ); ?></textarea>
					<p class="description">یک یا دو جمله دقیق درباره شرکت؛ مدل‌های زبانی معمولاً همین جمله را نقل می‌کنند. خالی = توضیح کوتاه سایت (تگ‌لاین).</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="psb-ai-about">پاراگراف معرفی</label></th>
				<td><textarea id="psb-ai-about" class="large-text" rows="5" name="psb_ai[about]"><?php echo esc_textarea( $s['about'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row">اطلاعات تماس</th>
				<td><label><input type="checkbox" name="psb_ai[contact]" value="1" <?php checked( ! empty( $s['contact'] ) ); ?>> درج نشانی، تلفن‌ها (موارد نمایش‌داده‌شده در فوتر) و ایمیل از تنظیمات سایت</label></td>
			</tr>
			<tr>
				<th scope="row">بخش‌ها</th>
				<td>
					<div class="psb-grid2">
						<?php foreach ( psb_ai_sections() as $key => $section ) : ?>
							<label>
								<input type="checkbox" name="psb_ai[sections][<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $s['sections'][ $key ] ) ); ?>>
								<?php echo esc_html( $section['title'] ); ?>
								<?php if ( ! post_type_exists( $section['post_type'] ) ) : ?>
									<span class="psb-badge psb-off">نوع نوشته موجود نیست</span>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="psb-ai-max">حداکثر تعداد مقالات</label></th>
				<td><input id="psb-ai-max" type="number" min="0" max="200" class="small-text" name="psb_ai[max_posts]" value="<?php echo esc_attr( (string) $s['max_posts'] ); ?>"> <span class="description">جدیدترین مقالات؛ مقالات سنجاق‌شده (sticky) اول می‌آیند.</span></td>
			</tr>
			<tr>
				<th scope="row"><label for="psb-ai-pages">شناسه صفحات اصلی</label></th>
				<td>
					<input id="psb-ai-pages" type="text" class="regular-text ltr" name="psb_ai[key_pages]" value="<?php echo esc_attr( $s['key_pages'] ); ?>">
					<p class="description">با کاما جدا کنید، به ترتیب نمایش (مثلاً درباره ما، تماس با ما). صفحه اصلی سایت خودکار افزوده می‌شود.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="psb-ai-exclude">شناسه‌های مستثنا</label></th>
				<td>
					<input id="psb-ai-exclude" type="text" class="regular-text ltr" name="psb_ai[exclude_ids]" value="<?php echo esc_attr( $s['exclude_ids'] ); ?>">
					<p class="description">این نوشته‌ها در هیچ فایلی نمی‌آیند. نوشته‌های noindex در Rank Math، رمزدار و منتشرنشده خودکار حذف می‌شوند.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="psb-ai-cap">سقف حجم llms-full.txt</label></th>
				<td><input id="psb-ai-cap" type="number" min="100" max="5000" class="small-text" name="psb_ai[full_max_kb]" value="<?php echo esc_attr( (string) $s['full_max_kb'] ); ?>"> کیلوبایت</td>
			</tr>
		</table>

		<h2>دسترسی خزنده‌های هوش مصنوعی (robots.txt)</h2>
		<p class="description">ربات‌های «جستجو» و «درخواست کاربر» برای دیده‌شدن در پاسخ‌های ChatGPT، Claude و Perplexity لازم‌اند. ربات‌های «آموزش مدل» محتوا را برای آموزش مدل‌ها جمع می‌کنند؛ مسدود کردن آن‌ها روی نمایش در جستجوی هوش مصنوعی اثر مستقیمی ندارد. ربات‌های مجاز همان محدودیت‌های عمومی robots.txt را رعایت می‌کنند.</p>
		<table class="widefat striped psb-bots">
			<thead><tr><th>ربات</th><th>شرکت</th><th>نوع</th><th>دسترسی</th></tr></thead>
			<tbody>
			<?php foreach ( psb_ai_bots() as $name => $bot ) : ?>
				<?php $v = $s['bots'][ $name ] ?? 'allow'; ?>
				<tr>
					<td class="ltr"><code><?php echo esc_html( $name ); ?></code></td>
					<td><?php echo esc_html( $bot['vendor'] ); ?></td>
					<td><?php echo esc_html( psb_ai_kind_label( $bot['kind'] ) ); ?></td>
					<td>
						<label><input type="radio" name="psb_ai[bots][<?php echo esc_attr( $name ); ?>]" value="allow" <?php checked( 'allow', $v ); ?>> مجاز</label>
						&nbsp;
						<label><input type="radio" name="psb_ai[bots][<?php echo esc_attr( $name ); ?>]" value="disallow" <?php checked( 'disallow', $v ); ?>> مسدود</label>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">Googlebot و Bingbot (جستجوی معمولی) دست‌نخورده می‌مانند. Google-Extended و Applebot-Extended فقط توکن robots.txt هستند و در گزارش بازدید دیده نمی‌شوند.</p>

		<h2>گزارش فعالیت</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">ثبت بازدید ربات‌ها</th>
				<td><label><input type="checkbox" name="psb_ai[log_enabled]" value="1" <?php checked( ! empty( $s['log_enabled'] ) ); ?>> ثبت تعداد بازدید روزانه هر ربات هوش مصنوعی (حداکثر یک نوشتن در دقیقه برای هر ربات)</label></td>
			</tr>
		</table>

		<?php submit_button( 'ذخیره تنظیمات' ); ?>
	</form>
	<?php
}

function psb_ai_admin_tab_activity() {
	$log   = psb_ai_log_get();
	$bots  = psb_ai_bots();
	$rules = psb_ai_setting( 'bots', array() );
	$today = wp_date( 'Y-m-d' );

	// Last 30 days, oldest → newest.
	$series = array();
	for ( $i = PSB_AI_LOG_DAYS - 1; $i >= 0; $i-- ) {
		$day            = wp_date( 'Y-m-d', time() - $i * DAY_IN_SECONDS );
		$series[ $day ] = isset( $log['days'][ $day ] ) && is_array( $log['days'][ $day ] ) ? array_sum( array_map( 'intval', $log['days'][ $day ] ) ) : 0;
	}
	$max = max( 1, max( $series ) );

	$per_bot = array();
	foreach ( $log['days'] as $day => $counts ) {
		if ( ! is_array( $counts ) ) {
			continue;
		}
		foreach ( $counts as $bot => $n ) {
			$per_bot[ $bot ] = ( $per_bot[ $bot ] ?? 0 ) + (int) $n;
		}
	}
	arsort( $per_bot );
	$bot_max = $per_bot ? max( 1, max( $per_bot ) ) : 1;
	?>
	<h2>بازدید روزانه خزنده‌های هوش مصنوعی (۳۰ روز اخیر)</h2>
	<div class="psb-chart" role="img" aria-label="نمودار بازدید روزانه">
		<?php foreach ( $series as $day => $n ) : ?>
			<div class="psb-bar<?php echo $n ? '' : ' is-zero'; ?>" style="height:<?php echo esc_attr( (string) max( 2, round( $n / $max * 100 ) ) ); ?>%" title="<?php echo esc_attr( $day . ': ' . number_format_i18n( $n ) ); ?>"></div>
		<?php endforeach; ?>
	</div>
	<div class="psb-chart-labels"><span><?php echo esc_html( array_key_first( $series ) ); ?></span><span><?php echo esc_html( $today ); ?></span></div>
	<?php if ( ! empty( $log['sampled'] ) ) : ?>
		<p class="description">کش آبجکت پایدار روی سرور فعال نیست؛ برای کاهش بار پایگاه داده حداکثر یک بازدید در دقیقه برای هر ربات ثبت می‌شود، بنابراین اعداد «نمونه‌برداری‌شده» و کمتر از واقع هستند.</p>
	<?php endif; ?>

	<h2>ربات‌ها</h2>
	<table class="widefat striped">
		<thead><tr><th>ربات</th><th>شرکت</th><th>نوع</th><th>robots.txt</th><th>۳۰ روز</th><th>امروز</th><th>آخرین بازدید</th><th>آخرین مسیر</th></tr></thead>
		<tbody>
		<?php
		$order = array_unique( array_merge( array_keys( $per_bot ), array_keys( $bots ) ) );
		foreach ( $order as $name ) :
			if ( ! isset( $bots[ $name ] ) || empty( $bots[ $name ]['ua'] ) ) {
				continue;
			}
			$bot   = $bots[ $name ];
			$entry = $log['bots'][ $name ] ?? array();
			$total = (int) ( $per_bot[ $name ] ?? 0 );
			$tdy   = (int) ( $log['days'][ $today ][ $name ] ?? 0 );
			$rule  = 'disallow' === ( $rules[ $name ] ?? 'allow' ) ? array( 'psb-bad', 'مسدود' ) : array( 'psb-ok', 'مجاز' );
			?>
			<tr>
				<td class="ltr"><code><?php echo esc_html( $name ); ?></code></td>
				<td><?php echo esc_html( $bot['vendor'] ); ?></td>
				<td><?php echo esc_html( psb_ai_kind_label( $bot['kind'] ) ); ?></td>
				<td><span class="psb-badge <?php echo esc_attr( $rule[0] ); ?>"><?php echo esc_html( $rule[1] ); ?></span></td>
				<td><span class="psb-mini" style="width:<?php echo esc_attr( (string) round( $total / $bot_max * 80 ) ); ?>px"></span> <?php echo esc_html( number_format_i18n( $total ) ); ?></td>
				<td><?php echo esc_html( number_format_i18n( $tdy ) ); ?></td>
				<td><?php echo esc_html( psb_ai_fmt_time( $entry['last'] ?? 0 ) ); ?></td>
				<td class="ltr"><?php echo esc_html( $entry['path'] ?? '—' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<div class="psb-actions">
		<?php psb_ai_admin_button( 'psb_ai_clear_log', 'پاک کردن گزارش', 'button button-link-delete' ); ?>
	</div>
	<?php
}
