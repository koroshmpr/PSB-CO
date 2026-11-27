<?php
/**
 * Template Name: درباره ما
 *
 * About: hero, stats, story (page content), timeline, values, capabilities, credentials teaser,
 * team, factory gallery, FAQ. Every text and photo comes from the page fields
 * (inc/page-fields/about.php); a cleared field hides its element.
 *
 * @package bluebox
 */

get_header();

while ( have_posts() ) :
	the_post();
	$psb_id      = get_the_ID();
	$psb_t       = function ( $name ) use ( $psb_id ) {
		return psb_pf_text( psb_pf( $name, $psb_id ) );
	};
	$psb_badge   = $psb_t( 'about_hero_badge' );
	$psb_sub     = $psb_t( 'about_hero_subtitle' );
	$psb_hero    = psb_pf_image_id( psb_pf( 'about_hero_image', $psb_id ) );
	$psb_stats   = psb_pf_rows( 'about_stats', $psb_id );
	$psb_values  = psb_pf_rows( 'about_values', $psb_id );
	$psb_owners  = psb_pf_rows( 'owners', $psb_id );
	$psb_gallery = array_values( array_filter( array_map( 'psb_pf_image_id', (array) psb_pf( 'about_factory_gallery', $psb_id ) ) ) );
	$psb_actions = '';
	if ( $psb_t( 'about_hero_btn1' ) ) {
		$psb_actions .= '<a class="btn btn-primary" href="' . esc_url( psb_quote_url() ) . '">' . esc_html( $psb_t( 'about_hero_btn1' ) ) . ' ' . psb_icon( 'arrow', 'btn-arrow' ) . '</a>';
	}
	if ( $psb_t( 'about_hero_btn2' ) ) {
		$psb_actions .= '<a class="btn ' . ( $psb_hero ? 'btn-on-dark' : 'btn-outline' ) . '" href="' . esc_url( psb_shop_url() ) . '">' . esc_html( $psb_t( 'about_hero_btn2' ) ) . '</a>';
	}

	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'eyebrow' => $psb_badge,
			'lead'    => $psb_sub,
			'image'   => $psb_hero,
			'variant' => $psb_hero ? 'photo' : 'light',
			'actions' => $psb_actions,
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
								<dd class="stat-value"><?php echo esc_html( psb_fa_num( psb_pf_text( $psb_s['number'] ) ) ); ?></dd>
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
		$psb_intro_img = psb_pf_image_id( psb_pf( 'about_intro_image', $psb_id ) );
		$psb_intro_img = $psb_intro_img ? $psb_intro_img : ( $psb_gallery ? $psb_gallery[0] : 0 );
		if ( ! $psb_intro_img ) {
			foreach ( array( 1410, 1726, 1442 ) as $psb_try ) {
				if ( wp_attachment_is_image( $psb_try ) && (int) $psb_try !== $psb_hero ) {
					$psb_intro_img = $psb_try;
					break;
				}
			}
		}
		?>
		<div class="container grid items-start gap-10 lg:grid-cols-12 lg:gap-6">
			<div class="min-w-0 lg:col-span-7 lg:pe-10">
				<?php if ( $psb_t( 'about_intro_eyebrow' ) ) : ?>
					<span class="eyebrow"><?php echo psb_pf_html( $psb_t( 'about_intro_eyebrow' ) ); // phpcs:ignore ?></span>
				<?php endif; ?>
				<h2 id="about-story-title" class="section-title"><?php echo psb_pf_html( $psb_t( 'about_intro_title' ) ); // phpcs:ignore ?></h2>
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
	// Timeline rows from the page fields (link: URL, «archive:<type>» or «doc:<type>:<slug>»).
	$psb_timeline = array();
	foreach ( psb_pf_rows( 'about_timeline', $psb_id ) as $psb_row ) {
		if ( ! empty( $psb_row['year'] ) || ! empty( $psb_row['title'] ) ) {
			$psb_timeline[] = array( psb_pf_text( $psb_row['year'] ?? '' ), $psb_row['title'] ?? '', $psb_row['text'] ?? '', psb_pf_url( $psb_row['link'] ?? '' ) );
		}
	}
	$psb_timeline = apply_filters( 'psb_about_timeline', $psb_timeline );
	?>
	<?php if ( $psb_timeline ) : ?>
	<section class="border-y border-line bg-white py-section" aria-labelledby="about-timeline-title">
		<div class="container grid gap-10 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:gap-16">
			<?php
			get_template_part(
				'template-parts/global/section-head',
				null,
				psb_pf_head( 'about_timeline', $psb_id, array( 'id' => 'about-timeline-title' ) )
			);
			?>
			<ol class="timeline" data-timeline>
				<?php
				$psb_last = count( $psb_timeline ) - 1;
				foreach ( $psb_timeline as $psb_i => $psb_ev ) :
					?>
					<li class="timeline-item <?php echo $psb_i === $psb_last ? 'is-current' : ''; ?>">
						<span class="timeline-year"><?php echo esc_html( $psb_ev[0] ); ?></span>
						<h3 class="timeline-title"><?php echo psb_pf_html( $psb_ev[1] ); // phpcs:ignore ?></h3>
						<p class="timeline-text"><?php echo psb_pf_html( $psb_ev[2] ); // phpcs:ignore ?>
							<?php if ( ! empty( $psb_ev[3] ) ) : ?>
								<a class="link-arrow mt-1" href="<?php echo esc_url( $psb_ev[3] ); ?>">مشاهده سند <?php echo psb_icon( 'arrow', 'btn-arrow size-4' ); // phpcs:ignore ?></a>
							<?php endif; ?>
						</p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( is_array( $psb_values ) && $psb_values ) : ?>
		<section class="py-section" aria-labelledby="about-values-title">
			<div class="container">
				<?php
				get_template_part(
					'template-parts/global/section-head',
					null,
					psb_pf_head( 'about_values', $psb_id, array( 'id' => 'about-values-title' ) )
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
							<h3 class="text-h3 font-bold text-foreground"><?php echo psb_pf_html( $psb_v['title'] ); // phpcs:ignore ?></h3>
							<?php if ( ! empty( $psb_v['description'] ) ) : ?>
								<p class="mt-2 text-sm leading-7 text-slate-600"><?php echo psb_pf_html( $psb_v['description'] ); // phpcs:ignore ?></p>
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
				psb_pf_head( 'about_cap', $psb_id, array( 'id' => 'about-cap-title' ) )
			);
			$psb_products = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 6 ) );
			$psb_services = get_posts( array( 'post_type' => 'services', 'post_status' => 'publish', 'numberposts' => 6 ) );
			$psb_tavanir  = array_filter( wp_list_pluck( psb_pf_rows( 'about_cap_list', $psb_id ), 'item' ) );
			?>
			<div class="grid gap-gutter lg:grid-cols-3">
				<?php if ( $psb_tavanir ) : ?>
				<div class="rounded-card border border-line bg-white p-6">
					<h3 class="text-base font-bold text-dark"><?php echo psb_pf_html( $psb_t( 'about_cap_list_title' ) ); // phpcs:ignore ?></h3>
					<ul class="mt-4 grid gap-2 text-sm text-slate-700" role="list">
						<?php foreach ( $psb_tavanir as $psb_item ) : ?>
							<li class="flex items-center gap-2"><?php echo psb_icon( 'check', 'size-4 text-foreground' ); // phpcs:ignore ?><?php echo psb_pf_html( $psb_item ); // phpcs:ignore ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>
				<?php if ( $psb_products ) : ?>
					<div class="rounded-card border border-line bg-white p-6 max-lg:hidden">
						<h3 class="text-base font-bold text-dark"><?php echo esc_html( $psb_t( 'about_cap_products_title' ) ); ?></h3>
						<ul class="mt-4 grid gap-1" role="list">
							<?php foreach ( $psb_products as $psb_p ) : ?>
								<li><a class="flex min-h-11 items-center justify-between rounded-control px-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-primary-700" href="<?php echo esc_url( get_permalink( $psb_p ) ); ?>"><?php echo esc_html( get_the_title( $psb_p ) ); ?><?php echo psb_icon( 'chevron', 'size-4 text-slate-500' ); // phpcs:ignore ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
				<?php if ( $psb_services ) : ?>
					<div class="rounded-card border border-line bg-white p-6 max-lg:hidden">
						<h3 class="text-base font-bold text-dark"><?php echo esc_html( $psb_t( 'about_cap_services_title' ) ); ?></h3>
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
				psb_pf_head( 'about_creds', $psb_id, array( 'id' => 'about-creds-title', 'link' => array( 'url' => psb_archive_url( 'confirmation' ) ) ) )
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
			<?php if ( $psb_t( 'about_creds_note' ) ) : ?>
				<p class="mt-6 text-[0.9375rem] text-slate-700"><?php echo psb_pf_html( $psb_t( 'about_creds_note' ) ); // phpcs:ignore ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( is_array( $psb_owners ) && $psb_owners ) : ?>
		<section class="border-t border-line bg-white py-section-sm" aria-labelledby="about-team-title">
			<div class="container">
				<?php get_template_part( 'template-parts/global/section-head', null, psb_pf_head( 'about_team', $psb_id, array( 'id' => 'about-team-title' ) ) ); ?>
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
			<?php get_template_part( 'template-parts/portfolio/gallery-rail', null, array( 'images' => $psb_gallery, 'title' => $psb_t( 'about_gallery_title' ), 'alt' => 'کارخانه ' . psb_brand_name(), 'id' => 'about-gallery-title' ) ); ?>
		</div>
	<?php endif; ?>

	<?php
	$psb_faq = psb_field( 'faq_list', array(), $psb_id );
	if ( is_array( $psb_faq ) && $psb_faq ) :
		?>
		<section class="border-t border-line py-section-sm">
			<div class="container max-w-content">
				<?php get_template_part( 'template-parts/global/faq-list', null, array( 'items' => $psb_faq, 'title' => $psb_t( 'about_faq_title' ) ) ); ?>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
