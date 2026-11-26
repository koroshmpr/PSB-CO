<?php
/**
 * RFQ quick request (name + mobile + product + note) — for product pages, home page, modals.
 *
 * $args:
 * - product  int|string|WP_Post  product ID / slug to prefill (default: current product on single product pages)
 * - title    heading text (default «استعلام سریع قیمت»); pass '' to hide via 'hide_title' => true
 * - text     short lead
 * - tag      heading tag (h2|h3, default h2)
 * - class    extra classes
 *
 * Usage: get_template_part( 'template-parts/rfq/quick-form', null, array( 'product' => get_the_ID() ) ) or [psb_rfq_quick product="slug"].
 *
 * @package PSB-CO
 */

if ( ! function_exists( 'psb_rfq_enqueue' ) ) {
	return;
}
psb_rfq_enqueue();
require_once __DIR__ . '/helpers.php';

$psb_product = isset( $args['product'] ) && '' !== $args['product'] ? $args['product'] : ( is_singular( 'product' ) ? get_the_ID() : 0 );
if ( is_string( $psb_product ) && ! is_numeric( $psb_product ) ) {
	$psb_product = get_page_by_path( sanitize_title( $psb_product ), OBJECT, 'product' );
}
$psb_post  = $psb_product ? get_post( $psb_product ) : null;
$psb_type  = $psb_post ? psb_rfq_type_for_product( $psb_post ) : '';
$psb_ptitle = $psb_post ? html_entity_decode( get_the_title( $psb_post ), ENT_QUOTES, 'UTF-8' ) : '';
$psb_uid   = 'rfqq' . wp_rand( 1000, 9999 );
$psb_tag   = in_array( isset( $args['tag'] ) ? $args['tag'] : 'h2', array( 'h2', 'h3' ), true ) ? ( isset( $args['tag'] ) ? $args['tag'] : 'h2' ) : 'h2';
$psb_title = isset( $args['title'] ) && '' !== $args['title'] ? $args['title'] : 'استعلام سریع قیمت';
$psb_text  = isset( $args['text'] ) && '' !== $args['text'] ? $args['text'] : 'نام و شماره موبایل خود را وارد کنید؛ کارشناس فروش برای تکمیل مشخصات با شما تماس می‌گیرد.';
$psb_full  = function_exists( 'psb_quote_url' ) ? psb_quote_url( $psb_post ? rawurldecode( $psb_post->post_name ) : '' ) : home_url( '/request-quote/' );
?>
<div class="rfq rfq-quick <?php echo esc_attr( isset( $args['class'] ) ? $args['class'] : '' ); ?>" id="<?php echo esc_attr( $psb_uid ); ?>" data-rfq="quick" data-product="<?php echo esc_attr( $psb_ptitle ); ?>">
	<form class="rfq-form" novalidate <?php echo empty( $args['hide_title'] ) ? 'aria-labelledby="' . esc_attr( $psb_uid ) . '-h"' : 'aria-label="' . esc_attr( $psb_title ) . '"'; ?>>
		<?php if ( empty( $args['hide_title'] ) ) : ?>
			<<?php echo esc_html( $psb_tag ); ?> class="rfq-quick-title" id="<?php echo esc_attr( $psb_uid ); ?>-h"><?php echo esc_html( $psb_title ); ?></<?php echo esc_html( $psb_tag ); ?>>
			<p class="rfq-quick-lead"><?php echo esc_html( $psb_text ); ?></p>
		<?php endif; ?>
		<div class="rfq-hp" aria-hidden="true"><label>وب‌سایت <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
		<div class="rfq-grid">
			<div class="rfq-field" data-f="contact.name">
				<label class="rfq-label" for="<?php echo esc_attr( $psb_uid ); ?>-name">نام و نام خانوادگی <span class="rfq-req" aria-hidden="true">*</span></label>
				<input class="rfq-input" id="<?php echo esc_attr( $psb_uid ); ?>-name" name="contact.name" autocomplete="name" maxlength="80" aria-required="true" aria-describedby="<?php echo esc_attr( $psb_uid ); ?>-name-err">
				<p class="rfq-err" id="<?php echo esc_attr( $psb_uid ); ?>-name-err" aria-live="polite"></p>
			</div>
			<div class="rfq-field" data-f="contact.mobile">
				<label class="rfq-label" for="<?php echo esc_attr( $psb_uid ); ?>-mobile">شماره موبایل <span class="rfq-req" aria-hidden="true">*</span></label>
				<input class="rfq-input rfq-ltr" type="tel" inputmode="tel" dir="ltr" id="<?php echo esc_attr( $psb_uid ); ?>-mobile" name="contact.mobile" autocomplete="tel" maxlength="20" placeholder="۰۹۱۲ ۱۲۳ ۴۵۶۷" aria-required="true" aria-describedby="<?php echo esc_attr( $psb_uid ); ?>-mobile-err">
				<p class="rfq-err" id="<?php echo esc_attr( $psb_uid ); ?>-mobile-err" aria-live="polite"></p>
			</div>
			<div class="rfq-field" data-f="items">
				<label class="rfq-label" for="<?php echo esc_attr( $psb_uid ); ?>-type">نوع تابلو</label>
				<select class="rfq-input" id="<?php echo esc_attr( $psb_uid ); ?>-type" name="quick.type">
					<?php foreach ( psb_rfq_types() as $psb_k => $psb_t ) : ?>
						<option value="<?php echo esc_attr( $psb_k ); ?>" <?php selected( $psb_type ? $psb_type : 'other', $psb_k ); ?>><?php echo psb_rfq_bidi_plain( $psb_t['label'] ); // phpcs:ignore -- escaped by psb_rfq_bidi_plain(). ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="rfq-field" data-f="contact.company">
				<label class="rfq-label" for="<?php echo esc_attr( $psb_uid ); ?>-company">شرکت / سازمان <span class="rfq-opt">(اختیاری)</span></label>
				<input class="rfq-input" id="<?php echo esc_attr( $psb_uid ); ?>-company" name="contact.company" autocomplete="organization" maxlength="120">
			</div>
			<div class="rfq-field rfq-field--wide" data-f="project.description">
				<label class="rfq-label" for="<?php echo esc_attr( $psb_uid ); ?>-note">توضیح کوتاه <span class="rfq-opt">(اختیاری)</span></label>
				<textarea class="rfq-input" rows="3" id="<?php echo esc_attr( $psb_uid ); ?>-note" name="project.description" maxlength="3000" placeholder="مثلاً: تابلو MDB ۱۶۰۰ آمپر برای یک ساختمان اداری، تحویل دو ماه دیگر"></textarea>
			</div>
			<div class="rfq-field rfq-field--challenge" data-f="challenge" data-challenge hidden>
				<label class="rfq-label" for="<?php echo esc_attr( $psb_uid ); ?>-answer">سؤال امنیتی: حاصل <bdi class="rfq-q" data-q></bdi> چند می‌شود؟ <span class="rfq-req" aria-hidden="true">*</span></label>
				<input class="rfq-input rfq-ltr" type="text" inputmode="numeric" dir="ltr" id="<?php echo esc_attr( $psb_uid ); ?>-answer" name="answer" maxlength="3" autocomplete="off" aria-describedby="<?php echo esc_attr( $psb_uid ); ?>-answer-err">
				<p class="rfq-err" id="<?php echo esc_attr( $psb_uid ); ?>-answer-err" aria-live="polite"></p>
			</div>
		</div>
		<p class="rfq-err rfq-err--submit" data-err="submit" role="alert"></p>
		<div class="rfq-quick-actions">
			<button type="submit" class="btn btn-primary" data-act="submit">ارسال درخواست</button>
			<a class="rfq-link" href="<?php echo esc_url( $psb_full ); ?>">فرم کامل، با امکان ارسال نقشه</a>
		</div>
		<p class="rfq-privacy"><?php echo esc_html( psb_rfq_opt( 'privacy_note' ) ); ?></p>
	</form>
	<div class="rfq-success rfq-success--quick" data-success hidden tabindex="-1">
		<span class="rfq-success-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
		<p class="rfq-success-title" data-s-title></p>
		<p class="rfq-code"><span>کد پیگیری</span> <strong class="rfq-lat" data-s-code dir="ltr"></strong></p>
		<p class="rfq-success-time" data-s-time></p>
	</div>
</div>
