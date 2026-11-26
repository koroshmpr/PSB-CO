<?php
/**
 * Section header: eyebrow + h2 + lead (+ optional action link).
 *
 * $args:
 * - eyebrow   string
 * - title     string (required)
 * - lead      string
 * - tag       heading tag, default 'h2'
 * - id        id for the heading (use with aria-labelledby on the <section>)
 * - align     'start' (default) | 'center'
 * - invert    bool, for dark sections
 * - link      array( 'url' => '', 'label' => '' ) shown at the end (split layout)
 * - class     extra classes
 */
$psb_title = $args['title'] ?? '';
if ( '' === $psb_title ) {
	return;
}
$psb_tag     = in_array( $args['tag'] ?? 'h2', array( 'h1', 'h2', 'h3' ), true ) ? ( $args['tag'] ?? 'h2' ) : 'h2';
$psb_link    = $args['link'] ?? null;
$psb_classes = array( 'section-head' );
if ( 'center' === ( $args['align'] ?? '' ) ) {
	$psb_classes[] = 'section-head--center';
}
if ( $psb_link && ! empty( $psb_link['url'] ) ) {
	$psb_classes[] = 'section-head--split';
}
if ( ! empty( $args['invert'] ) ) {
	$psb_classes[] = 'section-head--invert';
}
if ( ! empty( $args['class'] ) ) {
	$psb_classes[] = $args['class'];
}
?>
<div class="<?php echo esc_attr( implode( ' ', $psb_classes ) ); ?>">
	<div>
		<?php if ( ! empty( $args['eyebrow'] ) ) : ?>
			<span class="eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></span>
		<?php endif; ?>
		<<?php echo $psb_tag; // phpcs:ignore ?> class="section-title"<?php echo ! empty( $args['id'] ) ? ' id="' . esc_attr( $args['id'] ) . '"' : ''; ?>><?php echo esc_html( $psb_title ); ?></<?php echo $psb_tag; // phpcs:ignore ?>>
		<?php if ( ! empty( $args['lead'] ) ) : ?>
			<p class="section-lead"><?php echo esc_html( $args['lead'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( $psb_link && ! empty( $psb_link['url'] ) ) : ?>
		<a class="link-arrow shrink-0 self-start md:self-auto" href="<?php echo esc_url( $psb_link['url'] ); ?>">
			<?php echo esc_html( $psb_link['label'] ?? 'مشاهده همه' ); ?>
			<?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?>
		</a>
	<?php endif; ?>
</div>
