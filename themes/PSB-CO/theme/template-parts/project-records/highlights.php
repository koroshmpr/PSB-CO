<?php
/**
 * Highlights strip: featured project records as larger cards.
 * Media (3:2): the linked portfolio photo when it is sharp enough, otherwise the sector line-art illustration labelled
 * «تصویر نمایشی» (inc/placeholders.php) — never a fake photo. The typographic card (sector glyph, client, place, year,
 * scope chips) remains the fallback when the placeholder module is not loaded.
 *
 * Usage (home / about):
 *   get_template_part( 'template-parts/project-records/highlights', null, array( 'count' => 3 ) );
 *
 * $args:
 * - count    int    (default 6)
 * - title    string section title (default «پروژه‌های اجراشده»); '' with head=false hides the head
 * - eyebrow  string (default «سوابق اجرایی»)
 * - lead     string
 * - link     array( url, label ) — default: the «سوابق اجرایی» page
 * - head     bool   (default true)
 * - class    extra section classes (e.g. 'bg-background')
 *
 * @package PSB-CO
 */

if ( ! function_exists( 'psb_pr_featured' ) ) {
	return;
}
$psb_count = max( 1, (int) ( $args['count'] ?? 6 ) );
$psb_rows  = psb_pr_featured( $psb_count );
if ( ! $psb_rows ) {
	return;
}
psb_pr_enqueue_style();

$psb_names = psb_pr_sector_names();
$psb_link  = $args['link'] ?? null;
if ( null === $psb_link ) {
	$psb_page = get_page_by_path( 'project-references' );
	$psb_link = $psb_page ? array( 'url' => get_permalink( $psb_page ), 'label' => 'همه سوابق اجرایی' ) : array();
}
$psb_stats = psb_pr_stats();
$psb_uid   = 'pr-hl-' . wp_unique_id();
?>
<section class="pr-hl py-section <?php echo esc_attr( $args['class'] ?? '' ); ?>" aria-labelledby="<?php echo esc_attr( $psb_uid ); ?>">
	<div class="container">
		<?php
		if ( $args['head'] ?? true ) {
			get_template_part(
				'template-parts/global/section-head',
				null,
				array(
					'eyebrow' => $args['eyebrow'] ?? 'سوابق اجرایی',
					'title'   => $args['title'] ?? 'پروژه‌های اجراشده',
					'lead'    => $args['lead'] ?? sprintf( '%s پروژه در %s حوزه؛ چند نمونه:', psb_pr_fa_int( $psb_stats['projects'] ), psb_pr_fa( $psb_stats['sectors'] ) ),
					'id'      => $psb_uid,
					'link'    => $psb_link,
				)
			);
		}
		?>
		<ul class="pr-hl-grid" role="list">
			<?php
			foreach ( $psb_rows as $psb_r ) :
				$psb_sector = $psb_names[ $psb_r['sector'] ] ?? '';
				// Linked photo only when sharp enough for the 3:2 slot; otherwise the sector illustration («تصویر نمایشی»).
				if ( function_exists( 'psb_best_image' ) ) {
					$psb_media = psb_best_image(
						$psb_r['img'] ? (int) $psb_r['img'] : 0,
						380,
						'3 / 2',
						array(
							'key'  => psb_placeholder_sector_key( $psb_r['sector'] ) ? psb_placeholder_sector_key( $psb_r['sector'] ) : 'sector-other',
							'attr' => array( 'alt' => '', 'sizes' => '(min-width: 1024px) 380px, 82vw' ),
						)
					);
				} else {
					$psb_media = $psb_r['img'] ? psb_image( $psb_r['img'], 'medium_large', array( 'alt' => '', 'sizes' => '(min-width: 1024px) 380px, 82vw' ) ) : '';
				}
				?>
				<li class="pr-hl-item">
					<article class="pr-card<?php echo $psb_media ? ' pr-card--photo' : ''; ?>">
						<?php if ( $psb_media ) : ?>
							<div class="pr-card-media">
								<?php echo $psb_media; // phpcs:ignore ?>
							</div>
						<?php else : ?>
							<div class="pr-card-top">
								<span class="pr-card-glyph"><?php echo psb_pr_sector_icon( $psb_r['sector'], 'pr-ico' ); // phpcs:ignore ?></span>
								<span class="kicker"><?php echo esc_html( $psb_sector ); ?></span>
								<?php if ( $psb_r['year'] ) : ?>
									<span class="pr-card-year"><?php echo esc_html( psb_pr_fa( $psb_r['year'] ) ); ?></span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						<div class="pr-card-body">
							<?php if ( $psb_media ) : ?>
								<span class="kicker"><?php echo esc_html( $psb_sector ); ?></span>
							<?php endif; ?>
							<h3 class="pr-card-title">
								<?php if ( $psb_r['url'] ) : ?>
									<a class="card-link" href="<?php echo esc_url( $psb_r['url'] ); ?>"><?php echo psb_pr_lat( $psb_r['title'] ); // phpcs:ignore ?></a>
								<?php else : ?>
									<?php echo psb_pr_lat( $psb_r['title'] ); // phpcs:ignore ?>
								<?php endif; ?>
							</h3>
							<dl class="pr-card-meta">
								<?php if ( '' !== $psb_r['client'] ) : ?>
									<div><dt>کارفرما</dt><dd><?php echo psb_pr_lat( $psb_r['client'] ); // phpcs:ignore ?></dd></div>
								<?php endif; ?>
								<?php if ( '' !== $psb_r['loc'] ) : ?>
									<div><dt>محل</dt><dd><?php echo esc_html( $psb_r['loc'] ); ?></dd></div>
								<?php endif; ?>
								<?php if ( $psb_media && $psb_r['year'] ) : ?>
									<div><dt>سال</dt><dd><?php echo esc_html( psb_pr_fa( $psb_r['year'] ) ); ?></dd></div>
								<?php endif; ?>
							</dl>
							<?php if ( $psb_r['types'] ) : ?>
								<div class="pr-card-tags"><?php echo psb_pr_type_chips( $psb_r['types'], 5 ); // phpcs:ignore ?></div>
							<?php endif; ?>
						</div>
					</article>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
