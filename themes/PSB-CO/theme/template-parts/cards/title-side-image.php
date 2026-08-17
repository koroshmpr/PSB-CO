<?php
/**
 * Corporate Title Side Image Card - Light Theme
 * PSB-CO Theme
 */

$thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop';
$categories = get_the_category();
?>

<article class="group p-2.5 rounded-md border border-slate-200 bg-white hover:border-blue-400 transition-all duration-200 shadow-sm " data-aos="fade-up">
    <a href="<?php the_permalink(); ?>" class="flex items-center gap-3">
        <div class="w-16 h-16 rounded overflow-hidden bg-slate-100 flex-shrink-0 relative">
            <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
        </div>
        <div class="flex-1 space-y-1">
            <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                <?php if (!empty($categories)) : ?>
                    <span class="font-bold text-blue-700"><?= esc_html($categories[0]->name); ?></span>
                    <span>•</span>
                <?php endif; ?>
                <span><?= esc_html(get_the_date('j F Y')); ?></span>
            </div>
            <h4 class="text-xs md:text-sm font-bold text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2 leading-snug">
                <?= get_the_title(); ?>
            </h4>
        </div>
    </a>
</article>
