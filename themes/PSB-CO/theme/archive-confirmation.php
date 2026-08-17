<?php
/**
 * Minimal Corporate Confirmations & Approvals Archive Template
 * PSB-CO High & Medium Voltage Engineering Theme
 */

get_header();
?>

<!-- Minimal Header Banner -->
<section class="relative py-10 lg:py-14 bg-slate-50 border-b border-slate-200 text-slate-900 mb-10 select-none" data-aos="fade-up">
    <div class="container mx-auto px-4 md:px-8 text-center max-w-3xl space-y-3">
        <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
            تأییدیه‌های کارفرمایان & وندورلیست‌ها
        </span>
        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight">
            تأییدیه‌ها و رضایت‌نامه‌های شرکت پارس سویچ برد
        </h1>
        <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">
            مجموعه تأییدیه‌های رسمی صادره از شرکت توانیر، شرکت‌های توزیع برق، مپنا و پتروشیمی‌های کشور
        </p>
    </div>
</section>

<!-- Confirmations Grid -->
<main class="container mx-auto px-4 md:px-8 pb-16">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php
        $args = array(
            'post_type' => 'confirmation',
            'post_status' => 'publish',
            'order' => 'DESC',
            'posts_per_page' => -1,
            'ignore_sticky_posts' => true
        );
        $loop = new WP_Query($args);
        if ($loop->have_posts()) :
            while ($loop->have_posts()) :
                $loop->the_post();
                $organization = get_field('issuing_organization') ?: 'شرکت کارفرما';
                $letter_number = get_field('letter_number');
                $scope = get_field('approval_scope');
                $letter_file = get_field('letter_file');
                $file_url = !empty($letter_file['url']) ? $letter_file['url'] : get_the_post_thumbnail_url(get_the_ID(), 'full');
                $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=600&auto=format&fit=crop';
        ?>
                <article class="group relative rounded-2xl border border-slate-200/80 bg-white hover:border-blue-500/50 transition-all duration-300 shadow-sm hover:shadow-xl flex flex-col h-full overflow-hidden" data-aos="fade-up">
                    <!-- Image Frame -->
                    <div class="relative h-56 w-full overflow-hidden bg-slate-100 p-3 border-b border-slate-100">
                        <span class="absolute top-3 right-3 z-10 px-2.5 py-1 text-[11px] font-bold bg-blue-700 text-white rounded-full shadow-sm">
                            🏛️ <?= esc_html($organization); ?>
                        </span>
                        <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300" />
                    </div>

                    <!-- Content -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-3 bg-white">
                        <div class="space-y-2">
                            <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-700 transition-colors leading-snug">
                                <?= get_the_title(); ?>
                            </h3>
                            <?php if ($scope) : ?>
                                <p class="text-xs text-slate-600 leading-relaxed line-clamp-2">
                                    <?= esc_html($scope); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($letter_number) : ?>
                                <div class="text-[11px] text-slate-500 font-mono pt-1">
                                    شماره نامه: <span class="font-bold text-slate-700"><?= esc_html($letter_number); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Action Button -->
                        <?php if ($file_url) : ?>
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-[11px] text-emerald-600 font-bold flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    مورد تأیید رسمی
                                </span>
                                <a href="<?= esc_url($file_url); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold bg-blue-50 hover:bg-blue-700 text-blue-700 hover:text-white transition-all border border-blue-200">
                                    <span>مشاهده نامه</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>
            <?php
            endwhile;
        else : ?>
            <div class="col-span-full py-16 text-center text-slate-500 text-sm bg-white rounded-2xl border border-slate-200 shadow-sm">
                هیچ تأییدیه‌ای ثبت نشده است.
            </div>
        <?php
        endif;
        wp_reset_postdata();
        ?>
    </div>
</main>

<?php get_footer(); ?>
