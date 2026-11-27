<?php
/**
 * Issuer strip (spec v2 §4.7 / §5.2): white band under the hero. Each approval issuer once
 * (greyscale logo 40px + name, linking to its approval), then the labs as Latin wordmarks.
 * Hidden when there are no approvals.
 */
$psb_issuers = array();
foreach ( get_posts( array( 'post_type' => 'confirmation', 'posts_per_page' => 50, 'no_found_rows' => true ) ) as $psb_p ) {
	// Approvals / vendor-list entries only (titles «تأییدیه…»); customer satisfaction letters and tender
	// qualifications live in the same post type but are not «approved manufacturer» listings.
	if ( ! preg_match( '/^\s*ت(أ|ا)یید(یه|یهٔ)/u', get_the_title( $psb_p ) ) ) {
		continue;
	}
	$psb_org = trim( (string) psb_field( 'issuing_organization', '', $psb_p->ID ) );
	$psb_org = $psb_org ? $psb_org : get_the_title( $psb_p );
	$psb_key = psb_search_normalize( $psb_org );
	if ( ! isset( $psb_issuers[ $psb_key ] ) ) {
		$psb_issuers[ $psb_key ] = array(
			'name'  => $psb_org,
			'url'   => get_permalink( $psb_p ),
			'thumb' => get_post_thumbnail_id( $psb_p ),
		);
	}
}
if ( ! $psb_issuers ) {
	return;
}
$psb_labs = array();
foreach ( get_posts( array( 'post_type' => 'type-test', 'posts_per_page' => 20, 'no_found_rows' => true ) ) as $psb_p ) {
	$psb_body = (string) psb_field( 'issuing_body', '', $psb_p->ID );
	if ( false !== stripos( $psb_body, 'EPIL' ) && ! isset( $psb_labs['EPIL'] ) ) {
		$psb_labs['EPIL'] = array( 'آزمایشگاه آزمون نوعی', get_permalink( $psb_p ) );
	} elseif ( false !== stripos( $psb_body, 'SAREL' ) && ! isset( $psb_labs['SAREL'] ) ) {
		$psb_labs['SAREL'] = array( 'لیسانس ساخت', get_permalink( $psb_p ) );
	}
}
?>
<section class="border-b border-line bg-white" aria-label="مراجع تأیید و آزمون">
	<div class="container py-5 md:py-6">
		<?php $psb_label = psb_pf_text( psb_pf( 'home_issuers_label', (int) get_option( 'page_on_front' ) ) ); ?>
		<?php if ( $psb_label ) : ?>
			<p class="mb-3 text-xs font-semibold text-slate-600 md:mb-4"><?php echo esc_html( rtrim( $psb_label, ': ' ) ); ?></p>
		<?php endif; ?>
		<ul class="issuer-row" role="list">
			<?php foreach ( $psb_issuers as $psb_i ) : ?>
				<li class="shrink-0">
					<a class="issuer-item" href="<?php echo esc_url( $psb_i['url'] ); ?>">
						<?php if ( $psb_i['thumb'] ) : ?>
							<?php echo psb_image( $psb_i['thumb'], 'thumbnail', array( 'class' => 'h-9 w-auto shrink-0 opacity-80 grayscale contrast-110 mix-blend-multiply', 'alt' => '', 'srcset' => false, 'sizes' => false ) ); // phpcs:ignore ?>
						<?php endif; ?>
						<span class="issuer-name"><?php echo esc_html( $psb_i['name'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
			<?php if ( $psb_labs ) : ?>
				<li class="h-9 w-px shrink-0 bg-line" aria-hidden="true"></li>
				<?php foreach ( $psb_labs as $psb_lab => $psb_info ) : ?>
					<li class="shrink-0">
						<a class="issuer-item" href="<?php echo esc_url( $psb_info[1] ); ?>">
							<span class="flex flex-col leading-tight">
								<span class="lat text-base font-bold tracking-[0.06em] text-slate-600"><?php echo esc_html( $psb_lab ); ?></span>
								<span class="text-xs text-slate-600"><?php echo esc_html( $psb_info[0] ); ?></span>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>
	</div>
</section>
