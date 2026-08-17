<?php
/**
 * Modern High-End Blog Post Card Component
 * PSB-CO High & Medium Voltage Electrical Theme
 */

$is_eager = $args['eager'] ?? false;
$loading_attr = $is_eager ? 'eager' : 'lazy';
$fetch_priority = $is_eager ? 'fetchpriority="high"' : '';

$categories = get_the_category();
$cat_name = !empty($categories) ? $categories[0]->name : 'مقالات';

$post_id = get_the_ID();
$view = get_post_meta($post_id, 'post_views', true) ?: '128';
$comments_cnt = get_comments_number();

$day = function_exists('shamsi_date') ? shamsi_date('d', get_the_time('U')) : get_the_date('d');
$month = function_exists('shamsi_date') ? shamsi_date('F', get_the_time('U')) : get_the_date('F');
$year = function_exists('shamsi_date') ? shamsi_date('Y', get_the_time('U')) : get_the_date('Y');
?>

<a href="<?php the_permalink(); ?>" 
   x-data="{ imgLoaded: false }"
   class="relative group overflow-hidden rounded-2xl flex flex-col justify-end aspect-[3/4] w-full border border-slate-200/80 shadow-md hover:shadow-2xl transition-all duration-500 bg-slate-900 select-none <?= esc_attr($args['class'] ?? ''); ?>">
    
    <!-- Skeleton Loading Effect -->
    <div x-show="!imgLoaded" x-transition.opacity.duration.300ms
         class="absolute inset-0 z-0 bg-slate-800 animate-pulse"></div>

    <!-- Category Badge Top Right -->
    <span class="absolute top-3.5 right-3.5 z-20 px-3 py-1 text-[11px] font-bold bg-slate-950/85 text-blue-300 rounded-full border border-blue-500/30 backdrop-blur-md shadow-md">
        📰 <?= esc_html($cat_name); ?>
    </span>

    <!-- Shamsi Date Badge Top Left -->
    <div class="absolute top-3.5 left-3.5 z-20 text-center text-white p-2 rounded-xl bg-blue-600/90 group-hover:bg-blue-600 transition-all duration-300 backdrop-blur-md shadow-md min-w-[48px]">
        <span class="text-base font-black leading-none block font-mono"><?= esc_html($day); ?></span>
        <span class="text-[10px] font-bold block mt-0.5"><?= esc_html($month); ?></span>
    </div>

    <!-- Background Image with Zoom on Hover -->
    <picture class="absolute inset-0 w-full h-full z-0">
        <source media="(min-width: 961px)" srcset="<?php the_post_thumbnail_url('large'); ?>">
        <source media="(max-width: 960px)" srcset="<?php the_post_thumbnail_url('medium'); ?>">
        <img class="w-full h-full object-cover group-hover:scale-108 transition-transform duration-700 ease-out opacity-85 group-hover:opacity-100"
             x-ref="blogImg"
             x-init="if ($refs.blogImg && $refs.blogImg.complete) imgLoaded = true"
             @load="imgLoaded = true"
             loading="<?= esc_attr($loading_attr); ?>"
            <?= $fetch_priority; ?>
             src="<?= get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop'; ?>"
             alt="<?= esc_attr(get_the_title()); ?>">
    </picture>

    <!-- Gradient Overlay & Card Footer Content -->
    <div class="relative z-10 w-full p-4 sm:p-5 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent pt-16 flex flex-col justify-end space-y-2">
        
        <!-- Post Stats (Views & Comments) -->
        <div class="flex items-center gap-3 text-[11px] text-slate-300 font-mono">
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span><?= esc_html($view); ?></span>
            </span>
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 4.418 9 8z"/></svg>
                <span><?= esc_html($comments_cnt); ?></span>
            </span>
        </div>

        <!-- Post Title -->
        <h3 class="text-sm sm:text-base font-bold text-white group-hover:text-blue-300 transition-colors line-clamp-2 leading-snug">
            <?php the_title(); ?>
        </h3>

        <!-- Excerpt Snippet (Shows smoothly on hover) -->
        <p class="text-[11px] sm:text-xs text-slate-300 line-clamp-2 leading-relaxed font-sans opacity-90 group-hover:opacity-100 transition-opacity">
            <?= esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 14)); ?>
        </p>

        <!-- Read Action -->
        <div class="pt-2 flex items-center justify-between text-xs font-bold text-blue-400 group-hover:text-white transition-colors">
            <span>ادامه مقاله</span>
            <svg class="w-4 h-4 rtl:rotate-180 transform group-hover:translate-x-[-3px] transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </div>
    </div>
</a>