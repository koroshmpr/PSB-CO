<?php
/**
 * Professional Electrical Service Card Component - Minimal Corporate Light Theme
 * PSB-CO High & Medium Voltage Theme
 */

$badge = get_field('services_badge') ?: 'خدمات تخصصی';
$icon = get_field('service_icon') ?: '⚡';
$description = get_field('services_description') ?: wp_trim_words(get_the_excerpt() ?: get_the_content(), 14);
$standards = get_field('standards_compliance');
$features_text = get_field('technical_features');
$features = $features_text ? array_filter(array_map('trim', explode("\n", $features_text))) : [];

$thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://images.unsplash.com/photo-1581092335397-9583fe92d232?q=80&w=800&auto=format&fit=crop';
?>

<article class="group relative rounded-2xl border border-slate-200/80 bg-white hover:border-blue-500/50 transition-all duration-300 shadow-sm hover:shadow-xl flex flex-col h-full overflow-hidden" data-aos="fade-up">
    <!-- Image Container -->
    <div class="relative h-48 w-full overflow-hidden bg-slate-900">
        <!-- Floating Category Badge Top Right -->
        <div class="absolute top-3 right-3 z-20">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/95 backdrop-blur-md text-blue-800 border border-slate-200 shadow-sm">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                <?= esc_html($badge); ?>
            </span>
        </div>

        <!-- Floating Icon Top Left -->
        <?php if ($icon) : ?>
            <div class="absolute top-3 left-3 z-20 w-10 h-10 rounded-xl bg-white/95 backdrop-blur-md border border-slate-200 flex items-center justify-center text-lg shadow-sm">
                <?= esc_html($icon); ?>
            </div>
        <?php endif; ?>

        <!-- Featured Image -->
        <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="w-full h-full object-cover object-center group-hover:scale-108 transition-transform duration-700 ease-out opacity-90 group-hover:opacity-100" />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
    </div>

    <!-- Content Area -->
    <div class="p-5 flex-1 flex flex-col justify-between space-y-4 bg-white">
        <div class="space-y-2">
            <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-700 transition-colors line-clamp-2 leading-snug">
                <a href="<?php the_permalink(); ?>" class="hover:underline">
                    <?= get_the_title(); ?>
                </a>
            </h3>

            <p class="text-slate-600 text-xs leading-relaxed line-clamp-2">
                <?= esc_html($description); ?>
            </p>

            <!-- Technical Feature Bullets -->
            <?php if (!empty($features)) : ?>
                <ul class="pt-2 space-y-1.5 border-t border-slate-100 text-[11px] text-slate-600">
                    <?php 
                    $count = 0;
                    foreach ($features as $feature) : 
                        if ($count >= 2) break;
                    ?>
                        <li class="flex items-center gap-1.5 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 shrink-0"></span>
                            <span class="line-clamp-1"><?= esc_html($feature); ?></span>
                        </li>
                    <?php 
                        $count++;
                    endforeach; 
                    ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Action Footer -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-slate-500 font-semibold flex items-center gap-1">
                <?php if ($standards) : ?>
                    <span class="text-blue-700 font-mono font-bold"><?= esc_html($standards); ?></span>
                <?php else : ?>
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <span>خط تولید & آزمایشگاه</span>
                <?php endif; ?>
            </span>

            <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold bg-blue-50 hover:bg-blue-700 text-blue-700 hover:text-white transition-all border border-blue-200/80">
                <span>جزئیات خدمت</span>
                <svg class="w-3.5 h-3.5 rtl:rotate-180 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>
</article>
