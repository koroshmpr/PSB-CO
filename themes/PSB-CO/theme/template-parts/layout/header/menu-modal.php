<?php
/**
 * Ultra-Premium Full-Width Mobile Navigation Modal & Drawer
 * PSB-CO High-Voltage Electrical Panel Theme
 */
$phones = get_field('phones', 'option') ?: get_field('phone', 'option');
$first_phone = '';
if (!empty($phones) && is_array($phones)) {
    foreach ($phones as $p) {
        $num_obj = $p['number'] ?? ($p['phone'] ?? null);
        if (is_array($num_obj)) {
            $num_obj = $num_obj['url'] ?? '';
        }
        $raw_num = is_string($num_obj) ? $num_obj : '';
        $clean_num = preg_replace('#^https?://#i', '', $raw_num);
        $clean_num = str_replace('tel:', '', $clean_num);
        if ($clean_num) {
            $first_phone = $clean_num;
            break;
        }
    }
}
if (!$first_phone) {
    $first_phone = get_field('phone_number', 'option') ?: '02166707611';
}
$phone_href = 'tel:' . preg_replace('/[^0-9+]/', '', $first_phone);
?>

<div id="mobileMenu"
     x-show="menuOpen"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     @keydown.escape.window="menuOpen = false"
     class="fixed inset-0 z-[100] w-full h-full bg-slate-950/98 backdrop-blur-2xl text-white select-none flex flex-col justify-between p-6 sm:p-8 overflow-y-auto"
     style="display: none;">

    <!-- 1. Top Header Bar -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-5 shrink-0">
        <div class="flex items-center gap-3">
            <?php get_template_part('template-parts/global/logo', null, [
                'logoSize' => 'h-10 w-auto',
                'class' => 'inline-block',
                'logoLink' => 'footer_logo',
            ]); ?>
            <div class="space-y-0.5">
                <span class="text-xs font-bold text-blue-400 block tracking-wide">پارس سویچ برد</span>
                <span class="text-[10px] text-slate-400 block font-mono">منوی دسترسی سریع</span>
            </div>
        </div>

        <!-- Close Button -->
        <button @click="menuOpen = false" aria-label="بستن منو" class="w-10 h-10 rounded-2xl bg-slate-900 border border-slate-800 hover:bg-red-600 hover:border-red-500 text-slate-300 hover:text-white flex items-center justify-center transition-all cursor-pointer shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- 2. Full-Width Navigation Links -->
    <nav class="my-6 space-y-2 flex-1 overflow-y-auto py-4">
        <?php
        if (has_nav_menu('menu-3')) {
            wp_nav_menu(array(
                'theme_location' => 'menu-3',
                'menu_id' => 'mobile-menu-items',
                'menu_class' => 'space-y-3 font-bold text-base text-slate-200',
                'container' => false,
                'depth' => 2,
                'link_before' => '<span class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800/80 hover:bg-blue-600/20 hover:border-blue-500/50 hover:text-blue-400 transition-all"><span>',
                'link_after' => '</span><svg class="w-4 h-4 text-slate-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></span>',
            ));
        } else {
            // Fallback Menu
            echo '<div class="space-y-3 font-bold text-base">
                <a href="' . esc_url(home_url('/')) . '" class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800 text-slate-200 hover:bg-blue-600/20 hover:border-blue-500 hover:text-blue-400 transition-all"><span>صفحه اصلی</span><svg class="w-4 h-4 text-slate-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
                <a href="' . esc_url(site_url('/services')) . '" class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800 text-slate-200 hover:bg-blue-600/20 hover:border-blue-500 hover:text-blue-400 transition-all"><span>خدمات مهندسی</span><svg class="w-4 h-4 text-slate-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
                <a href="' . esc_url(site_url('/portfolios')) . '" class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800 text-slate-200 hover:bg-blue-600/20 hover:border-blue-500 hover:text-blue-400 transition-all"><span>نمونه کارها</span><svg class="w-4 h-4 text-slate-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
                <a href="' . esc_url(site_url('/about-us')) . '" class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800 text-slate-200 hover:bg-blue-600/20 hover:border-blue-500 hover:text-blue-400 transition-all"><span>درباره ما</span><svg class="w-4 h-4 text-slate-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
                <a href="' . esc_url(site_url('/contact-us')) . '" class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800 text-slate-200 hover:bg-blue-600/20 hover:border-blue-500 hover:text-blue-400 transition-all"><span>تماس با ما</span><svg class="w-4 h-4 text-slate-500 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
            </div>';
        }
        ?>
    </nav>

    <!-- 3. Bottom Quick Contact & Social Footer -->
    <div class="space-y-4 pt-4 border-t border-slate-800 shrink-0">
        <div class="grid grid-cols-2 gap-3">
            <a href="<?= esc_url($phone_href); ?>" class="flex items-center justify-center gap-2 p-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-lg shadow-blue-600/30 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                <span>تماس مستقیم</span>
            </a>
            <a href="<?= esc_url(site_url('/contact-us')); ?>" class="flex items-center justify-center gap-2 p-3 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-200 font-bold text-xs transition-all">
                <span>استعلام قیمت</span>
                <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>

        <div class="flex items-center justify-between pt-2">
            <span class="text-[11px] text-slate-400 font-medium">پارس سویچ برد در شبکه‌های اجتماعی</span>
            <div class="flex gap-1.5 items-center">
                <?php get_template_part('template-parts/global/social-links', null, ['class' => 'border-slate-800 text-slate-400 hover:text-white hover:bg-blue-600']); ?>
            </div>
        </div>
    </div>

</div>