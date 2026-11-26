<?php
/**
 * Footer navigation column.
 *
 * $args:
 * - theme_location  'footerLocationOne' | 'footerLocationTwo' | 'footerLocationThree'
 * - fallback_title  heading when the menu has no name
 * - fallback_links  array( array( 'url' => '', 'label' => '' ) ) when no menu is assigned
 */
$psb_location = $args['theme_location'] ?? 'footerLocationOne';
$psb_title    = $args['fallback_title'] ?? '';
$psb_links    = $args['fallback_links'] ?? array();

$psb_locations = get_nav_menu_locations();
if ( ! empty( $psb_locations[ $psb_location ] ) ) {
	$psb_menu = wp_get_nav_menu_object( $psb_locations[ $psb_location ] );
	if ( $psb_menu && ! empty( $psb_menu->name ) ) {
		$psb_title = $psb_menu->name;
	}
}
?>
<nav aria-label="<?php echo esc_attr( $psb_title ); ?>">
	<p class="footer-title"><?php echo esc_html( $psb_title ); ?></p>
	<?php
	if ( has_nav_menu( $psb_location ) ) {
		wp_nav_menu(
			array(
				'theme_location' => $psb_location,
				'menu_class'     => 'footer-links',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
	} elseif ( $psb_links ) {
		echo '<ul class="footer-links">';
		foreach ( $psb_links as $psb_link ) {
			printf( '<li><a href="%s">%s</a></li>', esc_url( $psb_link['url'] ), esc_html( $psb_link['label'] ) );
		}
		echo '</ul>';
	}
	?>
</nav>
