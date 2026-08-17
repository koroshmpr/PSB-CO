<?php
/**
 * Minimal Corporate Electrical Switchboard Single Portfolio Template
 * PSB-CO High & Medium Voltage Engineering Theme
 */

get_header();

track_post_views(get_the_ID());

while (have_posts()) :
    the_post();

    // Fetch electrical portfolio custom fields
    $brand_logo = get_field('brand-logo');
    $client_name = get_field('client_name');
    $voltage_class = get_field('voltage_class');
    $rated_current = get_field('rated_current');
    $short_circuit_current = get_field('short_circuit_current');
    $ip_rating = get_field('ip_rating');
    $panel_type = get_field('panel_type');
    $standards_compliance = get_field('standards_compliance');
    $project_location = get_field('project_location');
    $execution_year = get_field('execution_year');
    $sld_document = get_field('sld_document');
    $project_gallery = get_field('project_gallery');
    $portfolio_website = get_field('portfolio-website');

    $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'full');

    // Voltage Badge formatting with Taxonomy priority
    $voltage_terms = get_the_terms(get_the_ID(), 'voltage_level');
    $voltage_badge = ($voltage_terms && !is_wp_error($voltage_terms)) ? $voltage_terms[0]->name : 'فشار متوسط (20kV - MV)';
    $voltage_color = 'bg-amber-50 text-amber-700 border-amber-200';

    if (!$voltage_terms || is_wp_error($voltage_terms)) {
        if ($voltage_class === '400v') {
            $voltage_badge = 'فشار ضعیف (400V - LV)';
            $voltage_color = 'bg-blue-50 text-blue-700 border-blue-200';
        } elseif ($voltage_class === '20kv') {
            $voltage_badge = 'فشار متوسط (20kV - MV)';
            $voltage_color = 'bg-amber-50 text-amber-700 border-amber-200';
        } elseif ($voltage_class === '33kv') {
            $voltage_badge = 'فشار متوسط (33kV - MV)';
            $voltage_color = 'bg-orange-50 text-orange-700 border-orange-200';
        } elseif ($voltage_class === 'hv') {
            $voltage_badge = 'فشار قوی (HV)';
            $voltage_color = 'bg-red-50 text-red-700 border-red-200';
        }
    }

    $industry_terms = get_the_terms(get_the_ID(), 'industry_sector');
    $industry_name = ($industry_terms && !is_wp_error($industry_terms)) ? $industry_terms[0]->name : '';
?>

<article class="bg-slate-50/70 text-slate-900 min-h-screen py-8 md:py-12 select-none">
    <div class="container mx-auto px-4 md:px-8 max-w-6xl space-y-8">
        
        <!-- Breadcrumb & Top Header -->
        <header class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <!-- Categories Badges -->
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $voltage_color; ?>">
                    <span class="w-2 h-2 rounded-full bg-current inline-block me-1.5 animate-pulse"></span>
                    <?= esc_html($voltage_badge); ?>
                </span>

                <?php if ($industry_name) : ?>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                        🏢 <?= esc_html($industry_name); ?>
                    </span>
                <?php endif; ?>

                <?php
                $terms = get_the_terms(get_the_ID(), 'portfolio_categories');
                if ($terms && !is_wp_error($terms)) :
                    foreach ($terms as $term) : ?>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            <?= esc_html($term->name); ?>
                        </span>
                    <?php endforeach;
                endif; ?>
            </div>

            <!-- Page Title & Actions -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pt-1">
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 leading-tight tracking-tight">
                    <?= get_the_title(); ?>
                </h1>

                <?php if (!empty($portfolio_website['url'])) : ?>
                    <a href="<?= esc_url($portfolio_website['url']); ?>" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold bg-blue-700 hover:bg-blue-800 text-white text-sm shadow-sm transition-all self-start lg:self-auto shrink-0">
                        <span>وب‌سایت پروژه</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Quick Project Meta Pills -->
            <div class="flex flex-wrap items-center gap-6 pt-4 border-t border-slate-100 text-xs sm:text-sm text-slate-600">
                <?php if ($client_name) : ?>
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400">کارفرما:</span>
                        <strong class="text-slate-900 font-bold"><?= esc_html($client_name); ?></strong>
                    </div>
                <?php endif; ?>
                <?php if ($project_location) : ?>
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400">محل پروژه:</span>
                        <span class="text-slate-700 font-medium"><?= esc_html($project_location); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($execution_year) : ?>
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400">سال اجرا:</span>
                        <span class="text-slate-700 font-mono font-bold"><?= esc_html($execution_year); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($panel_type) : ?>
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-400">ساختار:</span>
                        <span class="text-slate-700 font-medium"><?= esc_html($panel_type); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Main Content & Sidebar Grid -->
        <div class="grid lg:grid-cols-12 gap-8 items-start">
            
            <!-- Main Column (Specifications, Gallery, Content) -->
            <main class="lg:col-span-8 space-y-8 order-2 lg:order-1">
                
                <!-- Main Featured Image Showcase Frame -->
                <?php if ($thumbnail) : ?>
                    <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-white p-2 shadow-sm group">
                        <?php if (!empty($brand_logo['url'])) : ?>
                            <div class="absolute top-5 left-5 z-20 w-14 h-14 rounded-xl bg-white/95 backdrop-blur-md p-1.5 shadow-md border border-slate-200 flex items-center justify-center">
                                <img src="<?= esc_url($brand_logo['url']); ?>" alt="<?= esc_attr($brand_logo['title'] ?? get_the_title()); ?>" class="max-w-full max-h-full object-contain" />
                            </div>
                        <?php endif; ?>

                        <div class="overflow-hidden rounded-xl bg-slate-100">
                            <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="w-full h-auto max-h-[500px] object-cover group-hover:scale-103 transition-transform duration-500" />
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Technical Datasheet Parameters Table -->
                <section class="p-6 md:p-8 rounded-2xl bg-white border border-slate-200/80 space-y-6 shadow-sm" data-aos="fade-up">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center font-bold text-lg">
                            ⚡
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">جدول مشخصات و پارامترهای فنی تابلو برق</h2>
                            <p class="text-xs text-slate-500">اطلاعات ساختاری و مشخصات الکتریکی پروژه</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-sm">
                        <?php if ($voltage_class || $voltage_badge) : ?>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                                <span class="text-slate-500 text-xs sm:text-sm">سطح ولتاژ کاری:</span>
                                <strong class="text-blue-700 font-bold text-xs sm:text-sm"><?= esc_html($voltage_badge); ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($rated_current) : ?>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                                <span class="text-slate-500 text-xs sm:text-sm">جریان نامی (Amperage):</span>
                                <strong class="text-slate-900 font-mono font-bold text-xs sm:text-sm"><?= esc_html($rated_current); ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($short_circuit_current) : ?>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                                <span class="text-slate-500 text-xs sm:text-sm">تحمل اتصال کوتاه:</span>
                                <strong class="text-slate-900 font-mono font-bold text-xs sm:text-sm"><?= esc_html($short_circuit_current); ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($ip_rating) : ?>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                                <span class="text-slate-500 text-xs sm:text-sm">درجه حفاظت (IP Rating):</span>
                                <strong class="text-emerald-700 font-mono font-bold text-xs sm:text-sm"><?= esc_html($ip_rating); ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($panel_type) : ?>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                                <span class="text-slate-500 text-xs sm:text-sm">نوع و ساختار تابلو:</span>
                                <strong class="text-slate-800 font-semibold text-xs sm:text-sm"><?= esc_html($panel_type); ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if ($standards_compliance) : ?>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                                <span class="text-slate-500 text-xs sm:text-sm">استاندارد مرجع:</span>
                                <strong class="text-slate-800 font-mono text-xs"><?= esc_html($standards_compliance); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- SLD Technical Drawing Download Box -->
                <?php if (!empty($sld_document['url'])) : ?>
                    <div class="p-6 rounded-2xl bg-blue-50/70 border border-blue-200/80 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm" data-aos="fade-up">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xl shrink-0 shadow-sm">
                                📄
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">نقشه تک خطی (SLD) / مشخصات فنی PDF</h3>
                                <p class="text-xs text-slate-600 mt-0.5"><?= esc_html($sld_document['filename'] ?? 'فایل مشخصات پروژه'); ?></p>
                            </div>
                        </div>

                        <a href="<?= esc_url($sld_document['url']); ?>" target="_blank" download class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold bg-blue-700 hover:bg-blue-800 text-white text-sm shadow-sm transition-all shrink-0">
                            <span>دانلود نقشه / کاتالوگ</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Article Body / Engineering Description -->
                <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">شرح فنی و مهندسی پروژه</h2>
                    <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed text-justify">
                        <?php the_content(); ?>
                    </div>
                </div>

                <!-- Technical Gallery -->
                <?php if ($project_gallery) : ?>
                    <section class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4" data-aos="fade-up">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <span class="text-blue-600">🖼️</span>
                            گالری تصاویر کارگاه و تجهیزات نصب‌شده
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                            <?php foreach ($project_gallery as $img) : ?>
                                <a href="<?= esc_url($img['url']); ?>" target="_blank" class="group relative rounded-xl overflow-hidden border border-slate-200 aspect-video bg-slate-100 block">
                                    <img src="<?= esc_url($img['sizes']['medium'] ?? $img['url']); ?>" alt="<?= esc_attr($img['title'] ?? get_the_title()); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div class="absolute inset-0 bg-blue-700/0 group-hover:bg-blue-700/10 transition-colors"></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Tags Section -->
                <?php
                $tags = get_the_terms(get_the_ID(), 'portfolio_tag') ?: get_the_tags();
                if ($tags && !is_wp_error($tags)) : ?>
                    <div class="pt-4 border-t border-slate-200 space-y-3">
                        <h4 class="text-xs font-bold text-slate-500">برچسب‌های فنی:</h4>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($tags as $tag) : ?>
                                <a href="<?= esc_url(get_term_link($tag)); ?>" class="px-3 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 hover:bg-blue-50 hover:text-blue-700 hover:border-blue-200 transition-colors">
                                    # <?= esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>

            <!-- Sidebar Column -->
            <aside class="lg:col-span-4 space-y-6 order-1 lg:order-2 lg:sticky lg:top-24">
                <!-- Client Brand Card -->
                <?php if (!empty($brand_logo['url'])) : ?>
                    <div class="p-6 rounded-2xl bg-white border border-slate-200/80 text-center space-y-3 shadow-sm">
                        <div class="w-20 h-20 mx-auto rounded-xl bg-slate-50 p-2 border border-slate-200 flex items-center justify-center">
                            <img src="<?= esc_url($brand_logo['url']); ?>" alt="<?= esc_attr($brand_logo['title'] ?? get_the_title()); ?>" class="max-w-full max-h-full object-contain" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900"><?= esc_html($brand_logo['title'] ?? $client_name ?? 'کارفرمای پروژه'); ?></h3>
                            <span class="text-xs text-slate-500">کارفرما / مشاور رسمی</span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Switchboard Technical Consultation / Inquiry Box -->
                <div class="p-6 rounded-2xl bg-white border border-blue-200 space-y-4 shadow-sm text-center">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-xl">
                        📞
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900">استعلام قیمت و مشاوره تابلو برق</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">برای مشاوره فنی، طراحی Single Line Diagram یا استعلام قیمت ساخت تابلو با کارشناسان ما تماس بگیرید.</p>
                    </div>
                    <button type="button" @click="shopContact = true" class="block w-full py-3.5 px-4 rounded-xl font-bold bg-blue-700 hover:bg-blue-800 text-white text-sm shadow-md shadow-blue-700/20 transition-all cursor-pointer">
                        ارتباط با کارشناسان فنی
                    </button>
                </div>
            </aside>

        </div>

        <!-- Related Portfolio Projects Grid -->
        <section class="mt-12 pt-10 border-t border-slate-200 space-y-6">
            <div class="text-center space-y-1">
                <h3 class="text-xl sm:text-2xl font-black text-slate-900">پروژه‌های مرتبط تابلوسازی</h3>
                <p class="text-slate-500 text-xs sm:text-sm">سایر پروژه‌های اجرا شده در این سطح ولتاژ و حوزه صنعت</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $related_args = array(
                    'post_type' => 'portfolio',
                    'post_status' => 'publish',
                    'posts_per_page' => 3,
                    'post__not_in' => array(get_the_ID()),
                    'ignore_sticky_posts' => true
                );
                $related_loop = new WP_Query($related_args);
                if ($related_loop->have_posts()) :
                    while ($related_loop->have_posts()) :
                        $related_loop->the_post(); ?>
                        <div class="h-full">
                            <?php get_template_part('template-parts/portfolio/portfolio-card'); ?>
                        </div>
                    <?php
                    endwhile;
                endif;
                wp_reset_postdata();
                ?>
            </div>
        </section>
    </div>
</article>

<?php
endwhile;
get_footer();
?>
