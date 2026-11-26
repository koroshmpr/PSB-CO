<?php
/**
 * Universal content card (.card). Works for posts, products, services, portfolio, CPTs.
 *
 * $args:
 * - post      WP_Post|int (default: current post in the loop)
 * - layout    'stack' (default) | 'side' (image beside text) | 'overlay' (title on image)
 * - eyebrow   string above the title (e.g. category / client)
 * - excerpt   string|false (default: trimmed excerpt; false hides it)
 * - words     excerpt length in words (default 22)
 * - badges    array of strings (rendered as .badge-standard chips, e.g. 'IEC 61439', 'IP54')
 * - specs     array( 'label' => 'value' ) rendered as a .spec-list
 * - meta      array of strings under the title (date, location …)
 * - cta       footer link label (default 'مشاهده جزئیات'; false hides footer)
 * - quote     URL for a secondary «استعلام قیمت» button in the footer (e.g. psb_quote_url( $slug ))
 * - ratio     CSS aspect-ratio for the media (default '4 / 3')
 * - size      image size (default 'medium_large')
 * - tag       heading tag (default 'h3')
 * - eager     bool, first visible card above the fold
 * - class     extra classes
 */
$psb_post = get_post( $args['post'] ?? null );
if ( ! $psb_post ) {
	return;
}

$psb_layout  = $args['layout'] ?? 'stack';
$psb_url     = get_permalink( $psb_post );
$psb_title   = get_the_title( $psb_post );
$psb_tag     = in_array( $args['tag'] ?? 'h3', array( 'h2', 'h3', 'h4' ), true ) ? ( $args['tag'] ?? 'h3' ) : 'h3';
$psb_words   = (int) ( $args['words'] ?? 22 );
$psb_excerpt = array_key_exists( 'excerpt', $args ?? array() ) ? $args['excerpt'] : wp_trim_words( get_the_excerpt( $psb_post ), $psb_words, '…' );
$psb_cta     = array_key_exists( 'cta', $args ?? array() ) ? $args['cta'] : 'مشاهده جزئیات';
$psb_thumb   = get_post_thumbnail_id( $psb_post );
$psb_ratio   = $args['ratio'] ?? '4 / 3';
$psb_size    = $args['size'] ?? 'medium_large';
$psb_sizes   = 'side' === $psb_layout ? '(min-width: 1024px) 220px, 40vw' : '(min-width: 1280px) 400px, (min-width: 768px) 45vw, 88vw';
$psb_iattr   = array(
	'alt'           => $psb_title,
	'sizes'         => $psb_sizes,
	'loading'       => empty( $args['eager'] ) ? 'lazy' : 'eager',
	'fetchpriority' => empty( $args['eager'] ) ? 'auto' : 'high',
);
if ( function_exists( 'psb_best_image' ) ) {
	// Real photo only when sharp enough for the slot; otherwise the type-matched «تصویر نمایشی» (inc/placeholders.php).
	$psb_img = psb_best_image(
		$psb_post,
		'side' === $psb_layout ? 240 : 400,
		'overlay' === $psb_layout ? ( $args['ratio'] ?? '4 / 5' ) : $psb_ratio,
		array(
			'size' => $psb_size,
			'attr' => $psb_iattr,
		)
	);
} else {
	$psb_img = $psb_thumb ? psb_image( $psb_thumb, $psb_size, $psb_iattr ) : '';
}

$psb_classes = 'card card-hover ' . ( $args['class'] ?? '' );
if ( 'side' === $psb_layout ) {
	$psb_classes .= ' sm:flex-row';
}
?>
<article class="<?php echo esc_attr( trim( $psb_classes ) ); ?>">
	<?php if ( 'overlay' === $psb_layout ) : ?>
		<div class="card-media" style="--card-ratio: <?php echo esc_attr( $args['ratio'] ?? '4 / 5' ); ?>">
			<?php echo $psb_img ? $psb_img : '<div class="media-placeholder size-full"></div>'; // phpcs:ignore ?>
			<div class="absolute inset-0 bg-gradient-to-t from-dark via-dark/40 to-transparent"></div>
			<div class="absolute inset-x-0 bottom-0 flex flex-col gap-2 p-5 text-white">
				<?php if ( ! empty( $args['eyebrow'] ) ) : ?>
					<span class="text-xs font-semibold text-slate-300"><?php echo esc_html( $args['eyebrow'] ); ?></span>
				<?php endif; ?>
				<<?php echo $psb_tag; // phpcs:ignore ?> class="text-h3 font-bold text-white">
					<a class="card-link" href="<?php echo esc_url( $psb_url ); ?>"><?php echo psb_lat( $psb_title ); // phpcs:ignore ?></a>
				</<?php echo $psb_tag; // phpcs:ignore ?>>
				<?php if ( ! empty( $args['meta'] ) ) : ?>
					<p class="card-meta !text-slate-300"><?php echo esc_html( implode( ' · ', (array) $args['meta'] ) ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	<?php else : ?>
		<div class="card-media <?php echo 'side' === $psb_layout ? 'sm:w-2/5 sm:shrink-0' : ''; ?> <?php echo $psb_img ? '' : 'media-placeholder'; ?>" style="--card-ratio: <?php echo esc_attr( $psb_ratio ); ?>">
			<?php echo $psb_img ? $psb_img : ''; // phpcs:ignore ?>
		</div>
		<div class="flex flex-1 flex-col">
			<div class="card-body">
				<?php if ( ! empty( $args['eyebrow'] ) ) : ?>
					<span class="card-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></span>
				<?php endif; ?>
				<<?php echo $psb_tag; // phpcs:ignore ?> class="card-title">
					<a class="card-link" href="<?php echo esc_url( $psb_url ); ?>"><?php echo psb_lat( $psb_title ); // phpcs:ignore ?></a>
				</<?php echo $psb_tag; // phpcs:ignore ?>>
				<?php if ( ! empty( $args['meta'] ) ) : ?>
					<p class="card-meta"><?php echo esc_html( implode( ' · ', (array) $args['meta'] ) ); ?></p>
				<?php endif; ?>
				<?php if ( $psb_excerpt ) : ?>
					<p class="card-text"><?php echo psb_lat( $psb_excerpt ); // phpcs:ignore ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $args['badges'] ) ) : ?>
					<div class="flex flex-wrap gap-1.5 pt-1">
						<?php foreach ( array_filter( (array) $args['badges'] ) as $psb_badge ) : ?>
							<span class="badge badge-standard"><?php echo esc_html( $psb_badge ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $args['specs'] ) ) : ?>
					<dl class="spec-list mt-1">
						<?php foreach ( $args['specs'] as $psb_label => $psb_value ) : ?>
							<?php if ( '' !== trim( (string) $psb_value ) ) : ?>
								<div><dt><?php echo esc_html( $psb_label ); ?></dt><dd><?php echo esc_html( $psb_value ); ?></dd></div>
							<?php endif; ?>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>
			</div>
			<?php if ( $psb_cta ) : ?>
				<div class="card-footer">
					<span class="link-arrow"><?php echo esc_html( $psb_cta ); ?> <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></span>
					<?php if ( ! empty( $args['quote'] ) ) : ?>
						<a class="btn btn-secondary btn-sm relative z-10" href="<?php echo esc_url( $args['quote'] ); ?>">استعلام قیمت</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</article>
