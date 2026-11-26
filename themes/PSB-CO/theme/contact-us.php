<?php
/**
 * Template Name: Contact Us - Editorial Style
 *
 * Contact: quick channels, office/factory cards (ACF options via helpers), Gravity Form,
 * maps loaded only after a click (no heavy iframe on page load).
 *
 * @package bluebox
 */

get_header();

$psb_form_id   = (int) psb_field( 'gravity_form', 0, get_the_ID() );
$psb_phones    = psb_phones();
$psb_emails    = psb_emails();
$psb_addresses = psb_addresses();
$psb_maps_raw  = psb_field( 'contact', array() );
$psb_socials   = psb_socials();

/** Embed URL of the "map" textarea (iframe code) of the contact row with this index; only Google Maps embeds. */
$psb_map_src = function ( $index ) use ( $psb_maps_raw ) {
	$row = is_array( $psb_maps_raw ) ? array_values( array_filter( $psb_maps_raw, function ( $r ) {
		return ! empty( $r['address'] );
	} ) ) : array();
	$html = (string) ( $row[ $index ]['map'] ?? '' );
	if ( preg_match( '#src="(https://www\.google\.com/maps/embed\?[^"]+)"#', $html, $m ) ) {
		return html_entity_decode( $m[1] );
	}
	return '';
};

/** Phone of a contact row (matched by title, e.g. «دفتر مرکزی» / «کارخانه»). */
$psb_phone_for = function ( $title ) use ( $psb_phones ) {
	foreach ( $psb_phones as $p ) {
		if ( $title && trim( $p['title'] ) === trim( $title ) ) {
			return $p;
		}
	}
	return null;
};

while ( have_posts() ) :
	the_post();
	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'variant' => 'light',
			'eyebrow' => 'ارتباط با ' . psb_brand_name(),
			'lead'    => 'برای استعلام قیمت، مشاوره فنی، بازدید از کارخانه یا پیگیری سفارش، از راه‌های ارتباطی زیر استفاده کنید. پاسخ‌گویی در ساعات اداری انجام می‌شود.',
		)
	);
	?>

	<section class="container py-section-sm lg:py-section" aria-labelledby="contact-form-title">
		<div class="grid items-start gap-6 lg:grid-cols-12">
			<div class="grid content-start gap-6 lg:col-span-7">
				<h2 class="sr-only">دفتر مرکزی و کارخانه</h2>
				<?php foreach ( $psb_addresses as $psb_i => $psb_a ) : ?>
					<?php
					$psb_ph  = $psb_phone_for( $psb_a['title'] );
					$psb_src = $psb_map_src( $psb_i );
					?>
					<article class="contact-card">
						<div class="flex items-start gap-3">
							<span class="icon-tile" aria-hidden="true"><?php echo psb_icon( false !== mb_strpos( (string) $psb_a['title'], 'کارخانه' ) ? 'factory' : 'map' ); // phpcs:ignore ?></span>
							<div class="min-w-0">
								<h3 class="text-base font-bold text-dark"><?php echo esc_html( $psb_a['title'] ? $psb_a['title'] : 'نشانی' ); ?></h3>
								<p class="mt-1 text-sm leading-7 text-slate-600"><?php echo esc_html( psb_fa_num( $psb_a['address'] ) ); ?></p>
							</div>
						</div>
						<div class="flex flex-wrap gap-2">
							<?php if ( $psb_ph ) : ?>
								<a class="btn btn-outline btn-sm" href="<?php echo esc_url( $psb_ph['href'] ); ?>"><?php echo psb_icon( 'phone', 'size-4' ); // phpcs:ignore ?><span dir="ltr"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_ph['number'] ) ) ); ?></span></a>
							<?php endif; ?>
							<?php if ( $psb_a['map_link'] ) : ?>
								<a class="btn btn-ghost btn-sm" href="<?php echo esc_url( $psb_a['map_link'] ); ?>" target="_blank" rel="noopener"><?php echo psb_icon( 'map', 'size-4' ); // phpcs:ignore ?> مسیریابی</a>
							<?php endif; ?>
						</div>
						<?php if ( $psb_src ) : ?>
							<div class="map-placeholder" data-map>
								<?php echo psb_icon( 'map', 'size-8 text-foreground' ); // phpcs:ignore ?>
								<button type="button" class="btn btn-outline btn-sm" data-map-load="<?php echo esc_url( $psb_src ); ?>" data-map-title="<?php echo esc_attr( 'نقشه ' . $psb_a['title'] ); ?>">نمایش نقشه</button>
								<p class="px-4 text-xs text-slate-500">نقشه گوگل پس از کلیک بارگذاری می‌شود.</p>
							</div>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>

			</div>
			<div class="grid content-start gap-6 lg:col-span-5">
			<div class="panel">
				<h2 id="contact-form-title" class="text-h3 font-bold text-foreground">ارسال پیام</h2>
				<p class="mt-2 text-sm leading-7 text-slate-600">برای استعلام قیمت دقیق‌تر، از <a class="font-bold text-primary-700" href="<?php echo esc_url( psb_quote_url() ); ?>">فرم استعلام</a> استفاده کنید و نقشه تک‌خطی یا مشخصات پروژه را پیوست کنید.</p>
				<div class="gform-psb mt-6">
					<?php
					if ( $psb_form_id && shortcode_exists( 'gravityform' ) ) {
						echo do_shortcode( '[gravityform id="' . (int) $psb_form_id . '" title="false" description="false" ajax="true"]' );
					} elseif ( '' !== trim( get_the_content() ) ) {
						the_content();
					} else {
						echo '<a class="btn btn-primary" href="' . esc_url( psb_quote_url() ) . '">ثبت درخواست استعلام</a>';
					}
					?>
				</div>
			</div>

				<?php if ( count( $psb_phones ) > 1 || $psb_emails ) : ?>
					<div class="contact-card">
						<h3 class="text-base font-bold text-foreground">خطوط مستقیم</h3>
						<dl class="spec-list">
							<?php foreach ( $psb_phones as $psb_p ) : ?>
								<div><dt class="flex items-center gap-2"><?php echo psb_icon( false !== mb_strpos( (string) $psb_p['title'], 'فاکس' ) ? 'fax' : 'phone', 'size-4 text-slate-500' ); // phpcs:ignore ?><?php echo esc_html( $psb_p['title'] ? $psb_p['title'] : 'تلفن' ); ?></dt><dd><a class="ltr-num font-bold text-dark hover:text-primary-700" href="<?php echo esc_url( $psb_p['href'] ); ?>"><?php echo esc_html( psb_fa_num( psb_format_phone( $psb_p['number'] ) ) ); ?></a></dd></div>
							<?php endforeach; ?>
							<?php foreach ( $psb_emails as $psb_e ) : ?>
								<div><dt class="flex items-center gap-2"><?php echo psb_icon( 'mail', 'size-4 text-slate-500' ); // phpcs:ignore ?>ایمیل</dt><dd><a class="font-bold text-dark hover:text-primary-700" dir="ltr" href="mailto:<?php echo esc_attr( $psb_e ); ?>"><?php echo esc_html( $psb_e ); ?></a></dd></div>
							<?php endforeach; ?>
						</dl>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<script>
	document.addEventListener('click',function(e){var b=e.target.closest('[data-map-load]');if(!b)return;var box=b.closest('[data-map]'),f=document.createElement('iframe');f.src=b.getAttribute('data-map-load');f.title=b.getAttribute('data-map-title')||'نقشه';f.loading='lazy';f.referrerPolicy='no-referrer-when-downgrade';f.allowFullscreen=true;box.appendChild(f);b.remove();});
	</script>
	<?php
endwhile;
get_footer();
