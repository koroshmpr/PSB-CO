<?php
/**
 * Social network links from Site Setting → "Social".
 *
 * $args:
 * - class      extra classes for every link (legacy callers pass colour classes here)
 * - variant    'dark' (default, for dark backgrounds) | 'light'
 * - wrap_class when set, links are wrapped in <div class="…">
 * - size       icon size (default 18)
 */
$psb_socials = psb_socials();
if ( ! $psb_socials ) {
	return;
}
$psb_variant = $args['variant'] ?? 'dark';
$psb_base    = 'light' === $psb_variant
	? 'inline-flex size-10 items-center justify-center rounded-control border border-line bg-white text-slate-600 transition-colors hover:border-primary hover:text-primary'
	: 'social-btn';
$psb_wrap    = $args['wrap_class'] ?? '';

if ( $psb_wrap ) {
	echo '<div class="' . esc_attr( $psb_wrap ) . '">';
}
foreach ( $psb_socials as $psb_social ) :
	$psb_label = psb_social_label( $psb_social['name'] );
	?>
	<a class="<?php echo esc_attr( trim( $psb_base . ' ' . ( $args['class'] ?? '' ) ) ); ?>"
		href="<?php echo esc_url( $psb_social['url'] ); ?>"
		target="_blank" rel="noopener noreferrer"
		aria-label="<?php echo esc_attr( $psb_label ); ?>" title="<?php echo esc_attr( $psb_label ); ?>">
		<?php
		if ( locate_template( 'template-parts/svg/socials/' . $psb_social['name'] . '.php' ) ) {
			get_template_part( 'template-parts/svg/socials/' . $psb_social['name'], null, array( 'size' => $args['size'] ?? 18 ) );
		} else {
			echo esc_html( mb_substr( $psb_label, 0, 1 ) );
		}
		?>
	</a>
	<?php
endforeach;
if ( $psb_wrap ) {
	echo '</div>';
}
