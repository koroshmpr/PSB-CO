<?php
/**
 * Proof (spec v2 §5.4): white, 4/8 split. Start: eyebrow, H2, text, two links.
 * End: two document registers — approvals (3 key ones) and type tests / licence.
 * Data from CPT ACF (letter_number, letter_date, approval_scope, letter_file; issuing_body,
 * standard_number, report_number, type_test_file/certificate_file). Missing meta is hidden.
 */
$psb_approvals = get_posts( array( 'post_type' => 'confirmation', 'posts_per_page' => 20, 'no_found_rows' => true ) );
$psb_tests     = post_type_exists( 'type-test' ) ? get_posts( array( 'post_type' => 'type-test', 'posts_per_page' => 4, 'no_found_rows' => true ) ) : array();
if ( ! $psb_approvals && ! $psb_tests ) {
	return;
}

// Key approvals first: Tavanir, NIOEC (oil), Tehran-province MV; then the rest.
$psb_rank = function ( $p ) {
	$t = $p->post_title;
	foreach ( array( 'توانیر' => 0, 'نفت' => 1, 'فشارمتوسط' => 2, 'فشار متوسط' => 2 ) as $kw => $r ) {
		if ( false !== mb_strpos( $t, $kw ) ) {
			return $r;
		}
	}
	return 9;
};
$psb_total_a = count( $psb_approvals );
usort(
	$psb_approvals,
	function ( $a, $b ) use ( $psb_rank ) {
		return $psb_rank( $a ) <=> $psb_rank( $b );
	}
);
$psb_approvals = array_slice( $psb_approvals, 0, 3 );

$psb_file_url = function ( $id, $keys ) {
	foreach ( $keys as $k ) {
		$f = psb_field( $k, null, $id );
		if ( is_array( $f ) && ! empty( $f['url'] ) ) {
			return $f['url'];
		}
		if ( is_string( $f ) && 0 === strpos( $f, 'http' ) ) {
			return $f;
		}
	}
	return '';
};
?>
<section class="bg-white py-section" aria-labelledby="home-proof-title">
	<div class="container grid items-start gap-8 lg:grid-cols-12 lg:gap-16">
		<div class="lg:col-span-4" data-reveal>
			<span class="eyebrow">تأییدیه‌ها و آزمون‌های نوعی</span>
			<h2 id="home-proof-title" class="section-title mb-4 mt-3">اسنادی که در مناقصه به آن استناد می‌کنید</h2>
			<p class="section-lead mb-5 lg:mb-8">نام <?php echo esc_html( psb_brand_name() ); ?> در فهرست سازندگان تأییدشده توانیر، شرکت‌های توزیع برق تهران و شرکت ملی مهندسی و ساختمان نفت ثبت است. اصل هر سند در سایت قابل مشاهده است.</p>
			<div class="flex flex-wrap gap-x-6 gap-y-4 lg:flex-col lg:items-start">
				<a class="link-arrow" href="<?php echo esc_url( psb_archive_url( 'confirmation' ) ); ?>">همه تأییدیه‌ها <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></a>
				<?php if ( $psb_tests ) : ?>
					<a class="link-arrow" href="<?php echo esc_url( psb_archive_url( 'type-test' ) ); ?>">گزارش‌های آزمون نوعی <?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?></a>
				<?php endif; ?>
			</div>
		</div>

		<div class="grid gap-10 lg:col-span-8">
			<?php if ( $psb_approvals ) : ?>
				<div data-reveal>
					<h3 class="register-title"><span>تأییدیه و وندورلیست</span><span><?php echo esc_html( psb_fa_num( $psb_total_a ) . ' سند' ); ?></span></h3>
					<ol class="register">
						<?php foreach ( $psb_approvals as $psb_p ) : ?>
							<?php
							$psb_id   = $psb_p->ID;
							$psb_meta = array_filter(
								array(
									(string) psb_field( 'letter_number', '', $psb_id ) ? 'نامه ' . psb_fa_num( psb_field( 'letter_number', '', $psb_id ) ) : '',
									psb_fa_num( (string) psb_field( 'letter_date', '', $psb_id ) ),
									(string) psb_field( 'approval_scope', '', $psb_id ),
								)
							);
							if ( ! $psb_meta ) {
								$psb_meta = array( (string) psb_field( 'issuing_organization', '', $psb_id ) );
							}
							$psb_doc = $psb_file_url( $psb_id, array( 'letter_file' ) );
							?>
							<li>
								<span class="register-logo">
									<?php if ( has_post_thumbnail( $psb_p ) ) : ?>
										<?php echo psb_image( get_post_thumbnail_id( $psb_p ), 'thumbnail', array( 'alt' => '', 'srcset' => false, 'sizes' => false ) ); // phpcs:ignore ?>
									<?php else : ?>
										<?php echo psb_icon( 'doc', 'size-5 text-slate-400' ); // phpcs:ignore ?>
									<?php endif; ?>
								</span>
								<span class="min-w-0">
									<a class="register-name card-link hover:text-primary-700" href="<?php echo esc_url( get_permalink( $psb_p ) ); ?>"><?php echo psb_lat( get_the_title( $psb_p ) ); // phpcs:ignore ?></a>
									<span class="register-meta"><?php echo psb_lat( implode( ' · ', array_filter( $psb_meta ) ) ); // phpcs:ignore ?></span>
								</span>
								<?php if ( $psb_doc ) : ?>
									<a class="register-doc" href="<?php echo esc_url( $psb_doc ); ?>" target="_blank" rel="noopener"><?php echo psb_icon( 'doc' ); // phpcs:ignore ?>مشاهده سند</a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>

			<?php if ( $psb_tests ) : ?>
				<div data-reveal>
					<h3 class="register-title"><span>گزارش‌های آزمون نوعی و لیسانس</span><span><?php echo esc_html( psb_fa_num( psb_nav_count( 'type-test' ) ) . ' سند' ); ?></span></h3>
					<ol class="register">
						<?php foreach ( $psb_tests as $psb_p ) : ?>
							<?php
							$psb_id   = $psb_p->ID;
							$psb_body = (string) psb_field( 'issuing_body', '', $psb_id );
							$psb_lab  = preg_match( '/EPIL|SAREL/i', $psb_body, $m ) ? strtoupper( $m[0] ) : '';
							$psb_std  = (string) psb_field( 'standard_number', '', $psb_id );
							$psb_std  = array_filter( array_map( 'trim', preg_split( '/\s+(?:AND|and)\s+|;|،/u', $psb_std ) ) );
							$psb_num  = (string) psb_field( 'report_number', '', $psb_id );
							$psb_doc  = $psb_file_url( $psb_id, array( 'type_test_file', 'certificate_file' ) );
							?>
							<li>
								<span class="register-logo">
									<?php if ( $psb_lab ) : ?>
										<span class="lat"><?php echo esc_html( $psb_lab ); ?></span>
									<?php else : ?>
										<?php echo psb_icon( 'doc', 'size-5 text-slate-400' ); // phpcs:ignore ?>
									<?php endif; ?>
								</span>
								<span class="min-w-0">
									<a class="register-name card-link hover:text-primary-700" href="<?php echo esc_url( get_permalink( $psb_p ) ); ?>"><?php echo psb_lat( get_the_title( $psb_p ) ); // phpcs:ignore ?></a>
									<span class="register-meta">
										<?php if ( $psb_num ) : ?>
											<span><?php echo esc_html( 'گزارش ' . psb_fa_num( $psb_num ) ); ?></span>
										<?php endif; ?>
										<?php foreach ( array_slice( $psb_std, 0, 2 ) as $psb_s ) : ?>
											<span class="badge badge-standard"><?php echo esc_html( $psb_s ); ?></span>
										<?php endforeach; ?>
									</span>
								</span>
								<?php if ( $psb_doc ) : ?>
									<a class="register-doc" href="<?php echo esc_url( $psb_doc ); ?>" target="_blank" rel="noopener"><?php echo psb_icon( 'doc' ); // phpcs:ignore ?>مشاهده سند</a>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
