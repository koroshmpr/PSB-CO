<?php
/**
 * Template Name: درباره ما
 *
 * About: hero, verified stats (ACF about_stats), story (page content), timeline from ۱۳۵۵
 * (facts verified from documents on the site), values (ACF), capabilities, credentials teaser,
 * team and factory gallery (when filled), FAQ.
 *
 * @package bluebox
 */

get_header();

while ( have_posts() ) :
	the_post();
	$psb_id      = get_the_ID();
	$psb_badge   = psb_field( 'about_hero_badge', 'فعال در صنعت تابلوسازی برق از سال ' . psb_fa_num( PSB_FOUNDED_JALALI ), $psb_id );
	$psb_sub     = psb_field( 'about_hero_subtitle', '', $psb_id );
	$psb_stats   = psb_field( 'about_stats', array(), $psb_id );
	$psb_values  = psb_field( 'about_values', array(), $psb_id );
	$psb_owners  = psb_field( 'owners', array(), $psb_id );
	$psb_gallery = psb_field( 'about_factory_gallery', array(), $psb_id );

	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'eyebrow' => $psb_badge,
			'lead'    => $psb_sub,
			'image'   => get_post_thumbnail_id(),
			'actions' => '<a class="btn btn-primary" href="' . esc_url( psb_quote_url() ) . '">استعلام قیمت ' . psb_icon( 'arrow', 'btn-arrow' ) . '</a><a class="btn btn-outline" href="' . esc_url( psb_shop_url() ) . '">محصولات</a>',
		)
	);
	?>

	<?php if ( is_array( $psb_stats ) && $psb_stats ) : ?>
		<section class="border-b border-line bg-white py-section-sm" aria-label="آمار کلیدی">
			<div class="container">
				<dl class="stats">
					<?php foreach ( $psb_stats as $psb_s ) : ?>
						<?php if ( ! empty( $psb_s['number'] ) && ! empty( $psb_s['label'] ) ) : ?>
							<div class="stat">
								<dt class="stat-label order-last"><?php echo esc_html( $psb_s['label'] ); ?></dt>
								<dd class="stat-value"><?php echo esc_html( psb_fa_num( $psb_s['number'] ) ); ?></dd>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</dl>
			</div>
		</section>
	<?php endif; ?>

	<section class="py-section" aria-labelledby="about-story-title">
		<?php
		// Intro photo (spec 6.5): factory gallery first image, else the approved factory photos (1410 test lab, 1726 interior, 1442 CNC).
		$psb_intro_img = ( is_array( $psb_gallery ) && $psb_gallery ) ? $psb_gallery[0] : 0;
		if ( ! $psb_intro_img ) {
			foreach ( array( 1410, 1726, 1442 ) as $psb_try ) {
				if ( wp_attachment_is_image( $psb_try ) && (int) $psb_try !== (int) get_post_thumbnail_id() ) {
					$psb_intro_img = $psb_try;
					break;
				}
			}
		}
		?>
		<div class="container grid items-start gap-10 lg:grid-cols-12 lg:gap-6">
			<div class="min-w-0 lg:col-span-7 lg:pe-10">
				<span class="eyebrow">معرفی شرکت</span>
				<h2 id="about-story-title" class="section-title"><?php echo esc_html( psb_brand_name() ); ?>؛ <?php echo esc_html( psb_fa_num( psb_years_active() ) ); ?> سال تابلوسازی</h2>
				<div class="article-prose mt-6"><?php the_content(); ?></div>
			</div>
			<?php if ( $psb_intro_img ) : ?>
				<figure class="lg:sticky lg:col-span-5" style="top:calc(var(--header-h,4rem) + var(--wp-admin--admin-bar--height,0px) + 2rem)">
					<?php echo psb_image( $psb_intro_img, 'large', array( 'alt' => 'کارخانه ' . psb_brand_name(), 'sizes' => '(min-width: 1024px) 30rem, 92vw', 'class' => 'aspect-[4/5] w-full rounded-card object-cover' ) ); // phpcs:ignore ?>
				</figure>
			<?php endif; ?>
		</div>
	</section>

	<?php
	// Timeline: only facts documented on the site (company-facts.md). Each item links to its source.
	$psb_link = function ( $type, $slug_or_id ) {
		$p = is_numeric( $slug_or_id ) ? get_post( (int) $slug_or_id ) : get_page_by_path( $slug_or_id, OBJECT, $type );
		return ( $p && 'publish' === $p->post_status && $type === $p->post_type ) ? get_permalink( $p ) : psb_archive_url( $type );
	};
	$psb_timeline = apply_filters(
		'psb_about_timeline',
		array(
			array( psb_fa_num( PSB_FOUNDED_JALALI ), 'تأسیس شرکت', 'آغاز فعالیت شرکت تولیدی و صنعتی پارس سوئیچ بورد در طراحی و ساخت تابلوهای برق.', '' ),
			array( '۱۳۶۴', 'پروانه بهره‌برداری از وزارت صنایع', 'صدور کارت شناسایی کارگاه، معادل پروانه بهره‌برداری، برای تولید تابلوهای برق فشار ضعیف.', '' ),
			array( '۱۳۶۸', 'توسعه ظرفیت تولید', 'اصلاح ظرفیت تولید به ۱۲۰۰ سلول در سال در یک شیفت، شامل تابلوهای فشار متوسط، فشار ضعیف و MCC.', '' ),
			array( '۱۳۸۰', 'تأمین‌کننده نمونه صنایع شیر ایران', 'انتخاب به‌عنوان تأمین‌کننده نمونه ماشین‌آلات و تجهیزات صنایع شیر ایران (پگاه).', '' ),
			array( '۱۳۸۲', 'آزمون تابلوهای ۲۴ کیلوولت متال‌کلد', 'قبولی در آزمون استقامت عایقی فرکانس قدرت تابلوهای ۲۴ کیلوولت در آزمایشگاه EPIL.', psb_archive_url( 'type-test' ) ),
			array( '۱۳۸۳', 'آزمون نوعی باس‌داکت ۴۰۰۰ آمپر', 'گواهی آزمون نوعی باس‌داکت 4000 A / 400 V مطابق IEC 60439-1 و IEC 60439-2 از آزمایشگاه EPIL.', psb_archive_url( 'type-test' ) ),
			array( '۱۳۸۵', 'ارزیابی سازندگان تابلو برق', 'کسب تأیید کامل در هر شش گروه، شامل تابلوهای فشار ضعیف و فشار متوسط ثابت و کشویی، در ارزیابی انجمن صنفی تولیدکنندگان تابلوهای برق ایران.', '' ),
			array( '۱۳۹۲', 'ثبت در وندورلیست شرکت ملی مهندسی و ساختمان نفت ایران', 'تابلوهای فشار ضعیف ثابت تا ۴۰۰ ولت.', $psb_link( 'confirmation', 'low-voltage-switchboard-approval-national-iranian-oil-engineering-and-construction-company' ) ),
			array( '۱۳۹۳', 'گزارش‌های آزمون EPIL و لیسانس SAREL', 'آزمون درجه حفاظت IP54 و IP65 بدنه‌ها و حق ساخت تابلوهای فشار متوسط SYStem6.', psb_archive_url( 'type-test' ) ),
			array( '۱۳۹۷', 'آزمون تابلو ایزوله بیمارستانی', 'آزمون ایمنی بر اساس IEC 60601-1 در آزمایشگاه EPIL با نتیجه قبول.', psb_archive_url( 'type-test' ) ),
			array( '۱۳۹۸–۱۳۹۹', 'تأییدیه‌های توانیر و شرکت‌های توزیع نیروی برق', 'درج در فهرست سازندگان تأییدشده توانیر و تأییدیه‌های شرکت‌های توزیع نیروی برق استان تهران و تهران بزرگ.', $psb_link( 'confirmation', 'tavanir-approval' ) ),
			array( psb_fa_num( psb_jalali_year() ), psb_fa_num( psb_years_active() ) . ' سال فعالیت', 'طراحی، ساخت، آزمون و نصب تابلوهای فشار متوسط، فشار ضعیف و فرمان و کنترل برای شبکه توزیع، صنایع و ساختمان‌ها.', '' ),
		)
	);
	?>
	<section class="border-y border-line bg-white py-section" aria-labelledby="about-timeline-title">
		<div class="container grid gap-10 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:gap-16">
			<?php
			get_template_part(
				'template-parts/global/section-head',
				null,
				array(
					'id'      => 'about-timeline-title',
					'eyebrow' => 'مسیر شرکت',
					'title'   => 'از ' . psb_fa_num( PSB_FOUNDED_JALALI ) . ' تا امروز',
					'lead'    => 'مهم‌ترین نقطه‌های مسیر شرکت، بر اساس اسنادی که در همین سایت منتشر شده است.',
				)
			);
			?>
			<ol class="timeline">
				<?php
				$psb_last = count( $psb_timeline ) - 1;
				foreach ( $psb_timeline as $psb_i => $psb_t ) :
					?>
					<li class="timeline-item <?php echo $psb_i === $psb_last ? 'is-current' : ''; ?>">
						<span class="timeline-year"><?php echo esc_html( $psb_t[0] ); ?></span>
						<h3 class="timeline-title"><?php echo esc_html( $psb_t[1] ); ?></h3>
						<p class="timeline-text"><?php echo esc_html( $psb_t[2] ); ?>
							<?php if ( ! empty( $psb_t[3] ) ) : ?>
								<a class="link-arrow mt-1" href="<?php echo esc_url( $psb_t[3] ); ?>">مشاهده سند <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></a>
							<?php endif; ?>
						</p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<?php if ( is_array( $psb_values ) && $psb_values ) : ?>
		<section class="py-section" aria-labelledby="about-values-title">
			<div class="container">
				<?php
				get_template_part(
					'template-parts/global/section-head',
					null,
					array(
						'id'      => 'about-values-title',
						'eyebrow' => 'ارزش‌ها',
						'title'   => 'آنچه به آن متعهدیم',
											)
				);
				$psb_icons = array( 'iso', 'shield', 'users', 'target', 'cog', 'eye' );
				?>
				<div class="grid gap-gutter md:grid-cols-3">
					<?php foreach ( $psb_values as $psb_i => $psb_v ) : ?>
						<?php
						if ( empty( $psb_v['title'] ) ) {
							continue;
						}
						$psb_icon = ( ! empty( $psb_v['icon'] ) && psb_icon( sanitize_key( $psb_v['icon'] ) ) ) ? sanitize_key( $psb_v['icon'] ) : $psb_icons[ $psb_i % count( $psb_icons ) ];
						?>
						<article class="panel">
							<?php echo psb_icon( $psb_icon, 'mb-4 size-7 text-slate-700' ); // phpcs:ignore ?>
							<h3 class="text-h3 font-bold text-foreground"><?php echo esc_html( $psb_v['title'] ); ?></h3>
							<?php if ( ! empty( $psb_v['description'] ) ) : ?>
								<p class="mt-2 text-sm leading-7 text-slate-600"><?php echo esc_html( $psb_v['description'] ); ?></p>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="bg-tech-grid-light bg-slate-50 py-section" aria-labelledby="about-cap-title">
		<div class="container">
			<?php
			get_template_part(
				'template-parts/global/section-head',
				null,
				array(
					'id'      => 'about-cap-title',
					'eyebrow' => 'توانمندی‌ها',
					'title'   => 'محصولات و امکانات تولید',
					'lead'    => 'گروه‌های تابلویی که در فهرست سازندگان توانیر برای شرکت درج شده و امکاناتی که همه مراحل ساخت در آن انجام می‌شود.',
				)
			);
			$psb_products = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 6 ) );
			$psb_services = get_posts( array( 'post_type' => 'services', 'post_status' => 'publish', 'numberposts' => 6 ) );
			$psb_tavanir  = array( 'تابلو سنجش و توزیع', 'تابلو فشار ضعیف ثابت', 'تابلو فشار متوسط سنتی', 'تابلو فشار متوسط کمپکت', 'تابلو کنترل و پروسس', 'تابلو حفاظت و کنترل نیروگاهی' );
			?>
			<div class="grid gap-gutter lg:grid-cols-3">
				<div class="rounded-card border border-line bg-white p-6">
					<h3 class="text-base font-bold text-dark">گروه‌های درج‌شده در فهرست توانیر</h3>
					<ul class="mt-4 grid gap-2 text-sm text-slate-700" role="list">
						<?php foreach ( $psb_tavanir as $psb_t ) : ?>
							<li class="flex items-center gap-2"><?php echo psb_icon( 'check', 'size-4 text-foreground' ); // phpcs:ignore ?><?php echo esc_html( $psb_t ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php if ( $psb_products ) : ?>
					<div class="rounded-card border border-line bg-white p-6 max-lg:hidden">
						<h3 class="text-base font-bold text-dark">محصولات</h3>
						<ul class="mt-4 grid gap-1" role="list">
							<?php foreach ( $psb_products as $psb_p ) : ?>
								<li><a class="flex min-h-11 items-center justify-between rounded-control px-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary-700" href="<?php echo esc_url( get_permalink( $psb_p ) ); ?>"><?php echo esc_html( get_the_title( $psb_p ) ); ?><?php echo psb_icon( 'chevron', 'size-4 text-slate-500' ); // phpcs:ignore ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
				<?php if ( $psb_services ) : ?>
					<div class="rounded-card border border-line bg-white p-6 max-lg:hidden">
						<h3 class="text-base font-bold text-dark">کارخانه و آزمایشگاه</h3>
						<ul class="mt-4 grid gap-1" role="list">
							<?php foreach ( $psb_services as $psb_p ) : ?>
								<li><a class="flex min-h-11 items-center justify-between rounded-control px-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary-700" href="<?php echo esc_url( get_permalink( $psb_p ) ); ?>"><?php echo esc_html( get_the_title( $psb_p ) ); ?><?php echo psb_icon( 'chevron', 'size-4 text-slate-500' ); // phpcs:ignore ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="py-section" aria-labelledby="about-creds-title">
		<div class="container">
			<?php
			get_template_part(
				'template-parts/global/section-head',
				null,
				array(
					'id'      => 'about-creds-title',
					'eyebrow' => 'اعتبارنامه‌ها',
					'title'   => 'تأییدیه‌ها و گزارش‌های آزمون',
					'link'    => array(
						'url'   => psb_archive_url( 'confirmation' ),
						'label' => 'همه تأییدیه‌ها',
					),
				)
			);
			?>
			<?php
			$psb_docs = get_posts(
				array(
					'post_type'   => array_values( array_filter( array( 'confirmation', 'type-test' ), 'post_type_exists' ) ),
					'post_status' => 'publish',
					'numberposts' => 6,
					'orderby'     => 'type',
					'order'       => 'ASC',
				)
			);
			?>
			<?php if ( $psb_docs ) : ?>
				<div class="register border-t-2 border-foreground">
					<?php
					foreach ( $psb_docs as $psb_doc ) {
						get_template_part( 'template-parts/certificates/reg-row', null, array( 'post' => $psb_doc->ID ) );
					}
					?>
				</div>
			<?php endif; ?>
			<?php if ( PSB_ISO_9001 ) : ?>
				<p class="mt-6 text-[0.9375rem] text-slate-700">سیستم مدیریت کیفیت: دارای گواهینامه <span class="lat">ISO 9001</span></p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( is_array( $psb_owners ) && $psb_owners ) : ?>
		<section class="border-t border-line bg-white py-section-sm" aria-labelledby="about-team-title">
			<div class="container">
				<?php get_template_part( 'template-parts/global/section-head', null, array( 'id' => 'about-team-title', 'eyebrow' => 'مدیریت', 'title' => 'مدیران شرکت' ) ); ?>
				<ul class="grid gap-gutter sm:grid-cols-2 lg:grid-cols-4" role="list">
					<?php foreach ( $psb_owners as $psb_o ) : ?>
						<?php
						if ( empty( $psb_o['name'] ) ) {
							continue;
						}
						?>
						<li class="panel flex items-center gap-4">
							<?php if ( ! empty( $psb_o['image'] ) ) : ?>
								<?php echo psb_image( $psb_o['image'], 'thumbnail', array( 'alt' => $psb_o['name'], 'class' => 'size-16 shrink-0 rounded-control object-cover' ) ); // phpcs:ignore ?>
							<?php else : ?>
								
							<?php endif; ?>
							<div>
								<h3 class="text-base font-bold text-dark"><?php echo esc_html( $psb_o['name'] ); ?></h3>
								<?php if ( ! empty( $psb_o['position'] ) ) : ?>
									<p class="text-xs text-primary-700"><?php echo esc_html( $psb_o['position'] ); ?></p>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( is_array( $psb_gallery ) && $psb_gallery ) : ?>
		<div class="container pb-section-sm">
			<?php get_template_part( 'template-parts/portfolio/gallery-rail', null, array( 'images' => $psb_gallery, 'title' => 'تصاویر کارخانه', 'alt' => 'کارخانه ' . psb_brand_name(), 'id' => 'about-gallery-title' ) ); ?>
		</div>
	<?php endif; ?>

	<?php
	$psb_faq = psb_field( 'faq_list', array(), $psb_id );
	if ( is_array( $psb_faq ) && $psb_faq ) :
		?>
		<section class="border-t border-line py-section-sm">
			<div class="container max-w-content">
				<?php get_template_part( 'template-parts/global/faq-list', null, array( 'items' => $psb_faq, 'title' => 'پرسش‌های متداول' ) ); ?>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
