<?php
/**
 * Reusable Confirmation & Vendor Approvals Component
 * Post Type: 'confirmation'
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

$conf_query = new WP_Query(array(
    'post_type' => 'confirmation',
    'post_status' => 'publish',
    'posts_per_page' => $postCount,
    'order' => 'DESC',
));

if ($conf_query->have_posts()) :
?>

<section class="py-12 bg-white text-slate-900 border-b border-slate-200 relative overflow-hidden select-none" data-aos="fade-up">
    <div class="container mx-auto px-4 md:px-8 space-y-6">
        
        <?php if ($show_header) : ?>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary border border-primary/20">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        تأییدیه‌ها & وندورلیست‌ها
                    </span>
                    <h3 class="text-xl lg:text-2xl font-black text-slate-900">
                        تأییدیه‌های رسمی توانیر و شرکت‌های برق منطقه‌ای
                    </h3>
                </div>

                <a href="<?= esc_url(get_post_type_archive_link('confirmation') ?: site_url('/confirmation')); ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-slate-50 hover:bg-primary hover:text-white text-slate-700 border border-slate-200 transition-all self-start sm:self-auto shrink-0 shadow-sm">
                    <span>مشاهده همه تأییدیه‌ها</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        <?php endif; ?>

        <?php if ($layout === 'slider') : ?>
            <!-- Swiper Slider Layout -->
            <div class="post_swiper swiper !py-2 !pb-10" data-perpage="4" data-laptop="3" data-tablet="2" data-mobile="1.2">
                <div class="swiper-wrapper">
                    <?php while ($conf_query->have_posts()) : $conf_query->the_post();
                        $organization = get_field('issuing_organization') ?: 'توانیر / کارفرما';
                        $letter_file = get_field('letter_file');
                        $file_url = !empty($letter_file['url']) ? $letter_file['url'] : get_the_post_thumbnail_url(get_the_ID(), 'full');
                        $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=400&auto=format&fit=crop';
                    ?>
                        <div class="swiper-slide h-auto">
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-primary transition-all flex flex-col justify-between space-y-3 h-full group shadow-sm">
                                <div class="space-y-2 text-center">
                                    <div class="w-14 h-14 bg-white p-2 rounded-xl mx-auto flex items-center justify-center border border-slate-200 shadow-sm">
                                        <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform" />
                                    </div>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary">
                                        🏛️ <?= esc_html($organization); ?>
                                    </span>
                                    <h4 class="text-xs font-bold text-slate-800 group-hover:text-primary transition-colors line-clamp-2 leading-snug">
                                        <?= get_the_title(); ?>
                                    </h4>
                                </div>
                                <?php if ($file_url) : ?>
                                    <a href="<?= esc_url($file_url); ?>" target="_blank" class="block text-center py-1.5 text-[11px] font-bold text-primary hover:text-blue-800 border-t border-slate-200/60">
                                        مشاهده تاییدیه ↗
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>

                <!-- Custom Swiper Controls Bar -->
                <div class="flex items-center justify-center gap-3 mt-4 bg-slate-100/80 border border-slate-200/60 rounded-lg p-1 w-fit mx-auto shadow-sm">
                    <button class="swiper-button-prev post__nav !static !w-9 !h-9 !m-0 after:!content-none bg-white hover:bg-primary hover:text-white text-slate-700 rounded-xl flex items-center justify-center border border-slate-300 transition-all shadow-sm cursor-pointer">
                        <svg class="w-4 h-4 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div class="swiper-pagination !static !w-auto text-primary font-mono text-xs px-2 font-bold translate-x-1/2"></div>
                    <button class="swiper-button-next post__nav !static !w-9 !h-9 !m-0 after:!content-none bg-white hover:bg-primary hover:text-white text-slate-700 rounded-xl flex items-center justify-center border border-slate-300 transition-all shadow-sm cursor-pointer">
                        <svg class="w-4 h-4 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                </div>
            </div>

        <?php else : ?>

            <!-- Grid Layout -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <?php while ($conf_query->have_posts()) : $conf_query->the_post();
                    $organization = get_field('issuing_organization') ?: 'توانیر / کارفرما';
                    $scope = get_field('approval_scope');
                    $letter_file = get_field('letter_file');
                    $file_url = !empty($letter_file['url']) ? $letter_file['url'] : get_the_post_thumbnail_url(get_the_ID(), 'full');
                ?>
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/70 hover:border-primary transition-all flex flex-col justify-between space-y-3 shadow-sm">
                        <div class="space-y-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-primary/10 text-primary inline-block">
                                🏛️ <?= esc_html($organization); ?>
                            </span>
                            <h4 class="text-sm font-bold text-slate-900 line-clamp-2"><?= get_the_title(); ?></h4>
                            <?php if ($scope) : ?>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed"><?= esc_html($scope); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($file_url) : ?>
                            <a href="<?= esc_url($file_url); ?>" target="_blank" class="inline-flex items-center justify-between w-full pt-2 border-t border-slate-200/60 text-xs font-bold text-primary hover:text-blue-900">
                                <span>مشاهده نامه تاییدیه</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 01-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php endif; ?>
