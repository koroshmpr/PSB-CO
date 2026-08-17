<?php
/**
 * Minimal Corporate About Us Homepage Section - Pure White Background
 * PSB-CO High & Medium Voltage Theme
 */

$aboutUs = get_field('about_us');
if ($aboutUs) :
    $img = $aboutUs['image'] ?? null;
    $title = $aboutUs['title'] ?? 'درباره شرکت پارس سویچ برد';
    $content = $aboutUs['content'] ?? '';
    $btn_text = $aboutUs['btn_text'] ?? 'اطلاعات بیشتر درباره ما';
    $btn_link = $aboutUs['btn_link']['url'] ?? get_permalink(get_page_by_path('about')) ?: '#';
?>
    <section class="py-16 bg-white border-b border-slate-200/80 text-slate-900 select-none relative overflow-hidden" data-aos="fade-up">
        <div class="container mx-auto px-4 md:px-8 relative z-10">
            <div class="grid lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                
                <!-- Text Content Column -->
                <div class="lg:col-span-7 space-y-5" data-aos="fade-up">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                        <span>نزدیک به نیم قرن سابقه در تابلوسازی صنعت برق</span>
                    </div>

                    <h2 class="text-2xl sm:text-4xl font-black text-slate-900 leading-snug tracking-tight">
                        <?= esc_html($title); ?>
                    </h2>

                    <div class="text-slate-600 text-sm sm:text-base leading-relaxed text-justify prose max-w-none">
                        <?= wp_kses_post($content); ?>
                    </div>

                    <!-- Key Stats Counter Pills -->
                    <div class="grid grid-cols-3 gap-3 pt-2">
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-center shadow-sm">
                            <strong class="block text-xl sm:text-3xl font-black text-blue-700 font-mono">50+</strong>
                            <span class="text-[11px] sm:text-xs text-slate-500 font-medium">سال سابقه تخصصی</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-center shadow-sm">
                            <strong class="block text-xl sm:text-3xl font-black text-blue-700 font-mono">500+</strong>
                            <span class="text-[11px] sm:text-xs text-slate-500 font-medium">پروژه کلید در دست</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-center shadow-sm">
                            <strong class="block text-xl sm:text-3xl font-black text-emerald-600 font-mono">100%</strong>
                            <span class="text-[11px] sm:text-xs text-slate-500 font-medium">رعایت IEC & ISO</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <?php if ($btn_link) : ?>
                        <div class="pt-2">
                            <a href="<?= esc_url($btn_link); ?>" class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-xl font-bold bg-blue-700 hover:bg-blue-800 text-white shadow-sm hover:shadow transition-all text-sm group">
                                <span><?= esc_html($btn_text); ?></span>
                                <svg class="w-4 h-4 rtl:rotate-180 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Featured Factory Image Frame -->
                <div class="lg:col-span-5 relative group" data-aos="fade-left">
                    <div class="relative rounded-3xl overflow-hidden border border-slate-200 bg-slate-50 p-2.5 shadow-lg">
                        <div class="overflow-hidden rounded-2xl bg-slate-100 aspect-[4/3]">
                            <?php if (!empty($img['url'])) : ?>
                                <img src="<?= esc_url($img['url']); ?>" alt="<?= esc_attr($img['title'] ?? $title); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <?php else : ?>
                                <img src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=800&auto=format&fit=crop" alt="PSB Factory" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
<?php endif; ?>