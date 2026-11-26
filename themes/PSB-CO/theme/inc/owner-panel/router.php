<?php
/**
 * Owner panel — router (parse_request, no rewrite rules → nothing to flush on deploy).
 *
 *   /panel/                       app shell (any /panel/<screen>/… renders the same shell) or the login screen
 *   POST /panel/                  login (wp_signon, "remember me" on by default)
 *   /panel/manifest.webmanifest   separate installable app «PSB Panel» (scope /panel/) — public, no data
 *   /panel/sw.js                  panel service worker (static assets only; API data is never cached) — public
 *   /panel/nonce                  fresh wp_rest nonce for long-lived tabs / the installed app
 *   /panel/logout?_wpnonce=…      sign out of this device
 *   /panel/file/{id}/{n}?_wpnonce RFQ attachment download inside the app scope
 *
 * Never cached (DONOTCACHEPAGE + Cache-Control: no-store), never indexed, never counted as a page view
 * (exits before template_redirect), not a post → never in sitemaps / llms.txt.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_action( 'parse_request', 'psb_panel_route', 2 );

/**
 * @param WP $wp Current WordPress environment instance.
 */
function psb_panel_route( $wp ) {
	$req = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	if ( PSB_PANEL_SLUG !== $req && 0 !== strpos( $req, PSB_PANEL_SLUG . '/' ) ) {
		return;
	}
	$rest = trim( substr( $req, strlen( PSB_PANEL_SLUG ) ), '/' );
	$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- compared only.
	if ( '' === $rest && '/' !== substr( $path, -1 ) && 'GET' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		wp_safe_redirect( psb_panel_base(), 301 );
		exit;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
	header_remove( 'Link' );
	header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
	header( 'X-Content-Type-Options: nosniff' );

	if ( 'manifest.webmanifest' === $rest ) {
		status_header( 200 );
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );
		echo wp_json_encode( psb_panel_manifest(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	if ( 'sw.js' === $rest ) {
		status_header( 200 );
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: ' . psb_panel_base() );
		header( 'Cache-Control: no-cache, max-age=0, must-revalidate' );
		$src = get_template_directory() . '/pwa/panel/panel-sw.js';
		echo 'self.__PANEL=' . wp_json_encode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'v'      => psb_panel_asset_version(),
				'scope'  => psb_panel_base(),
				'assets' => array(
					(string) wp_parse_url( get_template_directory_uri() . '/pwa/', PHP_URL_PATH ),
					(string) wp_parse_url( get_template_directory_uri() . '/fonts/', PHP_URL_PATH ),
				),
			),
			JSON_UNESCAPED_SLASHES
		) . ";\n";
		if ( file_exists( $src ) ) {
			readfile( $src ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		exit;
	}

	// Everything below is private.
	nocache_headers();
	header( 'Cache-Control: no-store, no-cache, private, max-age=0', true );
	header( 'Referrer-Policy: same-origin' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'X-PSB-SW: no-store' );

	if ( 'nonce' === $rest ) {
		header( 'Content-Type: application/json; charset=utf-8' );
		if ( ! psb_panel_can() ) {
			status_header( 401 );
			echo '{"ok":false}';
			exit;
		}
		status_header( 200 );
		echo wp_json_encode( array( 'ok' => true, 'nonce' => wp_create_nonce( 'wp_rest' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	if ( 'logout' === $rest ) {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( is_user_logged_in() && wp_verify_nonce( $nonce, 'psb_panel_logout' ) ) {
			wp_logout();
		}
		wp_safe_redirect( psb_panel_base() );
		exit;
	}

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
	$error  = '';
	if ( 'POST' === $method && isset( $_POST['psb_panel_action'] ) && 'login' === $_POST['psb_panel_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification -- verified in psb_panel_handle_login().
		$error = psb_panel_handle_login(); // Redirects on success.
	}

	if ( ! is_user_logged_in() ) {
		status_header( $error ? 401 : 200 );
		header( 'Content-Type: text/html; charset=utf-8' );
		psb_panel_login_screen( $error, psb_panel_base() . ( $rest ? $rest . '/' : '' ) );
		exit;
	}

	if ( ! psb_panel_can() ) {
		status_header( 403 );
		header( 'Content-Type: text/html; charset=utf-8' );
		psb_panel_no_access();
		exit;
	}

	if ( preg_match( '#^file/(\d+)/(\d+)$#', $rest, $m ) ) {
		psb_panel_rfq_send_file( (int) $m[1], (int) $m[2] );
	}

	psb_panel_maybe_refresh_cookie();
	status_header( 200 );
	header( 'Content-Type: text/html; charset=utf-8' );
	psb_panel_shell();
	exit;
}

function psb_panel_manifest() {
	$base = psb_panel_base();
	$img  = get_template_directory_uri() . '/pwa/icons/';
	return array(
		'id'               => $base,
		'name'             => 'PSB Panel — پنل مدیریت پارس سوئیچ بورد',
		'short_name'       => 'PSB Panel',
		'description'      => 'پنل موبایل مدیران: درخواست‌های استعلام، محتوا، فرم‌ها و وضعیت سایت',
		'lang'             => 'fa',
		'dir'              => 'rtl',
		'start_url'        => $base,
		'scope'            => $base,
		'display'          => 'standalone',
		'display_override' => array( 'standalone' ),
		'orientation'      => 'portrait',
		'background_color' => '#0B1220',
		'theme_color'      => '#0B1220',
		'categories'       => array( 'business', 'productivity' ),
		'icons'            => array(
			array( 'src' => $img . 'panel-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
			array( 'src' => $img . 'panel-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
			array( 'src' => $img . 'panel-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
		),
		'shortcuts'        => array(
			array( 'name' => 'درخواست‌ها', 'url' => $base . 'requests/' ),
			array( 'name' => 'محتوا', 'url' => $base . 'content/' ),
		),
	);
}

/** <head> shared by the shell, the login screen and the no-access screen. */
function psb_panel_head( $title ) {
	$s    = psb_panel_settings();
	$font = get_template_directory_uri() . '/fonts/Peyda/fonts/woff2/PeydaWebFaNum-';
	?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0B1220">
<meta name="color-scheme" content="light dark">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="PSB Panel">
<meta name="referrer" content="same-origin">
<title><?php echo esc_html( $title ); ?></title>
<link rel="manifest" href="<?php echo esc_url( psb_panel_url( 'manifest.webmanifest' ) ); ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url( get_template_directory_uri() . '/pwa/icons/panel-192.png' ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( get_template_directory_uri() . '/pwa/icons/panel-apple-touch-icon.png' ); ?>">
<link rel="preload" href="<?php echo esc_url( $font . 'Regular.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?php echo esc_url( $font . 'Bold.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
<style>@font-face{font-family:Peyda;src:url(<?php echo esc_url( $font . 'Regular.woff2' ); ?>) format('woff2');font-weight:400;font-display:swap}@font-face{font-family:Peyda;src:url(<?php echo esc_url( $font . 'Bold.woff2' ); ?>) format('woff2');font-weight:700;font-display:swap}:root{--accent:<?php echo esc_attr( $s['accent'] ); ?>}</style>
<link rel="stylesheet" href="<?php echo esc_url( psb_panel_asset( 'panel/panel.css' ) ); ?>">
	<?php
}

function psb_panel_login_screen( $error, $to ) {
	psb_panel_head( 'ورود به پنل مدیریت — پارس سوئیچ بورد' );
	$otp = psb_panel_otp_available();
	$to  = 0 === strpos( $to, psb_panel_base() ) ? $to : psb_panel_base();
	?>
</head>
<body class="op-auth">
<main class="op-login">
	<img class="op-login__logo" src="<?php echo esc_url( get_template_directory_uri() . '/pwa/icons/panel-192.png' ); ?>" width="72" height="72" alt="">
	<h1>پنل مدیریت پارس سوئیچ بورد</h1>
	<p class="op-muted">برای مدیران سایت. پس از ورود، روی این گوشی تا مدت طولانی وارد می‌مانید.</p>
	<?php if ( $error ) : ?>
		<p class="op-alert" role="alert"><?php echo esc_html( $error ); ?>
			<?php if ( function_exists( 'psb_panel_login_needs_wp_login' ) && psb_panel_login_needs_wp_login() ) : ?>
				<br><a class="op-btn" href="<?php echo esc_url( wp_login_url( psb_panel_base() ) ); ?>">ورود از صفحه ورود وردپرس</a>
			<?php endif; ?>
		</p>
	<?php endif; ?>
	<form class="op-card op-form" method="post" action="<?php echo esc_url( psb_panel_base() ); ?>" id="op-pass">
		<input type="hidden" name="psb_panel_action" value="login">
		<input type="hidden" name="to" value="<?php echo esc_attr( $to ); ?>">
		<?php wp_nonce_field( 'psb_panel_login', 'psb_panel_nonce', false ); ?>
		<label>نام کاربری یا ایمیل
			<input name="log" type="text" required autocomplete="username" autocapitalize="off" spellcheck="false" dir="ltr" value="<?php echo isset( $_POST['log'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['log'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification ?>">
		</label>
		<label>رمز عبور
			<input name="pwd" type="password" required autocomplete="current-password" dir="ltr">
		</label>
		<label class="op-check"><input type="checkbox" name="rememberme" value="1" checked> مرا به خاطر بسپار</label>
		<button class="op-btn op-btn--primary" type="submit">ورود</button>
	</form>
	<?php if ( $otp ) : ?>
		<details class="op-card op-otp">
			<summary>ورود با کد پیامکی</summary>
			<form class="op-form" id="op-otp" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'my-nonce' ) ); ?>">
				<label>شماره موبایل
					<input name="phone" type="tel" inputmode="numeric" autocomplete="tel" dir="ltr" placeholder="09xxxxxxxxx" required>
				</label>
				<label class="op-otp__code" hidden>کد تأیید
					<input name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" dir="ltr" maxlength="6">
				</label>
				<p class="op-muted op-otp__msg" role="status"></p>
				<button class="op-btn" type="submit">ارسال کد</button>
			</form>
		</details>
		<script>
		(function(){var f=document.getElementById('op-otp');if(!f)return;var step=1,msg=f.querySelector('.op-otp__msg'),btn=f.querySelector('button'),code=f.querySelector('.op-otp__code');
		function fa2en(s){return String(s).replace(/[۰-۹]/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)}).replace(/[٠-٩]/g,function(d){return '٠١٢٣٤٥٦٧٨٩'.indexOf(d)}).replace(/\D/g,'')}
		function freshNonce(){return fetch(f.dataset.ajax+'?action=psb_my_nonce',{credentials:'same-origin',cache:'no-store'}).then(function(r){return r.json()}).then(function(j){return j&&j.success&&j.data&&j.data.nonce?j.data.nonce:f.dataset.nonce}).catch(function(){return f.dataset.nonce})}
		f.addEventListener('submit',function(e){e.preventDefault();var b=new FormData();b.append('phone',fa2en(f.phone.value));
		if(step===1){b.append('action','kavenegar_send_otp')}else{b.append('action','kavenegar_verify_otp');b.append('otp',fa2en(f.otp.value))}
		btn.disabled=true;msg.textContent='…';
		freshNonce().then(function(n){b.append('security',n);return fetch(f.dataset.ajax,{method:'POST',body:b,credentials:'same-origin'})}).then(function(r){return r.json()}).then(function(j){btn.disabled=false;
		if(!j||!j.success){msg.textContent=(j&&j.data&&j.data.message)||'خطا؛ دوباره تلاش کنید.';return}
		if(step===1){step=2;code.hidden=false;f.otp.focus();btn.textContent='ورود';msg.textContent='کد برای شما پیامک شد.';return}
		if(j.data&&j.data.action==='logged_in'){location.reload()}else{msg.textContent='این شماره به حساب کاربری مدیری متصل نیست.'}
		}).catch(function(){btn.disabled=false;msg.textContent='ارتباط برقرار نشد.'})});})();
		</script>
	<?php endif; ?>
	<p class="op-muted op-small"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به سایت</a></p>
</main>
</body>
</html>
	<?php
}

function psb_panel_no_access() {
	psb_panel_head( 'دسترسی ندارید — پنل مدیریت' );
	?>
</head>
<body class="op-auth">
<main class="op-login">
	<h1>دسترسی ندارید</h1>
	<p class="op-muted">حساب «<?php echo esc_html( wp_get_current_user()->display_name ); ?>» اجازه استفاده از پنل مدیریت را ندارد.</p>
	<a class="op-btn" href="<?php echo esc_url( wp_nonce_url( psb_panel_url( 'logout' ), 'psb_panel_logout' ) ); ?>">خروج و ورود با حساب دیگر</a>
	<p class="op-muted op-small"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به سایت</a></p>
</main>
</body>
</html>
	<?php
}

/** Data the app needs before its first API call. */
function psb_panel_boot() {
	$u    = wp_get_current_user();
	$ai   = defined( 'PSB_AI_PAGE' ) ? admin_url( 'admin.php?page=' . PSB_AI_PAGE ) : ( function_exists( 'psb_ai_setting' ) ? admin_url( 'admin.php?page=psb-ai-visibility' ) : '' );
	$rfq  = psb_panel_rfq_active() ? psb_panel_rfq_admin_urls() : array();
	$sess = psb_panel_session_info();
	return array(
		'base'     => psb_panel_base(),
		'rest'     => esc_url_raw( rest_url( 'psb-panel/v1/' ) ),
		'nonce'    => wp_create_nonce( 'wp_rest' ),
		'nonceUrl' => psb_panel_url( 'nonce' ),
		'sw'       => psb_panel_url( 'sw.js' ),
		'site'     => home_url( '/' ),
		'admin'    => admin_url(),
		'logout'   => wp_nonce_url( psb_panel_url( 'logout' ), 'psb_panel_logout' ),
		'user'     => $u->display_name,
		'session'  => $sess,
		'days'     => psb_panel_settings()['session_days'],
		'caps'     => array(
			'admin'    => current_user_can( 'manage_options' ),
			'rfq'      => psb_panel_rfq_can(),
			'comments' => current_user_can( 'moderate_comments' ),
			'forms'    => class_exists( 'GFAPI' ) && ( current_user_can( 'gravityforms_view_entries' ) || current_user_can( 'manage_options' ) ),
			'cache'    => psb_panel_cache_available() && psb_panel_can_clear_cache(),
		),
		'links'    => array_filter(
			array(
				'rfqList'     => $rfq['list'] ?? '',
				'rfqChannels' => current_user_can( 'manage_options' ) ? ( $rfq['channels'] ?? '' ) : '',
				'rfqSettings' => current_user_can( 'manage_options' ) ? ( $rfq['settings'] ?? '' ) : '',
				'ai'          => current_user_can( 'manage_options' ) ? $ai : '',
				'panel'       => current_user_can( 'manage_options' ) ? admin_url( 'options-general.php?page=' . PSB_SETTINGS_PAGE ) : '',
				'forms'       => class_exists( 'GFAPI' ) ? admin_url( 'admin.php?page=gf_entries' ) : '',
				'comments'    => admin_url( 'edit-comments.php' ),
				'posts'       => admin_url( 'edit.php' ),
				'newPost'     => admin_url( 'post-new.php' ),
			)
		),
	);
}

function psb_panel_shell() {
	psb_panel_head( 'پنل مدیریت — پارس سوئیچ بورد' );
	?>
<script id="op-boot" type="application/json"><?php echo wp_json_encode( psb_panel_boot(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
<script src="<?php echo esc_url( psb_panel_asset( 'panel/panel.js' ) ); ?>" defer></script>
</head>
<body>
<div id="op-app" class="op-app" aria-busy="true">
	<div class="op-splash" role="status"><span class="op-spinner" aria-hidden="true"></span><span>در حال بارگذاری پنل…</span></div>
</div>
<noscript><p style="padding:24px">برای استفاده از پنل، جاوااسکریپت مرورگر را فعال کنید. <a href="<?php echo esc_url( admin_url() ); ?>">پیشخوان وردپرس</a></p></noscript>
</body>
</html>
	<?php
}
