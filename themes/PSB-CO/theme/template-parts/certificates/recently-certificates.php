<?php
/**
 * Reusable Certificates & ISO Standards Component
 * Post Type: 'certificates'
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

$cert_query = new WP_Query(array(
    'post_type' => 'certificates',
    'post_status' => 'publish',
    'posts_per_page' => $postCount,
    'order' => 'DESC',
));

if ($cert_query->have_posts()) :
?>

<section class="py-12 bg-white text-slate-900 border-b border-slate-200 relative overflow-hidden select-none" data-aos="fade-up">
    <div class="container mx-auto px-4 md:px-8 space-y-6">
        
        <?php if ($show_header) : ?>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-2 h-2 rounded-full bg-amber-600 animate-pulse"></span>
                        گواهینامه‌های کیفیت ISO & تایپ‌تست‌ها
                    </span>
                    <h3 class="text-xl lg:text-2xl font-black text-slate-900">
                        گواهینامه‌های بین‌المللی و تایپ‌تست‌های معتبر
                    </h3>
                </div>

                <a href="<?= esc_url(get_post_type_archive_link('certificates') ?: site_url('/certificates')); ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 hover:bg-amber-50 text-slate-700 hover:text-amber-700 border border-slate-200 transition-colors self-start sm:self-auto shrink-0">
                    <span>مشاهده همه گواهینامه‌ها</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($layout === 'slider') : ?>
            <!-- Swiper Slider Layout -->
            <div class="post_swiper swiper !py-2 !pb-10" 
            data-perpage="6.2" 
            data-laptop="5.2" 
            data-tablet="4.3" 
            data-mobile="1.2">
                <div class="swiper-wrapper">
                    <?php while ($cert_query->have_posts()) : $cert_query->the_post();
                        $issuing_body = get_field('issuing_body') ?: 'مرجع صادرکننده';
                        $standard_number = get_field('standard_number');
                        $certificate_file = get_field('certificate_file');
                        $file_url = !empty($certificate_file['url']) ? $certificate_file['url'] : get_the_post_thumbnail_url(get_the_ID(), 'full');
                        $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?q=80&w=400&auto=format&fit=crop';
                    ?>
                        <div class="swiper-slide h-auto">
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-amber-400 transition-all flex flex-col justify-between space-y-3 h-full group shadow-sm">
                                <div class="space-y-2 text-center">
                                    <div class="w-14 h-14 bg-white p-2 rounded-xl mx-auto flex items-center justify-center border border-slate-200 shadow-sm">
                                        <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform" />
                                    </div>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        🏆 <?= esc_html($issuing_body); ?>
                                    </span>
                                    <h4 class="text-xs font-bold text-slate-800 group-hover:text-amber-700 transition-colors line-clamp-2 leading-snug">
                                        <?= get_the_title(); ?>
                                    </h4>
                                    <?php if ($standard_number) : ?>
                                        <span class="text-[11px] font-mono font-bold text-blue-700 block"><?= esc_html($standard_number); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($file_url) : ?>
                                    <a href="<?= esc_url($file_url); ?>" target="_blank" class="block text-center py-1.5 text-[11px] font-bold text-amber-700 hover:text-amber-800 border-t border-slate-200/60">
                                        مشاهده گواهی ↗
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>

                <!-- Custom Swiper Controls Bar -->
                <div class="flex items-center justify-center gap-3 mt-4">
                    <button class="swiper-button-prev post__nav !static !w-9 !h-9 !m-0 after:!content-none bg-white hover:bg-amber-50 text-slate-700 hover:text-amber-700 rounded-xl flex items-center justify-center border border-slate-300 transition-all shadow-sm cursor-pointer">
                        <svg class="w-4 h-4 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div class="swiper-pagination !static !w-auto text-amber-700 font-mono text-xs px-2 font-bold translate-x-1/2"></div>
                    <button class="swiper-button-next post__nav !static !w-9 !h-9 !m-0 after:!content-none bg-white hover:bg-amber-50 text-slate-700 hover:text-amber-700 rounded-xl flex items-center justify-center border border-slate-300 transition-all shadow-sm cursor-pointer">
                        <svg class="w-4 h-4 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                </div>
            </div>

        <?php else : ?>

            <!-- Grid Layout -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php while ($cert_query->have_posts()) : $cert_query->the_post();
                    $issuing_body = get_field('issuing_body') ?: 'مرجع صادرکننده';
                    $standard_number = get_field('standard_number');
                    $certificate_file = get_field('certificate_file');
                    $file_url = !empty($certificate_file['url']) ? $certificate_file['url'] : get_the_post_thumbnail_url(get_the_ID(), 'full');
                ?>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/70 hover:border-amber-300 transition-all flex flex-col justify-between space-y-3 shadow-sm">
                        <div class="space-y-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 inline-block">
                                🏆 <?= esc_html($issuing_body); ?>
                            </span>
                            <h4 class="text-sm font-bold text-slate-900 line-clamp-2"><?= get_the_title(); ?></h4>
                            <?php if ($standard_number) : ?>
                                <span class="text-xs font-mono font-bold text-blue-700 block">📜 <?= esc_html($standard_number); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($file_url) : ?>
                            <a href="<?= esc_url($file_url); ?>" target="_blank" class="inline-flex items-center justify-between w-full pt-2 border-t border-slate-200/60 text-xs font-bold text-amber-700 hover:text-amber-800">
                                <span>مشاهده گواهی</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php endif; ?>
