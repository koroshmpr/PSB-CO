<?php
/**
 * Single product — B2B catalogue page (no price, cart, ratings or reviews).
 * Hero + gallery + key facts, anchor nav, description, related credentials, FAQ, downloads,
 * related products/articles, sticky mobile CTA «استعلام قیمت این محصول».
 *
 * @package bluebox
 */

get_header();

while ( have_posts() ) :
	the_post();
	$psb_id      = get_the_ID();
	$psb_product = function_exists( 'wc_get_product' ) ? wc_get_product( $psb_id ) : null;
	$psb_slug    = urldecode( get_post_field( 'post_name', $psb_id ) );
	$psb_quote   = psb_product_quote_url( $psb_id ); // ?product=<slug>&type=<rfq key>
	$psb_title   = get_the_title();
	$psb_pcat    = psb_product_primary_cat( $psb_id );
	$psb_cats    = $psb_pcat ? array( $psb_pcat->name ) : array();
	$psb_short   = trim( wp_strip_all_tags( $psb_product ? $psb_product->get_short_description() : get_the_excerpt() ) );
	$psb_content = (string) get_post_field( 'post_content', $psb_id );
	$psb_phone   = psb_primary_phone();

	// Key facts: visible, non-empty attributes only.
	$psb_specs = array();
	if ( $psb_product ) {
		foreach ( $psb_product->get_attributes() as $psb_attr ) {
			if ( ! is_object( $psb_attr ) || ! $psb_attr->get_visible() ) {
				continue;
			}
			$psb_vals = $psb_attr->is_taxonomy() ? wc_get_product_terms( $psb_id, $psb_attr->get_name(), array( 'fields' => 'names' ) ) : $psb_attr->get_options();
			$psb_vals = array_filter( array_map( 'trim', (array) $psb_vals ), function ( $v ) {
				return '' !== $v && ! in_array( $v, array( 'نامعلوم', '-', '—' ), true );
			} );
			if ( $psb_vals ) {
				$psb_specs[ wc_attribute_label( $psb_attr->get_name(), $psb_product ) ] = implode( '، ', $psb_vals );
			}
		}
		if ( $psb_product->has_dimensions() ) {
			$psb_specs['ابعاد'] = wp_strip_all_tags( wc_format_dimensions( $psb_product->get_dimensions( false ) ) );
		}
		if ( $psb_product->has_weight() ) {
			$psb_specs['وزن'] = $psb_product->get_weight() . ' ' . get_option( 'woocommerce_weight_unit' );
		}
	}
	$psb_standards = psb_extract_standards( $psb_short . ' ' . $psb_content, 6 );

	// Gallery: featured image + product gallery.
	$psb_images = array_filter( array_merge( array( get_post_thumbnail_id( $psb_id ) ), $psb_product ? $psb_product->get_gallery_image_ids() : array() ) );
	$psb_images = array_values( array_unique( array_map( 'intval', $psb_images ) ) );
	// Real photos (featured + gallery) go to the «تصاویر واقعی» section; the hero always shows the
	// product's line illustration (owner decision 2026-11-27). Blocked renders are never shown.
	$psb_images = array_values( array_filter( $psb_images, function ( $i ) { return wp_attachment_is_image( $i ) && ! ( function_exists( 'psb_image_blocked' ) && psb_image_blocked( $i ) ); } ) );
	if ( $psb_images ) {
		psb_lightbox();
	}

	// Downloads: catalogue PDF from known ACF names, attached PDFs, WooCommerce downloads.
	$psb_downloads = array();
	foreach ( array( 'catalog_pdf', 'catalogue_pdf', 'catalog', 'catalogue', 'brochure_pdf', 'datasheet' ) as $psb_f ) {
		$psb_doc = psb_doc( psb_field( $psb_f, null, $psb_id ) );
		if ( $psb_doc ) {
			$psb_downloads[ $psb_doc['url'] ] = $psb_doc;
		}
	}
	foreach ( get_attached_media( 'application/pdf', $psb_id ) as $psb_att ) {
		$psb_doc = psb_doc( $psb_att->ID );
		if ( $psb_doc ) {
			$psb_downloads[ $psb_doc['url'] ] = $psb_doc;
		}
	}
	if ( $psb_product && $psb_product->is_downloadable() ) {
		foreach ( $psb_product->get_downloads() as $psb_dl ) {
			$psb_doc = psb_doc( $psb_dl->get_file() );
			if ( $psb_doc ) {
				$psb_doc['title']                 = $psb_dl->get_name() ? $psb_dl->get_name() : $psb_doc['title'];
				$psb_downloads[ $psb_doc['url'] ] = $psb_doc;
			}
		}
	}

	$psb_creds = psb_related_credentials( $psb_id, $psb_title . ' ' . implode( ' ', $psb_cats ) );
	$psb_faq   = psb_field( 'faq_list', array(), $psb_id );
	$psb_faq   = is_array( $psb_faq ) ? $psb_faq : array();

	// Spec table (always rendered): built only from real data — attributes, standards in the
	// description, related type-test reports and the owner-confirmed warranty / after-sales facts.
	$psb_ip  = array();
	$psb_sys = '';
	foreach ( $psb_creds as $psb_c ) {
		if ( 'type-test' !== $psb_c->post_type ) {
			continue;
		}
		if ( preg_match( '/IP\s?\d{2}/i', $psb_c->post_title, $psb_m ) ) {
			$psb_ip[] = strtoupper( str_replace( ' ', '', $psb_m[0] ) );
		}
		if ( false !== stripos( $psb_c->post_title, 'SYStem6' ) ) {
			$psb_sys = 'SAREL SYStem6';
		}
	}
	$psb_build = array_values( array_filter( $psb_standards, function ( $c ) {
		return 0 !== strpos( $c, 'IP' );
	} ) );
	$psb_table = array();
	if ( $psb_cats ) {
		$psb_table['گروه محصول'] = array( 'text', implode( '، ', $psb_cats ) );
	}
	foreach ( $psb_specs as $psb_label => $psb_value ) {
		if ( 'استاندارد' === trim( $psb_label ) ) {
			$psb_table['استاندارد'] = array( 'chips', array_map( 'trim', explode( '،', $psb_value ) ) );
			continue;
		}
		$psb_table[ $psb_label ] = array( 'text', $psb_value );
	}
	if ( $psb_build && ! isset( $psb_table['استاندارد'] ) ) {
		$psb_table['استاندارد ساخت'] = array( 'chips', $psb_build );
	}
	if ( $psb_ip ) {
		$psb_table['درجه حفاظت آزمون‌شده'] = array( 'chips', array_unique( $psb_ip ) );
	}
	if ( $psb_sys ) {
		$psb_table['سیستم / لیسانس'] = array( 'chips', array( $psb_sys ) );
	}
	$psb_table['ساخت'] = array( 'text', 'سفارشی، بر اساس نقشه تک‌خطی و مشخصات پروژه' );
	if ( $psb_pcat && $psb_pcat->parent ) {
		$psb_parent_cat = get_term( $psb_pcat->parent, 'product_cat' );
		if ( $psb_parent_cat && ! is_wp_error( $psb_parent_cat ) ) {
			$psb_table['گروه محصول'] = array( 'text', $psb_parent_cat->name . ' / ' . $psb_pcat->name );
		}
	}
	$psb_table['گارانتی'] = array( 'text', psb_fa_num( PSB_WARRANTY_MONTHS ) . ' ماه' );
	$psb_table['خدمات پس از فروش'] = array( 'text', psb_fa_num( PSB_SERVICE_YEARS ) . ' سال' );

	$psb_nav = array(
		'overview' => 'معرفی',
		'specs'    => 'خلاصه مشخصات',
	);
	if ( $psb_images ) {
		$psb_nav['photos'] = 'تصاویر واقعی';
	}
	if ( $psb_creds ) {
		$psb_nav['credentials'] = 'تأییدیه‌ها و آزمون‌ها';
	}
	if ( $psb_faq ) {
		$psb_nav['faq'] = 'پرسش‌ها';
	}
	if ( $psb_downloads ) {
		$psb_nav['downloads'] = 'دانلود';
	}
	?>

	<section class="page-hero page-hero--light">
		<div class="container">
			<?php get_template_part( 'template-parts/global/breadcrumb' ); ?>
			<div class="mt-5 grid items-start gap-8 lg:grid-cols-12 lg:gap-6">
				<div class="min-w-0 lg:col-span-6 lg:pe-6">
					<?php if ( $psb_cats ) : ?>
						<span class="eyebrow"><?php echo esc_html( $psb_cats[0] ); ?></span>
					<?php endif; ?>
					<h1 class="page-hero-title"><?php echo psb_lat_html( $psb_title ); // phpcs:ignore -- escaped in helper ?></h1>
					<?php if ( $psb_short ) : ?>
						<p class="page-hero-lead"><?php echo psb_lat_html( $psb_short ); // phpcs:ignore -- escaped in helper ?></p>
					<?php endif; ?>
					<?php
					// Key facts from the product attributes (only what is filled) + owner-confirmed warranty.
					$psb_facts = array();
					$psb_volt  = psb_product_attr( $psb_id, 'سطح ولتاژ' );
					$psb_inst  = psb_product_attr( $psb_id, 'نوع نصب' );
					$psb_std   = psb_product_attr( $psb_id, 'استاندارد' );
					$psb_lead  = psb_product_attr( $psb_id, 'زمان ساخت' );
					if ( ! $psb_lead ) {
						$psb_lead = psb_product_attr( $psb_id, 'زمان تحویل' );
					}
					if ( $psb_volt ) {
						$psb_facts[] = array( 'bolt', 'سطح ولتاژ', psb_lat_html( implode( '، ', $psb_volt ) ) );
					}
					if ( $psb_std ) {
						$psb_facts[] = array( 'seal', 'استاندارد', implode( ' ', array_map( function ( $c ) {
							return '<span class="badge badge-standard lat">' . esc_html( $c ) . '</span>';
						}, $psb_std ) ) );
					} elseif ( $psb_standards ) {
						$psb_facts[] = array( 'seal', 'استاندارد', implode( ' ', array_map( function ( $c ) {
							return '<span class="badge badge-standard lat">' . esc_html( $c ) . '</span>';
						}, array_slice( $psb_standards, 0, 3 ) ) ) );
					}
					if ( $psb_inst ) {
						$psb_facts[] = array( 'cabinet', 'نوع نصب', psb_lat_html( implode( '، ', $psb_inst ) ) );
					}
					if ( $psb_lead ) {
						$psb_facts[] = array( 'clock', 'زمان ساخت', esc_html( psb_fa_num( implode( '، ', $psb_lead ) ) ) );
					}
					$psb_facts[] = array( 'warranty', 'گارانتی', esc_html( psb_fa_num( PSB_WARRANTY_MONTHS ) . ' ماه گارانتی و ' . psb_fa_num( PSB_SERVICE_YEARS ) . ' سال خدمات پس از فروش' ) );
					$psb_facts[] = array( 'drawing', 'ساخت', 'سفارشی، بر اساس نقشه تک‌خطی و مشخصات پروژه' );
					?>
					<dl class="product-facts mt-6">
						<?php foreach ( $psb_facts as $psb_f ) : ?>
							<div class="product-fact">
								<dt><?php echo psb_icon( $psb_f[0], 'size-5 shrink-0 text-slate-500' ); // phpcs:ignore ?><span><?php echo esc_html( $psb_f[1] ); ?></span></dt>
								<dd><?php echo $psb_f[2]; // phpcs:ignore -- escaped above ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
					<div class="mt-7 flex flex-wrap gap-3">
						<a class="btn btn-primary btn-lg max-sm:w-full" href="<?php echo esc_url( $psb_quote ); ?>">استعلام قیمت این محصول <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></a>
						<?php if ( $psb_phone ) : ?>
							<a class="btn btn-outline btn-lg max-sm:w-full" href="<?php echo esc_url( $psb_phone['href'] ); ?>"><?php echo psb_icon( 'phone' ); // phpcs:ignore ?><span dir="ltr"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></span></a>
						<?php endif; ?>
					</div>
				</div>

				<div class="min-w-0 max-lg:order-first lg:col-span-6">
					<div class="product-gallery-main flex items-center justify-center text-slate-300"><?php echo function_exists( 'psb_placeholder_img' ) ? psb_placeholder_img( psb_placeholder_key( $psb_id ), array( 'class' => 'size-full', 'alt' => $psb_title, 'loading' => 'eager', 'fetchpriority' => 'high' ) ) : psb_icon( 'bolt', 'size-12' ); // phpcs:ignore ?></div>
				</div>
			</div>
		</div>
	</section>

	<nav class="anchor-nav" aria-label="بخش‌های صفحه" data-anchor-nav>
		<div class="container flex gap-6">
			<?php foreach ( $psb_nav as $psb_anchor => $psb_label ) : ?>
				<a href="#<?php echo esc_attr( $psb_anchor ); ?>"><?php echo esc_html( $psb_label ); ?></a>
			<?php endforeach; ?>
		</div>
	</nav>

	<div class="container py-section-sm lg:py-section">
		<div class="inner-grid">
			<div class="inner-main">
				<section id="overview" class="scroll-mt-32" aria-label="معرفی">
					<?php if ( '' !== trim( $psb_content ) ) : ?>
						<div class="article-prose" data-clamp><?php echo psb_lat_content( apply_filters( 'the_content', get_the_content() ) ); // phpcs:ignore -- filtered post content ?></div>
						<button type="button" class="btn btn-outline btn-block mt-4 hidden" data-clamp-toggle aria-expanded="false">ادامه توضیحات</button>
						<script>
						(function(){var c=document.querySelector('[data-clamp]'),b=document.querySelector('[data-clamp-toggle]');if(!c||!b||window.matchMedia('(min-width: 64rem)').matches||c.scrollHeight<1400)return;c.classList.add('is-clamped');b.classList.remove('hidden');b.addEventListener('click',function(){c.classList.remove('is-clamped');b.remove();});})();
						</script>
					<?php elseif ( $psb_short ) : ?>
						<p class="text-lead text-slate-700"><?php echo esc_html( $psb_short ); ?></p>
					<?php endif; ?>
				</section>

				<section id="specs" class="mt-12 scroll-mt-32" aria-labelledby="product-specs-title">
					<h2 id="product-specs-title" class="section-title">خلاصه مشخصات</h2>
					<table class="spec-table mt-6">
						<caption class="sr-only">مشخصات فنی <?php echo esc_html( $psb_title ); ?></caption>
						<tbody>
							<?php foreach ( $psb_table as $psb_label => $psb_row ) : ?>
								<tr>
									<th scope="row"><?php echo esc_html( $psb_label ); ?></th>
									<td>
										<?php if ( 'chips' === $psb_row[0] ) : ?>
											<span class="flex flex-wrap gap-1.5"><?php foreach ( $psb_row[1] as $psb_chip ) : ?><span class="badge badge-standard lat"><?php echo esc_html( $psb_chip ); ?></span><?php endforeach; ?></span>
										<?php else : ?>
											<?php echo psb_lat_html( $psb_row[1] ); // phpcs:ignore -- escaped in helper ?>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>

				<?php if ( $psb_creds ) : ?>
					<div id="credentials" class="mt-12 scroll-mt-32">
						<?php
						get_template_part(
							'template-parts/certificates/related-credentials',
							null,
							array(
								'posts' => $psb_creds,
								'id'    => 'product-creds-title',
								'title' => 'تأییدیه‌ها و گزارش‌های آزمون مرتبط',
								'class' => '',
							)
						);
						?>
					</div>
				<?php endif; ?>

				<?php if ( $psb_images ) : ?>
					<div id="photos" class="scroll-mt-32">
						<?php get_template_part( 'template-parts/portfolio/gallery-rail', null, array( 'images' => $psb_images, 'title' => 'تصاویر واقعی', 'alt' => $psb_title, 'id' => 'product-photos-title' ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $psb_faq ) : ?>
					<section id="faq" class="mt-12 scroll-mt-32" aria-labelledby="product-faq-title">
						<h2 id="product-faq-title" class="section-title mb-6">پرسش‌های متداول</h2>
						<?php get_template_part( 'template-parts/global/faq-list', null, array( 'items' => $psb_faq ) ); ?>
					</section>
				<?php endif; ?>

				<?php if ( $psb_downloads ) : ?>
					<section id="downloads" class="mt-12 scroll-mt-32" aria-labelledby="product-dl-title">
						<h2 id="product-dl-title" class="section-title mb-6">دانلود کاتالوگ و مدارک</h2>
						<ul class="grid gap-3 sm:grid-cols-2" role="list">
							<?php foreach ( $psb_downloads as $psb_doc ) : ?>
								<li>
									<a class="doc-link" href="<?php echo esc_url( $psb_doc['url'] ); ?>" target="_blank" rel="noopener">
										<?php echo psb_icon( 'doc', 'size-5 shrink-0 text-slate-600' ); // phpcs:ignore ?>
										<span class="min-w-0 flex-1">
											<span class="block truncate font-semibold text-foreground"><?php echo esc_html( $psb_doc['title'] ); ?></span>
											<span class="doc-link-meta"><?php echo esc_html( psb_doc_label( $psb_doc ) ); ?></span>
										</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>
			</div>

			<aside class="inner-aside max-lg:hidden" aria-label="استعلام قیمت">
				<div class="panel">
					<p class="panel-title">استعلام قیمت <?php echo esc_html( $psb_title ); ?></p>
					<p class="text-[0.9375rem] leading-8 text-slate-600">نقشه تک‌خطی یا مشخصات فنی پروژه را ارسال کنید تا پیشنهاد فنی و قیمت تهیه شود.</p>
					<a class="btn btn-primary btn-block mt-5" href="<?php echo esc_url( $psb_quote ); ?>">استعلام قیمت</a>
					<?php if ( $psb_phone ) : ?>
						<a class="link-arrow mt-4" href="<?php echo esc_url( $psb_phone['href'] ); ?>">تماس: <span dir="ltr"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></span></a>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</div>

	<?php
	// «انواع مرتبط»: other products of the same (child) category, then the rest of the catalogue.
	$psb_sib_args = array(
		'post_type'    => 'product',
		'post_status'  => psb_catalogue_statuses(),
		'numberposts'  => 3,
		'post__not_in' => array( $psb_id ),
	);
	$psb_others   = $psb_pcat ? get_posts( $psb_sib_args + array( 'tax_query' => array( array( 'taxonomy' => 'product_cat', 'terms' => $psb_pcat->term_id ) ) ) ) : array(); // phpcs:ignore
	if ( count( $psb_others ) < 3 ) {
		$psb_sib_args['post__not_in'] = array_merge( array( $psb_id ), wp_list_pluck( $psb_others, 'ID' ) );
		$psb_sib_args['numberposts']  = 3 - count( $psb_others );
		$psb_others                   = array_merge( $psb_others, get_posts( $psb_sib_args ) );
	}
	if ( $psb_others ) :
		?>
		<section class="border-t border-line bg-white py-section-sm" aria-labelledby="product-more-title">
			<div class="container">
				<?php
				get_template_part(
					'template-parts/global/section-head',
					null,
					array(
						'id'    => 'product-more-title',
						'title' => 'انواع مرتبط',
						'link'  => array(
							'url'   => $psb_pcat && ! is_wp_error( get_term_link( $psb_pcat ) ) ? get_term_link( $psb_pcat ) : psb_shop_url(),
							'label' => $psb_pcat ? 'همه ' . $psb_pcat->name : 'همه محصولات',
						),
					)
				);
				?>
				<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
					<?php
					foreach ( $psb_others as $psb_p ) {
						get_template_part( 'template-parts/product/card/product-card', null, array( 'post' => $psb_p->ID ) );
					}
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<div class="sticky-cta" role="region" aria-label="استعلام قیمت">
		<a class="btn btn-primary flex-1" href="<?php echo esc_url( $psb_quote ); ?>">استعلام قیمت</a>
		<?php if ( $psb_phone ) : ?>
			<a class="btn btn-outline btn-icon size-12" href="<?php echo esc_url( $psb_phone['href'] ); ?>" aria-label="تماس تلفنی"><?php echo psb_icon( 'phone' ); // phpcs:ignore ?></a>
		<?php endif; ?>
	</div>
	<script>
	(function(){var nav=document.querySelector('[data-anchor-nav]');if(!nav||!('IntersectionObserver' in window))return;var links=nav.querySelectorAll('a'),map={};links.forEach(function(a){var el=document.getElementById(a.getAttribute('href').slice(1));if(el)map[el.id]=a;});
	var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){links.forEach(function(l){l.removeAttribute('aria-current');});map[e.target.id].setAttribute('aria-current','true');}});},{rootMargin:'-30% 0px -60% 0px'});Object.keys(map).forEach(function(id){io.observe(document.getElementById(id));});})();
	</script>
	<?php
endwhile;

get_footer();
