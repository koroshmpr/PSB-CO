<?php
/**
 * Certificate card (CPT certificates: ISO 9001 and future items).
 *
 * $args: post (ID), tag
 */
$psb_id    = (int) ( $args['post'] ?? get_the_ID() );
$psb_tag   = in_array( $args['tag'] ?? 'h3', array( 'h2', 'h3' ), true ) ? $args['tag'] ?? 'h3' : 'h3';
$psb_data  = psb_certificate_data( $psb_id );
$psb_thumb = psb_doc_preview_id( $psb_data['doc'], $psb_id );
?>
<article class="letter-card">
	<div class="letter-card-media">
		<div class="doc-frame <?php echo $psb_thumb ? '' : 'doc-frame--empty'; ?>">
			<?php echo $psb_thumb ? psb_image( $psb_thumb, 'medium', array( 'alt' => 'تصویر ' . get_the_title( $psb_id ), 'sizes' => '15rem' ) ) : psb_icon( 'shield', 'size-10' ); // phpcs:ignore ?>
		</div>
	</div>
	<div class="card-body">
		<?php if ( $psb_data['issuer'] ) : ?>
			<p class="cred-card-issuer"><?php echo esc_html( $psb_data['issuer'] ); ?></p>
		<?php endif; ?>
		<<?php echo $psb_tag; // phpcs:ignore ?> class="cred-card-title"><a class="card-link" href="<?php echo esc_url( get_permalink( $psb_id ) ); ?>"><?php echo esc_html( get_the_title( $psb_id ) ); ?></a></<?php echo $psb_tag; // phpcs:ignore ?>>
		<?php if ( $psb_data['standards'] ) : ?>
			<div class="flex flex-wrap gap-1.5">
				<?php foreach ( $psb_data['standards'] as $psb_s ) : ?>
					<span class="badge badge-standard"><?php echo esc_html( $psb_s ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<div class="card-footer text-xs text-slate-500">
		<span><?php echo esc_html( $psb_data['rows']['تاریخ سند'] ?? '' ); ?></span>
		<span class="link-arrow">مشاهده <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></span>
	</div>
</article>
