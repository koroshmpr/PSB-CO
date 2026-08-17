<?php
/**
 * Reusable Footer Navigation Menu Column Component
 * PSB-CO High-Voltage Theme
 * 
 * Supported $args:
 * - 'theme_location': string (required, e.g. 'footerLocationOne', 'footerLocationTwo', 'footerLocationThree')
 * - 'fallback_title': string (optional)
 * - 'fallback_links': array of array('url' => '...', 'label' => '...') (optional)
 */

$theme_location = $args['theme_location'] ?? 'footerLocationOne';
$fallback_title = $args['fallback_title'] ?? 'فهرست فوتر';
$fallback_links = $args['fallback_links'] ?? array();

// Dynamically retrieve menu title set in WP Admin for this location
$menu_title = $fallback_title;
$locations = get_nav_menu_locations();
if (isset($locations[$theme_location]) && $locations[$theme_location] > 0) {
    $menu_obj = wp_get_nav_menu_object($locations[$theme_location]);
    if ($menu_obj && !empty($menu_obj->name)) {
        $menu_title = $menu_obj->name;
    }
}
?>

<div class="lg:col-span-2 space-y-4 select-none">
    <h4 class="font-bold text-white text-base border-b border-slate-800/80 pb-2.5 flex items-center justify-between">
        <span class="flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block"></span>
            <?= esc_html($menu_title); ?>
        </span>
    </h4>
    <?php
    if (has_nav_menu($theme_location)) {
        wp_nav_menu(array(
            'theme_location' => $theme_location,
            'menu_class'     => 'flex flex-col gap-2.5 text-xs text-slate-300 font-medium',
            'container'      => false,
            'depth'          => 1,
            'link_before'    => '<span class="inline-flex items-center gap-2 text-white/80 hover:text-blue-400 transition-colors py-1"><span class="w-1 h-1 rounded-full bg-slate-600"></span>',
            'link_after'     => '</span>',
        ));
    } elseif (!empty($fallback_links)) {
        echo '<ul class="flex flex-col gap-2.5 text-xs text-slate-300 font-medium">';
        foreach ($fallback_links as $link) {
            echo '<li><a href="' . esc_url($link['url']) . '" class="inline-flex items-center gap-2 hover:text-blue-400 transition-colors py-1"><span class="w-1 h-1 rounded-full bg-slate-600"></span>' . esc_html($link['label']) . '</a></li>';
        }
        echo '</ul>';
    }
    ?>
</div>
