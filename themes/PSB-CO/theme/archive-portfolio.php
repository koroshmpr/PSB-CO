<?php
/**
 * Minimal Corporate Portfolio Archive Template
 * PSB-CO High-Voltage Electrical Panel Manufacturing Theme
 */

get_header();
?>

<!-- Minimal Header Banner -->
<section class="relative py-10 lg:py-14 bg-slate-50 border-b border-slate-200 text-slate-900 mb-10 select-none" data-aos="fade-up">
    <div class="container mx-auto px-4 md:px-8 text-center max-w-3xl space-y-3">
        <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
            رزومه و پروژه‌های اجرا شده
        </span>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
            نمونه کارهای شرکت پارس سویچ برد
        </h1>
        <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">
            نمایش پروژه‌های موفق طراحی، ساخت و مونتاژ تابلوهای برق فشار ضعیف، متوسط و پست‌های اختصاصی صنعت برق
        </p>
    </div>
</section>

<!-- Portfolio Grid -->
<main class="container mx-auto px-4 md:px-8 pb-16">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php
        $args = array(
            'post_type' => 'portfolio',
            'post_status' => 'publish',
            'order' => 'DESC',
            'posts_per_page' => -1,
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
        else : ?>
            <div class="col-span-full py-16 text-center text-slate-500 text-sm bg-white rounded-2xl border border-slate-200 shadow-sm">
                هیچ نمونه کاری یافت نشد.
            </div>
        <?php
        endif;
        wp_reset_postdata();
        ?>
    </div>
</main>

<?php get_footer(); ?>