<?php
/**
 * Catalogue filters: category (?product_cat=slug,slug) and product attributes (?pa_xxx=slug) only.
 * Price, weight, stock and sale filters were removed (B2B catalogue, prices on request).
 */
add_action( 'woocommerce_product_query', 'psb_catalogue_query', 100 );
function psb_catalogue_query( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	// Catalogue statuses (publish in production; a local preview may add drafts via the filter).
	$statuses = psb_catalogue_statuses();
	if ( array( 'publish' ) !== $statuses ) {
		$q->set( 'post_status', $statuses );
	}

	$tax_query = $q->get( 'tax_query' );
	$tax_query = is_array( $tax_query ) ? $tax_query : array();
	$applied   = false;

	if ( ! empty( $_GET['product_cat'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array_map( 'sanitize_title', explode( ',', sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) ) ), // phpcs:ignore
			'operator' => 'IN',
		);
		$applied     = true;
	}
	foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification
		if ( 0 === strpos( (string) $key, 'pa_' ) && '' !== $value && is_string( $value ) && taxonomy_exists( sanitize_key( $key ) ) ) {
			$tax_query[] = array(
				'taxonomy' => sanitize_key( $key ),
				'field'    => 'slug',
				'terms'    => array_map( 'sanitize_title', explode( ',', sanitize_text_field( wp_unslash( $value ) ) ) ),
				'operator' => 'IN',
			);
			$applied     = true;
		}
	}
	if ( $applied ) {
		$tax_query['relation'] = 'AND';
		$q->set( 'tax_query', $tax_query );
	}
}
