<?php
/**
 * Most Visited / Popular Posts Section Component
 * PSB-CO High & Medium Voltage Theme
 */

$args = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'meta_key' => 'post_views',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'ignore_sticky_posts' => true
);

$popular_posts = new WP_Query($args);

if ($popular_posts->have_posts()) :
    $posts_array = $popular_posts->posts;
    $hero_post = $posts_array[0] ?? null;
    $side_posts = array_slice($posts_array, 1);
?>
    <section class="bg-primary-dark rounded-3xl border border-slate-200/90 p-6 sm:p-8 shadow-sm space-y-6 select-none" data-aos="fade-up">
        <!-- Section Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div class="space-y-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    🔥 برترین‌های مجله
                </span>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                    پربازدیدترین مقالات تخصصی صنعت برق
                </h2>
            </div>
            <span class="text-xs text-slate-500 font-medium">بر اساس بیشترین آمار بازدید کاربران</span>
        </div>

        <!-- Asymmetric Layout: 1 Hero Featured + 4 Compact Ranked Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
            
            <!-- Hero Popular Post (lg:col-span-7) -->
            <?php if ($hero_post) :
                $post = $hero_post;
                setup_postdata($post);
                $hero_thumb = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop';
                $hero_views = get_post_meta(get_the_ID(), 'post_views', true) ?: '340';
                $hero_cats = get_the_category();
                $hero_cat_name = !empty($hero_cats) ? $hero_cats[0]->name : 'مقاله تخصصی';
            ?>
                <div class="lg:col-span-7">
                    <a href="<?php the_permalink(); ?>" class="group relative rounded-2xl overflow-hidden flex flex-col justify-end aspect-[16/10] sm:aspect-[16/9] w-full border border-slate-200 shadow-md hover:shadow-xl transition-all duration-500 bg-slate-950">
                        <span class="absolute top-4 right-4 z-20 px-3 py-1 rounded-full text-xs font-bold bg-blue-600 text-white shadow-sm">
                            ⭐ منتخب بازدیدکنندگان
                        </span>

                        <img src="<?= esc_url($hero_thumb); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 opacity-80 group-hover:opacity-95" />

                        <div class="relative z-10 p-5 sm:p-6 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent pt-20 space-y-2">
                            <div class="flex items-center gap-3 text-xs text-blue-300 font-mono font-bold">
                                <span>📰 <?= esc_html($hero_cat_name); ?></span>
                                <span>•</span>
                                <span>👁️ <?= esc_html($hero_views); ?> بازدید</span>
                            </div>

                            <h3 class="text-base sm:text-xl font-bold text-white group-hover:text-blue-300 transition-colors leading-snug">
                                <?php the_title(); ?>
                            </h3>

                            <p class="text-xs sm:text-sm text-slate-300 line-clamp-2 leading-relaxed font-sans">
                                <?= esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 20)); ?>
                            </p>
                        </div>
                    </a>
                </div>
            <?php wp_reset_postdata(); endif; ?>

            <!-- Ranked List (4 items, lg:col-span-5) -->
            <div class="lg:col-span-5 flex flex-col justify-between space-y-3">
                <?php
                $rank = 2;
                foreach ($side_posts as $post) :
                    setup_postdata($post);
                    $item_views = get_post_meta(get_the_ID(), 'post_views', true) ?: '210';
                    $item_thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                ?>
                    <a href="<?php the_permalink(); ?>" class="group p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-blue-500 hover:bg-white hover:shadow-md transition-all flex items-center gap-3.5">
                        <span class="w-7 h-7 rounded-xl bg-slate-200 group-hover:bg-blue-600 text-slate-700 group-hover:text-white font-mono text-xs font-bold flex items-center justify-center flex-shrink-0 transition-colors">
                            #<?= $rank++; ?>
                        </span>

                        <?php if ($item_thumb) : ?>
                            <div class="w-16 h-14 rounded-xl overflow-hidden bg-slate-200 flex-shrink-0">
                                <img src="<?= esc_url($item_thumb); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform" />
                            </div>
                        <?php endif; ?>

                        <div class="space-y-1 flex-1 min-w-0">
                            <h4 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2 leading-snug">
                                <?php the_title(); ?>
                            </h4>
                            <span class="text-[11px] text-slate-400 font-mono block">
                                👁️ <?= esc_html($item_views); ?> بازدید
                            </span>
                        </div>
                    </a>
                <?php endforeach; wp_reset_postdata(); ?>
            </div>

        </div>
    </section>
<?php
endif;
wp_reset_postdata();
?>
