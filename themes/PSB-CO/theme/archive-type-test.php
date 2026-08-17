<?php
/**
 * Minimal Corporate Type-Test & Electrical Standards Archive Template
 * PSB-CO High & Medium Voltage Engineering Theme
 */

get_header();
?>

<!-- Minimal Header Banner -->
<section class="relative py-10 lg:py-14 bg-slate-50 border-b border-slate-200 text-slate-900 mb-10 select-none" data-aos="fade-up">
    <div class="container mx-auto px-4 md:px-8 text-center max-w-3xl space-y-3">
        <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
            <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
            تایپ‌تست‌های آزمایشگاهی & استانداردهای تجهیزات
        </span>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
            گواهی‌نامه‌ها و گزارش‌های تایپ‌تست پارس سویچ برد
        </h1>
        <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">
            گزارش‌های رسمی آزمون‌های تایپ‌تست (Type Test Reports) صادره از پژوهشگاه نیرو و آزمایشگاه‌های آکردیته بین‌المللی بر اساس استانداردهای IEC 61439 و IEC 62271
        </p>
    </div>
</section>

<!-- Type-Tests Grid -->
<main class="container mx-auto px-4 md:px-8 pb-16">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php
        $args = array(
            'post_type' => 'type-test',
            'post_status' => 'publish',
            'order' => 'DESC',
            'posts_per_page' => -1,
            'ignore_sticky_posts' => true
        );
        $loop = new WP_Query($args);
        if ($loop->have_posts()) :
            while ($loop->have_posts()) :
                $loop->the_post();
                $issuing_body = get_field('issuing_body') ?: 'آزمایشگاه آکردیته';
                $standard_number = get_field('standard_number');
                $tested_equipment = get_field('tested_equipment');
                $test_parameters = get_field('test_parameters');
                $validity_date = get_field('validity_date');
                $type_test_file = get_field('type_test_file') ?: get_field('certificate_file');
                $file_url = '';
                if (!empty($type_test_file)) {
                    $file_url = is_array($type_test_file) ? ($type_test_file['url'] ?? '') : $type_test_file;
                }
                if (empty($file_url)) {
                    $file_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
                }
                $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=400&auto=format&fit=crop';
        ?>
                <article class="group relative rounded-2xl border border-slate-200/80 bg-white hover:border-indigo-500/50 transition-all duration-300 shadow-sm hover:shadow-xl flex flex-col h-full overflow-hidden" data-aos="fade-up">
                    <!-- Image Frame -->
                    <div class="relative h-56 w-full overflow-hidden bg-slate-50 p-4 border-b border-slate-100 flex items-center justify-center">
                        <span class="absolute top-3 right-3 z-10 px-2.5 py-1 text-[11px] font-bold bg-indigo-700 text-white rounded-full shadow-sm">
                            🔬 <?= esc_html($issuing_body); ?>
                        </span>
                        <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300" />
                    </div>

                    <!-- Content -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-3 bg-white">
                        <div class="space-y-2">
                            <h3 class="text-base font-bold text-slate-900 group-hover:text-indigo-700 transition-colors leading-snug">
                                <a href="<?php the_permalink(); ?>"><?= get_the_title(); ?></a>
                            </h3>
                            <?php if ($standard_number) : ?>
                                <div class="text-xs font-mono font-bold text-blue-700">
                                    📜 <?= esc_html($standard_number); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($tested_equipment) : ?>
                                <div class="text-xs text-slate-600 font-medium">
                                    ⚙️ تجهیز: <span class="font-bold text-slate-800"><?= esc_html($tested_equipment); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($test_parameters) : ?>
                                <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">
                                    ⚡ <?= esc_html($test_parameters); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($validity_date) : ?>
                                <div class="text-[11px] text-slate-500 font-medium">
                                    اعتبار/سال: <span class="font-bold text-slate-700 font-mono"><?= esc_html($validity_date); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Action Bar -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-700 hover:text-indigo-900">
                                <span>جزئیات کامل</span>
                                <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                            <?php if ($file_url) : ?>
                                <a href="<?= esc_url($file_url); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white transition-all border border-indigo-200">
                                    <span>فایل PDF</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php
            endwhile;
        else : ?>
            <div class="col-span-full py-16 text-center text-slate-500 text-sm bg-white rounded-2xl border border-slate-200 shadow-sm">
                هیچ تایپ‌تستی ثبت نشده است.
            </div>
        <?php
        endif;
        wp_reset_postdata();
        ?>
    </div>
</main>

<?php get_footer(); ?>
