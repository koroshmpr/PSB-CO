<?php
/**
 * Shared layout of credential singles (confirmation, type-test, certificates) — spec 6.3.
 * Light head (breadcrumb, eyebrow = type, H1, meta line) · hub tabs · body 7/5:
 * start = spec table + «درباره این سند» prose + FAQ; end = 3:4 document preview (lightbox) + full-file button.
 * Related documents as register rows. Must be called inside the loop.
 *
 * $args: type, eyebrow, meta (string[] for the head line), rows (label => text), standards (string[]),
 *        date (raw), doc (psb_doc|null), doc_label, table, more_title
 */
$psb_id    = get_the_ID();
$psb_type  = $args['type'] ?? get_post_type();
$psb_rows  = array_filter( (array) ( $args['rows'] ?? array() ), 'strlen' );
$psb_stds  = array_values( array_filter( (array) ( $args['standards'] ?? array() ) ) );
$psb_title = psb_brand_fix( get_the_title() );
$psb_doc   = $args['doc'] ?? null;
$psb_prev  = psb_doc_preview_id( $psb_doc, $psb_id );
$psb_full  = $psb_prev ? wp_get_attachment_image_url( $psb_prev, 'full' ) : '';
$psb_pdf   = ( $psb_doc && $psb_doc['is_pdf'] ) ? $psb_doc : null;
if ( $psb_full ) {
	psb_lightbox();
}

get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'title'   => $psb_title,
		'eyebrow' => $args['eyebrow'] ?? '',
		'lead'    => implode( ' · ', array_filter( (array) ( $args['meta'] ?? array() ) ) ),
	)
);
get_template_part( 'template-parts/certificates/cred-nav', null, array( 'current' => $psb_type ) );
?>
<section class="container py-section-sm lg:py-section">
	<div class="grid gap-10 lg:grid-cols-12 lg:gap-6">
		<div class="min-w-0 lg:col-span-7">
			<table class="spec-table">
				<caption><?php echo esc_html( $args['table'] ?? 'مشخصات سند' ); ?></caption>
				<tbody>
					<?php foreach ( $psb_rows as $psb_label => $psb_value ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $psb_label ); ?></th>
							<td>
								<?php
								if ( 'تاریخ سند' === $psb_label ) {
									echo psb_doc_date( $psb_value ); // phpcs:ignore -- escaped in helper
								} elseif ( preg_match( '/^[\x20-\x7E]+$/', $psb_value ) ) {
									echo '<span class="lat">' . esc_html( $psb_value ) . '</span>';
								} else {
									echo esc_html( psb_brand_fix( $psb_value ) );
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ( $psb_stds ) : ?>
						<tr>
							<th scope="row">استاندارد</th>
							<td><span class="flex flex-wrap gap-1.5"><?php foreach ( $psb_stds as $psb_s ) : ?><span class="badge badge-standard lat"><?php echo esc_html( $psb_s ); ?></span><?php endforeach; ?></span></td>
						</tr>
					<?php endif; ?>
					<tr>
						<th scope="row">دارنده</th>
						<td>شرکت تولیدی و صنعتی <?php echo esc_html( psb_brand_name() ); ?></td>
					</tr>
				</tbody>
			</table>

			<?php if ( '' !== trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
				<h2 class="mt-12 text-h3 font-bold text-foreground">درباره این سند</h2>
				<div class="article-prose mt-4"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php
			get_template_part(
				'template-parts/global/faq-list',
				null,
				array(
					'title' => 'پرسش‌های متداول',
					'class' => 'mt-12',
				)
			);
			?>
		</div>

		<aside class="lg:col-span-5 lg:ps-10" aria-label="<?php echo esc_attr( $args['doc_label'] ?? 'سند' ); ?>">
			<div class="lg:sticky" style="top:calc(var(--header-h,4rem) + var(--wp-admin--admin-bar--height,0px) + 4rem)">
				<?php if ( $psb_full ) : ?>
					<button type="button" class="doc-frame w-full cursor-zoom-in" data-lightbox="<?php echo esc_url( $psb_full ); ?>" data-lightbox-title="<?php echo esc_attr( $psb_title ); ?>" <?php echo $psb_pdf ? 'data-lightbox-pdf="' . esc_url( $psb_pdf['url'] ) . '"' : ''; ?>>
						<?php echo psb_image( $psb_prev, 'large', array( 'alt' => 'تصویر سند: ' . $psb_title, 'sizes' => '(min-width: 1024px) 28rem, 92vw' ) ); // phpcs:ignore ?>
						<span class="sr-only">نمایش تصویر سند در اندازه بزرگ</span>
					</button>
					<p class="mt-2 text-xs text-slate-600">برای بزرگ‌نمایی روی تصویر بزنید.</p>
				<?php else : ?>
					<div class="doc-frame doc-frame--empty"><?php echo psb_icon( 'doc', 'size-12' ); // phpcs:ignore ?><span>پیش‌نمایش سند به‌زودی</span></div>
				<?php endif; ?>
				<?php if ( $psb_doc ) : ?>
					<a class="btn btn-outline btn-block mt-4" href="<?php echo esc_url( $psb_doc['url'] ); ?>" target="_blank" rel="noopener">
						<?php echo psb_icon( 'doc', 'size-[18px]' ); // phpcs:ignore ?>
						مشاهده فایل کامل<?php echo $psb_doc['size'] || $psb_doc['is_pdf'] ? ' (' . esc_html( trim( ( $psb_doc['is_pdf'] ? 'PDF' : 'تصویر' ) . ( $psb_doc['size'] ? '، ' . $psb_doc['size'] : '' ) ) ) . ')' : ''; ?>
					</a>
				<?php endif; ?>
			</div>
		</aside>
	</div>
</section>
<?php
$psb_more = get_posts(
	array(
		'post_type'    => $psb_type,
		'post_status'  => 'publish',
		'numberposts'  => 4,
		'post__not_in' => array( $psb_id ),
	)
);
if ( $psb_more ) :
	?>
	<div class="border-t border-line bg-white">
		<div class="container">
			<?php
			get_template_part(
				'template-parts/certificates/related-credentials',
				null,
				array(
					'posts' => $psb_more,
					'id'    => 'cred-more-title',
					'title' => $args['more_title'] ?? 'اسناد دیگر',
				)
			);
			?>
		</div>
	</div>
<?php endif; ?>
