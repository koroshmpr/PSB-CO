<?php
/**
 * Site header: <head>, body state (Alpine), skip link, sticky header.
 *
 * @package bluebox
 */

global $woo_active;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php if ( ! function_exists( 'psb_pwa_head_tags' ) ) : // The PWA module prints theme-color + manifest itself. ?>
	<meta name="theme-color" content="#0B1220">
	<?php endif; ?>
	<?php
	// LCP: preload the front-page hero photo (AVIF, per breakpoint; same srcset/sizes as the <picture>).
	if ( is_front_page() ) {
		$psb_fp   = (int) get_option( 'page_on_front' );
		$psb_hero = function_exists( 'psb_pf_image_id' ) ? psb_pf_image_id( psb_pf( 'home_hero_image', $psb_fp ) ) : 0;
		if ( $psb_hero ) {
			psb_pf_preload( $psb_hero, psb_pf_image_id( psb_pf( 'home_hero_image_mobile', $psb_fp ) ), '(min-width: 64rem) 60vw, 100vw' );
		} else {
			psb_photo_preload( 'hero' );
		}
	}

	wp_head();

	// Site-wide verification / analytics snippets from Site Setting (admin-controlled).
	echo psb_field( 'header-scripts', '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</head>

<body <?php body_class(); ?>
	x-data="{
		menuOpen: false, searchOpen: false, shopContact: false, categoryOpen: false, cart: false, openForm: false,
		stickyMenu: false, atBottom: false, intro: false,
		scrolled: false, scrollingUp: false, scrollingDown: false, lastScroll: 0, ticking: false,
		gridView: (document.cookie.match(/gridView=([^;]+)/) || [])[1] || 'large'
	}"
	x-init="
		$watch('gridView', v => document.cookie = `gridView=${v}; path=/; max-age=31536000`);
		const onScroll = () => {
			const y = window.scrollY;
			scrollingDown = y > lastScroll && y > 20;
			scrollingUp = y < lastScroll && y > 20;
			scrolled = y > 24;
			atBottom = window.innerHeight + y >= document.documentElement.scrollHeight - 120;
			lastScroll = y;
			ticking = false;
		};
		window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
		onScroll();
		requestAnimationFrame(() => intro = true);
	"
	:class="(searchOpen || categoryOpen || shopContact || openForm || menuOpen || cart) && 'overflow-hidden'">
<?php
echo psb_field( 'body-scripts', '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
wp_body_open();
?>
<a class="skip-link" href="#main">رفتن به محتوای اصلی</a>
<?php get_template_part( 'template-parts/layout/header-content' ); ?>

<main id="main" tabindex="-1"
	class="relative outline-none <?php echo ( $woo_active && function_exists( 'is_shop' ) && ( is_cart() || is_checkout() || is_account_page() ) ) ? 'container max-lg:px-2' : ''; ?>"
	data-post="<?php echo esc_attr( ( get_post_type() ?: 'page' ) . '-' . get_the_ID() ); ?>">
