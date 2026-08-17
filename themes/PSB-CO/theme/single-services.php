<?php
/**
 * Minimal Corporate Electrical Service Detail Page Template
 * PSB-CO High & Medium Voltage Engineering Theme
 */

get_header();

while (have_posts()) :
    the_post();
    
    // Fetch custom fields
    $badge = get_field('services_badge') ?: 'خدمات مهندسی & تولید';
    $icon = get_field('service_icon') ?: '⚡';
    $description = get_field('services_description');
    $standards = get_field('standards_compliance');
    $features_text = get_field('technical_features');
    $features = $features_text ? array_filter(array_map('trim', explode("\n", $features_text))) : [];
    $brochure_pdf = get_field('brochure_pdf');
    $service_gallery = get_field('service_gallery');
    $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'full');
?>

<article class="bg-slate-50/70 text-slate-900 min-h-screen py-8 md:py-12 select-none">
    <div class="container mx-auto px-4 md:px-8 max-w-6xl space-y-8">
        
        <!-- Header & Breadcrumb Box -->
        <header class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    <span class="w-2 h-2 rounded-full bg-blue-600 inline-block me-1.5 animate-pulse"></span>
                    <?= esc_html($badge); ?>
                </span>

                <?php if ($standards) : ?>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 font-mono">
                        📜 <?= esc_html($standards); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-1">
                <?php if ($icon) : ?>
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center text-2xl shrink-0">
                        <?= esc_html($icon); ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h1 class="text-2xl sm:text-4xl font-black text-slate-900 leading-tight tracking-tight">
                        <?= get_the_title(); ?>
                    </h1>
                    <?php if ($description) : ?>
                        <p class="text-slate-600 text-sm sm:text-base leading-relaxed mt-1">
                            <?= esc_html($description); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Main Content & Sidebar Grid -->
        <div class="grid lg:grid-cols-12 gap-8 items-start">
            
            <!-- Main Column -->
            <main class="lg:col-span-8 space-y-8 order-2 lg:order-1">
                
                <!-- Main Featured Image Frame -->
                <?php if ($thumbnail) : ?>
                    <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-white p-2 shadow-sm">
                        <div class="overflow-hidden rounded-xl bg-slate-100">
                            <img src="<?= esc_url($thumbnail); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="w-full h-auto max-h-[480px] object-cover" />
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Technical Capabilities & Equipment Table -->
                <?php if (!empty($features) || $standards) : ?>
                    <section class="p-6 md:p-8 rounded-2xl bg-white border border-slate-200/80 space-y-6 shadow-sm" data-aos="fade-up">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center font-bold text-lg">
                                ⚙️
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">مشخصات، دستگاه‌ها و قابلیت‌های فنی خط تولید</h2>
                                <p class="text-xs text-slate-500">تجهیزات مرجع کارگاهی و ظرفیت تولید تخصصی</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <?php foreach ($features as $feature) : ?>
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-600 shrink-0"></span>
                                    <span class="text-slate-800 font-semibold text-xs sm:text-sm"><?= esc_html($feature); ?></span>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($standards) : ?>
                                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between sm:col-span-2">
                                    <span class="text-slate-500 text-xs sm:text-sm">استاندارد و گواهی آزمون:</span>
                                    <strong class="text-blue-700 font-mono font-bold text-xs sm:text-sm"><?= esc_html($standards); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- PDF Brochure Download Box -->
                <?php if (!empty($brochure_pdf['url'])) : ?>
                    <div class="p-6 rounded-2xl bg-blue-50/70 border border-blue-200/80 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm" data-aos="fade-up">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl shrink-0 shadow-sm">
                                📄
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">کاتالوگ فنی / فایل مشخصات خط تولید PDF</h3>
                                <p class="text-xs text-slate-600 mt-0.5"><?= esc_html($brochure_pdf['filename'] ?? 'فایل مشخصات فنی'); ?></p>
                            </div>
                        </div>

                        <a href="<?= esc_url($brochure_pdf['url']); ?>" target="_blank" download class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold bg-blue-700 hover:bg-blue-800 text-white text-sm shadow-sm transition-all shrink-0">
                            <span>دانلود کاتالوگ</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Article Body / Description -->
                <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <h2 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">شرح کامل خط تولید و خدمات مهندسی</h2>
                    <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed text-justify">
                        <?php the_content(); ?>
                    </div>
                </div>

                <!-- Workshop Gallery -->
                <?php if ($service_gallery) : ?>
                    <section class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4" data-aos="fade-up">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                            <span class="text-blue-600">🖼️</span>
                            گالری تصاویر ماشین‌آلات و کارگاه مربوطه
                        </h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5">
                            <?php foreach ($service_gallery as $img) : ?>
                                <a href="<?= esc_url($img['url']); ?>" target="_blank" class="group relative rounded-xl overflow-hidden border border-slate-200 aspect-video bg-slate-100 block">
                                    <img src="<?= esc_url($img['sizes']['medium'] ?? $img['url']); ?>" alt="<?= esc_attr($img['title'] ?? get_the_title()); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                    <div class="absolute inset-0 bg-blue-700/0 group-hover:bg-blue-700/10 transition-colors"></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </main>

            <!-- Sidebar Column -->
            <aside class="lg:col-span-4 space-y-6 order-1 lg:order-2 lg:sticky lg:top-24">
                <!-- Technical Consultation / Inquiry Box -->
                <div class="p-6 rounded-2xl bg-white border border-blue-200 space-y-4 shadow-sm text-center">
                    <div class="w-12 h-12 mx-auto rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-xl">
                        📞
                    </div>
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-slate-900">درخواست خدمات و مشاوره فنی</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">جهت استعلام خدمات کارگاهی، تست‌های روتین آزمایشگاهی یا ساخت تابلو با کارشناسان ما تماس بگیرید.</p>
                    </div>
                    <button type="button" @click="shopContact = true" class="block w-full py-3.5 px-4 rounded-xl font-bold bg-blue-700 hover:bg-blue-800 text-white text-sm shadow-md shadow-blue-700/20 transition-all cursor-pointer">
                        ارتباط با واحد مهندسی و فروش
                    </button>
                </div>

                <!-- Quick Services Navigation Box -->
                <div class="p-6 rounded-2xl bg-white border border-slate-200/80 space-y-4 shadow-sm">
                    <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <span class="text-blue-700">🛠️</span>
                        سایر خدمات و امکانات
                    </h3>
                    <ul class="space-y-2 text-xs sm:text-sm">
                        <?php
                        $other_services = new WP_Query(array(
                            'post_type' => 'services',
                            'posts_per_page' => 6,
                            'post__not_in' => array(get_the_ID()),
                            'post_status' => 'publish'
                        ));
                        if ($other_services->have_posts()) :
                            while ($other_services->have_posts()) :
                                $other_services->the_post(); ?>
                                <li>
                                    <a href="<?php the_permalink(); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 hover:bg-blue-50 hover:text-blue-700 text-slate-700 transition-colors font-medium">
                                        <span class="line-clamp-1"><?= get_the_title(); ?></span>
                                        <svg class="w-4 h-4 rtl:rotate-180 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </li>
                            <?php
                            endwhile;
                        endif;
                        wp_reset_postdata();
                        ?>
                    </ul>
                </div>
            </aside>

        </div>

        <!-- Related Switchboard Portfolios Section -->
        <section class="mt-12 pt-10 border-t border-slate-200 space-y-6">
            <div class="text-center space-y-1">
                <h3 class="text-xl sm:text-2xl font-black text-slate-900">تابلوهای تولید شده با این امکانات</h3>
                <p class="text-slate-500 text-xs sm:text-sm">نمونه کارهایی که از این خط تولید و خدمات مهندسی استفاده کرده‌اند</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                $related_portfolios = new WP_Query(array(
                    'post_type' => 'portfolio',
                    'post_status' => 'publish',
                    'posts_per_page' => 3,
                    'ignore_sticky_posts' => true
                ));
                if ($related_portfolios->have_posts()) :
                    while ($related_portfolios->have_posts()) :
                        $related_portfolios->the_post(); ?>
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
