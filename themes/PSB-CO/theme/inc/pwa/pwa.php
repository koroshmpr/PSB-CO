<?php
/**
 * PWA — makes psb.co.ir installable and app-like («Add to Home Screen»).
 *
 *  /manifest.webmanifest   web app manifest (dynamic, no physical file)
 *  /sw.js                  service worker, scope "/" (theme/pwa/sw.js + injected config; kill switch in settings)
 *  /pwa-offline/           branded offline page (precached by the service worker)
 *  POST psb-pwa/v1/stat    install-funnel counters (prompt / accepted / dismissed / installed / ios)
 *
 * Paths are matched on `parse_request` (same approach as inc/ai-visibility): no rewrite rules, nothing to flush.
 * Everything printed into public HTML is identical for every visitor, so it is safe behind the page cache.
 *
 * Files:
 *  inc/pwa/helpers.php   settings, version, manifest, service-worker config
 *  inc/pwa/routes.php    virtual endpoints + "do not store in the SW" header for private responses
 *  inc/pwa/head.php      psb_pwa_head_tags() + deferred registration / install-prompt script
 *  inc/pwa/admin.php     settings section (Settings → پنل مدیریت و اپ)
 *  theme/pwa/sw.js, theme/pwa/pwa.js, theme/pwa/icons/*
 *
 * Load from functions.php:
 *   require get_template_directory() . '/inc/pwa/pwa.php';
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'PSB_PWA_VERSION' ) ) {
	return;
}
define( 'PSB_PWA_VERSION', '1.0.0' );
define( 'PSB_PWA_DIR', __DIR__ );

require_once PSB_PWA_DIR . '/helpers.php';
require_once PSB_PWA_DIR . '/routes.php';
require_once PSB_PWA_DIR . '/head.php';

if ( is_admin() ) {
	require_once PSB_PWA_DIR . '/admin.php';
}
