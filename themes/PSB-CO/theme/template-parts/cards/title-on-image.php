<?php
/**
 * Corporate Title On Image Card - Light Theme
 * PSB-CO Theme
 */

$thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop';
$categories = get_the_category();
?>

<article class="group relative h-64 rounded-md overflow-hidden shadow-sm border border-slate-200 bg-slate-900 flex flex-col justify-between p-4 transition-all duration-200 hover:border-blue-400 " data-aos="fade-up">
    <!-- Background Image & Gradient -->
    <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="absolute inset-0 w-full h-full object-cover object-center opacity-40 group-hover:scale-105 transition-transform duration-300 ease-out z-0" />
    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/20 z-10"></div>

    <!-- Author & Date Header -->
    <div class="relative z-20 flex items-center gap-2">
        <div class="w-7 h-7 rounded-full bg-slate-800 border border-slate-700 overflow-hidden flex items-center justify-center text-blue-400 font-bold text-xs">
            <?= esc_html(mb_substr(get_the_author_meta('display_name'), 0, 1)); ?>
        </div>
        <div class="text-[11px] text-slate-300">
            <span class="block font-bold text-white"><?= esc_html(get_the_author_meta('display_name')); ?></span>
            <span class="text-slate-400"><?= esc_html(get_the_date('j F Y')); ?></span>
        </div>
    </div>

    <!-- Title & Category Footer -->
    <div class="relative z-20 space-y-1">
        <?php if (!empty($categories)) : ?>
            <span class="inline-block text-[11px] font-bold text-blue-300 bg-blue-700/80 px-2 py-0.5 rounded">
                <?= esc_html($categories[0]->name); ?>
            </span>
        <?php endif; ?>
        
        <h3 class="text-sm font-bold text-white group-hover:text-blue-300 transition-colors line-clamp-2 leading-snug">
            <a href="<?php the_permalink(); ?>">
                <?= get_the_title(); ?>
            </a>
        </h3>
        <p class="text-slate-300 text-xs line-clamp-2">
            <?= esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 12)); ?>
        </p>
    </div>
</article>
