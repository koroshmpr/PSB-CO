<?php
/**
 * Most Discussed / Highly Commented Posts Component
 * PSB-CO High & Medium Voltage Electrical Theme
 */

$args = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 4,
    'orderby' => 'comment_count',
    'order' => 'DESC',
    'ignore_sticky_posts' => true
);

$discussed_query = new WP_Query($args);

if ($discussed_query->have_posts()) : ?>
    <section class="bg-slate-900 text-white rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-xl space-y-6 select-none my-8" data-aos="fade-up">
        <!-- Section Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
            <div class="space-y-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-bold bg-blue-600/30 text-blue-300 border border-blue-500/30">
                    💬 پربحث‌ترین مقالات و تبادل نظرها
                </span>
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    محبوب‌ترین مباحث آموزشی با بیشترین دیدگاه‌ها
                </h2>
            </div>
            <span class="text-xs text-slate-400 font-medium">پاسخگویی به پرسش‌های چالش‌برانگیز صنعت برق</span>
        </div>

        <!-- 4 Column Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php while ($discussed_query->have_posts()) : $discussed_query->the_post();
                $comments_count = get_comments_number();
                $post_thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                $post_views = get_post_meta(get_the_ID(), 'post_views', true) ?: '190';
            ?>
                <a href="<?php the_permalink(); ?>" class="group p-4 rounded-2xl bg-slate-950/80 border border-slate-800 hover:border-blue-500 transition-all duration-300 flex flex-col justify-between space-y-3 shadow-md">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-blue-600/30 text-blue-300 border border-blue-500/30 flex items-center gap-1">
                                💬 <span><?= esc_html($comments_count); ?> دیدگاه</span>
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">👁️ <?= esc_html($post_views); ?></span>
                        </div>

                        <?php if ($post_thumb) : ?>
                            <div class="w-full h-32 rounded-xl overflow-hidden bg-slate-900">
                                <img src="<?= esc_url($post_thumb); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 opacity-90 group-hover:opacity-100" />
                            </div>
                        <?php endif; ?>

                        <h4 class="text-xs sm:text-sm font-bold text-white group-hover:text-blue-300 transition-colors line-clamp-2 leading-snug">
                            <?php the_title(); ?>
                        </h4>
                    </div>

                    <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] font-bold text-blue-400 group-hover:text-white">
                        <span>ورود به گفت‌وگو</span>
                        <svg class="w-3.5 h-3.5 rtl:rotate-180 group-hover:translate-x-[-2px] transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </div>
                </a>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </section>
<?php endif; wp_reset_postdata(); ?>
