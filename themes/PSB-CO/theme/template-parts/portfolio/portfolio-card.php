<?php
/**
 * Professional Electrical Switchboard Portfolio Card Component
 * PSB-CO High & Medium Voltage Theme
 */

$brand_logo = get_field('brand-logo');
$client_name = get_field('client_name');
$voltage_class = get_field('voltage_class');
$rated_current = get_field('rated_current');
$ip_rating = get_field('ip_rating');
$project_location = get_field('project_location');
$panel_type = get_field('panel_type');

$thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop';
$excerpt = wp_trim_words(get_the_excerpt() ?: get_the_content(), 14);

// Priority 1: Check Voltage Level Taxonomy Term
$voltage_terms = get_the_terms(get_the_ID(), 'voltage_level');
$voltage_badge = 'فشار متوسط (MV)';
$voltage_color = 'bg-amber-500/10 text-amber-600 border-amber-500/20';

if ($voltage_terms && !is_wp_error($voltage_terms)) {
    $voltage_badge = $voltage_terms[0]->name;
} else {
    if ($voltage_class === '400v') {
        $voltage_badge = 'فشار ضعیف (400V)';
        $voltage_color = 'bg-blue-500/10 text-blue-600 border-blue-500/20';
    } elseif ($voltage_class === '20kv') {
        $voltage_badge = 'فشار متوسط (20kV)';
        $voltage_color = 'bg-amber-500/10 text-amber-600 border-amber-500/20';
    } elseif ($voltage_class === '33kv') {
        $voltage_badge = 'فشار متوسط (33kV)';
        $voltage_color = 'bg-orange-500/10 text-orange-600 border-orange-500/20';
    } elseif ($voltage_class === 'hv') {
        $voltage_badge = 'فشار قوی (HV)';
        $voltage_color = 'bg-red-500/10 text-red-600 border-red-500/20';
    }
}

// Priority 2: Check Industry Sector Taxonomy Term
$industry_terms = get_the_terms(get_the_ID(), 'industry_sector');
$industry_name = ($industry_terms && !is_wp_error($industry_terms)) ? $industry_terms[0]->name : '';
?>

<article class="group relative rounded-2xl border border-slate-200/80 bg-white hover:border-blue-500/50 transition-all duration-300 shadow-sm hover:shadow-xl flex flex-col h-full overflow-hidden" data-aos="fade-up">
    <!-- Top Image Container -->
    <div class="relative h-52 w-full overflow-hidden bg-slate-900">
        <!-- Voltage Class Badge Top Right -->
        <div class="absolute top-3 right-3 z-20">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border backdrop-blur-md <?= $voltage_color; ?> shadow-sm">
                <span class="w-2 h-2 rounded-full bg-current animate-pulse"></span>
                <?= esc_html($voltage_badge); ?>
            </span>
        </div>

        <!-- Client Logo Floating Badge Top Left -->
        <?php if (!empty($brand_logo['url'])) : ?>
            <div class="absolute top-3 left-3 z-20 w-11 h-11 rounded-xl bg-white/95 backdrop-blur-md p-1.5 shadow-md border border-slate-200 flex items-center justify-center">
                <img src="<?= esc_url($brand_logo['url']); ?>" alt="<?= esc_attr($brand_logo['title'] ?? get_the_title()); ?>" class="max-w-full max-h-full object-contain" />
            </div>
        <?php endif; ?>

        <!-- Featured Image -->
        <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="w-full h-full object-cover object-center group-hover:scale-108 transition-transform duration-700 ease-out opacity-90 group-hover:opacity-100" />
        
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>

        <!-- Location or Industry Badge Bottom Overlay -->
        <?php if ($industry_name || $project_location) : ?>
            <div class="absolute bottom-3 right-3 z-20 text-[11px] font-medium text-slate-200 flex items-center gap-1.5 bg-slate-950/70 backdrop-blur-md px-2.5 py-0.5 rounded-full border border-white/10">
                <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span><?= esc_html($industry_name ?: $project_location); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Content Area -->
    <div class="p-5 flex-1 flex flex-col justify-between space-y-4 bg-white">
        <div class="space-y-2">
            <!-- Project Title -->
            <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-600 transition-colors line-clamp-2 leading-snug">
                <a href="<?php the_permalink(); ?>" class="hover:underline">
                    <?= get_the_title(); ?>
                </a>
            </h3>

            <!-- Client & Panel Type Meta -->
            <?php if ($client_name || $panel_type) : ?>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500 font-medium">
                    <?php if ($client_name) : ?>
                        <span class="flex items-center gap-1">
                            <span class="text-slate-400">کارفرما:</span>
                            <strong class="text-slate-700 font-semibold"><?= esc_html($client_name); ?></strong>
                        </span>
                    <?php endif; ?>
                    <?php if ($panel_type) : ?>
                        <span class="flex items-center gap-1">
                            <span class="text-slate-400">ساختار:</span>
                            <span class="text-slate-600"><?= esc_html($panel_type); ?></span>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Technical Spec Pills -->
            <div class="flex flex-wrap items-center gap-1.5 pt-1">
                <?php if ($rated_current) : ?>
                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-mono font-bold border border-slate-200">
                        ⚡ <?= esc_html($rated_current); ?>
                    </span>
                <?php endif; ?>
                <?php if ($ip_rating) : ?>
                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-mono font-bold border border-slate-200">
                        🛡️ <?= esc_html($ip_rating); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Footer -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-emerald-600 font-bold flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                پروژه به بهره‌برداری رسیده
            </span>

            <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition-all shadow-sm group-hover:shadow-md">
                <span>مشخصات فنی</span>
                <svg class="w-3.5 h-3.5 rtl:rotate-180 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>
    </div>
</article>
