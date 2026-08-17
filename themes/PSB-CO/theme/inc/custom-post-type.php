<?php
// Register all CPTs and Taxonomies natively for PSB-CO

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
            'all_items' => 'همه ی نمونه کارها',
            'singular_name' => 'نمونه کار'
        ),
        'menu_icon' => 'dashicons-portfolio'
    ));

    register_taxonomy('portfolio_categories', 'portfolio', array(
        'hierarchical' => true,
        'label' => 'دسته بندی نمونه کار',
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
            'name' => 'خدمات ما',
            'singular_name' => 'خدمت',
            'add_new' => 'افزودن خدمت جدید',
            'add_new_item' => 'افزودن خدمت جدید',
            'edit_item' => 'ویرایش خدمت',
            'all_items' => 'همه‌ی خدمات',
            'menu_name' => 'خدمات ما',
        ),
        'menu_icon' => 'dashicons-admin-generic',
    ));

    register_taxonomy('services_categories', 'services', array(
        'hierarchical' => true,
        'label' => 'دسته بندی خدمات',
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

    // 3. Rating Post Type
    register_post_type('rating', array(
        'labels' => array(
            'name' => 'Ratings',
            'singular_name' => 'Rating',
            'menu_name' => 'Ratings'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title'),
        'rewrite' => array('slug' => 'ratings', 'with_front' => false)
    ));

    // 4. Certificates Post Type & Taxonomy
    register_post_type('certificates', array(
        'supports' => array('title', 'editor', 'custom-fields', 'thumbnail'),
        'rewrite' => array('slug' => 'certificates', 'with_front' => false),
        'has_archive' => true,
        'public' => true,
        'labels' => array(
            'name' => 'تائیدیه',
            'add_new' => 'افزودن تائیدیه جدید',
            'add_new_item' => 'افزودن تائیدیه جدید',
            'edit_item' => 'ویرایش تائیدیه',
            'all_items' => 'همه ی تائیدیه‌ها',
            'singular_name' => 'تائیدیه'
        ),
        'menu_icon' => 'dashicons-awards'
    ));

    register_taxonomy('certificate_category', 'certificates', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => 'دسته‌بندی تائیدیه',
            'singular_name' => 'دسته‌بندی تائیدیه',
            'menu_name' => 'دسته‌بندی تائیدیه',
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
        'menu_icon' => 'dashicons-verified'
    ));
}
add_action('init', 'psb_co_register_custom_post_types');