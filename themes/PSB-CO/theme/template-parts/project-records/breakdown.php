<?php
/**
 * «گستره سوابق»: projects by sector and by province as ranked bars (each bar filters the list),
 * plus a decade timeline when enough rows carry a year (otherwise it is hidden — no invented dates).
 *
 * $args: stats
 *
 * @package PSB-CO
 */

if ( ! function_exists( 'psb_pr_totals' ) ) {
	return;
}
$psb_s      = $args['stats'] ?? psb_pr_stats();
$psb_totals = psb_pr_totals();
$psb_names  = psb_pr_sector_names();
$psb_base   = get_permalink();

$psb_sec = $psb_totals['sector'];
arsort( $psb_sec );
if ( isset( $psb_sec['other'] ) ) {
	$psb_o = $psb_sec['other'];
	unset( $psb_sec['other'] );
	$psb_sec['other'] = $psb_o;
}
$psb_sec_max = $psb_sec ? max( $psb_sec ) : 1;

$psb_prov      = $psb_totals['prov'];
$psb_prov_top  = array_slice( $psb_prov, 0, 12, true );
$psb_prov_rest = array_slice( $psb_prov, 12, null, true );
$psb_prov_max  = $psb_prov ? max( $psb_prov ) : 1;
$psb_located   = array_sum( $psb_prov );

$psb_show_tl = psb_pr_years_useful( $psb_s ) && count( $psb_totals['dec'] ) > 1;
?>
<section class="pr-breakdown py-section bg-background" aria-labelledby="pr-bd-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			array(
				'eyebrow' => 'گستره سوابق',
				'title'   => 'حوزه‌های صنعتی و استان‌های محل اجرا',
				'lead'    => sprintf( 'توزیع %s پروژه ثبت‌شده بر اساس حوزه و استان. روی هر ردیف بزنید تا فهرست فیلتر شود.', psb_pr_fa_int( $psb_s['projects'] ) ),
				'id'      => 'pr-bd-title',
			)
		);
		?>

		<?php if ( $psb_show_tl ) : ?>
			<?php
			$psb_dec_max = max( $psb_totals['dec'] );
			$psb_from    = (int) ( floor( ( $psb_s['span_from'] ? $psb_s['span_from'] : min( array_keys( $psb_totals['dec'] ) ) ) / 10 ) * 10 );
			$psb_to      = (int) ( floor( max( array_keys( $psb_totals['dec'] ) ) / 10 ) * 10 );
			?>
			<figure class="pr-tl">
				<figcaption class="pr-bd-cap">پروژه‌های تاریخ‌دار بر اساس دهه</figcaption>
				<ol class="pr-tl-list">
					<?php for ( $psb_d = $psb_from; $psb_d <= $psb_to; $psb_d += 10 ) : ?>
						<?php $psb_n = $psb_totals['dec'][ $psb_d ] ?? 0; ?>
						<li class="pr-tl-item">
							<span class="pr-tl-bar" style="--v:<?php echo esc_attr( round( $psb_n / $psb_dec_max, 3 ) ); ?>"><span class="pr-tl-n"><?php echo $psb_n ? esc_html( psb_pr_fa( $psb_n ) ) : ''; ?></span></span>
							<span class="pr-tl-dec"><?php echo esc_html( psb_pr_fa( $psb_d ) ); ?></span>
						</li>
					<?php endfor; ?>
				</ol>
			</figure>
		<?php endif; ?>

		<div class="pr-bd-grid">
			<figure class="pr-bd">
				<figcaption class="pr-bd-cap">بر اساس حوزه</figcaption>
				<ul class="pr-bars" role="list">
					<?php foreach ( $psb_sec as $psb_slug => $psb_n ) : ?>
						<li>
							<a class="pr-bar-row" href="<?php echo esc_url( add_query_arg( 'sector', $psb_slug, $psb_base ) . '#pr-list' ); ?>" rel="nofollow" data-pr-set="sector" data-v="<?php echo esc_attr( $psb_slug ); ?>">
								<span class="pr-bar-label"><?php echo psb_pr_sector_icon( $psb_slug, 'pr-ico-sm' ); // phpcs:ignore ?><?php echo esc_html( $psb_names[ $psb_slug ] ?? $psb_slug ); ?></span>
								<span class="pr-bar-track"><span class="pr-bar-fill" style="--v:<?php echo esc_attr( round( $psb_n / $psb_sec_max, 3 ) ); ?>"></span></span>
								<span class="pr-bar-n"><?php echo esc_html( psb_pr_fa_int( $psb_n ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>

			<?php if ( $psb_prov ) : ?>
				<figure class="pr-bd">
					<figcaption class="pr-bd-cap">بر اساس استان <span>(<?php echo esc_html( psb_pr_fa_int( $psb_located ) ); ?> پروژه با محل مشخص)</span></figcaption>
					<ul class="pr-bars" role="list">
						<?php foreach ( $psb_prov_top as $psb_p => $psb_n ) : ?>
							<li>
								<a class="pr-bar-row" href="<?php echo esc_url( add_query_arg( 'prov', rawurlencode( $psb_p ), $psb_base ) . '#pr-list' ); ?>" rel="nofollow" data-pr-set="prov" data-v="<?php echo esc_attr( $psb_p ); ?>">
									<span class="pr-bar-label"><?php echo esc_html( $psb_p ); ?></span>
									<span class="pr-bar-track"><span class="pr-bar-fill" style="--v:<?php echo esc_attr( round( $psb_n / $psb_prov_max, 3 ) ); ?>"></span></span>
									<span class="pr-bar-n"><?php echo esc_html( psb_pr_fa_int( $psb_n ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php if ( $psb_prov_rest ) : ?>
						<p class="pr-bd-rest">
							<span>و همچنین:</span>
							<?php
							$psb_links = array();
							foreach ( $psb_prov_rest as $psb_p => $psb_n ) {
								$psb_links[] = '<a href="' . esc_url( add_query_arg( 'prov', rawurlencode( $psb_p ), $psb_base ) . '#pr-list' ) . '" rel="nofollow" data-pr-set="prov" data-v="' . esc_attr( $psb_p ) . '">' . esc_html( $psb_p ) . ' <span>' . esc_html( psb_pr_fa( $psb_n ) ) . '</span></a>';
							}
							echo implode( '، ', $psb_links ); // phpcs:ignore
							?>
						</p>
					<?php endif; ?>
				</figure>
			<?php endif; ?>
		</div>
	</div>
</section>
