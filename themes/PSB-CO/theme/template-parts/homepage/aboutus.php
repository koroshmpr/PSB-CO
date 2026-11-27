<?php
/**
 * About (spec v2 §5.7): alt background, 6/6. Theme photo set «about» (panel assembly, live 1921/1922):
 * 4:5 and sticky beside the timeline at lg+, 16:9 between the intro and the timeline below lg.
 * Every text, the photo (about_us.image; empty → theme set «about») and the timeline come from the
 * front page fields (inc/page-fields/home.php). Timeline scroll progress: javascript/modules/timeline.js.
 */
$psb_pid   = (int) get_option( 'page_on_front' );
$psb_link  = psb_pf( 'about_us.btn_link', $psb_pid );
$psb_btn   = is_array( $psb_link ) && ! empty( $psb_link['url'] ) ? $psb_link : array( 'url' => home_url( '/about-us/' ), 'title' => 'درباره شرکت', 'target' => '' );
$psb_btn_t = trim( (string) ( $psb_btn['title'] ?? '' ) ) ?: 'درباره شرکت';
$psb_eyebr = psb_pf_text( psb_pf( 'about_us.eyebrow', $psb_pid ) );
$psb_title = psb_pf_text( psb_pf( 'about_us.heading', $psb_pid ) );
$psb_body  = (string) psb_pf( 'about_us.content', $psb_pid );
$psb_img   = psb_pf_image_id( psb_pf( 'about_us.image', $psb_pid ) );
$psb_alt   = psb_pf_text( psb_pf( 'about_us.image_alt', $psb_pid ) );
$psb_cap   = psb_pf_text( psb_pf( 'about_us.caption', $psb_pid ) );
$psb_steps = array();
foreach ( psb_pf_rows( 'home_timeline', $psb_pid ) as $psb_row ) {
	if ( ! empty( $psb_row['year'] ) || ! empty( $psb_row['title'] ) ) {
		$psb_steps[] = array( psb_pf_text( $psb_row['year'] ?? '' ), $psb_row['title'] ?? '' );
	}
}
$psb_img_cls = 'aspect-video w-full object-cover object-[50%_60%] lg:aspect-[4/5] lg:object-center';
?>
<section class="bg-background py-section" aria-labelledby="home-about-title">
	<div class="container grid gap-8 lg:grid-cols-2 lg:grid-rows-[auto_1fr] lg:gap-x-16 lg:gap-y-0">
		<div data-reveal>
			<?php if ( $psb_eyebr ) : ?>
				<span class="eyebrow"><?php echo psb_pf_html( $psb_eyebr ); // phpcs:ignore ?></span>
			<?php endif; ?>
			<h2 id="home-about-title" class="section-title mt-3"><?php echo psb_pf_html( $psb_title ); // phpcs:ignore ?></h2>
			<?php if ( '' !== trim( wp_strip_all_tags( $psb_body ) ) ) : ?>
				<div class="mt-4 max-w-[38rem] text-[1.0625rem] leading-[1.9] text-slate-600 [&_p]:mb-3">
					<?php echo wp_kses_post( psb_lat_content( psb_pf_text( $psb_body ) ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<figure class="about-photo lg:col-start-2 lg:row-span-2 lg:row-start-1" data-reveal>
			<?php
			echo $psb_img // phpcs:ignore
				? psb_image( $psb_img, 'large', array( 'class' => $psb_img_cls, 'alt' => $psb_alt, 'sizes' => '(min-width: 80rem) 36rem, (min-width: 64rem) 45vw, (min-width: 48rem) 42rem, 100vw' ) )
				: psb_photo_picture( 'about', array( 'class' => $psb_img_cls, 'alt' => $psb_alt ) );
			?>
			<?php if ( $psb_cap ) : ?>
				<figcaption class="about-photo-cap"><?php echo esc_html( $psb_cap ); ?></figcaption>
			<?php endif; ?>
		</figure>

		<div class="lg:col-start-1 lg:row-start-2">
			<?php if ( $psb_steps ) : ?>
			<ol class="timeline lg:mt-8" data-timeline>
				<?php foreach ( $psb_steps as $psb_i => $psb_s ) : ?>
					<li class="timeline-item <?php echo count( $psb_steps ) - 1 === $psb_i ? 'is-current' : ''; ?>">
						<span class="timeline-year"><?php echo esc_html( $psb_s[0] ); ?></span>
						<span class="timeline-title"><?php echo psb_pf_html( $psb_s[1] ); // phpcs:ignore ?></span>
					</li>
				<?php endforeach; ?>
			</ol>
			<?php endif; ?>

			<a class="btn btn-secondary mt-8" href="<?php echo esc_url( $psb_btn['url'] ); ?>"<?php echo '_blank' === ( $psb_btn['target'] ?? '' ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $psb_btn_t ); ?></a>
		</div>
	</div>
</section>
