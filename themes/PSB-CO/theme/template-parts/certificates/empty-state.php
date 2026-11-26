<?php
/**
 * Elegant empty state (no fake items).
 *
 * $args: title, text, icon, actions (array of [url, label, class])
 */
$psb_actions = $args['actions'] ?? array();
?>
<div class="empty-state">
	<span class="icon-tile"><?php echo psb_icon( $args['icon'] ?? 'doc' ); // phpcs:ignore ?></span>
	<h2 class="empty-state-title"><?php echo esc_html( $args['title'] ?? 'به‌زودی' ); ?></h2>
	<?php if ( ! empty( $args['text'] ) ) : ?>
		<p class="empty-state-text"><?php echo esc_html( $args['text'] ); ?></p>
	<?php endif; ?>
	<?php if ( $psb_actions ) : ?>
		<div class="flex flex-wrap justify-center gap-3">
			<?php foreach ( $psb_actions as $psb_a ) : ?>
				<a class="btn <?php echo esc_attr( $psb_a[2] ?? 'btn-outline' ); ?>" href="<?php echo esc_url( $psb_a[0] ); ?>"><?php echo esc_html( $psb_a[1] ); ?> <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
