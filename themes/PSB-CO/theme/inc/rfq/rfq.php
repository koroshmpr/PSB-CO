<?php
/**
 * RFQ — «استعلام قیمت / ثبت سفارش تابلو برق»
 *
 * Multi-step quote request wizard + quick form, stored as private `psb_rfq` posts,
 * pushed to the sales team via Bale / Telegram (relay) / WhatsApp Cloud / webhook / email,
 * with an optional SMS confirmation to the customer. See themes/PSB-CO/RFQ.md.
 *
 * Load from functions.php:
 *   require get_template_directory() . '/inc/rfq/rfq.php';
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'PSB_RFQ_VERSION' ) ) {
	return;
}
define( 'PSB_RFQ_VERSION', '1.0.0' );
define( 'PSB_RFQ_DIR', __DIR__ );
define( 'PSB_RFQ_CPT', 'psb_rfq' );
/** Capability required to view / manage requests (granted to administrator, editor, shop_manager). */
define( 'PSB_RFQ_CAP', 'edit_psb_rfqs' );

require PSB_RFQ_DIR . '/helpers.php';
require PSB_RFQ_DIR . '/options.php';
require PSB_RFQ_DIR . '/cpt.php';
require PSB_RFQ_DIR . '/files.php';
require PSB_RFQ_DIR . '/notify.php';
require PSB_RFQ_DIR . '/rest.php';
require PSB_RFQ_DIR . '/frontend.php';

if ( is_admin() ) {
	require PSB_RFQ_DIR . '/admin.php';
	require PSB_RFQ_DIR . '/settings.php';
}
