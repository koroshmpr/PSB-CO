<?php
/**
 * Related credentials as register rows (spec 6.2 / 6.3).
 *
 * $args: posts (WP_Post[]), title, eyebrow, id, lead, tag, class
 */
$psb_posts = array_filter( (array) ( $args['posts'] ?? array() ) );
if ( ! $psb_posts ) {
	return;
}
$psb_id = $args['id'] ?? 'related-creds-title';
?>
<section class="<?php echo esc_attr( $args['class'] ?? 'py-section-sm' ); ?>" aria-labelledby="<?php echo esc_attr( $psb_id ); ?>">
	<?php
	get_template_part(
		'template-parts/global/section-head',
		null,
		array(
			'id'      => $psb_id,
			'eyebrow' => $args['eyebrow'] ?? 'اعتبارنامه‌ها',
			'title'   => $args['title'] ?? 'تأییدیه‌ها و گزارش‌های آزمون مرتبط',
			'lead'    => $args['lead'] ?? '',
		)
	);
	?>
	<div class="register border-t-2 border-foreground">
		<?php
		foreach ( $psb_posts as $psb_p ) {
			get_template_part( 'template-parts/certificates/reg-row', null, array( 'post' => $psb_p->ID, 'tag' => $args['tag'] ?? 'h3' ) );
		}
		?>
	</div>
</section>
