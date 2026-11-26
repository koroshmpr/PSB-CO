<?php
/**
 * bluebox functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package bluebox
 */

if ( ! defined( 'BLUEBOX_VERSION' ) ) {
	/*
	 * Set the theme’s version number.
	 *
	 * This is used primarily for cache busting. If you use `npm run bundle`
	 * to create your production build, the value below will be replaced in the
	 * generated zip file with a timestamp, converted to base 36.
	 */
	define( 'BLUEBOX_VERSION', '1.0.0' );
}

if ( ! defined( 'BLUEBOX_TYPOGRAPHY_CLASSES' ) ) {
	/*
	 * Set Tailwind Typography classes for the front end, block editor and
	 * classic editor using the constant below.
	 *
	 * For the front end, these classes are added by the `bluebox_content_class`
	 * function. You will see that function used everywhere an `entry-content`
	 * or `page-content` class has been added to a wrapper element.
	 *
	 * For the block editor, these classes are converted to a JavaScript array
	 * and then used by the `./javascript/block-editor.js` file, which adds
	 * them to the appropriate elements in the block editor (and adds them
	 * again when they’re removed.)
	 *
	 * For the classic editor (and anything using TinyMCE, like Advanced Custom
	 * Fields), these classes are added to TinyMCE’s body class when it
	 * initializes.
	 */
	define(
		'BLUEBOX_TYPOGRAPHY_CLASSES',
		'prose prose-neutral max-w-none prose-a:text-primary'
	);
}

if ( ! function_exists( 'bluebox_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function bluebox_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on bluebox, use a find and replace
		 * to change 'bluebox' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'bluebox', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// Register nav menu locations.
		register_nav_menus(
			array(
				'menu-1' => __( 'Primary', 'bluebox' ),
				'menu-3' => __( 'Mobile Menu', 'bluebox' ),
				'footerLocationOne' => __( 'Footer Menu 1', 'bluebox' ),
				'footerLocationTwo' => __( 'Footer Menu 2', 'bluebox' ),
				'footerLocationThree' => __( 'Footer Menu 3', 'bluebox' ),
			)
		);

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		// Add support for editor styles.
		add_theme_support( 'editor-styles' );

		// Enqueue editor styles.
		add_editor_style( 'style-editor.css' );
		add_editor_style( 'style-editor-extra.css' );

		// Add support for responsive embedded content.
		add_theme_support( 'responsive-embeds' );

		// Remove support for block templates.
		remove_theme_support( 'block-templates' );
	}
endif;
add_action( 'after_setup_theme', 'bluebox_setup' );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function bluebox_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Footer', 'bluebox' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Add widgets here to appear in your footer.', 'bluebox' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'bluebox_widgets_init' );

/**
 * Asset version: theme version + build time of the compiled file, so every
 * `npm run production` busts caches automatically.
 */
function psb_asset_ver( $relative ) {
	$path = get_template_directory() . '/' . ltrim( $relative, '/' );
	return BLUEBOX_VERSION . ( file_exists( $path ) ? '.' . filemtime( $path ) : '' );
}

/**
 * Enqueue scripts and styles.
 *
 * - style.css: single render-blocking stylesheet (tokens + components + utilities).
 * - script.min.js: Alpine + reveal + Swiper loader, deferred, in footer.
 * - swiper.min.js / swiper.min.css: NOT enqueued; loaded on demand by script.min.js
 *   only when a slider is near the viewport.
 */
function bluebox_scripts() {
	wp_enqueue_style( 'bluebox-style', get_stylesheet_uri(), array(), psb_asset_ver( 'style.css' ) );

	wp_enqueue_script(
		'bluebox-script',
		get_template_directory_uri() . '/js/script.min.js',
		array(),
		psb_asset_ver( 'js/script.min.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_localize_script( 'bluebox-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	wp_localize_script( 'bluebox-script', 'jsData', array( 'root_url' => get_site_url(), 'nonce' => wp_create_nonce( 'my-nonce' ) ) );
	// Page-cached HTML (Cache Enabler, never expires) carries a stale `my-nonce`: the login/OTP/register forms ask
	// admin-ajax for a fresh one right before submitting and fall back to the inline value (security review M3).
	wp_add_inline_script(
		'bluebox-script',
		'window.psbFreshNonce=function(f){return fetch(' . wp_json_encode( admin_url( 'admin-ajax.php?action=psb_my_nonce' ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES ) . ',{credentials:"same-origin",cache:"no-store"}).then(function(r){return r.json()}).then(function(j){return j&&j.success&&j.data&&j.data.nonce?j.data.nonce:f}).catch(function(){return f})};',
		'before'
	);
	wp_localize_script(
		'bluebox-script',
		'psbTheme',
		array(
			'jsBase' => get_template_directory_uri() . '/js/',
			'ver'    => psb_asset_ver( 'js/swiper.min.js' ),
		)
	);

	// Legacy variation helper: only for variable products (catalogue products are simple, so normally not loaded).
	$psb_product = ( function_exists( 'is_product' ) && is_product() && function_exists( 'wc_get_product' ) ) ? wc_get_product( get_queried_object_id() ) : null;
	if ( $psb_product && $psb_product->is_type( 'variable' ) ) {
		wp_enqueue_script(
			'single-product',
			get_template_directory_uri() . '/js/single-product.js',
			array( 'jquery' ),
			psb_asset_ver( 'js/single-product.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'bluebox_scripts' );

/**
 * <head> essentials printed before anything else:
 * - `js` class on <html> (enables reveal animations only when JS runs)
 * - preload of the two above-the-fold font weights (Regular, Bold)
 */
function bluebox_preload_fonts() {
	echo "<script>document.documentElement.classList.add('js')</script>\n";
	// View-transition direction for back/forward (must run before first render of the new page).
	echo "<script>(function(){var d=document.documentElement,b=function(t){d.classList.toggle('vt-back',t==='traverse')};addEventListener('pageswap',function(e){if(e.viewTransition&&e.activation)b(e.activation.navigationType)});addEventListener('pagereveal',function(e){if(e.viewTransition&&window.navigation&&navigation.activation){b(navigation.activation.navigationType);e.viewTransition.finished.finally(function(){d.classList.remove('vt-back')})}})})()</script>\n";
	$font_dir = get_template_directory_uri() . '/fonts/Peyda/fonts/woff2/';
	foreach ( array( 'Regular', 'Bold' ) as $weight ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( $font_dir . 'PeydaWebFaNum-' . $weight . '.woff2' )
		);
	}
}
add_action( 'wp_head', 'bluebox_preload_fonts', 1 );

/**
 * Enqueue the block editor script.
 */
function bluebox_enqueue_block_editor_script() {
	if ( is_admin() ) {
		wp_enqueue_script(
			'bluebox-editor',
			get_template_directory_uri() . '/js/block-editor.min.js',
			array(
				'wp-blocks',
				'wp-edit-post',
			),
			BLUEBOX_VERSION,
			true
		);
		wp_add_inline_script( 'bluebox-editor', "tailwindTypographyClasses = '" . esc_attr( BLUEBOX_TYPOGRAPHY_CLASSES ) . "'.split(' ');", 'before' );
	}
}
add_action( 'enqueue_block_assets', 'bluebox_enqueue_block_editor_script' );

/**
 * Add the Tailwind Typography classes to TinyMCE.
 *
 * @param array $settings TinyMCE settings.
 * @return array
 */
function bluebox_tinymce_add_class( $settings ) {
	$settings['body_class'] = BLUEBOX_TYPOGRAPHY_CLASSES;
	return $settings;
}
add_filter( 'tiny_mce_before_init', 'bluebox_tinymce_add_class' );

/**
 * Null-safe WooCommerce cart accessors.
 * WC()->cart is null on REST, feeds, cron, bots, admin-ajax and early hooks.
 */
if ( ! function_exists( 'psb_wc_cart' ) ) {
	function psb_wc_cart() {
		if ( ! function_exists( 'WC' ) ) {
			return null;
		}
		$wc = WC();
		return ( $wc && isset( $wc->cart ) && is_object( $wc->cart ) ) ? $wc->cart : null;
	}
}
if ( ! function_exists( 'psb_cart_count' ) ) {
	function psb_cart_count() {
		$cart = psb_wc_cart();
		return $cart ? (int) $cart->get_cart_contents_count() : 0;
	}
}

// Design-system helpers (phones, socials, icons, images …)
require get_template_directory() . '/inc/design-helpers.php';
require get_template_directory() . '/inc/nav.php';

//post types functions
require get_template_directory() . '/inc/post-type-functions/persian-date.php';
require get_template_directory() . '/inc/post-type-functions/reading-time.php';
require get_template_directory() . '/inc/post-type-functions/post-view.php';
require get_template_directory() . '/inc/post-type-functions/post-rating.php';
require get_template_directory() . '/inc/post-type-functions/toc.php';
require get_template_directory() . '/inc/post-type-functions/comments-field.php';
require get_template_directory() . '/inc/post-type-functions/inline-ads.php';
//product functions
require get_template_directory() . '/inc/product/check-woocommerce-active.php';
require get_template_directory() . '/inc/product/compare-function.php';
require get_template_directory() . '/inc/product/product-filters.php';
// Remove the result count and catalog ordering from the default WooCommerce hooks
//require get_template_directory() . '/inc/product/wc-variations-radio-buttons.php';
//require get_template_directory() . '/inc/product/variation-function.php';
require get_template_directory() . '/inc/product/out-stock-product.php';
require get_template_directory() . '/inc/product/filter-search-query.php';
// return-policy-schema.php intentionally not loaded (wrong brand + fake shipping/return data in product schema; schema lives in inc/seo.php).
require get_template_directory() . '/inc/product/shop-actions.php';
require get_template_directory() . '/inc/product/cart-fragments.php';
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/template-functions.php';
require get_template_directory() . '/inc/walker.php';
require get_template_directory() . '/inc/custom-post-type.php';
require get_template_directory() . '/inc/gravity-form.php';
require get_template_directory() . '/inc/optimize.php';
require get_template_directory() . '/inc/performance.php';  // Perfmatters replacement (asset trims, DB cleanup).
require get_template_directory() . '/inc/login-guard.php';  // Custom login URL (/secret-door/); inactive while Perfmatters owns it.
require get_template_directory() . '/inc/antispam.php';     // Honeypot + time token + content rules for Gravity Forms and comments.
require get_template_directory() . '/inc/otp-login-form.php';

/**
 * Feature modules (each is self-contained; missing files are skipped).
 */
foreach ( array(
	'/inc/placeholders.php',                   // Type-matched fallback illustrations.
	'/inc/stability.php',                      // HTTP circuit breaker, cache exclusions, cron.
	'/inc/seo.php',                            // JSON-LD on top of Rank Math.
	'/inc/ai-visibility/ai-visibility.php',    // llms.txt, robots AI rules, dashboard.
	'/inc/rfq/rfq.php',                        // Quote requests + bot notifications.
	'/inc/pwa/pwa.php',                        // Manifest, service worker, install prompt.
	'/inc/owner-panel/owner-panel.php',        // Mobile admin panel.
	'/inc/project-records/project-records.php', // «سوابق اجرایی» CPT, importer, page.
) as $psb_module ) {
	if ( is_readable( get_template_directory() . $psb_module ) ) {
		require_once get_template_directory() . $psb_module;
	}
}
unset( $psb_module );
include_once get_template_directory() . '/inc/search-route.php';
include_once get_template_directory() . '/inc/ajax-search.php';

/**
 * Fix WP Nav Menu active classes for Custom Post Types
 * Ensures CPT pages (Services, Portfolio, Certificates, Confirmation, Type-Test) highlight their respective menu item
 * and prevents the Blog (مقالات) menu item from incorrectly staying active.
 */
add_filter('nav_menu_css_class', 'psb_fix_cpt_menu_active_classes', 10, 3);
function psb_fix_cpt_menu_active_classes($classes, $item, $args) {
    if (is_admin()) return $classes;

    $cpt_slugs = array('services', 'portfolio', 'certificates', 'confirmation', 'type-test', 'product');

    // Determine current post type
    $current_cpt = '';
    if (is_singular($cpt_slugs)) {
        $current_cpt = get_post_type();
    } elseif (is_post_type_archive($cpt_slugs)) {
        $current_cpt = get_query_var('post_type');
        if (is_array($current_cpt)) {
            $current_cpt = reset($current_cpt);
        }
    }

    if ($current_cpt) {
        $posts_page_id = (int) get_option('page_for_posts');
        
        // Remove false active classes from Blog (مقالات) menu item
        if ($posts_page_id && (int)$item->object_id === $posts_page_id) {
            $classes = array_diff($classes, array('current_page_parent', 'current-menu-item', 'current_page_item', 'current-menu-parent', 'current-page-ancestor', '!text-black', 'before:scale-x-100', 'active'));
        }

        // Get sanitized URLs to compare
        $item_url = strtolower(trim((string)parse_url($item->url, PHP_URL_PATH), '/'));
        $cpt_archive_url = strtolower(trim((string)parse_url(get_post_type_archive_link($current_cpt), PHP_URL_PATH), '/'));

        // Also check direct slug match (e.g. /services, /portfolio, /certificates, /confirmation)
        $is_matching_cpt = (!empty($cpt_archive_url) && $item_url === $cpt_archive_url) || ($item_url === strtolower($current_cpt)) || (strpos($item_url, strtolower($current_cpt)) !== false);

        if ($is_matching_cpt) {
            $classes[] = 'current_page_item';
            $classes[] = 'current-menu-item';
            $classes[] = 'active';
            $classes[] = '!text-black';
            $classes[] = 'before:scale-x-100';
        }
    }

    return array_unique($classes);
}

