<?php
/**
 * Template Name: About Us Page
 * PSB-CO High-Voltage Electrical Panel Manufacturing Theme
 * Ultra-Minimal Light Corporate UI
 */

get_header();

$hero_badge = get_field('about_hero_badge') ?: 'نزدیک به نیم قرن سابقه در صنعت تابلوسازی برق';
$hero_subtitle = get_field('about_hero_subtitle') ?: 'طراحی، تولید و مونتاژ انواع تابلوهای برق فشار ضعیف، متوسط، کشویی و پست‌های پاساژ بر اساس استانداردهای بین‌المللی';
$stats = get_field('about_stats');
$values = get_field('about_values');
$owners = get_field('owners');
$factory_gallery = get_field('about_factory_gallery');
$thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'full');
?>

<article class="bg-slate-50/70 text-slate-900 min-h-screen py-8 md:py-12 select-none">
    <div class="container mx-auto px-4 md:px-8 max-w-6xl space-y-12">
        
        <!-- 1. Hero & Breadcrumb Box -->
        <header class="bg-white p-6 md:p-10 rounded-2xl border border-slate-200/80 shadow-sm space-y-4 text-center max-w-4xl mx-auto" data-aos="fade-up">
            <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                <?= esc_html($hero_badge); ?>
            </span>
            <h1 class="text-3xl sm:text-5xl font-black text-slate-900 leading-tight tracking-tight">
                <?php the_title(); ?>
            </h1>
            <p class="text-slate-600 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">
                <?= esc_html($hero_subtitle); ?>
            </p>
        </header>

        <!-- 2. Main Featured Image & Key Stats Grid -->
        <section class="space-y-8" data-aos="fade-up">
            <?php if ($thumbnail) : ?>
                <div class="relative rounded-2xl overflow-hidden border border-slate-200 bg-white p-2 shadow-sm">
                    <div class="overflow-hidden w-full rounded-xl bg-slate-100 aspect-[21/9] max-h-[460px]">
                        <img src="<?= esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover" fetchpriority="high" />
                    </div>
                </div>
            <?php endif; ?>

            <!-- Key Stats Counter Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php if ($stats) : 
                    foreach ($stats as $st) : ?>
                        <div class="p-5 rounded-2xl bg-white border border-slate-200/80 text-center shadow-sm space-y-1">
                            <strong class="block text-2xl sm:text-4xl font-black text-blue-700 font-mono"><?= esc_html($st['number']); ?></strong>
                            <span class="text-xs sm:text-sm text-slate-600 font-medium"><?= esc_html($st['label']); ?></span>
                        </div>
                    <?php endforeach;
                else : ?>
                    <!-- Default Electrical Panel Industry Stats -->
                    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 text-center shadow-sm space-y-1">
                        <strong class="block text-2xl sm:text-4xl font-black text-blue-700 font-mono">50+</strong>
                        <span class="text-xs sm:text-sm text-slate-600 font-medium">سال سابقه تخصصی</span>
                    </div>
                    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 text-center shadow-sm space-y-1">
                        <strong class="block text-2xl sm:text-4xl font-black text-blue-700 font-mono">500+</strong>
                        <span class="text-xs sm:text-sm text-slate-600 font-medium">پروژه کلید در دست</span>
                    </div>
                    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 text-center shadow-sm space-y-1">
                        <strong class="block text-2xl sm:text-4xl font-black text-blue-700 font-mono">500m²</strong>
                        <span class="text-xs sm:text-sm text-slate-600 font-medium">مساحت خطوط تولید</span>
                    </div>
                    <div class="p-5 rounded-2xl bg-white border border-slate-200/80 text-center shadow-sm space-y-1">
                        <strong class="block text-2xl sm:text-4xl font-black text-emerald-600 font-mono">100%</strong>
                        <span class="text-xs sm:text-sm text-slate-600 font-medium">رعایت استانداردهای IEC</span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 3. Company History & Story Content -->
        <section class="bg-white p-6 md:p-10 rounded-2xl border border-slate-200/80 shadow-sm space-y-6" data-aos="fade-up">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center font-bold text-lg">
                    📜
                </div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">تاریخچه و معرفی شرکت پارس سویچ برد</h2>
                    <p class="text-xs text-slate-500">داستان رشد و افتخارات نیم قرن حضور در صنعت برق ایران</p>
                </div>
            </div>

            <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed text-justify space-y-4">
                <?php the_content(); ?>
            </div>
        </section>

        <!-- 4. Reusable Confirmations & Vendor Approvals Component (Grid Mode) -->
        <?php get_template_part('template-parts/confirmation/recently-confirmations', null, array('layout' => 'grid', 'postCount' => 4)); ?>

        <!-- 5. Reusable Certificates & ISO Quality Standards Component (Grid Mode) -->
        <?php get_template_part('template-parts/certificates/recently-certificates', null, array('layout' => 'grid', 'postCount' => 4)); ?>

        <!-- 6. Reusable Type-Tests & Laboratory Standards Component (Grid Mode) -->
        <?php get_template_part('template-parts/type-test/recently-type-tests', null, array('layout' => 'grid', 'postCount' => 4)); ?>

        <!-- 6. Mission, Vision & Core Values Section -->
        <section class="space-y-6" data-aos="fade-up">
            <div class="text-center space-y-1">
                <h2 class="text-2xl font-black text-slate-900">بیانیه ارزش‌ها و سرلوحه کاری ما</h2>
                <p class="text-slate-500 text-sm">ارزش‌های بنیادی که کیفیت محصولات پارس سویچ برد را تضمین می‌کند</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php if ($values) : 
                    foreach ($values as $val) : ?>
                        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-xl">
                                <?= esc_html($val['icon'] ?: '⚡'); ?>
                            </div>
                            <h3 class="text-base font-bold text-slate-900"><?= esc_html($val['title']); ?></h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed"><?= esc_html($val['description']); ?></p>
                        </div>
                    <?php endforeach;
                else : ?>
                    <!-- Default Corporate Values -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-xl">
                            🎯
                        </div>
                        <h3 class="text-base font-bold text-slate-900">ماموریت سازمانی (Mission)</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">تولید تابلوهای برق با بالاترین ایمنی و استانداردهای جهانی جهت پایداری شبکه برق صنایع کشور.</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-xl">
                            👁️
                        </div>
                        <h3 class="text-base font-bold text-slate-900">چشم‌انداز آینده (Vision)</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">پیشگامی در طراحی تابلوهای برق کشویی هوشمند و صادرات تجهیزات برق به کشورهای منطقه.</p>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center justify-center text-xl">
                            🛡️
                        </div>
                        <h3 class="text-base font-bold text-slate-900">کیفیت و استانداردهای فنی</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">التزام ۱۰۰٪ به ضوابط استانداردهای بین‌المللی IEC 61439، IEC 62271 و سیستم مدیریت کیفیت ISO 9001.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 7. Executive Team & Leadership Section -->
        <?php if ($owners) : ?>
            <section class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-6" data-aos="fade-up">
                <div class="text-center space-y-1">
                    <h2 class="text-2xl font-black text-slate-900">تیم مدیریتی و ارشد مهندسی</h2>
                    <p class="text-slate-500 text-sm">اعضای هیئت مدیره و مدیران متخصص تابلوسازی پارس سویچ برد</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php foreach ($owners as $owner) : ?>
                        <div class="flex items-center gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200/80 shadow-sm hover:border-blue-300 transition-colors">
                            <?php if (!empty($owner['image']['url'])) : ?>
                                <img class="w-16 h-16 rounded-xl object-cover border border-slate-200 shrink-0" src="<?= esc_url($owner['image']['url']); ?>" alt="<?= esc_attr($owner['name'] ?? ''); ?>" loading="lazy" />
                            <?php else : ?>
                                <div class="w-16 h-16 rounded-xl bg-blue-50 text-blue-700 font-bold flex items-center justify-center border border-blue-200 shrink-0 text-xl">
                                    👤
                                </div>
                            <?php endif; ?>
                            <div class="space-y-0.5">
                                <h3 class="font-bold text-slate-900 text-sm sm:text-base"><?= esc_html($owner['name'] ?? ''); ?></h3>
                                <span class="text-xs text-blue-700 font-medium block"><?= esc_html($owner['position'] ?? ''); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- 8. Factory Facilities & Production Line Gallery -->
        <?php if ($factory_gallery) : ?>
            <section class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm space-y-4" data-aos="fade-up">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span class="text-blue-700">🏭</span>
                        تصاویر خطوط تولید و کارخانه تابلوسازی
                    </h3>
                    <span class="text-xs text-slate-500 font-mono"><?= count($factory_gallery); ?> تصویر</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <?php foreach ($factory_gallery as $img) : ?>
                        <a href="<?= esc_url($img['url']); ?>" target="_blank" class="group relative rounded-xl overflow-hidden border border-slate-200 aspect-video bg-slate-100 block">
                            <img src="<?= esc_url($img['sizes']['medium'] ?? $img['url']); ?>" alt="<?= esc_attr($img['title'] ?? 'کارخانه پارس سویچ برد'); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <div class="absolute inset-0 bg-blue-700/0 group-hover:bg-blue-700/10 transition-colors"></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    </div>
</article>

<?php get_footer(); ?>