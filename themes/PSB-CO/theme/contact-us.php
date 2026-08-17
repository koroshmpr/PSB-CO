<?php
/* Template Name: Contact Us - Editorial Style */
get_header();
$form_id = get_field('gravity_form');

// ACF repeaters from site-setting
$contacts = get_field('contact', 'option');
$phones = get_field('phones', 'option') ?: get_field('phone', 'option');
$emails = get_field('emails', 'option') ?: get_field('email', 'option');

// Legacy fallbacks
$legacy_address = get_field('address', 'option');
$legacy_email = get_field('email', 'option');
$legacy_phone = get_field('phone', 'option') ?: get_field('phone_number', 'option');
$legacy_map = get_field('map', 'option') ?: get_field('map-link', 'option');
?>

<style>
/* High-End Modern Gravity Form Styling for Contact Page */
body .form-wrapper .gform_wrapper,
body .form-wrapper .gform_wrapper * {
    box-sizing: border-box !important;
}
body .form-wrapper .gform_wrapper {
    margin: 0 !important;
    max-width: 100% !important;
}
body .form-wrapper .gform_fields {
    display: grid !important;
    grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
    gap: 1.25rem !important;
    list-style: none !important;
    padding: 0 !important;
    margin: 0 !important;
}
body .form-wrapper .gfield {
    grid-column: span 12 / span 12 !important;
    margin: 0 !important;
    padding: 0 !important;
}
body .form-wrapper .gfield--width-half,
body .form-wrapper .gfield_html {
    grid-column: span 12 / span 12 !important;
}
@media (min-width: 640px) {
    body .form-wrapper .gfield--width-half {
        grid-column: span 6 / span 6 !important;
    }
}
body .form-wrapper .gfield_label,
body .form-wrapper label {
    display: block !important;
    font-size: 0.8125rem !important;
    font-weight: 700 !important;
    color: #1e293b !important;
    margin-bottom: 0.5rem !important;
}
body .form-wrapper input[type="text"],
body .form-wrapper input[type="email"],
body .form-wrapper input[type="tel"],
body .form-wrapper input[type="number"],
body .form-wrapper input[type="url"],
body .form-wrapper input[type="password"],
body .form-wrapper textarea,
body .form-wrapper select,
body .form-wrapper .gform_body input:not([type="submit"]):not([type="file"]),
body .form-wrapper .gform_body textarea,
body .form-wrapper .gform_body select,
body .form-wrapper .ginput_container input,
body .form-wrapper .ginput_container textarea,
body .form-wrapper .ginput_container select {
    width: 100% !important;
    background-color: #f8fafc !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 0.875rem !important;
    padding: 0.875rem 1.125rem !important;
    font-size: 0.875rem !important;
    line-height: 1.5 !important;
    color: #0f172a !important;
    transition: all 0.2s ease-in-out !important;
    outline: none !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
}
body .form-wrapper input[type="text"]::placeholder,
body .form-wrapper input[type="email"]::placeholder,
body .form-wrapper input[type="tel"]::placeholder,
body .form-wrapper textarea::placeholder,
body .form-wrapper .ginput_container input::placeholder,
body .form-wrapper .ginput_container textarea::placeholder {
    color: #94a3b8 !important;
    opacity: 1 !important;
}
body .form-wrapper input:focus,
body .form-wrapper textarea:focus,
body .form-wrapper select:focus,
body .form-wrapper .gform_body input:focus,
body .form-wrapper .gform_body textarea:focus,
body .form-wrapper .ginput_container input:focus,
body .form-wrapper .ginput_container textarea:focus,
body .form-wrapper .ginput_container select:focus {
    background-color: #ffffff !important;
    border-color: #1d4ed8 !important;
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.15) !important;
}
body .form-wrapper textarea,
body .form-wrapper .ginput_container textarea {
    min-height: 130px !important;
    resize: vertical !important;
}
body .form-wrapper .gform_footer,
body .form-wrapper .gform_page_footer {
    margin-top: 1.75rem !important;
    padding: 0 !important;
}
body .form-wrapper input[type="submit"],
body .form-wrapper button[type="submit"],
body .form-wrapper .gform_button {
    width: 100% !important;
    background-color: #1d4ed8 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    font-size: 0.875rem !important;
    padding: 0.875rem 2.5rem !important;
    border-radius: 0.875rem !important;
    border: none !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 4px 14px rgba(29, 78, 216, 0.25) !important;
}
body .form-wrapper input[type="submit"]:hover,
body .form-wrapper button[type="submit"]:hover,
body .form-wrapper .gform_button:hover {
    background-color: #1e40af !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 18px rgba(29, 78, 216, 0.35) !important;
}
@media (min-width: 640px) {
    body .form-wrapper input[type="submit"],
    body .form-wrapper button[type="submit"],
    body .form-wrapper .gform_button {
        width: auto !important;
        min-width: 180px !important;
    }
}
</style>

    <!-- 1. Immersive Hero Section -->
    <section class="relative w-full h-[45vh] min-h-[380px] flex items-center justify-center overflow-hidden bg-slate-900 select-none">
        <!-- Background Image -->
        <?php if (has_post_thumbnail()) : ?>
            <img src="<?php the_post_thumbnail_url('2048x2048'); ?>"
                 alt="<?php the_title_attribute(); ?>"
                 class="absolute inset-0 w-full h-full object-cover object-center opacity-40"
                 fetchpriority="high"
                 decoding="async">
        <?php else : ?>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 opacity-90"></div>
        <?php endif; ?>

        <!-- Soft Fade Overlay -->
        <div class="absolute inset-0 bg-gradient-to-b from-slate-950/60 via-slate-900/40 to-slate-50"></div>

        <!-- Header Text -->
        <div class="relative z-10 text-center px-4 mt-[-6vh]" data-aos="fade-up">
            <span class="inline-block px-3.5 py-1 rounded-full bg-blue-600/30 text-blue-300 border border-blue-500/30 text-xs font-bold mb-3">
                ارتباط مستقیم با واحد فروش و مهندسی
            </span>
            <h1 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-3 drop-shadow-md">
                <?php the_title(); ?>
            </h1>
            <p class="text-sm md:text-base text-slate-300 font-medium max-w-xl mx-auto leading-relaxed">
                پاسخگوی تمامی استعلام‌ها، مشاوره تخصصی تابلوسازی و درخواست‌های پروژه شما هستیم.
            </p>
        </div>
    </section>

    <!-- 2. Floating Contact Card -->
    <section class="relative z-20 container mx-auto px-4 md:px-8 max-w-6xl -mt-24 mb-16 select-none" data-aos="fade-up">
        <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-slate-200">
            <div class="grid lg:grid-cols-12">

                <!-- Right Side (Form Area - RTL) -->
                <div class="lg:col-span-7 p-6 sm:p-8 md:p-10 lg:p-12 order-2 lg:order-1 bg-white">
                    <div class="mb-8 border-b border-slate-100 pb-5">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 mb-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                            <span>فرم مشاوره & استعلام قیمت</span>
                        </div>
                        <h2 class="text-xl lg:text-2xl font-black text-slate-900 mb-1.5">ارسال پیام و مشخصات پروژه</h2>
                        <p class="text-xs md:text-sm text-slate-600 leading-relaxed">فرم زیر را تکمیل کنید تا کارشناسان ما در سریع‌ترین زمان ممکن با شما تماس بگیرند.</p>
                    </div>

                    <div class="form-wrapper">
                        <?php
                        if ($form_id) {
                            echo do_shortcode('[gravityform id="' . esc_attr($form_id) . '" title="false" ajax="true" description="false"]');
                        } else {
                            echo '<div class="p-6 bg-slate-50 rounded-2xl text-center text-slate-500 text-sm border border-slate-200">لطفاً فرم تماس را در تنظیمات برگه انتخاب نمایید.</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Left Side (Contact Info Area - RTL) -->
                <div class="lg:col-span-5 bg-slate-950 text-white p-6 sm:p-8 md:p-10 order-1 lg:order-2 flex flex-col justify-between relative overflow-hidden space-y-6 border-b lg:border-b-0 lg:border-r border-slate-800">

                    <div class="relative z-10 space-y-6">
                        <!-- Header -->
                        <h3 class="text-lg font-bold text-white border-b border-slate-800 pb-3 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block animate-pulse"></span>
                            اطلاعات تماس و دفتر مرکزی
                        </h3>

                        <!-- Addresses Repeater -->
                        <?php if (!empty($contacts) && is_array($contacts)) : ?>
                            <div class="space-y-3">
                                <?php foreach ($contacts as $c) :
                                    $c_title = $c['title'] ?? '';
                                    $c_addr = $c['address'] ?? '';
                                    $c_map_link = $c['map-link'] ?? '';
                                ?>
                                    <div class="space-y-2 p-4 rounded-2xl bg-slate-900 border border-slate-800/80 shadow-sm">
                                        <?php if ($c_title) : ?>
                                            <h4 class="text-xs sm:text-sm font-bold text-blue-400 flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                                <?= esc_html($c_title); ?>
                                            </h4>
                                        <?php endif; ?>

                                        <?php if ($c_addr) : ?>
                                            <div class="flex items-start gap-2.5">
                                                <div class="p-1.5 bg-slate-800 rounded-lg text-blue-400 flex-shrink-0 mt-0.5 border border-slate-700">
                                                    <?php get_template_part('template-parts/svg/map', null, ['size' => 16, 'class' => 'fill-current']); ?>
                                                </div>
                                                <div class="space-y-1 flex-1">
                                                    <address class="not-italic text-slate-300 text-xs leading-relaxed font-sans"><?= esc_html($c_addr); ?></address>
                                                    <?php if ($c_map_link) : ?>
                                                        <a href="<?= esc_url($c_map_link); ?>" target="_blank" class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300 text-[11px] font-semibold mt-0.5 transition-colors">
                                                            <span>مسیریابی در گوگل مپ</span>
                                                            <svg class="w-3 h-3 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($legacy_address) : ?>
                            <div class="flex items-start gap-3 p-4 rounded-2xl bg-slate-900 border border-slate-800">
                                <div class="p-2 bg-slate-800 rounded-lg text-blue-400 flex-shrink-0 border border-slate-700">
                                    <?php get_template_part('template-parts/svg/map', null, ['size' => 18, 'class' => 'fill-current']); ?>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 mb-1">آدرس</h4>
                                    <address class="not-italic text-slate-300 text-xs leading-relaxed font-sans"><?= esc_html($legacy_address); ?></address>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Phone Repeater Section (Clean 2-Column Grid for Multiple Numbers) -->
                        <?php if (!empty($phones) && is_array($phones)) : ?>
                            <div class="space-y-3 p-4 rounded-2xl bg-slate-900 border border-slate-800">
                                <h4 class="text-xs font-bold text-slate-300 flex items-center justify-between border-b border-slate-800/80 pb-2">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>                                        شماره‌های تماس و پشتیبانی
                                    </span>
                                    <span class="text-[10px] text-blue-400 font-mono"><?= count($phones); ?> شماره</span>
                                </h4>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
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
                                         <a href="<?= esc_url($phone_href); ?>" class="group p-2.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-blue-500/50 transition-all flex flex-col justify-between space-y-1">
                                             <span class="text-[11px] text-slate-400 font-medium group-hover:text-slate-200 transition-colors line-clamp-1"><?= esc_html($title ?: 'تلفن تماس'); ?></span>
                                             <span class="text-blue-400 group-hover:text-blue-300 font-mono text-xs font-bold ltr text-left tracking-wider">
                                                 <?= esc_html($clean_num); ?>
                                             </span>
                                         </a>
                                     <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Email Repeater Section -->
                        <?php if (!empty($emails) && is_array($emails)) : ?>
                            <div class="flex items-start gap-3 p-4 rounded-2xl bg-slate-900 border border-slate-800">
                                <div class="p-2 bg-slate-800 rounded-xl text-blue-400 flex-shrink-0 mt-0.5 border border-slate-700">
                                    <?php get_template_part('template-parts/svg/envelope', null, ['size' => 18, 'class' => 'fill-current']); ?>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-xs text-slate-400 block font-medium">ایمیل‌های ارتباطی:</span>
                                    <div class="space-y-1">
                                        <?php foreach ($emails as $e) :
                                            $e_val = $e['email'] ?? '';
                                            if ($e_val) :
                                        ?>
                                            <a href="mailto:<?= esc_attr($e_val); ?>" class="text-slate-200 text-xs hover:text-blue-400 transition-colors block font-mono"><?= esc_html($e_val); ?></a>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Social Links Footer -->
                    <div class="relative z-10 mt-6 pt-4 border-t border-slate-800">
                        <p class="text-xs font-bold text-slate-400 mb-2.5">شبکه‌های اجتماعی
                            <?= get_field('brand_name' , 'option') ?? 'پارس سوییچ بورد' ?>
                        </p>
                        <nav aria-label="social network links" class="flex gap-2">
                            <?php get_template_part('template-parts/global/social-links', null , ['class' => 'border-slate-700 text-slate-300 hover:text-white hover:bg-blue-600']); ?>
                        </nav>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 3. Interactive Maps Section (2-Column Grid with Header Box) -->
    <?php
    $valid_maps = array();

    if (!empty($contacts) && is_array($contacts)) {
        foreach ($contacts as $c) {
            $c_title = $c['title'] ?? 'موقعیت جغرافیایی';
            $c_addr = $c['address'] ?? '';
            $c_map = $c['map'] ?? '';
            $c_link = $c['map-link'] ?? '';

            if (!empty($c_map) || !empty($c_addr)) {
                $valid_maps[] = array(
                    'title' => $c_title,
                    'address' => $c_addr,
                    'map' => $c_map,
                    'link' => $c_link,
                );
            }
        }
    }

    // Fallback to legacy map if repeater is empty
    if (empty($valid_maps) && (!empty($legacy_map) || !empty($legacy_address))) {
        $valid_maps[] = array(
            'title' => 'دفتر مرکزی پارس سویچ برد',
            'address' => $legacy_address ?: '',
            'map' => $legacy_map ?: '',
            'link' => '',
        );
    }
    ?>

    <?php if (!empty($valid_maps)) : ?>
        <section class="container mx-auto px-4 md:px-8 pb-16 pt-4 select-none" data-aos="fade-up">
            <div class="text-center max-w-2xl mx-auto space-y-2 mb-8">
                <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                    نقشه و موقعیت جغرافیایی
                </span>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                    موقعیت کارخانه و دفاتر شرکت پارس سویچ برد
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                <?php foreach ($valid_maps as $m) : ?>
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between" data-aos="fade-up">
                        <!-- Box Header with Name & Address -->
                        <div class="p-5 sm:p-6 bg-slate-50/80 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="space-y-1">
                                <h3 class="font-bold text-slate-900 text-base sm:text-lg flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-600 shrink-0"></span>
                                    <?= esc_html($m['title']); ?>
                                </h3>
                                <?php if ($m['address']) : ?>
                                    <p class="text-xs text-slate-600 leading-relaxed max-w-md">
                                        <?= esc_html($m['address']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php if ($m['link']) : ?>
                                <a href="<?= esc_url($m['link']); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-white hover:bg-blue-700 text-blue-700 hover:text-white transition-all border border-blue-200 shadow-sm shrink-0 self-start sm:self-auto">
                                    <span>مسیریابی گوگل‌مپ</span>
                                    <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Map Iframe Frame -->
                        <?php if ($m['map']) : ?>
                            <div class="w-full h-80 min-h-[320px] relative overflow-hidden bg-slate-100 [&_iframe]:w-full [&_iframe]:h-full [&_iframe]:border-0">
                                <?= $m['map']; ?>
                            </div>
                        <?php else : ?>
                            <div class="w-full h-48 bg-slate-100 flex items-center justify-center text-xs text-slate-400">
                                نقشه‌ای ثبت نشده است.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

<?php
get_footer();
?>