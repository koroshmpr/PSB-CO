<?php
/**
 * Advanced Multi-CPT Live Search AJAX Handler
 * PSB-CO High-Voltage Electrical Panel Manufacturing Theme
 */

add_action('wp_ajax_custom_ajax_search', 'handle_custom_ajax_search');
add_action('wp_ajax_nopriv_custom_ajax_search', 'handle_custom_ajax_search');

function handle_custom_ajax_search()
{
    $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
    $selected_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'all';

    if (empty($keyword) || mb_strlen($keyword) < 2) {
        wp_send_json_success(array(
            'html' => '',
            'counts' => array('all' => 0, 'services' => 0, 'portfolio' => 0, 'certificates' => 0, 'confirmation' => 0, 'type-test' => 0, 'post' => 0),
        ));
    }

    $all_post_types = array('services', 'portfolio', 'certificates', 'confirmation', 'type-test', 'post');

    // Calculate count per post type
    $counts = array('all' => 0);
    foreach ($all_post_types as $pt) {
        $count_query = new WP_Query(array(
            's' => $keyword,
            'post_type' => $pt,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ));
        $counts[$pt] = $count_query->found_posts;
        $counts['all'] += $counts[$pt];
    }

    // Determine query post types
    if ($selected_type !== 'all' && in_array($selected_type, $all_post_types)) {
        $query_types = array($selected_type);
    } else {
        $query_types = $all_post_types;
    }

    $search_query = new WP_Query(array(
        's' => $keyword,
        'post_type' => $query_types,
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'order' => 'DESC',
    ));

    ob_start();

    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $p_type = get_post_type();
            $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
            $title = get_the_title();
            $permalink = get_permalink();
            $excerpt = get_the_excerpt() ?: wp_trim_words(get_the_content(), 12);

            // Render Distinct Card Component Based on Post Type
            if ($p_type === 'services') :
                $service_icon = get_field('service_icon') ?: '⚡';
                $service_category = get_field('service_category') ?: 'خدمات مهندسی';
            ?>
                <div class="group bg-white p-5 rounded-2xl border border-slate-200 hover:border-blue-500 shadow-sm hover:shadow-lg transition-all flex flex-col justify-between space-y-4 h-full">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center text-lg font-bold">
                                <?= esc_html($service_icon); ?>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                ⚡ <?= esc_html($service_category); ?>
                            </span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2 leading-snug">
                            <?= esc_html($title); ?>
                        </h4>
                        <?php if ($excerpt) : ?>
                            <p class="text-xs text-slate-500 line-clamp-3 leading-relaxed"><?= esc_html($excerpt); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= esc_url($permalink); ?>" class="inline-flex items-center justify-between w-full pt-3 border-t border-slate-100 text-xs font-bold text-blue-700 hover:text-blue-800">
                        <span>اطلاعات کامل خدمات</span>
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

            <?php elseif ($p_type === 'portfolio') :
                $client_name = get_field('client_name') ?: get_field('employer') ?: 'کارفرمای صنعتی';
                $voltage_level = get_field('voltage_level') ?: 'فشار متوسط / ضعیف';
            ?>
                <div class="group bg-slate-950 text-white p-5 rounded-2xl border border-slate-800 hover:border-blue-500 shadow-md hover:shadow-xl transition-all flex flex-col justify-between space-y-4 h-full">
                    <div class="space-y-3">
                        <?php if ($thumb) : ?>
                            <div class="w-full h-36 rounded-xl overflow-hidden bg-slate-900 relative">
                                <img src="<?= esc_url($thumb); ?>" alt="<?= esc_attr($title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                                <span class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-600 text-white shadow-sm">
                                    ⚡ <?= esc_html($voltage_level); ?>
                                </span>
                            </div>
                        <?php else : ?>
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-600/30 text-blue-300 border border-blue-500/30">
                                    🏢 پروژه صنعت برق
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="space-y-1">
                            <span class="text-[10px] text-blue-400 font-bold block">👤 کارفرما: <?= esc_html($client_name); ?></span>
                            <h4 class="text-sm font-bold text-white group-hover:text-blue-300 transition-colors line-clamp-2 leading-snug">
                                <?= esc_html($title); ?>
                            </h4>
                        </div>
                        <?php if ($excerpt) : ?>
                            <p class="text-xs text-slate-400 line-clamp-2 leading-relaxed font-sans"><?= esc_html($excerpt); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= esc_url($permalink); ?>" class="inline-flex items-center justify-between w-full pt-3 border-t border-slate-800 text-xs font-bold text-blue-400 hover:text-white">
                        <span>مشاهده پروژه</span>
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

            <?php elseif ($p_type === 'certificates') :
                $issuing_body = get_field('issuing_body') ?: 'مرجع صادرکننده ISO';
                $standard_number = get_field('standard_number');
            ?>
                <div class="group bg-white p-5 rounded-2xl border border-amber-200/80 hover:border-amber-400 shadow-sm hover:shadow-lg transition-all flex flex-col justify-between space-y-4 h-full">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                🏆 <?= esc_html($issuing_body); ?>
                            </span>
                            <?php if ($standard_number) : ?>
                                <span class="text-[10px] font-mono font-bold text-blue-700"><?= esc_html($standard_number); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($thumb) : ?>
                            <div class="w-16 h-16 bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex items-center justify-center">
                                <img src="<?= esc_url($thumb); ?>" alt="<?= esc_attr($title); ?>" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform" />
                            </div>
                        <?php endif; ?>

                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug">
                            <?= esc_html($title); ?>
                        </h4>
                    </div>
                    <a href="<?= esc_url($permalink); ?>" class="inline-flex items-center justify-between w-full pt-3 border-t border-slate-100 text-xs font-bold text-amber-700 hover:text-amber-800">
                        <span>مشاهده گواهی‌نامه</span>
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

            <?php elseif ($p_type === 'confirmation') :
                $organization = get_field('issuing_organization') ?: 'توانیر / کارفرما';
                $scope = get_field('approval_scope');
            ?>
                <div class="group bg-white p-5 rounded-2xl border border-blue-200/80 hover:border-blue-500 shadow-sm hover:shadow-lg transition-all flex flex-col justify-between space-y-4 h-full">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                🏛️ <?= esc_html($organization); ?>
                            </span>
                        </div>

                        <?php if ($thumb) : ?>
                            <div class="w-14 h-14 bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex items-center justify-center">
                                <img src="<?= esc_url($thumb); ?>" alt="<?= esc_attr($title); ?>" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform" />
                            </div>
                        <?php endif; ?>

                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2 leading-snug">
                            <?= esc_html($title); ?>
                        </h4>

                        <?php if ($scope) : ?>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed"><?= esc_html($scope); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= esc_url($permalink); ?>" class="inline-flex items-center justify-between w-full pt-3 border-t border-slate-100 text-xs font-bold text-blue-700 hover:text-blue-800">
                        <span>مشاهده نامه تاییدیه</span>
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

            <?php elseif ($p_type === 'type-test') :
                $issuing_body = get_field('issuing_body') ?: 'آزمایشگاه آکردیته IEC';
                $standard_number = get_field('standard_number');
                $tested_equipment = get_field('tested_equipment');
            ?>
                <div class="group bg-white p-5 rounded-2xl border border-indigo-200/80 hover:border-indigo-500 shadow-sm hover:shadow-lg transition-all flex flex-col justify-between space-y-4 h-full">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                🔬 <?= esc_html($issuing_body); ?>
                            </span>
                            <?php if ($standard_number) : ?>
                                <span class="text-[10px] font-mono font-bold text-blue-700"><?= esc_html($standard_number); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($thumb) : ?>
                            <div class="w-14 h-14 bg-white p-2 rounded-xl border border-slate-200 shadow-sm flex items-center justify-center">
                                <img src="<?= esc_url($thumb); ?>" alt="<?= esc_attr($title); ?>" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform" />
                            </div>
                        <?php endif; ?>

                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-indigo-700 transition-colors line-clamp-2 leading-snug">
                            <?= esc_html($title); ?>
                        </h4>

                        <?php if ($tested_equipment) : ?>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">⚙️ <?= esc_html($tested_equipment); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= esc_url($permalink); ?>" class="inline-flex items-center justify-between w-full pt-3 border-t border-slate-100 text-xs font-bold text-indigo-700 hover:text-indigo-800">
                        <span>مشاهده گزارش تایپ‌تست</span>
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>

            <?php else : // Blog Post Or Page
                $categories = get_the_category();
                $cat_name = !empty($categories) ? $categories[0]->name : 'مقالات';
                $date = get_the_date('j F Y');
            ?>
                <div class="group bg-white p-4 rounded-2xl border border-slate-200 hover:border-purple-500 shadow-sm hover:shadow-lg transition-all flex flex-col justify-between space-y-3 h-full">
                    <div class="space-y-3">
                        <?php if ($thumb) : ?>
                            <div class="w-full h-36 rounded-xl overflow-hidden bg-slate-100 relative">
                                <img src="<?= esc_url($thumb); ?>" alt="<?= esc_attr($title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
                                <span class="absolute top-2.5 right-2.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-600 text-white shadow-sm">
                                    📰 <?= esc_html($cat_name); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="space-y-1">
                            <span class="text-[10px] text-slate-400 font-mono block">📅 <?= esc_html($date); ?></span>
                            <h4 class="text-sm font-bold text-slate-900 group-hover:text-purple-700 transition-colors line-clamp-2 leading-snug">
                                <?= esc_html($title); ?>
                            </h4>
                        </div>
                        <?php if ($excerpt) : ?>
                            <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed font-sans"><?= esc_html($excerpt); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= esc_url($permalink); ?>" class="inline-flex items-center justify-between w-full pt-3 border-t border-slate-100 text-xs font-bold text-purple-700 hover:text-purple-800">
                        <span>ادامه مقاله</span>
                        <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            <?php endif; ?>

            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<div class="col-span-full text-center py-12 space-y-2">
            <span class="text-3xl block">🔍</span>
            <strong class="block text-sm font-bold text-slate-700">نتیجه‌ای یافت نشد</strong>
            <p class="text-xs text-slate-400">لطفاً واژه دیگری را مانند «تابلو کشویی»، «توانیر»، «تست» یا «خدمات» جستجو کنید.</p>
        </div>';
    }

    $response_html = ob_get_clean();
    wp_send_json_success(array(
        'html' => $response_html,
        'counts' => $counts,
    ));
}