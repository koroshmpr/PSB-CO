<?php
/**
 * Document panel for credential singles: framed preview (opens the lightbox when it is an image)
 * + open / download buttons. Never embeds a heavy PDF iframe.
 *
 * $args: doc (psb_doc array|null), post (ID), title, label (e.g. «نامه تأییدیه»), eager (bool)
 */
$psb_post  = (int) ( $args['post'] ?? get_the_ID() );
$psb_doc   = $args['doc'] ?? null;
$psb_title = $args['title'] ?? get_the_title( $psb_post );
$psb_prev  = psb_doc_preview_id( $psb_doc, $psb_post );
$psb_full  = $psb_prev ? wp_get_attachment_image_url( $psb_prev, 'full' ) : '';
$psb_pdf   = ( $psb_doc && $psb_doc['is_pdf'] ) ? $psb_doc['url'] : '';
if ( $psb_full ) {
	psb_lightbox();
}
?>
<div class="panel">
	<p class="panel-title flex items-center gap-2"><?php echo psb_icon( 'doc', 'size-5 text-foreground' ); // phpcs:ignore ?> <?php echo esc_html( $args['label'] ?? 'سند' ); ?></p>
	<?php if ( $psb_full ) : ?>
		<button type="button" class="doc-frame w-full cursor-zoom-in" data-lightbox="<?php echo esc_url( $psb_full ); ?>" data-lightbox-title="<?php echo esc_attr( $psb_title ); ?>" <?php echo $psb_pdf ? 'data-lightbox-pdf="' . esc_url( $psb_pdf ) . '"' : ''; ?>>
			<?php
			echo psb_image( // phpcs:ignore
				$psb_prev,
				'medium_large',
				array(
					'alt'           => 'تصویر سند: ' . $psb_title,
					'sizes'         => '(min-width: 1024px) 20rem, 90vw',
					'loading'       => empty( $args['eager'] ) ? 'lazy' : 'eager',
					'fetchpriority' => empty( $args['eager'] ) ? 'auto' : 'high',
				)
			);
			?>
			<span class="sr-only">نمایش تصویر سند در اندازه بزرگ</span>
		</button>
	<?php else : ?>
		<div class="doc-frame doc-frame--empty"><?php echo psb_icon( 'doc', 'size-12' ); // phpcs:ignore ?></div>
	<?php endif; ?>
	<div class="mt-4 grid gap-2">
		<?php if ( $psb_doc ) : ?>
			<a class="btn btn-primary btn-block" href="<?php echo esc_url( $psb_doc['url'] ); ?>" target="_blank" rel="noopener">
				<?php echo psb_icon( 'external', 'size-4' ); // phpcs:ignore ?>
				<?php echo $psb_doc['is_pdf'] ? 'مشاهده فایل PDF' : 'مشاهده سند'; ?>
			</a>
			<p class="text-center text-xs text-slate-500"><?php echo esc_html( psb_doc_label( $psb_doc ) ); ?></p>
		<?php elseif ( $psb_full ) : ?>
			<p class="text-center text-xs text-slate-500">برای مشاهده در اندازه بزرگ روی تصویر بزنید.</p>
		<?php endif; ?>
	</div>
</div>
