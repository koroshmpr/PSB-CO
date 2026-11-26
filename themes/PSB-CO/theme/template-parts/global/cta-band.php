<?php
/**
 * Closing quote CTA band (spec v2 §4.8): full-bleed primary-700 red, white button + phone.
 * No embedded form — the /request-quote/ wizard is the single form.
 * Printed by footer.php (one per page). Disable on a template: add_filter( 'psb_show_cta_band', '__return_false' );
 *
 * $args: title, text, class
 */
$psb_phone = psb_primary_phone();
$psb_title = $args['title'] ?? 'استعلام قیمت برای پروژه';
$psb_text  = $args['text'] ?? 'نقشه تک‌خطی یا فهرست تجهیزات را ارسال کنید؛ کارشناس فنی برای بررسی با شما تماس می‌گیرد.';
?>
<section class="cta-band on-dark <?php echo esc_attr( $args['class'] ?? '' ); ?>" aria-labelledby="cta-band-title">
	<div class="container cta-band-inner">
		<div>
			<h2 id="cta-band-title" class="cta-band-title"><?php echo esc_html( $psb_title ); ?></h2>
			<p class="cta-band-text"><?php echo esc_html( $psb_text ); ?></p>
		</div>
		<div class="cta-band-actions">
			<a class="btn btn-white max-sm:w-full" href="<?php echo esc_url( psb_quote_url() ); ?>">
				ارسال درخواست استعلام
				<?php echo psb_icon( 'arrow', 'btn-arrow' ); // phpcs:ignore ?>
			</a>
			<?php if ( $psb_phone ) : ?>
				<a class="btn btn-on-dark max-sm:w-full" href="<?php echo esc_url( $psb_phone['href'] ); ?>">
					<?php echo psb_icon( 'phone' ); // phpcs:ignore ?>
					<span dir="ltr"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_phone['number'] ) ) ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</section>
