<?php
/**
 * Reusable Type-Test & Electrical Standard Certificates Component
 * Post Type: 'type-test'
 * PSB-CO High & Medium Voltage Theme
 * 
 * Supported $args:
 * - 'layout': 'slider' (default) | 'grid'
 * - 'postCount': integer (default 10 for slider, 4 for grid)
 * - 'show_header': boolean (default true)
 */

$layout = $args['layout'] ?? 'slider';
$postCount = $args['postCount'] ?? ($layout === 'grid' ? 4 : 10);
$show_header = $args['show_header'] ?? true;

$type_test_query = new WP_Query(array(
    'post_type' => 'type-test',
    'post_status' => 'publish',
    'posts_per_page' => $postCount,
    'order' => 'DESC',
    'ignore_sticky_posts' => true
));

if ($type_test_query->have_posts()) :
?>

<section class="py-12 bg-white text-slate-900 border-b border-slate-200 relative overflow-hidden select-none" data-aos="fade-up">
    <div class="container mx-auto px-4 md:px-8 space-y-6">
        
        <?php if ($show_header) : ?>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                        <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                        تایپ‌تست‌های آزمایشگاهی & استانداردهای IEC
                    </span>
                    <h3 class="text-xl lg:text-2xl font-black text-slate-900">
                        گزارش‌های تایپ‌تست و تاییدیه آزمایشگاه‌های آکردیته
                    </h3>
                </div>

                <a href="<?= esc_url(get_post_type_archive_link('type-test') ?: site_url('/type-test')); ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 hover:bg-indigo-600 hover:text-white text-slate-700 border border-slate-200 transition-all self-start sm:self-auto shrink-0 shadow-sm">
                    <span>مشاهده همه تایپ‌تست‌ها</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($layout === 'slider') : ?>
            <!-- Swiper Slider Layout -->
            <div class="post_swiper swiper !py-2 !pb-10" 
                 data-perpage="4" 
                 data-laptop="3" 
                 data-tablet="2" 
                 data-mobile="1.2">
                <div class="swiper-wrapper">
                    <?php while ($type_test_query->have_posts()) : $type_test_query->the_post();
                        $issuing_body = get_field('issuing_body') ?: 'آزمایشگاه آکردیته';
                        $standard_number = get_field('standard_number');
                        $tested_equipment = get_field('tested_equipment');
                        $test_file = get_field('type_test_file') ?: get_field('certificate_file');
                        $file_url = '';
                        if (!empty($test_file)) {
                            $file_url = is_array($test_file) ? ($test_file['url'] ?? '') : $test_file;
                        }
                        if (empty($file_url)) {
                            $file_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                        }
                        $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=400&auto=format&fit=crop';
                    ?>
                        <div class="swiper-slide h-auto">
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-indigo-500 transition-all flex flex-col justify-between space-y-3 h-full group shadow-sm">
                                <div class="space-y-2 text-center">
                                    <div class="w-14 h-14 bg-white p-2 rounded-xl mx-auto flex items-center justify-center border border-slate-200 shadow-sm">
                                        <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform" />
                                    </div>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-800">
                                        🔬 <?= esc_html($issuing_body); ?>
                                    </span>
                                    <h4 class="text-xs font-bold text-slate-800 group-hover:text-indigo-700 transition-colors line-clamp-2 leading-snug">
                                        <a href="<?php the_permalink(); ?>"><?= get_the_title(); ?></a>
                                    </h4>
                                    <?php if ($standard_number) : ?>
                                        <span class="text-[11px] font-mono font-bold text-blue-700 block">📜 <?= esc_html($standard_number); ?></span>
                                    <?php endif; ?>
                                    <?php if ($tested_equipment) : ?>
                                        <p class="text-[11px] text-slate-500 line-clamp-1">⚙️ <?= esc_html($tested_equipment); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px]">
                                    <a href="<?php the_permalink(); ?>" class="font-bold text-indigo-700 hover:underline">
                                        جزئیات تست ↗
                                    </a>
                                    <?php if ($file_url) : ?>
                                        <a href="<?= esc_url($file_url); ?>" target="_blank" class="font-bold text-slate-600 hover:text-indigo-700">
                                            دانلود PDF 📄
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>

                <!-- Custom Swiper Controls Bar -->
                <div class="flex items-center justify-center gap-3 mt-4 bg-slate-100/80 border border-slate-200/60 rounded-lg p-1 w-fit mx-auto shadow-sm">
                    <button class="swiper-button-prev post__nav !static !w-9 !h-9 !m-0 after:!content-none bg-white hover:bg-indigo-600 hover:text-white text-slate-700 rounded-xl flex items-center justify-center border border-slate-300 transition-all shadow-sm cursor-pointer">
                        <svg class="w-4 h-4 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div class="swiper-pagination !static !w-auto text-indigo-700 font-mono text-xs px-2 font-bold translate-x-1/2"></div>
                    <button class="swiper-button-next post__nav !static !w-9 !h-9 !m-0 after:!content-none bg-white hover:bg-indigo-600 hover:text-white text-slate-700 rounded-xl flex items-center justify-center border border-slate-300 transition-all shadow-sm cursor-pointer">
                        <svg class="w-4 h-4 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                </div>
            </div>

        <?php else : ?>

            <!-- Grid Layout -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php while ($type_test_query->have_posts()) : $type_test_query->the_post();
                    $issuing_body = get_field('issuing_body') ?: 'آزمایشگاه آکردیته';
                    $standard_number = get_field('standard_number');
                    $tested_equipment = get_field('tested_equipment');
                    $test_file = get_field('type_test_file') ?: get_field('certificate_file');
                    $file_url = '';
                    if (!empty($test_file)) {
                        $file_url = is_array($test_file) ? ($test_file['url'] ?? '') : $test_file;
                    }
                    if (empty($file_url)) {
                        $file_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                    }
                ?>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 hover:border-indigo-500 transition-all flex flex-col justify-between space-y-3 shadow-sm">
                        <div class="space-y-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 inline-block">
                                🔬 <?= esc_html($issuing_body); ?>
                            </span>
                            <h4 class="text-sm font-bold text-slate-900 line-clamp-2">
                                <a href="<?php the_permalink(); ?>" class="hover:text-indigo-700 transition-colors"><?= get_the_title(); ?></a>
                            </h4>
                            <?php if ($standard_number) : ?>
                                <span class="text-xs font-mono font-bold text-blue-700 block">📜 <?= esc_html($standard_number); ?></span>
                            <?php endif; ?>
                            <?php if ($tested_equipment) : ?>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">⚙️ <?= esc_html($tested_equipment); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-xs">
                            <a href="<?php the_permalink(); ?>" class="font-bold text-indigo-700 hover:text-indigo-900">
                                مشاهده تایپ‌تست
                            </a>
                            <?php if ($file_url) : ?>
                                <a href="<?= esc_url($file_url); ?>" target="_blank" class="font-bold text-slate-500 hover:text-indigo-700">
                                    دانلود PDF ↗
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php endif; ?>
