<?php
/**
 * Single project (CPT portfolio): hero image, project spec table, description, gallery rail,
 * SLD download, customer letters of this project, related credentials, other projects.
 *
 * @package bluebox
 */

get_header();
while ( have_posts() ) :
	the_post();
	$psb_id     = get_the_ID();
	$psb_client = psb_field_text( 'client_name', $psb_id );
	$psb_loc    = psb_field_text( 'project_location', $psb_id );
	$psb_year   = psb_fa_num( psb_field_text( 'execution_year', $psb_id ) );
	$psb_sector = psb_field_text( 'industry_sector', $psb_id );
	$psb_specs  = array_filter(
		array(
			'کارفرما'                => $psb_client,
			'محل اجرا'               => $psb_loc,
			'سال اجرا'               => $psb_year,
			'حوزه کاربرد'            => $psb_sector,
			'سطح ولتاژ'              => psb_field_text( 'voltage_level', $psb_id ),
			'نوع و ساختار تابلو'     => psb_field_text( 'panel_type', $psb_id ),
			'جریان نامی'             => psb_field_text( 'rated_current', $psb_id ),
			'جریان اتصال کوتاه'      => psb_field_text( 'short_circuit_current', $psb_id ),
			'درجه حفاظت'             => psb_field_text( 'ip_rating', $psb_id ),
			'استاندارد مرجع'         => psb_field_text( 'standards_compliance', $psb_id ),
		),
		'strlen'
	);
	$psb_sld    = psb_doc( psb_field( 'sld_document', null, $psb_id ) );
	$psb_logo   = psb_field( 'brand-logo', null, $psb_id );
	$psb_thumb  = get_post_thumbnail_id();
	$psb_lead   = has_excerpt() ? wp_strip_all_tags( get_the_excerpt() ) : implode( ' · ', array_filter( array( $psb_client, $psb_loc, $psb_year ) ) );

	// Hero media: sharp photo (cover) / low-res photo at natural size in a frame / sector illustration «تصویر نمایشی».
	$psb_hero_media = '';
	if ( function_exists( 'psb_single_image' ) ) {
		$psb_hm = psb_single_image(
			$psb_id,
			600,
			'4 / 3',
			array(
				'attr' => array(
					'alt'           => get_the_title(),
					'sizes'         => '(min-width: 1024px) 600px, 100vw',
					'loading'       => 'eager',
					'fetchpriority' => 'high',
					'class'         => 'aspect-[4/3] w-full rounded-card object-cover',
				),
			)
		);
		if ( 'photo' === $psb_hm['mode'] ) {
			$psb_hero_media = $psb_hm['html'];
		} elseif ( 'lowres' === $psb_hm['mode'] ) {
			$psb_hero_media = '<div class="ph-frame ph-frame--hero">' . str_replace( array( 'aspect-[4/3] w-full rounded-card object-cover', ' w-full' ), '', $psb_hm['html'] ) . '</div>';
		} else {
			$psb_hero_media = '<div class="ph-frame--hero overflow-hidden rounded-card border border-line">' . str_replace( 'aspect-[4/3] w-full rounded-card object-cover', 'size-full', $psb_hm['html'] ) . '</div>';
		}
	}

	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'eyebrow' => $psb_sector ? 'پروژه · ' . $psb_sector : 'پروژه',
			'lead'    => $psb_lead,
			'image'   => get_post_thumbnail_id(),
			'variant' => $psb_hero_media ? 'split' : 'light',
			'media'   => $psb_hero_media,
		)
	);
	?>
	<section class="container py-section-sm lg:py-section">
		<div class="inner-grid">
			<div class="inner-main">

				<?php if ( $psb_specs ) : ?>
					<div class="spec-table-wrap mb-10">
						<table class="spec-table">
							<caption>مشخصات فنی پروژه</caption>
							<tbody>
								<?php foreach ( $psb_specs as $psb_label => $psb_value ) : ?>
									<tr><th scope="row"><?php echo esc_html( $psb_label ); ?></th><td><?php echo psb_lat_html( $psb_value ); // phpcs:ignore -- escaped in helper ?></td></tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<?php if ( '' !== trim( get_the_content() ) ) : ?>
					<div class="article-prose"><?php the_content(); ?></div>
				<?php endif; ?>

				<?php
				get_template_part(
					'template-parts/global/photo-gallery',
					null,
					array(
						'images' => psb_field( 'project_gallery', array(), $psb_id ),
						'title'  => 'گالری تصاویر پروژه',
						'id'     => 'project-gallery-title',
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

			<aside class="inner-aside" aria-label="خلاصه پروژه">
				<div class="panel">
					<?php if ( $psb_logo ) : ?>
						<div class="mb-4 flex h-16 items-center"><?php echo psb_image( $psb_logo, 'medium', array( 'alt' => $psb_client ? 'لوگوی ' . $psb_client : get_the_title(), 'class' => 'max-h-16 w-auto object-contain' ) ); // phpcs:ignore ?></div>
					<?php endif; ?>
					<dl class="spec-list">
						<?php foreach ( array_slice( $psb_specs, 0, 4, true ) as $psb_label => $psb_value ) : ?>
							<div><dt><?php echo esc_html( $psb_label ); ?></dt><dd><?php echo psb_lat_html( $psb_value ); // phpcs:ignore -- escaped in helper ?></dd></div>
						<?php endforeach; ?>
					</dl>
					<?php if ( $psb_sld ) : ?>
						<a class="doc-link mt-4" href="<?php echo esc_url( $psb_sld['url'] ); ?>" target="_blank" rel="noopener">
							<span class="icon-tile"><?php echo psb_icon( 'doc' ); // phpcs:ignore ?></span>
							<span class="min-w-0 flex-1"><span class="block font-bold text-dark">نقشه تک‌خطی / کاتالوگ</span><span class="doc-link-meta"><?php echo esc_html( psb_doc_label( $psb_sld ) ); ?></span></span>
						</a>
					<?php endif; ?>
				</div>
				<div class="panel">
					<p class="panel-title">استعلام قیمت برای پروژه مشابه</p>
					<p class="text-sm leading-7 text-slate-600">نقشه تک‌خطی یا فهرست بارها را ارسال کنید تا پیشنهاد فنی و قیمت تهیه شود.</p>
					<a class="btn btn-primary btn-block mt-4" href="<?php echo esc_url( psb_quote_url() ); ?>">ثبت درخواست استعلام</a>
				</div>
			</aside>
		</div>
	</section>

	<?php
	$psb_letters = function_exists( 'psb_project_letters' ) ? psb_project_letters( $psb_id, 6 ) : array();
	if ( $psb_letters ) :
		?>
		<section class="border-t border-line bg-white py-section-sm" aria-labelledby="project-letters-title">
			<div class="container">
				<?php
				get_template_part(
					'template-parts/global/section-head',
					null,
					array(
						'id'      => 'project-letters-title',
						'eyebrow' => 'رضایت کارفرما',
						'title'   => 'نامه‌های کارفرما درباره این پروژه',
						'link'    => array(
							'url'   => psb_archive_url( 'customer_letter', '/customer-letters/' ),
							'label' => 'همه رضایت‌نامه‌ها',
						),
					)
				);
				?>
				<div class="rail [--rail-item:82%] sm:[--rail-item:46%] lg:grid lg:grid-flow-row lg:grid-cols-3 lg:gap-6 lg:overflow-visible">
					<?php
					foreach ( $psb_letters as $psb_letter ) {
						get_template_part( 'template-parts/letters/letter-card', null, array( 'post' => $psb_letter->ID ) );
					}
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$psb_others = get_posts( array( 'post_type' => 'portfolio', 'post_status' => 'publish', 'numberposts' => 3, 'post__not_in' => array( $psb_id ) ) );
	if ( $psb_others ) :
		?>
		<section class="border-t border-line py-section-sm" aria-labelledby="project-more-title">
			<div class="container">
				<?php
				get_template_part(
					'template-parts/global/section-head',
					null,
					array(
						'id'    => 'project-more-title',
						'title' => 'پروژه‌های دیگر',
						'link'  => array(
							'url'   => psb_archive_url( 'portfolio', '/portfolios/' ),
							'label' => 'همه پروژه‌ها',
						),
					)
				);
				?>
				<div class="rail [--rail-item:82%] sm:[--rail-item:46%] lg:grid lg:grid-flow-row lg:grid-cols-3 lg:gap-6 lg:overflow-visible">
					<?php
					foreach ( $psb_others as $psb_o ) {
						get_template_part( 'template-parts/portfolio/project-card', null, array( 'post' => $psb_o->ID ) );
					}
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
