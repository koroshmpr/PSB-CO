<?php
/**
 * Register row for a credential (spec 4.3): tile (issuer logo / lab wordmark), title, meta line, «مشاهده سند».
 * Used in archives, related-credentials lists, about and product pages.
 *
 * $args: post (ID), tag (h2|h3)
 */
$psb_id   = (int) ( $args['post'] ?? get_the_ID() );
$psb_type = get_post_type( $psb_id );
$psb_tag  = in_array( $args['tag'] ?? 'h3', array( 'h2', 'h3' ), true ) ? $args['tag'] ?? 'h3' : 'h3';
$psb_url  = get_permalink( $psb_id );
$psb_meta = array();
$psb_tile = '';

if ( 'type-test' === $psb_type ) {
	$psb_d    = psb_type_test_data( $psb_id );
	$psb_tile = '<span class="lat">' . esc_html( $psb_d['lab'] ? $psb_d['lab'] : 'TEST' ) . '</span>';
	foreach ( array_slice( $psb_d['standards'], 0, 3 ) as $psb_s ) {
		$psb_meta[] = '<span class="lat">' . esc_html( $psb_s ) . '</span>';
	}
	if ( $psb_d['date'] ) {
		$psb_meta[] = '<span>' . psb_doc_date( $psb_d['date'] ) . '</span>';
	}
	$psb_action = 'گزارش آزمون';
} elseif ( 'certificates' === $psb_type ) {
	$psb_d    = psb_certificate_data( $psb_id );
	$psb_tile = '<span class="lat">ISO</span>';
	if ( $psb_d['issuer'] ) {
		$psb_meta[] = '<span>' . esc_html( $psb_d['issuer'] ) . '</span>';
	}
	foreach ( $psb_d['standards'] as $psb_s ) {
		$psb_meta[] = '<span class="lat">' . esc_html( $psb_s ) . '</span>';
	}
	$psb_action = 'مشاهده سند';
} else {
	$psb_d = psb_confirmation_data( $psb_id );
	$psb_logo = get_post_thumbnail_id( $psb_id );
	$psb_tile = $psb_logo ? psb_image( $psb_logo, 'thumbnail', array( 'alt' => $psb_d['issuer'] ? 'نشان ' . $psb_d['issuer'] : '', 'sizes' => '56px' ) ) : psb_icon( 'award', 'size-6 text-slate-500' );
	if ( $psb_d['number'] ) {
		$psb_meta[] = '<span>' . esc_html( psb_fa_num( $psb_d['number'] ) ) . '</span>';
	}
	if ( $psb_d['scope'] ) {
		$psb_scope  = wp_trim_words( $psb_d['scope'], 14, '…' );
		$psb_meta[] = '<span' . ( preg_match( '/^[\x20-\x7E…]+$/u', $psb_scope ) ? ' class="lat"' : '' ) . '>' . esc_html( $psb_scope ) . '</span>';
	} elseif ( $psb_d['issuer'] ) {
		$psb_meta[] = '<span>' . esc_html( $psb_d['issuer'] ) . '</span>';
	}
	$psb_action = 'مشاهده سند';
}
?>
<article class="reg-row">
	<span class="reg-tile" aria-hidden="true"><?php echo $psb_tile; // phpcs:ignore -- escaped above ?></span>
	<div class="min-w-0 flex-1">
		<<?php echo $psb_tag; // phpcs:ignore ?> class="reg-title"><a class="card-link" href="<?php echo esc_url( $psb_url ); ?>"><?php echo psb_lat_html( psb_brand_fix( get_the_title( $psb_id ) ) ); // phpcs:ignore -- escaped in helper ?></a></<?php echo $psb_tag; // phpcs:ignore ?>>
		<?php if ( $psb_meta ) : ?>
			<p class="reg-meta"><?php echo implode( '', $psb_meta ); // phpcs:ignore -- escaped above ?></p>
		<?php endif; ?>
	</div>
	<span class="reg-action" aria-hidden="true"><?php echo psb_icon( 'doc', 'size-4' ); // phpcs:ignore ?><?php echo esc_html( $psb_action ); ?></span>
</article>
