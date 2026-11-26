<?php
/**
 * PWA — virtual endpoints (matched on parse_request, before the main query; nothing to flush).
 *
 * The host hands unknown paths to index.php (verified: /sw.js and /manifest.webmanifest are WordPress 404s today),
 * so these work as long as no physical file with the same name is uploaded to the web root.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_action( 'parse_request', 'psb_pwa_maybe_serve', 2 );

/**
 * @param WP $wp Current WordPress environment instance.
 */
function psb_pwa_maybe_serve( $wp ) {
	$path = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	if ( '' === $path || strlen( $path ) > 24 ) {
		return;
	}
	switch ( $path ) {
		case 'manifest.webmanifest':
			if ( ! psb_pwa_enabled() ) {
				return; // Normal 404.
			}
			psb_pwa_send_headers( 'application/manifest+json', 'public, max-age=3600' );
			echo wp_json_encode( psb_pwa_manifest(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;

		case 'sw.js':
			psb_pwa_send_headers( 'application/javascript', 'no-cache, max-age=0, must-revalidate' );
			header( 'Service-Worker-Allowed: ' . psb_pwa_home_path() );
			echo psb_pwa_sw_source(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JavaScript.
			exit;

		case 'pwa-offline':
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true ); // Only the service worker keeps a copy.
			}
			psb_pwa_send_headers( 'text/html', 'public, max-age=3600' );
			header( 'X-Robots-Tag: noindex, nofollow', true );
			psb_pwa_offline_page();
			exit;
	}
}

function psb_pwa_send_headers( $type, $cache_control ) {
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
	header_remove( 'Expires' );
	header_remove( 'Pragma' );
	header_remove( 'Link' );
	status_header( 200 );
	header( 'Content-Type: ' . $type . '; charset=utf-8' );
	header( 'Cache-Control: ' . $cache_control );
	header( 'X-Content-Type-Options: nosniff' );
}

/** Service-worker script: config + theme/pwa/sw.js, or a self-destructing worker when the PWA is switched off. */
function psb_pwa_sw_source() {
	if ( ! psb_pwa_enabled() ) {
		return "/* PSB PWA disabled: remove caches and unregister. */\n"
			. "self.addEventListener('install',()=>self.skipWaiting());\n"
			. "self.addEventListener('activate',e=>e.waitUntil(caches.keys().then(k=>Promise.all(k.filter(n=>n.indexOf('psb-')===0&&n.indexOf('psb-panel-')!==0).map(n=>caches.delete(n)))).then(()=>self.registration.unregister()).then(()=>self.clients.matchAll()).then(cs=>cs.forEach(c=>c.navigate&&c.navigate(c.url)))));\n";
	}
	$cfg = array(
		'v'        => psb_pwa_version(),
		'home'     => psb_pwa_home_path(),
		'offline'  => psb_pwa_home_path() . 'pwa-offline/',
		'precache' => psb_pwa_precache(),
		'bypass'   => psb_pwa_bypass_paths(),
		'params'   => psb_pwa_bypass_params(),
		'static'   => array(
			(string) wp_parse_url( content_url( '/' ), PHP_URL_PATH ),
			(string) wp_parse_url( includes_url( '/' ), PHP_URL_PATH ),
		),
		'timeout'  => (int) apply_filters( 'psb_pwa_nav_timeout', 3500 ),
		'limits'   => array( 'pages' => 40, 'static' => 80, 'img' => 120 ),
	);
	$src  = get_template_directory() . '/pwa/sw.js';
	$body = file_exists( $src ) ? (string) file_get_contents( $src ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	return 'self.__PSB=' . wp_json_encode( $cfg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ";\n" . $body;
}

/**
 * Responses that are private to the visitor carry `X-PSB-SW: no-store`; the service worker never stores those
 * (logged-in users, previews, search, anything already marked DONOTCACHEPAGE for the page cache).
 */
add_action(
	'template_redirect',
	static function () {
		if ( headers_sent() ) {
			return;
		}
		$private = is_user_logged_in()
			|| is_preview()
			|| is_search()
			|| is_customize_preview()
			|| ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE )
			|| ( function_exists( 'is_account_page' ) && ( is_account_page() || is_cart() || is_checkout() ) );
		if ( $private ) {
			header( 'X-PSB-SW: no-store' );
		}
	},
	100
);

/** Install-funnel counters (one tiny non-autoloaded option; rate-limited per IP). */
add_action(
	'rest_api_init',
	static function () {
		register_rest_route(
			'psb-pwa/v1',
			'/stat',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'args'                => array(
					'e' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'prompt', 'accepted', 'dismissed', 'installed', 'ios', 'standalone' ),
					),
				),
				'callback'            => static function ( WP_REST_Request $req ) {
					$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
					$key = 'psb_pwa_rl_' . substr( md5( $ip . wp_salt( 'nonce' ) ), 0, 16 );
					$n   = (int) get_transient( $key );
					if ( $n >= 20 ) {
						return new WP_REST_Response( array( 'ok' => false ), 429 );
					}
					set_transient( $key, $n + 1, HOUR_IN_SECONDS );
					$ev       = (string) $req->get_param( 'e' );
					$s        = psb_pwa_stats();
					$s[ $ev ] = (int) $s[ $ev ] + 1;
					update_option( PSB_PWA_STATS, $s, false );
					return array( 'ok' => true );
				},
			)
		);
	}
);

/** Branded, self-contained offline page (Persian, RTL, phone number, retry). */
function psb_pwa_offline_page() {
	$phone = psb_pwa_offline_phone();
	$font  = get_template_directory_uri() . '/fonts/Peyda/fonts/woff2/PeydaWebFaNum-';
	$fa    = function_exists( 'psb_fa_num' ) ? 'psb_fa_num' : 'strval';
	$fmt   = function_exists( 'psb_format_phone' ) ? 'psb_format_phone' : 'strval';
	?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="<?php echo esc_attr( PSB_PWA_DARK ); ?>">
<title>اتصال اینترنت برقرار نیست — پارس سوئیچ بورد</title>
<style>
@font-face{font-family:Peyda;src:url(<?php echo esc_url( $font . 'Regular.woff2' ); ?>) format('woff2');font-weight:400;font-display:swap}
@font-face{font-family:Peyda;src:url(<?php echo esc_url( $font . 'Bold.woff2' ); ?>) format('woff2');font-weight:700;font-display:swap}
*{box-sizing:border-box;margin:0}
html{background:<?php echo esc_attr( PSB_PWA_DARK ); ?>}
body{min-height:100vh;min-height:100svh;display:grid;place-items:center;padding:max(24px,env(safe-area-inset-top)) 20px max(24px,env(safe-area-inset-bottom));background:radial-gradient(ellipse at 50% -10%,rgba(47,98,227,.35),transparent 60%),<?php echo esc_attr( PSB_PWA_DARK ); ?>;color:#e2e8f0;font-family:Peyda,Tahoma,sans-serif;text-align:center;line-height:1.9}
main{max-width:26rem;display:flex;flex-direction:column;gap:16px;align-items:center}
.logo{width:76px;height:76px;border-radius:20px;background:#fff;display:grid;place-items:center;box-shadow:0 10px 40px -10px rgba(47,98,227,.6)}
.logo img{width:76px;height:76px;border-radius:20px}
.line{width:56px;height:3px;border-radius:3px;background:#F59E0B}
h1{font-size:1.35rem;font-weight:700;color:#fff}
p{color:#cbd5e1;font-size:.95rem}
.row{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:4px}
a,button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:48px;padding:0 24px;border-radius:12px;font:inherit;font-weight:700;text-decoration:none;border:0;cursor:pointer}
.cta{background:#F59E0B;color:<?php echo esc_attr( PSB_PWA_DARK ); ?>}
.ghost{background:rgba(255,255,255,.06);color:#e2e8f0;border:1px solid rgba(255,255,255,.16)}
.tel{direction:ltr;unicode-bidi:isolate}
small{color:#94a3b8;font-size:.8rem}
a:focus-visible,button:focus-visible{outline:3px solid #8BAEF8;outline-offset:3px}
</style>
</head>
<body>
<main>
	<span class="logo"><img src="<?php echo esc_url( psb_pwa_asset( 'icons/icon-192.png' ) ); ?>" width="76" height="76" alt="پارس سوئیچ بورد"></span>
	<span class="line" aria-hidden="true"></span>
	<h1>اتصال اینترنت برقرار نیست</h1>
	<p>این صفحه بدون اینترنت در دسترس نیست. صفحه‌هایی که قبلاً باز کرده‌اید آفلاین هم نمایش داده می‌شوند. به‌محض برقراری اتصال، دوباره تلاش کنید.</p>
	<div class="row">
		<button type="button" class="cta" onclick="location.reload()">تلاش دوباره</button>
		<a class="ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a>
	</div>
	<?php if ( $phone ) : ?>
		<p>برای استعلام قیمت یا مشاوره فنی تماس بگیرید:<br><a class="ghost tel" href="<?php echo esc_url( $phone['href'], array( 'tel' ) ); ?>"><?php echo esc_html( call_user_func( $fa, call_user_func( $fmt, $phone['number'] ) ) ); ?></a></p>
	<?php endif; ?>
	<small>پارس سوئیچ بورد</small>
</main>
<script>addEventListener('online',function(){location.reload()});</script>
</body>
</html>
	<?php
}
