<?php
/**
 * Closes <main>, prints the quote CTA band, footer, floating UI and modals.
 *
 * @package bluebox
 */

global $woo_active;

$psb_is_account  = $woo_active && function_exists( 'is_account_page' ) && is_account_page();
$psb_is_checkout = $woo_active && function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || $psb_is_account );
$psb_is_product  = $woo_active && function_exists( 'is_product' ) && is_product();
$psb_is_contact  = is_page( array( 'contact-us', 'login', 'request-quote' ) ) || is_page_template( array( 'contact-us.php', 'page-request-quote.php' ) );
?>
</main>

<?php
if ( ! $psb_is_checkout && ! $psb_is_contact && apply_filters( 'psb_show_cta_band', true ) ) {
	get_template_part( 'template-parts/global/cta-band' );
}

if ( ! $psb_is_account && ! is_page( 'login' ) ) {
	get_template_part( 'template-parts/layout/footer-content' );
}

// Mobile app tab bar (see psb_show_tabbar(): hidden on product / cart / checkout / account / login).
if ( psb_show_tabbar() ) {
	get_template_part( 'template-parts/layout/sticky-social' );
}

if ( ! $psb_is_checkout ) {
	get_template_part( 'template-parts/global/backToTop' );
}

// Dialogs.
get_template_part( 'template-parts/layout/header/menu-modal' );
get_template_part( 'template-parts/layout/header/search-modal' );
// Contact modal only where a «shopContact = true» trigger exists (products, services, projects).
if ( ( function_exists( 'is_woocommerce' ) && is_woocommerce() ) || is_singular( array( 'product', 'services', 'portfolio' ) ) || apply_filters( 'psb_contact_modal', false ) ) {
	get_template_part( 'template-parts/shop/shop-contact-modal' );
}
// Category & cart modals are not rendered: catalogue mode has no cart and the header has no category trigger.


wp_footer();
?>
</body>
</html>
