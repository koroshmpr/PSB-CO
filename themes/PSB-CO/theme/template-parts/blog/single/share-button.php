<?php
/**
 * Share links (no third-party scripts) + copy link.
 */
$psb_url   = rawurlencode( wp_get_canonical_url() ? wp_get_canonical_url() : get_permalink() );
$psb_title = rawurlencode( get_the_title() );
$psb_links = array(
	'telegram' => array( 'تلگرام', 'https://t.me/share/url?url=' . $psb_url . '&text=' . $psb_title ),
	'whatsapp' => array( 'واتساپ', 'https://wa.me/?text=' . $psb_title . '%20' . $psb_url ),
	'linkedin' => array( 'لینکدین', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $psb_url ),
	'email'    => array( 'ایمیل', 'mailto:?subject=' . $psb_title . '&body=' . $psb_url ),
);
?>
<div class="mt-10 flex flex-wrap items-center gap-3 border-t border-line pt-6">
	<span class="text-sm font-bold text-dark">اشتراک‌گذاری:</span>
	<ul class="share-list" role="list">
		<?php foreach ( $psb_links as $psb_k => $psb_l ) : ?>
			<li><a class="btn btn-outline btn-sm no-prerender" href="<?php echo esc_url( $psb_l[1] ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $psb_l[0] ); ?></a></li>
		<?php endforeach; ?>
		<li><button type="button" class="btn btn-ghost btn-sm" data-copy-link="<?php echo esc_url( get_permalink() ); ?>" onclick="var b=this;navigator.clipboard&&navigator.clipboard.writeText(b.dataset.copyLink).then(function(){b.textContent='کپی شد';});"><?php echo psb_icon( 'external', 'size-4' ); // phpcs:ignore ?> کپی پیوند</button></li>
	</ul>
</div>
