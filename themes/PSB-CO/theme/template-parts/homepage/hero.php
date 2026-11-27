<?php
/**
 * Home hero (spec v2 §5.1): full-bleed graphite photo, value-proposition H1, 2 CTAs + direct line,
 * ruled 4-stat trust row. Static (no slider / no reveal) — the photo is the LCP image
 * (theme photo set «hero», psb_photo_picture(): MV line-up on the inline-end side at lg+, separate 4:5
 * mobile crop; eager, fetchpriority=high, AVIF preloaded in header.php with the same srcset/sizes).
 * All text, the stats row and the photo come from the page fields (inc/page-fields/home.php);
 * tokens such as {approvals} keep the counts live. No photo field → theme photo set «hero».
 */
$psb_pid   = (int) get_option( 'page_on_front' );
$psb_phone = psb_primary_phone();
$psb_stats = array();
foreach ( psb_pf_rows( 'home_hero_stats', $psb_pid ) as $psb_row ) {
	$psb_val = psb_pf_text( $psb_row['value'] ?? '' );
	if ( '' === $psb_val || '۰' === $psb_val || '0' === $psb_val ) {
		continue;
	}
	$psb_stats[] = array( $psb_val, psb_pf_text( $psb_row['unit'] ?? '' ), $psb_row['label'] ?? '', ! empty( $psb_row['icon'] ) ? $psb_row['icon'] : 'check' );
}
// Photo: page fields (desktop + optional mobile crop), else the theme photo set «hero».
$psb_hero_img  = psb_pf_image_id( psb_pf( 'home_hero_image', $psb_pid ) );
$psb_hero_mob  = psb_pf_image_id( psb_pf( 'home_hero_image_mobile', $psb_pid ) );
if ( $psb_hero_img && psb_pf_is_theme_photo( $psb_hero_img, 'psb-hero-mv-switchgear-lineup' ) && ( ! $psb_hero_mob || psb_pf_is_theme_photo( $psb_hero_mob, 'psb-hero-mv-switchgear-lineup' ) ) ) {
	$psb_hero_img = 0; // Same photo as the theme set → use the AVIF/WebP set (smaller LCP image).
	$psb_hero_mob = 0;
}
$psb_eyebrow   = psb_pf_text( psb_pf( 'home_hero_eyebrow', $psb_pid ) );
$psb_title     = psb_pf_text( psb_pf( 'home_hero_title', $psb_pid ) );
$psb_lead      = psb_pf( 'home_hero_lead', $psb_pid );
$psb_btn1      = psb_pf_text( psb_pf( 'home_hero_btn1', $psb_pid ) );
$psb_btn2      = psb_pf_text( psb_pf( 'home_hero_btn2', $psb_pid ) );
$psb_btn2_url  = trim( (string) psb_pf( 'home_hero_btn2_url', $psb_pid ) );
$psb_phone_txt = psb_pf_text( psb_pf( 'home_hero_phone', $psb_pid ) );
$psb_img_attrs = array(
	'class'         => 'size-full object-cover object-[50%_40%] lg:object-[78%_50%]',
	'loading'       => 'eager',
	'fetchpriority' => 'high',
	'decoding'      => 'sync',
	'alt'           => '',
);
?>
<section class="relative isolate overflow-hidden bg-dark text-white on-dark" aria-labelledby="home-hero-title">
	<div class="hero-media absolute inset-y-0 end-0 -z-20" aria-hidden="true">
		<?php
		echo $psb_hero_img // phpcs:ignore
			? psb_pf_picture( $psb_hero_img, $psb_hero_mob, $psb_img_attrs + array( 'sizes' => '(min-width: 64rem) 60vw, 100vw' ) )
			: psb_photo_picture( 'hero', $psb_img_attrs );
		?>
	</div>
	<div class="hero-overlay absolute inset-0 -z-10" aria-hidden="true"></div>

	<div class="container flex flex-col justify-between pt-10 md:min-h-[640px] md:pt-[5.5rem]">
		<div class="max-w-[40rem]">
			<?php if ( $psb_eyebrow ) : ?>
				<p class="eyebrow"><?php echo psb_pf_html( $psb_eyebrow ); // phpcs:ignore ?></p>
			<?php endif; ?>

			<h1 id="home-hero-title" class="mb-4 mt-3.5 text-display font-bold text-white md:mb-5 md:mt-[1.125rem]">
				<?php echo psb_pf_html( $psb_title ); // phpcs:ignore ?>
			</h1>

			<?php if ( '' !== trim( (string) $psb_lead ) ) : ?>
				<p class="mb-7 max-w-[38rem] text-lead text-slate-200 md:mb-9"><?php echo psb_pf_html( $psb_lead ); // phpcs:ignore ?></p>
			<?php endif; ?>

			<div class="flex flex-wrap items-center gap-3">
				<?php if ( $psb_btn1 ) : ?>
					<a class="btn btn-primary max-md:h-[52px] max-md:w-full" href="<?php echo esc_url( psb_quote_url() ); ?>">
						<?php echo esc_html( $psb_btn1 ); ?>
						<?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?>
					</a>
				<?php endif; ?>
				<?php if ( $psb_btn2 ) : ?>
					<a class="btn btn-on-dark max-md:h-[52px] max-md:w-full" href="<?php echo esc_url( $psb_btn2_url ? $psb_btn2_url : psb_shop_url() ); ?>"><?php echo esc_html( $psb_btn2 ); ?></a>
				<?php endif; ?>
				<?php if ( $psb_phone && $psb_phone_txt ) : ?>
					<p class="text-sm text-slate-300 max-md:w-full max-md:pt-1 max-md:text-center md:ms-3">
						<?php echo esc_html( $psb_phone_txt ); ?>
						<a class="font-semibold text-white" dir="ltr" href="<?php echo esc_url( $psb_phone['href'] ); ?>"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></a>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( count( $psb_stats ) > 1 ) : ?>
			<dl class="stats stats--invert mt-10 md:mt-16">
				<?php foreach ( $psb_stats as $psb_stat ) : ?>
					<div class="stat">
						<dt class="stat-value order-1 flex items-center gap-3"><?php echo psb_icon( $psb_stat[3] ?? 'check', 'size-7 shrink-0 text-slate-400' ); // phpcs:ignore ?><span><?php echo esc_html( $psb_stat[0] ); ?><small><?php echo esc_html( $psb_stat[1] ); ?></small></span></dt>
						<dd class="stat-label order-2"><?php echo psb_pf_html( $psb_stat[2] ); // phpcs:ignore ?></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
	</div>
</section>
