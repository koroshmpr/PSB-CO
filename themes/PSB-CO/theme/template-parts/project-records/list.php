<?php
/**
 * Filterable reference list. Server renders the current filter state (?q=&sector=&prov=&type=&dec=&sort=&pg=)
 * as real HTML (first 30 rows × pg) so it works without JS and is indexable; js/project-records.js then
 * filters the embedded JSON client-side (no AJAX) and keeps the URL in sync.
 *
 * @package PSB-CO
 */

if ( ! function_exists( 'psb_pr_records' ) ) {
	return;
}
$psb_rows  = psb_pr_records();
$psb_state = psb_pr_state_from_request();
$psb_match  = psb_pr_query( $psb_rows, $psb_state );
$psb_facets = psb_pr_facets( $psb_rows, $psb_state );
$psb_totals = psb_pr_totals();
$psb_years  = psb_pr_years_useful();
$psb_names  = psb_pr_sector_names();
$psb_tl     = psb_pr_type_labels();
$psb_per    = 30;
$psb_shown  = min( count( $psb_match ), $psb_per * $psb_state['pg'] );
$psb_anon   = count( array_filter( $psb_rows, static function ( $r ) {
	return $r['anon'];
} ) );

// Sector chips: by total count, «سایر» last.
$psb_sectors = array();
foreach ( $psb_names as $psb_slug => $psb_name ) {
	if ( ! empty( $psb_totals['sector'][ $psb_slug ] ) ) {
		$psb_sectors[ $psb_slug ] = $psb_totals['sector'][ $psb_slug ];
	}
}
arsort( $psb_sectors );
if ( isset( $psb_sectors['other'] ) ) {
	$psb_o = $psb_sectors['other'];
	unset( $psb_sectors['other'] );
	$psb_sectors['other'] = $psb_o;
}
$psb_types = array_filter( $psb_totals['type'], static function ( $n ) {
	return $n >= 2;
} );
$psb_provs = $psb_totals['prov'];
ksort( $psb_provs, SORT_STRING );
$psb_provs_sorted = array();
foreach ( array_merge( psb_pr_provinces(), array( psb_pr_abroad_label() ) ) as $psb_p ) {
	if ( isset( $psb_provs[ $psb_p ] ) ) {
		$psb_provs_sorted[ $psb_p ] = $psb_provs[ $psb_p ];
	}
}
$psb_active = ( $psb_state['sector'] ? 1 : 0 ) + ( '' !== $psb_state['prov'] ? 1 : 0 ) + count( $psb_state['type'] ) + ( $psb_state['dec'] ? 1 : 0 );
$psb_base   = get_permalink();
?>
<section id="pr-list" class="pr-list py-section" aria-labelledby="pr-list-title">
	<div class="container">
		<?php
		get_template_part(
			'template-parts/global/section-head',
			null,
			array(
				'eyebrow' => 'فهرست کامل',
				'title'   => 'همه سوابق اجرایی',
				'lead'    => 'بر اساس حوزه، استان یا نوع تابلو فیلتر کنید یا نام پروژه و کارفرما را جستجو کنید.',
				'id'      => 'pr-list-title',
			)
		);
		?>
		<form class="pr-filter" method="get" action="<?php echo esc_url( $psb_base ); ?>#pr-list" role="search" aria-label="فیلتر سوابق" data-pr-form>
			<div class="pr-bar">
				<label class="pr-search">
					<span class="sr-only">جستجو در سوابق</span>
					<?php echo psb_icon( 'search', 'pr-search-ico' ); // phpcs:ignore ?>
					<input type="search" name="q" value="<?php echo esc_attr( $psb_state['q'] ); ?>" placeholder="جستجوی پروژه، کارفرما یا شهر" autocomplete="off" enterkeyhint="search" data-pr-q>
				</label>
				<button type="button" class="pr-toggle" aria-expanded="false" aria-controls="pr-panel" data-pr-toggle>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
					<span>فیلترها</span>
					<span class="pr-toggle-n" data-pr-active<?php echo $psb_active ? '' : ' hidden'; ?>><?php echo esc_html( psb_pr_fa( $psb_active ) ); ?></span>
				</button>
			</div>

			<div class="pr-panel" id="pr-panel" data-pr-panel>
				<div class="pr-panel-head">
					<strong>فیلترها</strong>
					<button type="button" class="pr-panel-close" aria-label="بستن فیلترها" data-pr-close><?php echo psb_icon( 'close', 'size-5' ); // phpcs:ignore ?></button>
				</div>
				<div class="pr-panel-body">
					<fieldset class="pr-group">
						<legend class="pr-legend">حوزه</legend>
						<div class="pr-chips" data-pr-sectors>
							<label class="chip pr-chip"><input type="radio" name="sector" value="" <?php checked( '', $psb_state['sector'] ); ?>><span>همه</span><span class="pr-n" data-n=""><?php echo esc_html( psb_pr_fa_int( array_sum( $psb_facets['sector'] ) ) ); ?></span></label>
							<?php foreach ( $psb_sectors as $psb_slug => $psb_n ) : ?>
								<label class="chip pr-chip"><input type="radio" name="sector" value="<?php echo esc_attr( $psb_slug ); ?>" <?php checked( $psb_slug, $psb_state['sector'] ); ?>><span><?php echo esc_html( $psb_names[ $psb_slug ] ); ?></span><span class="pr-n" data-n="<?php echo esc_attr( $psb_slug ); ?>"><?php echo esc_html( psb_pr_fa_int( $psb_facets['sector'][ $psb_slug ] ?? 0 ) ); ?></span></label>
							<?php endforeach; ?>
						</div>
					</fieldset>

					<fieldset class="pr-group">
						<legend class="pr-legend">نوع تابلو و دامنه کار</legend>
						<div class="pr-chips" data-pr-types>
							<?php foreach ( $psb_types as $psb_k => $psb_n ) : ?>
								<label class="chip pr-chip"><input type="checkbox" name="type[]" value="<?php echo esc_attr( $psb_k ); ?>" <?php checked( in_array( $psb_k, $psb_state['type'], true ) ); ?>><span><?php echo esc_html( $psb_tl[ $psb_k ] ); ?></span><span class="pr-n" data-n="<?php echo esc_attr( $psb_k ); ?>"><?php echo esc_html( psb_pr_fa_int( $psb_facets['type'][ $psb_k ] ?? 0 ) ); ?></span></label>
							<?php endforeach; ?>
						</div>
					</fieldset>

					<div class="pr-selects">
						<label class="pr-field">
							<span class="pr-legend">استان</span>
							<select name="prov" class="pr-select" data-pr-prov>
								<option value="">همه استان‌ها</option>
								<?php foreach ( $psb_provs_sorted as $psb_p => $psb_n ) : ?>
									<option value="<?php echo esc_attr( $psb_p ); ?>" <?php selected( $psb_p, $psb_state['prov'] ); ?>><?php echo esc_html( $psb_p . ' (' . psb_pr_fa( $psb_n ) . ')' ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<?php if ( $psb_years && count( $psb_totals['dec'] ) > 1 ) : ?>
							<label class="pr-field">
								<span class="pr-legend">دهه</span>
								<select name="dec" class="pr-select" data-pr-dec>
									<option value="">همه سال‌ها</option>
									<?php foreach ( $psb_totals['dec'] as $psb_d => $psb_n ) : ?>
										<option value="<?php echo (int) $psb_d; ?>" <?php selected( (int) $psb_d, $psb_state['dec'] ); ?>><?php echo esc_html( 'دهه ' . psb_pr_fa( $psb_d ) . ' (' . psb_pr_fa( $psb_n ) . ')' ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						<?php endif; ?>
						<label class="pr-field">
							<span class="pr-legend">مرتب‌سازی</span>
							<select name="sort" class="pr-select" data-pr-sort>
								<option value="new" <?php selected( 'new', $psb_state['sort'] ); ?>>جدیدترین</option>
								<option value="old" <?php selected( 'old', $psb_state['sort'] ); ?>>قدیمی‌ترین</option>
								<option value="az" <?php selected( 'az', $psb_state['sort'] ); ?>>کارفرما (الفبایی)</option>
							</select>
						</label>
						<noscript><button class="btn btn-secondary pr-apply" type="submit">اعمال فیلتر</button></noscript>
					</div>
				</div>
				<div class="pr-panel-foot">
					<a class="btn btn-ghost" href="<?php echo esc_url( $psb_base ); ?>#pr-list" data-pr-reset>پاک کردن</a>
					<button type="button" class="btn btn-dark pr-panel-apply" data-pr-close>نمایش <span data-pr-n2><?php echo esc_html( psb_pr_fa_int( count( $psb_match ) ) ); ?></span> پروژه</button>
				</div>
			</div>
		</form>

		<div class="pr-summary" aria-live="polite">
			<p><strong data-pr-count><?php echo esc_html( psb_pr_fa_int( count( $psb_match ) ) ); ?></strong> پروژه<span data-pr-filtered<?php echo ( $psb_active || '' !== $psb_state['q'] ) ? '' : ' hidden'; ?>> مطابق فیلترها</span></p>
			<a class="pr-reset" href="<?php echo esc_url( $psb_base ); ?>#pr-list" data-pr-reset<?php echo ( $psb_active || '' !== $psb_state['q'] ) ? '' : ' hidden'; ?>>پاک کردن فیلترها</a>
		</div>

		<table class="pr-table<?php echo $psb_years ? '' : ' pr-table--noyear'; ?>">
			<caption class="sr-only">فهرست سوابق اجرایی</caption>
			<thead>
				<tr>
					<th scope="col" class="pr-c-title">پروژه</th>
					<th scope="col" class="pr-c-client">کارفرما</th>
					<th scope="col" class="pr-c-loc">محل</th>
					<th scope="col" class="pr-c-year">سال</th>
					<th scope="col" class="pr-c-sector">حوزه</th>
					<th scope="col" class="pr-c-types">نوع تابلو</th>
				</tr>
			</thead>
			<tbody data-pr-body>
				<?php
				foreach ( array_slice( $psb_match, 0, $psb_shown ) as $psb_r ) {
					echo psb_pr_row_html( $psb_r ); // phpcs:ignore
				}
				?>
			</tbody>
		</table>
		<div class="pr-empty" data-pr-empty<?php echo $psb_match ? ' hidden' : ''; ?>>
			<p>پروژه‌ای با این فیلترها پیدا نشد.</p>
			<a class="link-arrow" href="<?php echo esc_url( $psb_base ); ?>#pr-list" data-pr-reset>نمایش همه سوابق</a>
		</div>

		<div class="pr-more">
			<p class="pr-progress" data-pr-progress<?php echo $psb_match ? '' : ' hidden'; ?>>نمایش <?php echo esc_html( psb_pr_fa_int( $psb_shown ) ); ?> از <?php echo esc_html( psb_pr_fa_int( count( $psb_match ) ) ); ?></p>
			<?php if ( $psb_shown < count( $psb_match ) ) : ?>
				<a class="btn btn-secondary pr-more-btn" rel="nofollow" href="<?php echo esc_url( add_query_arg( 'pg', $psb_state['pg'] + 1 ) . '#pr-list' ); ?>" data-pr-more>نمایش بیشتر</a>
			<?php else : ?>
				<a class="btn btn-secondary pr-more-btn" rel="nofollow" href="#pr-list" data-pr-more hidden>نمایش بیشتر</a>
			<?php endif; ?>
		</div>
		<?php if ( $psb_anon ) : ?>
			<p class="pr-note">نام کارفرما و محل دقیق <?php echo esc_html( psb_pr_fa( $psb_anon ) ); ?> پروژه به دلیل محرمانگی نمایش داده نمی‌شود و با عنوانی عمومی آمده است.</p>
		<?php endif; ?>
	</div>
	<script type="application/json" id="pr-data"><?php echo wp_json_encode( psb_pr_payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ); ?></script>
</section>
