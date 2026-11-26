<?php
/**
 * Single customer letter: the scan large (lightbox), the transcription, the client, the related project, CTA.
 *
 * @package bluebox
 */

get_header();
while ( have_posts() ) :
	the_post();
	$psb_id   = get_the_ID();
	$psb_l    = psb_letter_data( $psb_id );
	$psb_name = get_the_title();
	$psb_full = $psb_l['scan'] ? wp_get_attachment_image_url( $psb_l['scan'], 'full' ) : '';
	if ( $psb_full ) {
		psb_lightbox();
	}
	$psb_lead = array_filter( array( $psb_l['org'], $psb_l['date'] ? psb_fa_num( $psb_l['date'] ) : '' ) );

	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'title'   => $psb_name,
			'eyebrow' => $psb_l['type'] ? $psb_l['type']->name : 'نامه کارفرما',
			'lead'    => implode( ' · ', $psb_lead ),
		)
	);
	get_template_part( 'template-parts/certificates/cred-nav', null, array( 'current' => 'customer_letter' ) );
	?>
	<section class="container py-section">
		<div class="grid gap-10 lg:grid-cols-[minmax(0,26rem)_minmax(0,1fr)] lg:gap-14">
			<div class="lg:sticky lg:self-start" style="top:calc(var(--header-h,4rem) + var(--wp-admin--admin-bar--height,0px) + 1.5rem)">
				<?php if ( $psb_full ) : ?>
					<button type="button" class="doc-frame w-full cursor-zoom-in" data-lightbox="<?php echo esc_url( $psb_full ); ?>" data-lightbox-title="<?php echo esc_attr( $psb_name ); ?>" <?php echo $psb_l['pdf'] ? 'data-lightbox-pdf="' . esc_url( $psb_l['pdf']['url'] ) . '"' : ''; ?>>
						<?php
						echo psb_image( // phpcs:ignore
							$psb_l['scan'],
							'large',
							array(
								'alt'           => 'اسکن ' . $psb_name,
								'sizes'         => '(min-width: 1024px) 26rem, 92vw',
								'loading'       => 'eager',
								'fetchpriority' => 'high',
							)
						);
						?>
						<span class="sr-only">نمایش اسکن در اندازه بزرگ</span>
					</button>
				<?php else : ?>
					<div class="doc-frame doc-frame--empty"><?php echo psb_icon( 'doc', 'size-12' ); // phpcs:ignore ?></div>
				<?php endif; ?>
				<div class="mt-4 flex flex-wrap gap-2">
					<?php if ( $psb_full ) : ?>
						<button type="button" class="btn btn-outline btn-sm" data-lightbox="<?php echo esc_url( $psb_full ); ?>" data-lightbox-title="<?php echo esc_attr( $psb_name ); ?>"><?php echo psb_icon( 'search', 'size-4' ); // phpcs:ignore ?> بزرگنمایی</button>
					<?php endif; ?>
					<?php if ( $psb_l['pdf'] ) : ?>
						<a class="btn btn-outline btn-sm" href="<?php echo esc_url( $psb_l['pdf']['url'] ); ?>" target="_blank" rel="noopener"><?php echo psb_icon( 'doc', 'size-4' ); // phpcs:ignore ?> فایل PDF <span class="text-xs text-slate-500"><?php echo esc_html( $psb_l['pdf']['size'] ); ?></span></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="min-w-0">
				<dl class="spec-list mb-8 max-w-prose rounded-card border border-line bg-white p-5">
					<?php if ( $psb_l['org'] ) : ?>
						<div><dt>کارفرما / سازمان</dt><dd><?php echo esc_html( $psb_l['org'] ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $psb_l['signer'] ) : ?>
						<div><dt>امضاکننده</dt><dd><?php echo esc_html( $psb_l['signer'] . ( $psb_l['role'] ? ' — ' . $psb_l['role'] : '' ) ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $psb_l['date'] ) : ?>
						<div><dt>تاریخ نامه</dt><dd><?php echo esc_html( psb_fa_num( $psb_l['date'] ) ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $psb_l['type'] ) : ?>
						<div><dt>نوع</dt><dd><?php echo esc_html( $psb_l['type']->name ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $psb_l['project'] ) : ?>
						<div><dt>پروژه</dt><dd><a class="font-bold text-primary-700" href="<?php echo esc_url( get_permalink( $psb_l['project'] ) ); ?>"><?php echo esc_html( get_the_title( $psb_l['project'] ) ); ?></a></dd></div>
					<?php endif; ?>
				</dl>

				<?php if ( $psb_l['quote'] ) : ?>
					<blockquote class="letter-quote mb-8 max-w-prose text-lg"><p><?php echo esc_html( $psb_l['quote'] ); ?></p></blockquote>
				<?php endif; ?>

				<?php if ( '' !== trim( get_the_content() ) ) : ?>
					<h2 class="mb-4 text-h3 font-bold text-dark">متن نامه</h2>
					<div class="article-prose rounded-card border border-line bg-white p-5 sm:p-8">
						<?php the_content(); ?>
					</div>
				<?php endif; ?>

				<?php if ( $psb_l['project'] ) : ?>
					<a class="card card-hover mt-8 max-w-prose flex-row items-center gap-4 p-4" href="<?php echo esc_url( get_permalink( $psb_l['project'] ) ); ?>">
						<span class="icon-tile"><?php echo psb_icon( 'factory' ); // phpcs:ignore ?></span>
						<span class="min-w-0 flex-1">
							<span class="block text-xs text-slate-500">پروژه مرتبط</span>
							<span class="block font-bold text-dark"><?php echo esc_html( get_the_title( $psb_l['project'] ) ); ?></span>
						</span>
						<?php echo psb_icon( 'arrow', 'btn-arrow size-5 text-foreground' ); // phpcs:ignore ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
	$psb_more_count = (int) ( wp_count_posts( 'customer_letter' )->publish ?? 0 );
	if ( $psb_more_count > 1 ) :
		?>
		<section class="border-t border-line bg-white py-section-sm" aria-labelledby="letters-more-title">
			<div class="container">
				<?php
				get_template_part(
					'template-parts/global/section-head',
					null,
					array(
						'id'    => 'letters-more-title',
						'title' => 'نامه‌های دیگر کارفرمایان',
						'link'  => array(
							'url'   => psb_archive_url( 'customer_letter', '/customer-letters/' ),
							'label' => 'همه نامه‌ها',
						),
					)
				);
				get_template_part(
					'template-parts/letters/letters-strip',
					null,
					array(
						'count'   => 3,
						'exclude' => array( $psb_id ),
					)
				);
				?>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
