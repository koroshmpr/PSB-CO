<?php
/**
 * Issuer strip (spec v2 §4.7 / §5.2): white band under the hero. Each approval issuer once
 * (greyscale logo 40px + name, linking to its approval), then the labs as Latin wordmarks.
 * Hidden when there are no approvals.
 */
$psb_issuers = array();
foreach ( get_posts( array( 'post_type' => 'confirmation', 'posts_per_page' => 20, 'no_found_rows' => true ) ) as $psb_p ) {
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
	<div class="container flex flex-col gap-4 py-6 md:flex-row md:items-center md:gap-10 md:py-7">
		<p class="max-w-[9.5rem] shrink-0 text-[0.8125rem] font-semibold leading-[1.6] text-slate-600 max-md:max-w-none">در فهرست سازندگان تأییدشده و آزمون‌شده نزد:</p>
		<ul class="-mx-[var(--spacing-gutter)] flex flex-1 items-center gap-7 overflow-x-auto px-[var(--spacing-gutter)] [scrollbar-width:none] md:mx-0 md:flex-wrap md:justify-start md:gap-x-9 md:gap-y-4 md:overflow-visible md:px-0" role="list">
			<?php foreach ( $psb_issuers as $psb_i ) : ?>
				<li class="shrink-0">
					<a class="flex items-center gap-3 text-[0.8125rem] font-medium leading-normal text-slate-700 hover:text-foreground" href="<?php echo esc_url( $psb_i['url'] ); ?>">
						<?php if ( $psb_i['thumb'] ) : ?>
							<?php echo psb_image( $psb_i['thumb'], 'thumbnail', array( 'class' => 'h-10 w-auto opacity-80 grayscale contrast-110 mix-blend-multiply', 'alt' => '', 'srcset' => false, 'sizes' => false ) ); // phpcs:ignore ?>
						<?php endif; ?>
						<span class="max-w-32"><?php echo esc_html( $psb_i['name'] ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
			<?php if ( $psb_labs ) : ?>
				<li class="hidden h-9 w-px shrink-0 bg-line md:block" aria-hidden="true"></li>
				<?php foreach ( $psb_labs as $psb_lab => $psb_info ) : ?>
					<li class="shrink-0">
						<a class="flex items-center gap-3 text-[0.8125rem] font-medium text-slate-700 hover:text-foreground" href="<?php echo esc_url( $psb_info[1] ); ?>">
							<span class="lat text-lg font-bold tracking-[0.06em] text-slate-600"><?php echo esc_html( $psb_lab ); ?></span>
							<span><?php echo esc_html( $psb_info[0] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>
	</div>
</section>
