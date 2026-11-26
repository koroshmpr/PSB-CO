<?php
/**
 * «درباره پارس سوئیچ بورد» box under articles (spec 6.4). No author link (author archives redirect).
 */
$psb_logo = psb_field( 'site_logo', null );
?>
<section class="about-box mt-10" aria-label="درباره <?php echo esc_attr( psb_brand_name() ); ?>">
	<?php if ( $psb_logo ) : ?>
		<?php echo psb_image( $psb_logo, 'thumbnail', array( 'alt' => psb_brand_name(), 'class' => 'h-8 w-auto shrink-0 object-contain', 'sizes' => '64px' ) ); // phpcs:ignore ?>
	<?php endif; ?>
	<div class="min-w-0">
		<p class="font-bold text-foreground">درباره <?php echo esc_html( psb_brand_name() ); ?></p>
		<p class="mt-1 text-[0.9375rem] leading-8 text-slate-600">طراحی و ساخت تابلوهای برق فشار متوسط، فشار ضعیف و فرمان و کنترل از سال <?php echo esc_html( psb_fa_num( PSB_FOUNDED_JALALI ) ); ?>؛ این مقاله توسط تیم فنی شرکت تهیه شده است.</p>
		<a class="link-arrow mt-2" href="<?php echo esc_url( home_url( '/about-us/' ) ); ?>">درباره شرکت</a>
	</div>
</section>
