<?php
/**
 * Minimal Corporate Post Card Component - Light Theme
 * PSB-CO Theme
 */

$thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop';
$categories = get_the_category();
$reading_time = ceil(str_word_count(strip_tags(get_the_content())) / 150) ?: 3;
?>

<article class="group relative rounded-2xl border border-slate-200/80 bg-white hover:border-blue-500/50 transition-all duration-300 shadow-sm hover:shadow-xl flex flex-col h-full overflow-hidden" data-aos="fade-up">
    <!-- Top Image Frame -->
    <div class="relative h-48 w-full overflow-hidden bg-slate-900">
        <?php if (!empty($categories)) : ?>
            <span class="absolute top-3 right-3 z-20 px-3 py-1 text-[11px] font-bold bg-white/95 text-blue-800 rounded-full border border-slate-200 shadow-sm backdrop-blur-md">
                <?= esc_html($categories[0]->name); ?>
            </span>
        <?php endif; ?>

        <!-- Reading Time Pill -->
        <span class="absolute bottom-3 right-3 z-20 text-[11px] font-medium text-slate-200 flex items-center gap-1 bg-slate-950/70 backdrop-blur-md px-2.5 py-0.5 rounded-full border border-white/10">
            ⏱️ <span><?= esc_html($reading_time); ?> دقیقه مطالعه</span>
        </span>

        <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="w-full h-full object-cover object-center group-hover:scale-108 transition-transform duration-700 ease-out opacity-90 group-hover:opacity-100" />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
    </div>

    <!-- Card Body -->
    <div class="p-5 flex-1 flex flex-col justify-between space-y-4 bg-white">
        <div class="space-y-2">
            <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2 leading-snug">
                <a href="<?php the_permalink(); ?>" class="hover:underline">
                    <?= get_the_title(); ?>
                </a>
            </h3>

            <p class="text-slate-600 text-xs leading-relaxed line-clamp-2">
                <?= esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 16)); ?>
            </p>
        </div>

        <!-- Action Footer -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-slate-500 font-medium flex items-center gap-1">
                <span>📅 <?= esc_html(get_the_date('j F Y')); ?></span>
            </span>

            <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold bg-blue-50 hover:bg-blue-700 text-blue-700 hover:text-white transition-all border border-blue-200/80">
                <span>مطالعه مقاله</span>
                <svg class="w-3.5 h-3.5 rtl:rotate-180 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>
</article>
