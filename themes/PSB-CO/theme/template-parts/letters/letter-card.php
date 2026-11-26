<?php
/**
 * Customer letter card: scan thumbnail in a paper frame, organisation, type badge, highlight quote.
 * The scan opens in the shared lightbox; the title links to the letter page (with its transcription).
 *
 * $args: post (ID), tag (h2|h3)
 */
$psb_id   = (int) ( $args['post'] ?? get_the_ID() );
$psb_tag  = in_array( $args['tag'] ?? 'h3', array( 'h2', 'h3' ), true ) ? $args['tag'] ?? 'h3' : 'h3';
$psb_l    = psb_letter_data( $psb_id );
$psb_full = $psb_l['scan'] ? wp_get_attachment_image_url( $psb_l['scan'], 'full' ) : '';
$psb_name = get_the_title( $psb_id );
if ( $psb_full ) {
	psb_lightbox();
}
?>
<article class="letter-card">
	<div class="letter-card-media pb-0">
		<?php if ( $psb_full ) : ?>
			<button type="button" class="doc-frame relative z-10 cursor-zoom-in" data-lightbox="<?php echo esc_url( $psb_full ); ?>" data-lightbox-title="<?php echo esc_attr( $psb_name ); ?>" <?php echo $psb_l['pdf'] ? 'data-lightbox-pdf="' . esc_url( $psb_l['pdf']['url'] ) . '"' : ''; ?>>
				<?php echo psb_image( $psb_l['scan'], 'medium', array( 'alt' => 'اسکن ' . $psb_name, 'sizes' => '15rem' ) ); // phpcs:ignore ?>
				<span class="sr-only">نمایش اسکن نامه در اندازه بزرگ</span>
			</button>
		<?php else : ?>
			<div class="doc-frame doc-frame--empty"><?php echo psb_icon( 'doc', 'size-10' ); // phpcs:ignore ?></div>
		<?php endif; ?>
	</div>
	<div class="card-body">
		<div class="flex flex-wrap items-center gap-2">
			<?php if ( $psb_l['type'] ) : ?>
				<span class="badge badge-primary"><?php echo esc_html( $psb_l['type']->name ); ?></span>
			<?php endif; ?>
			<?php if ( $psb_l['date'] ) : ?>
				<span class="text-xs text-slate-500"><?php echo esc_html( psb_fa_num( $psb_l['date'] ) ); ?></span>
			<?php endif; ?>
		</div>
		<<?php echo $psb_tag; // phpcs:ignore ?> class="cred-card-title"><a class="card-link" href="<?php echo esc_url( get_permalink( $psb_id ) ); ?>"><?php echo esc_html( $psb_l['org'] ? $psb_l['org'] : $psb_name ); ?></a></<?php echo $psb_tag; // phpcs:ignore ?>>
		<?php if ( $psb_l['org'] && $psb_name !== $psb_l['org'] ) : ?>
			<p class="text-xs text-slate-500"><?php echo esc_html( $psb_name ); ?></p>
		<?php endif; ?>
		<?php if ( $psb_l['quote'] ) : ?>
			<blockquote class="letter-quote"><p><?php echo esc_html( $psb_l['quote'] ); ?></p></blockquote>
		<?php endif; ?>
	</div>
	<div class="card-footer text-xs">
		<span class="text-slate-500"><?php echo $psb_l['project'] ? esc_html( 'پروژه: ' . get_the_title( $psb_l['project'] ) ) : ''; ?></span>
		<span class="link-arrow">متن کامل نامه <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></span>
	</div>
</article>
