<?php
/**
 * Image gallery as a scroll-snap rail (lightbox on click). Used by services and portfolio singles.
 *
 * $args: images (ACF gallery array | IDs), title, alt, id
 */
$psb_imgs = array();
foreach ( (array) ( $args['images'] ?? array() ) as $psb_img ) {
	$psb_iid = is_array( $psb_img ) ? (int) ( $psb_img['ID'] ?? ( $psb_img['id'] ?? 0 ) ) : (int) $psb_img;
	if ( $psb_iid && wp_attachment_is_image( $psb_iid ) ) {
		$psb_imgs[] = $psb_iid;
	}
}
if ( ! $psb_imgs ) {
	return;
}
psb_lightbox();
$psb_alt = $args['alt'] ?? get_the_title();
$psb_hid = $args['id'] ?? 'gallery-title';
?>
<section class="mt-12" aria-labelledby="<?php echo esc_attr( $psb_hid ); ?>">
	<h2 id="<?php echo esc_attr( $psb_hid ); ?>" class="mb-5 text-h3 font-bold text-dark"><?php echo esc_html( $args['title'] ?? 'گالری تصاویر' ); ?></h2>
	<ul class="rail [--rail-item:78%] sm:[--rail-item:44%] lg:[--rail-item:31%]" role="list">
		<?php foreach ( $psb_imgs as $psb_n => $psb_iid ) : ?>
			<li>
				<button type="button" class="media-box block w-full cursor-zoom-in overflow-hidden rounded-card border border-line bg-slate-100" style="--ratio:4/3" data-lightbox="<?php echo esc_url( wp_get_attachment_image_url( $psb_iid, 'full' ) ); ?>" data-lightbox-title="<?php echo esc_attr( $psb_alt ); ?>" data-lightbox-group="<?php echo esc_attr( $psb_hid ); ?>">
					<?php echo psb_image( $psb_iid, 'medium_large', array( 'alt' => $psb_alt . ' — تصویر ' . psb_fa_num( $psb_n + 1 ), 'sizes' => '(min-width: 1024px) 26rem, 78vw', 'class' => 'size-full object-cover' ) ); // phpcs:ignore ?>
					<span class="sr-only">نمایش بزرگ</span>
				</button>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
