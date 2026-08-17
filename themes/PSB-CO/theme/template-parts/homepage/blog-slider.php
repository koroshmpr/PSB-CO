<?php
/**
 * Minimal Corporate Blog & News Slider Component - Light Theme
 * PSB-CO High & Medium Voltage Theme
 */

$postSlider = get_field('post_title');
$first_part = $postSlider['first_part'] ?? 'مقالات و اخبار';
$underline_word = $postSlider['underline_word'] ?? 'تخصصی صنعت برق';
$short_desc = $postSlider['short_description'] ?? 'جدیدترین مقالات آموزشی، استانداردها و اخبار تجهیزات فشار قوی و پست‌های برق';
?>

<section class="py-12 bg-slate-50/70 text-slate-900 border-t border-slate-200 select-none relative overflow-hidden" data-aos="fade-up">
    <!-- Subtle Background Dot Pattern -->
    <div class="absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:24px_24px] opacity-40 pointer-events-none"></div>

    <div class="container mx-auto px-4 md:px-8 relative z-10">
        <!-- Section Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10 border-b border-slate-200 pb-6">
            <div class="space-y-3 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                    <span>مجله تخصصی تابلوسازی & پست‌های برق</span>
                </div>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-snug">
                    <?= esc_html($first_part); ?>
                    <span class="text-blue-700 border-b-4 border-blue-600 pb-0.5">
                        <?= esc_html($underline_word); ?>
                    </span>
                </h2>
                <p class="text-slate-600 text-sm md:text-base leading-relaxed">
                    <?= esc_html($short_desc); ?>
                </p>
            </div>

            <!-- View All Blog Archive Link -->
            <a href="<?= esc_url(get_permalink(get_option('page_for_posts')) ?: site_url('/blog')); ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 shadow-sm hover:shadow transition-all text-sm whitespace-nowrap self-start md:self-auto group shrink-0">
                <span>مشاهده وبلاگ و تمام مقالات</span>
                <svg class="w-4 h-4 rtl:rotate-180 text-blue-700 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>

        <!-- Post Swiper Container -->
        <div class="post_swiper swiper !pb-10" 
            data-perpage="4.5" 
            data-laptop="3.5" 
            data-tablet="1.5" 
            data-mobile="1.1"
            data-perfix="post"
            data-direction="horizontal"
        >
            <div class="swiper-wrapper">
                <?php
                $args = array(
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'order' => 'DESC',
                    'posts_per_page' => 8,
                    'ignore_sticky_posts' => true
                );
                $loop = new WP_Query($args);
                if ($loop->have_posts()) :
                    while ($loop->have_posts()) :
                        $loop->the_post(); ?>
                        <div class="swiper-slide h-auto">
                            <?php get_template_part('template-parts/blog/archive-card'); ?>
                        </div>
                    <?php
                    endwhile;
                else : ?>
                    <div class="col-span-full py-12 text-center text-slate-500 text-sm bg-white rounded-2xl border border-slate-200 shadow-sm">
                        مقاله‌ای ثبت نشده است.
                    </div>
                <?php
                endif;
                wp_reset_postdata(); ?>
            </div>

            <!-- Custom Swiper Controls Bar -->
            <div class="flex items-center justify-center gap-3 mt-4">
                <button class="swiper-button-prev post__nav !static !w-9 !h-9 !m-0 after:!content-none bg-white hover:bg-blue-50 text-slate-700 hover:text-blue-700 rounded-xl flex items-center justify-center border border-slate-300 transition-all shadow-sm cursor-pointer">
                    <svg class="w-4 h-4 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div class="swiper-pagination !static !w-auto text-blue-700 font-mono text-xs px-2 font-bold translate-x-1/2"></div>
                <button class="swiper-button-next post__nav !static !w-9 !h-9 !m-0 after:!content-none bg-white hover:bg-blue-50 text-slate-700 hover:text-blue-700 rounded-xl flex items-center justify-center border border-slate-300 transition-all shadow-sm cursor-pointer">
                    <svg class="w-4 h-4 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
            </div>
        </div>
    </div>
</section>
