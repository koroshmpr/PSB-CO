<?php
/**
 * Search results page — same engine as the instant search (inc/search-route.php).
 * ?s=…&type=<post type>&sp=<page>. Always noindex (see wp_robots filter in search-route.php).
 *
 * @package bluebox
 */

get_header();

$psb_q     = trim( (string) get_search_query( false ) );
$psb_types = psb_search_types();
$psb_type  = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification
if ( 'all' !== $psb_type && ! isset( $psb_types[ $psb_type ] ) ) {
	$psb_type = 'all';
}
$psb_page  = max( 1, isset( $_GET['sp'] ) ? absint( $_GET['sp'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification
$psb_per   = 12;
$psb_res   = psb_search_run( $psb_q, $psb_type );
$psb_all   = array_sum( $psb_res['counts'] );
$psb_shown = count( $psb_res['hits'] );
$psb_pages = max( 1, (int) ceil( $psb_shown / $psb_per ) );
$psb_page  = min( $psb_page, $psb_pages );
$psb_items = psb_search_items( array_slice( $psb_res['hits'], ( $psb_page - 1 ) * $psb_per, $psb_per ), $psb_res['units'] );
$psb_phone = psb_primary_phone();

$psb_url = function ( $args ) use ( $psb_q ) {
	return add_query_arg( array_merge( array( 's' => rawurlencode( $psb_q ) ), $args ), home_url( '/' ) );
};

get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'title'   => $psb_q ? 'نتایج جستجو برای «' . $psb_q . '»' : 'جستجو در سایت',
		'eyebrow' => $psb_q ? psb_fa_num( $psb_all ) . ' نتیجه' : '',
	)
);
?>
<section class="py-section-sm">
	<div class="container max-w-5xl">
		<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex gap-2">
			<label for="psb-sr-input" class="sr-only">عبارت جستجو</label>
			<input id="psb-sr-input" class="input" type="search" name="s" value="<?php echo esc_attr( $psb_q ); ?>" placeholder="جستجوی محصول یا تأییدیه…" enterkeyhint="search">
			<?php if ( 'all' !== $psb_type ) : ?>
				<input type="hidden" name="type" value="<?php echo esc_attr( $psb_type ); ?>">
			<?php endif; ?>
			<button class="btn btn-primary shrink-0" type="submit"><?php echo psb_icon( 'search' ); // phpcs:ignore ?>جستجو</button>
		</form>

		<?php if ( $psb_all ) : ?>
			<nav class="mt-6 flex gap-2 overflow-x-auto pb-1" aria-label="فیلتر نوع نتیجه">
				<a class="chip shrink-0 <?php echo 'all' === $psb_type ? 'is-active' : ''; ?>" href="<?php echo esc_url( $psb_url( array() ) ); ?>" <?php echo 'all' === $psb_type ? 'aria-current="page"' : ''; ?>>
					همه <span class="opacity-70"><?php echo esc_html( psb_fa_num( $psb_all ) ); ?></span>
				</a>
				<?php foreach ( $psb_types as $psb_key => $psb_info ) : ?>
					<?php
					$psb_n = (int) ( $psb_res['counts'][ $psb_key ] ?? 0 );
					if ( ! $psb_n ) {
						continue;
					}
					?>
					<a class="chip shrink-0 <?php echo $psb_key === $psb_type ? 'is-active' : ''; ?>" href="<?php echo esc_url( $psb_url( array( 'type' => $psb_key ) ) ); ?>" <?php echo $psb_key === $psb_type ? 'aria-current="page"' : ''; ?>>
						<?php echo esc_html( $psb_info[0] ); ?> <span class="opacity-70"><?php echo esc_html( psb_fa_num( $psb_n ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<?php if ( 'any' === $psb_res['mode'] ) : ?>
				<p class="mt-4 text-sm text-slate-500">نتیجه‌ای با همه کلمات پیدا نشد؛ نزدیک‌ترین نتایج نمایش داده می‌شود.</p>
			<?php endif; ?>

			<ol class="mt-6 grid gap-3">
				<?php foreach ( $psb_items as $psb_item ) : ?>
					<li>
						<article class="card card-hover flex-row items-start gap-4 p-4">
							<span class="icon-tile !size-14 shrink-0 overflow-hidden !p-0">
								<?php if ( $psb_item['thumb'] ) : ?>
									<img src="<?php echo esc_url( $psb_item['thumb'] ); ?>" alt="" width="56" height="56" loading="lazy" decoding="async" class="size-full object-cover">
								<?php else : ?>
									<?php echo psb_icon( $psb_types[ $psb_item['type'] ][1] ?? 'doc', 'size-6' ); // phpcs:ignore ?>
								<?php endif; ?>
							</span>
							<div class="min-w-0 flex-1">
								<p class="flex flex-wrap items-center gap-2 text-xs">
									<span class="badge"><?php echo esc_html( $psb_types[ $psb_item['type'] ][0] ?? '' ); ?></span>
									<?php if ( $psb_item['meta'] ) : ?>
										<span class="text-slate-500"><?php echo esc_html( $psb_item['meta'] ); ?></span>
									<?php endif; ?>
								</p>
								<h2 class="search-hl mt-1.5 text-base font-bold text-dark">
									<a class="card-link" href="<?php echo esc_url( $psb_item['url'] ); ?>"><?php echo $psb_item['title']; // phpcs:ignore -- escaped + highlighted ?></a>
								</h2>
								<p class="search-hl mt-1 line-clamp-2 text-sm leading-7 text-slate-600"><?php echo $psb_item['snippet']; // phpcs:ignore ?></p>
							</div>
						</article>
					</li>
				<?php endforeach; ?>
			</ol>

			<?php if ( $psb_pages > 1 ) : ?>
				<nav class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="صفحه‌بندی نتایج">
					<?php for ( $psb_i = 1; $psb_i <= $psb_pages; $psb_i++ ) : ?>
						<?php
						$psb_args = array( 'sp' => $psb_i );
						if ( 'all' !== $psb_type ) {
							$psb_args['type'] = $psb_type;
						}
						?>
						<a class="flex size-10 items-center justify-center rounded-control border text-sm font-bold <?php echo $psb_i === $psb_page ? 'border-foreground bg-foreground text-white' : 'border-line bg-white text-slate-700 hover:border-slate-700'; ?>"
							href="<?php echo esc_url( $psb_url( $psb_args ) ); ?>" <?php echo $psb_i === $psb_page ? 'aria-current="page"' : ''; ?>>
							<?php echo esc_html( psb_fa_num( $psb_i ) ); ?>
						</a>
					<?php endfor; ?>
				</nav>
			<?php endif; ?>

		<?php else : ?>
			<div class="mt-8 rounded-panel border border-line bg-white p-8 text-center">
				<p class="text-h3 font-bold text-dark">
					<?php echo $psb_q ? esc_html( 'نتیجه‌ای برای «' . $psb_q . '» پیدا نشد' ) : 'عبارتی برای جستجو وارد کنید'; ?>
				</p>
				<p class="mt-2 text-sm text-slate-500">املای عبارت را بررسی کنید یا یکی از جستجوهای زیر را امتحان کنید.</p>
				<div class="mt-5 flex flex-wrap justify-center gap-2">
					<?php foreach ( psb_search_popular() as $psb_term ) : ?>
						<a class="chip" href="<?php echo esc_url( add_query_arg( 's', rawurlencode( $psb_term ), home_url( '/' ) ) ); ?>"><?php echo esc_html( $psb_term ); ?></a>
					<?php endforeach; ?>
				</div>
				<div class="mt-6 flex flex-wrap justify-center gap-3">
					<a class="btn btn-primary" href="<?php echo esc_url( psb_quote_url() ); ?>">ثبت درخواست استعلام قیمت</a>
					<?php if ( $psb_phone ) : ?>
						<a class="btn btn-secondary" href="<?php echo esc_url( $psb_phone['href'] ); ?>"><?php echo psb_icon( 'phone' ); // phpcs:ignore ?><span class="ltr-num"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></span></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
