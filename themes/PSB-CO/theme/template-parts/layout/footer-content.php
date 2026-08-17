<?php
/**
 * Modern Minimal Corporate B2B Footer Template
 * PSB-CO High-Voltage Electrical Panel Manufacturing Theme
 */

$footer_desc = get_field('footer_description', 'option') ?: get_field('footer_content', 'option') ?: 'شرکت پارس سویچ برد با بیش از ۵۰ سال سابقه در صنعت برق و تابلوسازی کشور.';
$contact_title = get_field('contact_title', 'option') ?: 'درخواست مشاوره';

// ACF Repeaters from site-setting
$contacts = get_field('contact', 'option');
$phones = get_field('phones', 'option') ?: get_field('phone', 'option');
$emails = get_field('emails', 'option') ?: get_field('email', 'option');

// Fallback legacy fields if repeater empty
$legacy_address = get_field('address', 'option') ?: 'تهران - خیابان انقلاب - خیابان خارک - پلاک 21';
$legacy_phone = get_field('phone_number', 'option') ?: get_field('phone_legacy', 'option') ?: '02166707010';
$legacy_map = get_field('map-link', 'option') ?: '#';
?>

<footer id="footer" class="bg-slate-950 text-slate-300 border-t border-slate-800 text-sm select-none">
    <!-- Main Footer Grid with Generous Spacing for Desktop & Mobile -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-24">
        <div class="grid grid-cols-2 lg:grid-cols-12 gap-8 lg:gap-12">
            
            <!-- Column 1: Brand Info & Addresses (lg:col-span-4) -->
            <div class="col-span-2 lg:col-span-4 space-y-6">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-3">
                    <?php
                    $logo_args = array(
                        'logoSize' => 'h-12 w-auto',
                        'class' => 'inline-block',
                        'logoLink' => 'footer_logo',
                    );
                    get_template_part('template-parts/global/logo', null, $logo_args);
                    ?>
                </div>

                <p class="text-slate-400 text-xs sm:text-sm leading-relaxed text-justify max-w-md">
                    <?= esc_html($footer_desc); ?>
                </p>

                <!-- Location & Addresses Cards -->
                <div class="space-y-3 pt-2">
                    <?php if (!empty($contacts) && is_array($contacts)) : ?>
                        <?php foreach ($contacts as $c) :
                            $c_title = $c['title'] ?? '';
                            $c_addr = $c['address'] ?? '';
                            $c_map_link = $c['map-link'] ?? '';
                        ?>
                            <?php if ($c_addr) : ?>
                                <div class="p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-2 text-xs text-slate-300 shadow-sm">
                                    <div class="flex items-start gap-2.5">
                                        <svg class="w-4 h-4 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <div class="space-y-1.5 flex-1">
                                            <?php if ($c_title) : ?><span class="font-bold text-white block"><?= esc_html($c_title); ?>:</span><?php endif; ?>
                                            <span class="block text-slate-300 leading-relaxed font-sans"><?= esc_html($c_addr); ?></span>
                                            <?php if ($c_map_link) : ?>
                                                <a href="<?= esc_url($c_map_link); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-950 hover:bg-blue-600 border border-slate-800 hover:border-blue-500 text-blue-400 hover:text-white transition-all text-[11px] font-bold mt-1 shadow-sm">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                                    <span>مشاهده روی نقشه</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php elseif ($legacy_address) : ?>
                        <div class="p-3.5 rounded-2xl bg-slate-900/80 border border-slate-800 text-xs text-slate-300">
                            <div class="flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <div>
                                    <span class="block text-white font-medium"><?= esc_html($legacy_address); ?></span>
                                    <?php if ($legacy_map && $legacy_map !== '#') : ?>
                                        <a href="<?= esc_url($legacy_map); ?>" target="_blank" class="text-blue-400 hover:text-blue-300 transition-colors inline-block mt-1 text-[11px] font-bold">
                                            مشاهده روی نقشه
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 2: Footer Menu 1 Component -->
            <?php get_template_part('template-parts/layout/footer-menu-column', null, array(
                'theme_location' => 'footerLocationOne',
                'fallback_title' => get_field('first_menu', 'option') ?: 'دسترسی سریع',
                'fallback_links' => array(
                    array('url' => home_url('/'), 'label' => 'صفحه اصلی'),
                    array('url' => site_url('/about-us'), 'label' => 'درباره ما'),
                    array('url' => site_url('/contact-us'), 'label' => 'تماس با ما'),
                ),
            )); ?>

            <!-- Column 3: Footer Menu 2 Component -->
            <?php get_template_part('template-parts/layout/footer-menu-column', null, array(
                'theme_location' => 'footerLocationTwo',
                'fallback_title' => get_field('second_menu', 'option') ?: 'خدمات ما',
                'fallback_links' => array(
                    array('url' => site_url('/services'), 'label' => 'خدمات مهندسی'),
                    array('url' => site_url('/confirmation'), 'label' => 'تأییدیه‌ها و وندورلیست'),
                ),
            )); ?>

            <!-- Column 4: Footer Menu 3 Component -->
            <?php get_template_part('template-parts/layout/footer-menu-column', null, array(
                'theme_location' => 'footerLocationThree',
                'fallback_title' => get_field('third_menu', 'option') ?: 'نمونه کارها',
                'fallback_links' => array(
                    array('url' => site_url('/portfolios'), 'label' => 'پروژه‌های کلید در دست'),
                    array('url' => site_url('/certificates'), 'label' => 'گواهینامه‌های کیفیت'),
                    array('url' => site_url('/type-test'), 'label' => 'گزارش‌های تایپ‌تست'),
                ),
            )); ?>

            <!-- Column 5: Phone Contact & Badges (lg:col-span-2) -->
            <div class="col-span-2 space-y-4">
                <h4 class="font-bold text-white text-base border-b border-slate-800/80 pb-2.5 flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block"></span>
                        <?= esc_html($contact_title); ?>
                    </span>
                </h4>

                <!-- Phone Repeater Cards -->
                <?php if (!empty($phones) && is_array($phones)) : ?>
                    <div class="flex lg:flex-col max-lg:items-center gap-2">
                        <?php foreach ($phones as $p) :
                            $show = isset($p['show-on-footer']) ? $p['show-on-footer'] : (isset($p['show_on_footer']) ? $p['show_on_footer'] : true);
                            if ($show === false || $show === 0 || $show === '0' || $show === 'false') continue;

                            $num_obj = $p['number'] ?? ($p['phone'] ?? null);
                            $title = $p['title'] ?? '';

                            if (is_array($num_obj)) {
                                $title = $title ?: ($num_obj['title'] ?? '');
                                $num_obj = $num_obj['url'] ?? '';
                            }

                            $raw_num = is_string($num_obj) ? $num_obj : '';
                            $clean_num = preg_replace('#^https?://#i', '', $raw_num);
                            $clean_num = str_replace('tel:', '', $clean_num);

                            if (!$clean_num) continue;
                            $phone_href = 'tel:' . preg_replace('/[^0-9+]/', '', $clean_num);
                        ?>
                            <a href="<?= esc_url($phone_href); ?>" class="flex-1 group block p-3 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500/50 transition-all shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-blue-600/20 text-blue-400 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    </div>
                                    <div>
                                        <span class="block text-white font-bold font-mono text-xs ltr text-right"><?= esc_html($clean_num); ?></span>
                                        <?php if ($title) : ?>
                                            <span class="block text-slate-400 text-[11px] font-medium"><?= esc_html($title); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($legacy_phone) : ?>
                    <a href="tel:<?= esc_attr($legacy_phone); ?>" class="group block p-3 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500/50 transition-all shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-blue-600/20 text-blue-400 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <div>
                                <span class="block text-white font-bold font-mono text-xs ltr text-right"><?= esc_html($legacy_phone); ?></span>
                                <span class="block text-slate-400 text-[11px] font-medium">فروش و خدمات پشتیبانی</span>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>

                <!-- Trust Badges & Confirmations Repeater -->
                <?php if (have_rows('confirmation', 'option')) : ?>
                    <div class="flex flex-wrap gap-2 pt-2 justify-center items-center">
                        <?php while (have_rows('confirmation', 'option')) : the_row();
                            $conf = get_sub_field('conf_items', 'option');
                        ?>
                            <div class="p-1.5 overflow-hidden size-12 bg-white rounded-xl border border-slate-200 shadow-sm flex items-center justify-center text-xs">
                                <?= $conf; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Copyright Bar with Mobile Bottom Padding Clearance (pb-36) -->
    <div class="bg-slate-950/95 py-6 border-t border-slate-900 text-xs text-slate-400 pb-36 lg:pb-6">
        <div class="container mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p>© <?= date('Y'); ?> کلیه حقوق متعلق به <?= get_field('brand_name' , 'option') ?? 'پارس سوییچ بورد' ?> می‌باشد.</p>
            <div class="flex gap-2 items-center">
                <?php get_template_part('template-parts/global/social-links', null); ?>
            </div>
        </div>
    </div>
</footer>
