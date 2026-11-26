<?php
/**
 * Product in loops ([products] shortcode, related lists): catalogue card, no price / cart.
 *
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}
?>
<li class="list-none">
	<?php get_template_part( 'template-parts/product/card/product-card', null, array( 'post' => $product->get_id() ) ); ?>
</li>
