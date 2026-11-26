<?php
/**
 * FAQ accordion (native <details>, no JS) + FAQPage JSON-LD.
 *
 * $args:
 * - items          array of array( 'question' => '', 'answer' => '' ) (default: ACF "faq_list" of the current post)
 * - title          optional heading text (rendered as h2)
 * - class          extra wrapper classes
 * - open_first     bool, open the first item
 * - disable_schema bool
 */
$psb_items = ! empty( $args['items'] ) ? $args['items'] : psb_field( 'faq_list', array(), null );
if ( empty( $psb_items ) || ! is_array( $psb_items ) ) {
	return;
}
$psb_items = array_values(
	array_filter(
		$psb_items,
		function ( $item ) {
			return is_array( $item ) && ! empty( $item['question'] ) && ! empty( $item['answer'] );
		}
	)
);
if ( ! $psb_items ) {
	return;
}
$psb_title = $args['title'] ?? '';
?>
<div class="<?php echo esc_attr( $args['class'] ?? '' ); ?>">
	<?php if ( $psb_title ) : ?>
		<h2 class="mb-6 text-h3 font-bold text-dark"><?php echo esc_html( $psb_title ); ?></h2>
	<?php endif; ?>
	<div class="accordion">
		<?php foreach ( $psb_items as $psb_i => $psb_item ) : ?>
			<details class="accordion-item" <?php echo ( 0 === $psb_i && ! empty( $args['open_first'] ) ) ? 'open' : ''; ?>>
				<summary><?php echo esc_html( $psb_item['question'] ); ?></summary>
				<div class="accordion-panel"><?php echo wp_kses_post( wpautop( $psb_item['answer'] ) ); ?></div>
			</details>
		<?php endforeach; ?>
	</div>
</div>
<?php
if ( empty( $args['disable_schema'] ) ) :
	$psb_schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array(),
	);
	foreach ( $psb_items as $psb_item ) {
		$psb_schema['mainEntity'][] = array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $psb_item['question'] ),
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => wp_strip_all_tags( $psb_item['answer'] ),
			),
		);
	}
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $psb_schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
<?php endif; ?>
