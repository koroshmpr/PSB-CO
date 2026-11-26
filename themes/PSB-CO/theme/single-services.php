<?php
/**
 * Single service / facility (CPT services): features, description, gallery rail, brochure, FAQ, CTA.
 *
 * @package bluebox
 */

get_header();
while ( have_posts() ) :
	the_post();
	$psb_id       = get_the_ID();
	$psb_desc     = trim( (string) psb_field( 'services_description', '', $psb_id ) );
	$psb_features = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) psb_field( 'technical_features', '', $psb_id ) ) ) );
	$psb_stds     = psb_extract_standards( psb_field( 'standards_compliance', '', $psb_id ) . ' ' . get_the_content() );
	$psb_brochure = psb_doc( psb_field( 'brochure_pdf', null, $psb_id ) );
	$psb_thumb    = get_post_thumbnail_id();

	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'eyebrow' => 'خدمات و امکانات تولید',
			'lead'    => $psb_desc,
			'image'   => get_post_thumbnail_id(),
		)
	);
	?>
	<section class="container py-section-sm lg:py-section">
		<div class="inner-grid">
			<div class="inner-main">

				<?php if ( $psb_features ) : ?>
					<section class="mb-10" aria-labelledby="svc-features-title">
						<h2 id="svc-features-title" class="mb-5 text-h3 font-bold text-dark">تجهیزات و قابلیت‌ها</h2>
						<ul class="grid gap-3 sm:grid-cols-2" role="list">
							<?php foreach ( $psb_features as $psb_f ) : ?>
								<li class="flex items-start gap-3 rounded-control border border-line bg-white p-4 text-sm leading-7 text-slate-700">
									<?php echo psb_icon( 'check', 'mt-1 size-4 shrink-0 text-foreground' ); // phpcs:ignore ?>
									<span><?php echo esc_html( $psb_f ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( '' !== trim( get_the_content() ) ) : ?>
					<div class="article-prose"><?php the_content(); ?></div>
				<?php endif; ?>

				<?php
				get_template_part(
					'template-parts/portfolio/gallery-rail',
					null,
					array(
						'images' => psb_field( 'service_gallery', array(), $psb_id ),
						'title'  => 'تصاویر ' . get_the_title(),
						'id'     => 'svc-gallery-title',
					)
				);
				get_template_part(
					'template-parts/global/faq-list',
					null,
					array(
						'title' => 'پرسش‌های متداول',
						'class' => 'mt-12 max-w-prose',
					)
				);
				?>
			</div>

			<aside class="inner-aside" aria-label="اطلاعات تکمیلی">
				<?php if ( $psb_stds ) : ?>
					<div class="panel">
						<p class="panel-title">استانداردها</p>
						<div class="flex flex-wrap gap-1.5"><?php foreach ( $psb_stds as $psb_s ) : ?><span class="badge badge-standard"><?php echo esc_html( $psb_s ); ?></span><?php endforeach; ?></div>
					</div>
				<?php endif; ?>
				<?php if ( $psb_brochure ) : ?>
					<a class="doc-link" href="<?php echo esc_url( $psb_brochure['url'] ); ?>" target="_blank" rel="noopener">
						<span class="icon-tile"><?php echo psb_icon( 'doc' ); // phpcs:ignore ?></span>
						<span class="min-w-0 flex-1"><span class="block font-bold text-dark">کاتالوگ <?php the_title(); ?></span><span class="doc-link-meta"><?php echo esc_html( psb_doc_label( $psb_brochure ) ); ?></span></span>
					</a>
				<?php endif; ?>
				<div class="panel">
					<p class="panel-title">بازدید از کارخانه یا حضور در آزمون</p>
					<p class="text-sm leading-7 text-slate-600">برای هماهنگی بازدید، حضور در آزمون‌های تحویل (FAT) یا دریافت پیشنهاد فنی، با واحد بازرگانی شرکت تماس بگیرید.</p>
					<div class="mt-4 grid gap-2">
						<a class="btn btn-primary btn-block" href="<?php echo esc_url( psb_quote_url() ); ?>">ثبت درخواست استعلام</a>
						<a class="btn btn-ghost btn-block" href="<?php echo esc_url( psb_contact_url() ); ?>">تماس با شرکت</a>
					</div>
				</div>
				<?php
				$psb_others = get_posts( array( 'post_type' => 'services', 'post_status' => 'publish', 'numberposts' => 6, 'post__not_in' => array( $psb_id ) ) );
				if ( $psb_others ) :
					?>
					<nav class="panel" aria-label="سایر امکانات">
						<p class="panel-title">سایر امکانات</p>
						<ul class="flex flex-col gap-1" role="list">
							<?php foreach ( $psb_others as $psb_o ) : ?>
								<li><a class="flex min-h-11 items-center justify-between gap-2 rounded-control px-2 text-sm font-bold text-slate-700 hover:bg-slate-50 hover:text-primary-700" href="<?php echo esc_url( get_permalink( $psb_o ) ); ?>"><?php echo esc_html( get_the_title( $psb_o ) ); ?><?php echo psb_icon( 'chevron', 'size-4 text-slate-500' ); // phpcs:ignore ?></a></li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>
			</aside>
		</div>
	</section>
	<?php
endwhile;
get_footer();
