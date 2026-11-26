<?php
// Register all CPTs and Taxonomies natively for PSB-CO

// Customer letters (رضایت‌نامه‌ها) CPT, letter_type taxonomy, meta box, version-gated rewrite flush, lightbox.
require_once __DIR__ . '/post-type-functions/customer-letters.php';
// Article series taxonomy (series) + term meta + part order.
require_once __DIR__ . '/post-type-functions/series.php';

function psb_co_register_custom_post_types() {
    // 1. Portfolio Post Type & Taxonomies
    register_post_type('portfolio', array(
        'supports' => array('title', 'editor', 'comments', 'excerpt', 'custom-fields', 'thumbnail'),
        'rewrite' => array('slug' => 'portfolios', 'with_front' => false),
        'has_archive' => true,
        'public' => true,
        'labels' => array(
            'name' => 'نمونه کارها',
            'add_new' => 'افزودن نمونه کار جدید',
            'add_new_item' => 'افزودن نمونه کار جدید',
            'edit_item' => 'ویرایش نمونه کار',
            'all_items' => 'همه نمونه‌کارها',
            'singular_name' => 'نمونه کار'
        ),
        'menu_icon' => 'dashicons-portfolio'
    ));

    register_taxonomy('portfolio_categories', 'portfolio', array(
        'hierarchical' => true,
        'label' => 'دسته‌بندی نمونه کار',
        'query_var' => true,
        'rewrite' => array('slug' => 'portfolio-category', 'with_front' => false),
    ));

    register_taxonomy('portfolio_tag', 'portfolio', array(
        'hierarchical' => false,
        'labels' => array(
            'name' => 'برچسب‌های نمونه کارها',
            'singular_name' => 'برچسب نمونه کار',
            'menu_name' => 'برچسب نمونه کار',
        ),
        'show_ui' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug' => 'tag-portfolio', 'with_front' => false),
    ));

    // 2. Services Post Type & Taxonomies
    register_post_type('services', array(
        'supports' => array('title', 'editor', 'comments', 'excerpt', 'custom-fields', 'thumbnail'),
        'rewrite' => array('slug' => 'services', 'with_front' => false),
        'has_archive' => true,
        'public' => true,
        'labels' => array(
            'name' => 'خدمات',
            'singular_name' => 'خدمت',
            'add_new' => 'افزودن خدمت جدید',
            'add_new_item' => 'افزودن خدمت جدید',
            'edit_item' => 'ویرایش خدمت',
            'all_items' => 'همه‌ی خدمات',
            'menu_name' => 'خدمات',
        ),
        'menu_icon' => 'dashicons-admin-generic',
    ));

    register_taxonomy('services_categories', 'services', array(
        'hierarchical' => true,
        'label' => 'دسته‌بندی خدمات',
        'query_var' => true,
    ));

    register_taxonomy('services_tag', 'services', array(
        'hierarchical' => false,
        'labels' => array(
            'name' => 'برچسب‌های خدمات',
            'singular_name' => 'برچسب خدمت',
            'menu_name' => 'برچسب خدمات',
        ),
        'show_ui' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug' => 'tag-services', 'with_front' => false),
    ));

    // 4. Certificates Post Type & Taxonomy
    register_post_type('certificates', array(
        'supports' => array('title', 'editor', 'custom-fields', 'thumbnail', 'excerpt'),
        'rewrite' => array('slug' => 'certificates', 'with_front' => false),
        'has_archive' => true,
        'public' => true,
        'labels' => array(
            'name' => 'گواهینامه‌ها',
            'singular_name' => 'گواهینامه',
            'menu_name' => 'گواهینامه‌ها',
            'add_new' => 'افزودن گواهینامه جدید',
            'add_new_item' => 'افزودن گواهینامه جدید',
            'edit_item' => 'ویرایش گواهینامه',
            'all_items' => 'همه گواهینامه‌ها'
        ),
        'menu_icon' => 'dashicons-id-alt'
    ));

    register_taxonomy('certificate_category', 'certificates', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => 'دسته‌بندی گواهینامه',
            'singular_name' => 'دسته‌بندی گواهینامه',
            'menu_name' => 'دسته‌بندی گواهینامه',
        ),
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'certificate-category', 'with_front' => false),
    ));

    // 5. Confirmation Post Type
    register_post_type('confirmation', array(
        'supports' => array('title', 'editor', 'custom-fields', 'thumbnail'),
        'rewrite' => array('slug' => 'confirmation', 'with_front' => false),
        'has_archive' => true,
        'public' => true,
        'labels' => array(
            'name' => 'تأییدیه‌ها',
            'singular_name' => 'تأییدیه',
            'menu_name' => 'تأییدیه‌ها',
            'all_items' => 'همه تأییدیه‌ها',
            'add_new' => 'افزودن تأییدیه جدید',
            'add_new_item' => 'افزودن تأییدیه جدید',
            'edit_item' => 'ویرایش تأییدیه'
        ),
        'menu_icon' => 'dashicons-awards'
    ));

    // 6. Type-Test Post Type & Taxonomy (آزمون‌های تایپ‌تست و استانداردهای تجهیزات برق)
    register_post_type('type-test', array(
        'supports' => array('title', 'editor', 'custom-fields', 'thumbnail', 'excerpt'),
        'rewrite' => array('slug' => 'type-test', 'with_front' => false),
        'has_archive' => true,
        'public' => true,
        'labels' => array(
            'name' => 'تایپ‌تست‌ها',
            'singular_name' => 'تایپ‌تست',
            'menu_name' => 'تایپ تست',
            'all_items' => 'همه تایپ تست‌ها',
            'add_new' => 'افزودن تایپ تست جدید',
            'add_new_item' => 'افزودن تایپ تست جدید',
            'edit_item' => 'ویرایش تایپ تست'
        ),
        'menu_icon' => 'dashicons-yes-alt'
    ));

    register_taxonomy('type_test_category', 'type-test', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => 'دسته‌بندی تایپ‌تست',
            'singular_name' => 'دسته‌بندی تایپ‌تست',
            'menu_name' => 'دسته‌بندی تایپ‌تست',
        ),
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'type-test-category', 'with_front' => false),
    ));
}
add_action('init', 'psb_co_register_custom_post_types');