<?php
/**
 * Site logo (vector, theme/images/brand/). Usage rules: full logo ≥ 120px wide
 * (tagline legibility), mark ≥ 24px; clear space = one P-stem (~15% of logo width).
 *
 * $args:
 * - variant   'mark' (default; drawer, PSB letters only) | 'full' | 'white' (full, on dark) | 'mark-white'
 *             | 'swap' (header: full lockup + mark in one link; CSS swaps them by breakpoint and on scroll-compact)
 * - height    rendered height in px (default: mark 40, full 64)
 * - class     classes for the <a>
 * - logoSize  extra classes for the <img>
 * - eager     bool, above the fold
 */
$psb_files = array(
	'mark'       => array( 'psb-mark.svg', 1149, 511 ),
	'mark-white' => array( 'psb-mark-white.svg', 1149, 511 ),
	'full'       => array( 'psb-logo.svg', 1149, 655 ),
	'white'      => array( 'psb-logo-white.svg', 1149, 655 ),
);
$psb_variant = $args['variant'] ?? 'mark';
$psb_dir     = get_template_directory_uri() . '/images/brand/';

if ( 'swap' === $psb_variant ) :
	?>
	<a class="site-logo brand-swap <?php echo esc_attr( $args['class'] ?? '' ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( psb_brand_name() . ' (PSB)' ); ?>">
		<?php // Inline sizes: plugin CSS such as WooCommerce's `.woocommerce img { height:auto; max-width:100% }` (unlayered) must never collapse the logo. ?>
		<img class="logo-full" src="<?php echo esc_url( $psb_dir . 'psb-logo.svg' ); ?>" width="77" height="44" style="height:44px;width:77px;max-width:none" alt="" decoding="async" fetchpriority="high">
		<img class="logo-mark" src="<?php echo esc_url( $psb_dir . 'psb-mark.svg' ); ?>" width="63" height="28" style="height:28px;width:63px;max-width:none" alt="" decoding="async">
	</a>
	<?php
	return;
endif;

if ( ! isset( $psb_files[ $psb_variant ] ) ) {
	$psb_variant = 'mark';
}
list( $psb_file, $psb_vw, $psb_vh ) = $psb_files[ $psb_variant ];
$psb_h     = (int) ( $args['height'] ?? ( 0 === strpos( $psb_variant, 'mark' ) ? 40 : 64 ) );
$psb_w     = (int) round( $psb_h * $psb_vw / $psb_vh );
$psb_brand = psb_brand_name();
?>
<a class="site-logo <?php echo esc_attr( $args['class'] ?? '' ); ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
	<img src="<?php echo esc_url( get_template_directory_uri() . '/images/brand/' . $psb_file ); ?>"
		width="<?php echo esc_attr( $psb_w ); ?>" height="<?php echo esc_attr( $psb_h ); ?>"
		class="<?php echo esc_attr( $args['logoSize'] ?? '' ); ?>"
		style="height:<?php echo esc_attr( $psb_h ); ?>px;width:<?php echo esc_attr( $psb_w ); ?>px;max-width:none"
		alt="<?php echo esc_attr( $psb_brand . ' (PSB)' ); ?>"
		<?php echo empty( $args['eager'] ) ? 'loading="lazy"' : ''; ?>
		decoding="async">
</a>
