<?php
/**
 * High-Contrast Corporate Electrical Portfolio Showcase Section - Dark Accent Section
 * PSB-CO High-Voltage Electrical Panel Manufacturing Theme
 */

$part1 = 'نمونه کارهای';
$underline = 'ممتاز و تخصصی';
$part2 = 'تابلوسازی برق';

if (have_rows('portfolio_title')) {
    while (have_rows('portfolio_title')) {
        the_row();
        $part1 = get_sub_field('first_part') ?: $part1;
        $underline = get_sub_field('underline_word') ?: $underline;
        $part2 = get_sub_field('second_part') ?: $part2;
    }
}
?>

<section class="mx-4 sm:mx-8 lg:mx-12 my-12 rounded-3xl bg-slate-950 text-white py-14 border border-slate-800 shadow-2xl relative overflow-hidden select-none" data-aos="fade-up">
    <!-- Ambient Blue & Amber Background Glows -->
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-600/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    
    <!-- Subtle Blueprint Grid Pattern -->
    <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-40 pointer-events-none"></div>

    <div class="container mx-auto px-4 md:px-8 relative z-10">
        <!-- Section Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10 border-b border-slate-800 pb-6">
            <div class="space-y-3 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>پروژه‌های شاخص برق & نیروگاهی</span>
                </div>
                <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight leading-snug">
                    <?= esc_html($part1); ?>
                    <span class="text-amber-400 border-b-4 border-amber-500 pb-0.5">
                        <?= esc_html($underline); ?>
                    </span>
                    <?= esc_html($part2); ?>
                </h2>
                <p class="text-slate-300 text-sm md:text-base leading-relaxed">
                    نگاهی به برخی از مهم‌ترین پروژه‌های طراحی، تولید و مونتاژ تابلوهای برق فشار ضعیف، متوسط و پست‌های پاساژ
                </p>
            </div>

            <!-- View All Archive Link -->
            <a href="<?= esc_url(get_post_type_archive_link('portfolio')); ?>" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-lg hover:shadow-amber-500/20 transition-all text-sm whitespace-nowrap self-start md:self-auto group shrink-0">
                <span>مشاهده کلیه پروژه‌ها</span>
                <svg class="w-4 h-4 rtl:rotate-180 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>

        <!-- Portfolio Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php
            $args = array(
                'post_type' => 'portfolio',
                'post_status' => 'publish',
                'order' => 'DESC',
                'posts_per_page' => $args['postCount'] ?? 4,
                'ignore_sticky_posts' => true
            );
            $loop = new WP_Query($args);
            if ($loop->have_posts()) :
                while ($loop->have_posts()) :
                    $loop->the_post(); ?>
                    <div class="h-full">
                        <?php get_template_part('template-parts/portfolio/portfolio-card'); ?>
                    </div>
                <?php
                endwhile;
            else: ?>
                <div class="col-span-full py-12 text-center text-slate-400 text-sm bg-slate-900/60 rounded-2xl border border-slate-800">
                    هیچ پروژه‌ای یافت نشد.
                </div>
            <?php
            endif;
            wp_reset_postdata(); ?>
        </div>
    </div>
</section>
