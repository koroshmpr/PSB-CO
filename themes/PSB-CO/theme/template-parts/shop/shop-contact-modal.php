<?php
/**
 * Ultra-Modern Contact & Quick Inquiry Modal Component
 * PSB-CO High-Voltage Electrical Panel Manufacturing Theme
 */

$phones = get_field('phones', 'option') ?: get_field('phone', 'option');
$emails = get_field('emails', 'option') ?: get_field('email', 'option');
$brand_name = get_field('brand_name', 'option') ?: 'پارس سویچ برد';
?>

<div id="contactModal"
     x-show="shopContact"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     @keydown.escape.window="shopContact = false"
     @click.self="shopContact = false"
     class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6 bg-slate-950/80 backdrop-blur-md select-none"
     style="display: none;">

    <div class="bg-white rounded-md max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
        
        <!-- 1. Header with Dark Navy Background -->
        <div class="p-5 sm:p-6 bg-slate-950 text-white flex items-center justify-between border-b border-slate-800 shrink-0">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-blue-600/30 text-blue-400 border border-blue-500/30 flex items-center justify-center text-xl shadow-inner shrink-0">
                    📞
                </div>
                <div class="space-y-0.5">
                    <h3 class="font-black text-base sm:text-xl text-white tracking-tight">راه‌های ارتباط با واحد فروش و مهندسی</h3>
                    <p class="text-xs text-slate-400">تماس مستقیم جهت مشاوره فنی، استعلام قیمت و ثبت پروژه</p>
                </div>
            </div>

            <button @click="shopContact = false" aria-label="بستن پنجره" class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 hover:bg-red-600 hover:border-red-500 text-slate-400 hover:text-white flex items-center justify-center transition-all cursor-pointer shadow-md shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- 2. Scrollable Body Content -->
        <div class="p-5 sm:p-6 space-y-6 overflow-y-auto flex-1 text-slate-900 bg-slate-50/50">
            
            <!-- Section 1: Phone Numbers Grid -->
            <?php if (!empty($phones) && is_array($phones)) : ?>
                <div class="space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 animate-pulse"></span>
                            شماره‌های مستقیم تماس با بخش‌های مختلف
                        </h4>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            <?= count($phones); ?> شماره فعال
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php foreach ($phones as $p) :
                            $num_obj = $p['number'] ?? ($p['phone'] ?? null);
                            $title = $p['title'] ?? '';

                            if (is_array($num_obj)) {
                                $title = $title ?: ($num_obj['title'] ?? '');
                                $num_obj = $num_obj['url'] ?? '';
                            }

                            $raw_num = is_string($num_obj) ? $num_obj : '';
                            $clean_num = preg_replace('#^https?://#i', '', $raw_num);
                            $clean_num = str_replace('tel:', '', $clean_num);

                            if (!$clean_num) continue;
                            $phone_href = 'tel:' . preg_replace('/[^0-9+]/', '', $clean_num);
                        ?>
                            <a href="<?= esc_url($phone_href); ?>" class="group p-3.5 rounded-2xl bg-white border border-slate-200/90 hover:border-blue-500 hover:shadow-md transition-all flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    </div>
                                    <div class="space-y-0.5">
                                        <span class="block text-xs font-bold text-slate-800 group-hover:text-blue-700 transition-colors line-clamp-1"><?= esc_html($title ?: 'تلفن تماس'); ?></span>
                                        <span class="block text-[10px] text-slate-400 font-mono">لمس برای تماس</span>
                                    </div>
                                </div>
                                <span class="px-3 py-1.5 rounded-xl bg-slate-100 group-hover:bg-blue-600 text-slate-800 group-hover:text-white font-mono text-xs font-bold ltr transition-all shrink-0">
                                    <?= esc_html($clean_num); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Section 2: Email Section -->
            <?php if (!empty($emails) && is_array($emails)) : ?>
                <div class="space-y-3 pt-1">
                    <div class="border-b border-slate-200 pb-2.5">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            پست الکترونیک و مکاتبات رسمی
                        </h4>
                    </div>

                    <div class="space-y-2">
                        <?php foreach ($emails as $e) :
                            $e_val = $e['email'] ?? '';
                            if (!$e_val) continue;
                        ?>
                            <a href="mailto:<?= esc_attr($e_val); ?>" class="flex items-center justify-between p-3.5 rounded-2xl bg-white border border-slate-200/90 hover:border-emerald-500 hover:shadow-md transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center flex-shrink-0">
                                        <?php get_template_part('template-parts/svg/envelope', null, ['size' => 16, 'class' => 'fill-current']); ?>
                                    </div>
                                    <span class="text-xs font-bold text-slate-800">ایمیل واحد مربوطه:</span>
                                </div>
                                <span class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-600 text-slate-800 hover:text-white font-mono text-xs font-bold ltr transition-colors">
                                    <?= esc_html($e_val); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Section 3: Social Media Section -->
            <div class="space-y-3 pt-1">
                <div class="border-b border-slate-200 pb-2.5">
                    <h4 class="text-xs sm:text-sm font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        شبکه‌های اجتماعی و پیام‌رسان‌های رسمی
                    </h4>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <span class="text-xs font-bold text-slate-700">صفحات رسمی <?= esc_html($brand_name); ?></span>
                    <nav aria-label="راههای ارتباطی" class="flex gap-2 items-center">
                        <?php get_template_part('template-parts/global/social-links', null, ['class' => 'border-slate-200 text-slate-700 hover:text-white hover:bg-blue-600 shadow-sm']); ?>
                    </nav>
                </div>
            </div>

        </div>

        <!-- 3. Modal Footer Actions Bar -->
        <div class="p-4 sm:p-5 bg-white border-t border-slate-200 flex items-center justify-between shrink-0">
            <a href="<?= esc_url(get_permalink(get_page_by_path('contact-us')) ?: site_url('/contact-us')); ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-700 hover:text-white transition-all border border-blue-200">
                <span>ورود به برگه کامل تماس با ما</span>
                <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <button @click="shopContact = false" class="px-5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all cursor-pointer">
                بستن
            </button>
        </div>

    </div>
</div>