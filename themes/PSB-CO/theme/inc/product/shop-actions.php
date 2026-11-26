<?php
add_action( 'wp_enqueue_scripts', function() {
    if ( is_product() ) {
        wp_enqueue_script( 'wc-add-to-cart-variation' );
    }
});
add_filter( 'woocommerce_localisation_address_formats', 'wc_single_line_address_format', 20 );

function wc_single_line_address_format( $formats ) {
    // تغییر فرمت برای کشور ایران (IR)
    $formats['IR'] = "{name} {country} {city} ,{address_1}, {address_2}, {postcode}";
    return $formats;
}
//{company}<br>
/**
 * Quote deep links use /request-quote/?product=<slug>. "product" is also WooCommerce's public query
 * var (the product post type), so WordPress resolved those URLs as a product single and the quote
 * wizard never loaded. When a page is requested by path or ID, drop the product query vars; the
 * parameter stays in the URL for the wizard script (js/rfq.js reads location.search).
 */
add_filter(
	'request',
	function ( $qv ) {
		if ( is_admin() || empty( $qv['product'] ) ) {
			return $qv;
		}
		if ( ! empty( $qv['pagename'] ) || ! empty( $qv['page_id'] ) ) {
			$slug = $qv['product'];
			unset( $qv['product'] );
			if ( isset( $qv['post_type'] ) && 'product' === $qv['post_type'] ) {
				unset( $qv['post_type'] );
			}
			if ( isset( $qv['name'] ) && $qv['name'] === $slug ) {
				unset( $qv['name'] );
			}
		}
		return $qv;
	}
);
