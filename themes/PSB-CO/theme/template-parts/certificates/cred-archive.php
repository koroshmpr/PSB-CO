<?php
/**
 * Register archive for approvals / type tests (spec 6.3): light head, hub tabs, register list grouped
 * (by issuer or lab) with a 2px rule per group, and a sticky end column with a tender note + quote CTA.
 * Uses the main query.
 *
 * $args: type, title, lead, group ('issuer'|'lab'|''), empty (array for empty-state)
 */
$psb_type = $args['type'] ?? get_post_type();
get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'eyebrow' => 'اعتبارنامه‌ها',
		'title'   => $args['title'] ?? '',
		'lead'    => $args['lead'] ?? '',
	)
);
get_template_part( 'template-parts/certificates/cred-nav', null, array( 'current' => $psb_type ) );

$psb_groups = array();
if ( have_posts() ) {
	global $wp_query;
	foreach ( $wp_query->posts as $psb_p ) {
		$psb_key = '';
		if ( 'issuer' === ( $args['group'] ?? '' ) ) {
			$psb_key = psb_confirmation_data( $psb_p->ID )['issuer'];
			// Normalise the two Tehran utilities and Tavanir naming so each issuer appears once.
			$psb_key = preg_replace( '/\s+/u', ' ', (string) $psb_key );
		} elseif ( 'lab' === ( $args['group'] ?? '' ) ) {
			$psb_d   = psb_type_test_data( $psb_p->ID );
			$psb_key = $psb_d['lab'] ? $psb_d['lab'] : $psb_d['issuer'];
		}
		$psb_groups[ $psb_key ? $psb_key : 'سایر' ][] = $psb_p->ID;
	}
}
?>
<section class="container py-section-sm lg:py-section">
	<div class="inner-grid">
		<div class="inner-main">
			<?php if ( $psb_groups ) : ?>
				<h2 class="sr-only"><?php echo esc_html( $args['title'] ?? 'فهرست اسناد' ); ?></h2>
				<div class="register">
					<?php foreach ( $psb_groups as $psb_name => $psb_ids ) : ?>
						<div class="register-group">
							<?php if ( count( $psb_groups ) > 1 ) : ?>
								<p class="register-head"><span class="<?php echo preg_match( '/^[A-Z]+$/', $psb_name ) ? 'lat' : ''; ?>"><?php echo esc_html( $psb_name ); ?></span><span class="text-xs font-medium"><?php echo esc_html( psb_fa_num( count( $psb_ids ) ) ); ?> سند</span></p>
							<?php else : ?>
								<div class="border-t-2 border-foreground"></div>
							<?php endif; ?>
							<?php
							foreach ( $psb_ids as $psb_id ) {
								get_template_part( 'template-parts/certificates/reg-row', null, array( 'post' => $psb_id ) );
							}
							?>
						</div>
					<?php endforeach; ?>
				</div>
				<?php psb_pagination(); ?>
			<?php else : ?>
				<?php get_template_part( 'template-parts/certificates/empty-state', null, $args['empty'] ?? array() ); ?>
			<?php endif; ?>
		</div>
		<aside class="inner-aside" aria-label="راهنما و استعلام">
			<div class="panel">
				<p class="panel-title">استفاده از این اسناد در مناقصه</p>
				<p class="text-[0.9375rem] leading-8 text-slate-600">تصویر هر سند در صفحه آن قابل مشاهده است. برای ارزیابی کیفی، مناقصه یا ثبت در وندورلیست، نسخه کامل مدارک و رزومه شرکت همراه با پیشنهاد فنی ارسال می‌شود.</p>
				<a class="btn btn-primary btn-block mt-5" href="<?php echo esc_url( psb_quote_url() ); ?>">درخواست مدارک و استعلام</a>
				<a class="link-arrow mt-4" href="<?php echo esc_url( psb_contact_url() ); ?>">تماس با واحد بازرگانی</a>
			</div>
		</aside>
	</div>
</section>
