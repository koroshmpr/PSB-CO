<?php
/**
 * Photo gallery grid (projects): first photo large, the rest in a grid; every photo opens the shared
 * lightbox and the whole set can be browsed there (data-lightbox-group: arrows, keys, swipe, counter).
 * Desktop: 4 columns, the first photo spans 2 × 2; mobile: 2 columns.
 *
 * $args:
 * - images  attachment IDs or ACF image arrays
 * - title   section title (default «گالری تصاویر»)
 * - alt     alt text base (default: the post title)
 * - id      heading id / lightbox group (default 'photo-gallery')
 *
 * @package PSB-CO
 */

$psb_ids = array();
foreach ( (array) ( $args['images'] ?? array() ) as $psb_i ) {
	$psb_id = is_array( $psb_i ) ? (int) ( $psb_i['ID'] ?? ( $psb_i['id'] ?? 0 ) ) : (int) $psb_i;
	if ( $psb_id && wp_attachment_is_image( $psb_id ) && ! ( function_exists( 'psb_image_blocked' ) && psb_image_blocked( $psb_id ) ) ) {
		$psb_ids[] = $psb_id;
	}
}
$psb_ids = array_values( array_unique( $psb_ids ) );
if ( ! $psb_ids ) {
	return;
}
psb_lightbox();
$psb_alt = $args['alt'] ?? get_the_title();
$psb_hid = $args['id'] ?? 'photo-gallery';
?>
<section class="mt-12" aria-labelledby="<?php echo esc_attr( $psb_hid ); ?>">
	<div class="mb-5 flex items-baseline justify-between gap-4">
		<h2 id="<?php echo esc_attr( $psb_hid ); ?>" class="text-h3 font-bold text-dark"><?php echo esc_html( $args['title'] ?? 'گالری تصاویر' ); ?></h2>
		<span class="text-sm text-slate-600"><?php echo esc_html( psb_fa_num( count( $psb_ids ) ) . ' تصویر' ); ?></span>
	</div>
	<ul class="photo-grid <?php echo 1 === count( $psb_ids ) ? 'is-single' : ''; ?>" role="list">
		<?php foreach ( $psb_ids as $psb_n => $psb_iid ) : ?>
			<li class="<?php echo 0 === $psb_n ? 'is-lead' : ''; ?>">
				<button type="button" class="photo-tile" data-lightbox="<?php echo esc_url( wp_get_attachment_image_url( $psb_iid, 'full' ) ); ?>" data-lightbox-title="<?php echo esc_attr( $psb_alt ); ?>" data-lightbox-group="<?php echo esc_attr( $psb_hid ); ?>">
					<?php echo psb_image( $psb_iid, 0 === $psb_n ? 'large' : 'medium_large', array( 'alt' => $psb_alt . ' — تصویر ' . psb_fa_num( $psb_n + 1 ), 'sizes' => 0 === $psb_n ? '(min-width: 1024px) 34rem, 92vw' : '(min-width: 1024px) 17rem, 46vw', 'class' => 'size-full object-cover' ) ); // phpcs:ignore ?>
					<span class="photo-tile-zoom" aria-hidden="true"><?php echo psb_icon( 'search', 'size-4' ); // phpcs:ignore ?></span>
					<span class="sr-only">نمایش بزرگ و مرور تصاویر</span>
				</button>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
