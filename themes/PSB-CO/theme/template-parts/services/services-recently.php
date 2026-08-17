<?php
/**
 * Minimal Corporate Electrical Services Section - Section 2 Homepage
 * PSB-CO High & Medium Voltage Theme
 */

$cta_part1 = 'امکانات و خدمات';
$cta_underline = 'تخصصی ما';
$cta_desc = 'طراحی EPLAN، خطوط برش و پانچ CNC، شینه‌کشی مس، رنگ الکترواستاتیک و آزمایشگاه تست‌های روتین';

if (have_rows('cta_title')) {
    while (have_rows('cta_title')) {
        the_row();
        $cta_part1 = get_sub_field('first_part_cta') ?: $cta_part1;
        $cta_underline = get_sub_field('underline_word_cta') ?: $cta_underline;
        $cta_desc = get_sub_field('short_description_cta') ?: $cta_desc;
    }
}
?>

<section class="py-12 bg-white text-slate-900 border-b border-slate-200 relative overflow-hidden select-none" data-aos="fade-up">
    <!-- Subtle Radial Background Dot Pattern -->
    <div class="absolute inset-0 bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:24px_24px] opacity-60 pointer-events-none"></div>

    <div class="container mx-auto px-4 md:px-8 relative z-10">
        <!-- Section Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10 border-b border-slate-100 pb-6">
            <div class="space-y-3 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                    <span>امکانات کارگاهی، خطوط تولید & خدمات مهندسی</span>
                </div>
                <h2 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-snug">
                    <?= esc_html($cta_part1); ?>
                    <span class="text-blue-700 border-b-4 border-blue-600 pb-0.5">
                        <?= esc_html($cta_underline); ?>
                    </span>
                </h2>
                <p class="text-slate-600 text-sm md:text-base leading-relaxed">
                    <?= esc_html($cta_desc); ?>
                </p>
            </div>

            <!-- View All Services Archive Link -->
            <a href="<?= esc_url(get_post_type_archive_link('services')); ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold bg-slate-50 hover:bg-slate-100 text-slate-800 border border-slate-300 shadow-sm hover:shadow transition-all text-sm whitespace-nowrap self-start md:self-auto group shrink-0">
                <span>مشاهده کلیه خدمات و امکانات</span>
                <svg class="w-4 h-4 rtl:rotate-180 text-blue-700 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>

        <!-- Services Swiper Container -->
        <div class="post_swiper swiper !pb-10" 
            data-perpage="4" 
            data-laptop="3" 
            data-tablet="2" 
            data-mobile="1.1"
            data-perfix="post"
            data-direction="horizontal"
        >
            <div class="swiper-wrapper">
                <?php
                $args = array(
                    'post_type' => 'services',
                    'post_status' => 'publish',
                    'order' => 'DESC',
                    'posts_per_page' => $args['postCount'] ?? 8,
                    'ignore_sticky_posts' => true
                );
                $loop = new WP_Query($args);
                if ($loop->have_posts()) :
                    while ($loop->have_posts()) :
                        $loop->the_post(); ?>
                        <div class="swiper-slide h-auto">
                            <?php get_template_part('template-parts/services/services-card'); ?>
                        </div>
                    <?php
                    endwhile;
                else : ?>
                    <div class="col-span-full py-12 text-center text-slate-500 text-sm bg-slate-50 rounded-2xl border border-slate-200">
                        خدماتی ثبت نشده است.
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
