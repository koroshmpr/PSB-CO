<?php
/**
 * Owner panel — mobile-first, installable control panel for managers at /panel/ (front-end route, not wp-admin).
 * Same architecture as the kmpr.ir owner panel (inc/owner-panel/* there), adapted to PSB:
 *
 *  inc/owner-panel/helpers.php      settings, capability, URLs, session helpers
 *  inc/owner-panel/auth.php         long-lived logins (auth_cookie_expiration), silent cookie refresh, login/logout
 *  inc/owner-panel/rfq-adapter.php  every RFQ (psb_rfq) meta key / function the panel uses, in one place
 *  inc/owner-panel/router.php       /panel/* shell + login screen, manifest, service worker, nonce, logout
 *  inc/owner-panel/rest.php         REST API psb-panel/v1/* (cookie auth + wp_rest nonce + capability checks)
 *  inc/owner-panel/admin.php        Settings → «پنل مدیریت و اپ», admin-bar link, dashboard widget
 *  theme/pwa/panel/panel.js|css|sw.js  vanilla JS app (no build step)
 *
 * Load from functions.php:
 *   require get_template_directory() . '/inc/owner-panel/owner-panel.php';
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'PSB_PANEL_VERSION' ) ) {
	return;
}
define( 'PSB_PANEL_VERSION', '1.0.0' );
define( 'PSB_PANEL_DIR', __DIR__ );
define( 'PSB_PANEL_CAP', 'psb_owner_panel' );
define( 'PSB_PANEL_SLUG', 'panel' );
if ( ! defined( 'PSB_SETTINGS_PAGE' ) ) {
	define( 'PSB_SETTINGS_PAGE', 'psb-panel' );
	define( 'PSB_SETTINGS_GROUP', 'psb_panel_group' );
}

require_once PSB_PANEL_DIR . '/helpers.php';
require_once PSB_PANEL_DIR . '/auth.php';
require_once PSB_PANEL_DIR . '/rfq-adapter.php';
require_once PSB_PANEL_DIR . '/router.php';
require_once PSB_PANEL_DIR . '/rest.php';
require_once PSB_PANEL_DIR . '/admin.php';
