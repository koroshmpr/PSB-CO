<?php
/**
 * Corporate High-Voltage Hero Section - Full Screen & Modern Dark Theme
 * PSB-CO Theme - Professional Electrical Panel Manufacturing
 */

$has_repeater = have_rows('hero-slider');
$hero_single = get_field('hero');
?>

<section class="relative w-full h-[85vh] lg:h-[calc(100vh-80px)] min-h-[580px] bg-slate-950 text-white overflow-hidden select-none">
    <?php if ($has_repeater) : ?>
        <!-- Swiper Hero Slider -->
        <div class="hero_swiper swiper w-full h-full" data-perpage="1" data-laptop="1" data-tablet="1" data-mobile="1" data-autoplay="5000" data-loop="1" data-speed="900">
            <div class="swiper-wrapper">
                <?php 
                $slide_index = 0;
                while (have_rows('hero-slider')) : the_row();
                    $badge = get_sub_field('badge');
                    $title = get_sub_field('title');
                    $content = get_sub_field('content');
                    $btn_link = get_sub_field('button_link');
                    $image = get_sub_field('image');
                    $tag_name = ($slide_index === 0) ? 'h1' : 'h2';
                    $slide_index++;
                ?>
                    <div class="swiper-slide relative w-full h-full flex flex-col justify-center overflow-hidden">
                        <!-- Background Image -->
                        <?php if (!empty($image['url'])) : ?>
                            <img src="<?= esc_url($image['url']); ?>" alt="<?= esc_attr($image['title'] ?? $title); ?>" class="absolute inset-0 w-full h-full object-cover object-center transform scale-105 transition-transform duration-[8000ms] ease-out z-0" />
                        <?php endif; ?>

                        <!-- Dark Gradient Overlays for High Contrast & Legibility -->
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/85 to-slate-950/30 rtl:bg-gradient-to-l z-10"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-slate-950/40 z-10"></div>
                        
                        <!-- Tech Grid Pattern -->
                        <div class="absolute inset-0 bg-[radial-gradient(#3b82f6_1px,transparent_1px)] [background-size:32px_32px] opacity-10 z-10"></div>

                        <!-- Content Container -->
                        <div class="container mx-auto px-4 md:px-8 relative z-20">
                            <div class="max-w-3xl space-y-6">
                                <?php if ($badge) : ?>
                                    <div data-aos="fade-down" data-aos-duration="700" data-aos-delay="100">
                                        <span class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full text-xs sm:text-sm font-bold bg-blue-500/20 text-blue-300 border border-blue-400/30 backdrop-blur-md shadow-lg shadow-blue-950/40">
                                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                                            <?= esc_html($badge); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <?php if ($title) : ?>
                                    <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="200">
                                        <<?= $tag_name; ?> class="text-3xl sm:text-5xl lg:text-6xl font-black text-white leading-[1.2] tracking-tight drop-shadow-md">
                                            <?= esc_html($title); ?>
                                        </<?= $tag_name; ?>>
                                    </div>
                                <?php endif; ?>

                                <?php if ($content) : ?>
                                    <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="300">
                                        <p class="text-slate-300 text-sm sm:text-base lg:text-lg leading-relaxed max-w-2xl font-medium drop-shadow">
                                            <?= esc_html(wp_trim_words($content, 35)); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($btn_link['url'])) : ?>
                                    <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="400" class="pt-2">
                                        <a href="<?= esc_url($btn_link['url']); ?>" class="inline-flex items-center gap-3 px-8 py-3.5 rounded-xl font-bold bg-blue-600 hover:bg-blue-500 text-white shadow-xl shadow-blue-600/30 hover:shadow-blue-500/50 hover:scale-[1.02] transition-all duration-300 text-sm sm:text-base group">
                                            <span><?= esc_html($btn_link['title'] ?? 'مشاهده و اطلاعات بیشتر'); ?></span>
                                            <svg class="w-5 h-5 rtl:rotate-180 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                            </svg>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Swiper Floating Controls Bar -->
            <div class="absolute bottom-8 left-6 lg:left-12 z-30 flex items-center gap-3 bg-slate-900/80 backdrop-blur-xl border border-white/10 p-2 px-4 rounded-2xl shadow-2xl">
                <button class="swiper-button-prev !static !w-9 !h-9 !m-0 after:!content-none text-slate-300 hover:text-white hover:bg-white/10 rounded-xl transition-all flex items-center justify-center cursor-pointer">
                    <svg class="w-5 h-5 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div class="hero-pagination !static !w-auto text-blue-400 font-mono text-xs sm:text-sm px-2 font-bold translate-x-1/2 tracking-widest"></div>
                <button class="swiper-button-next !static !w-9 !h-9 !m-0 after:!content-none text-slate-300 hover:text-white hover:bg-white/10 rounded-xl transition-all flex items-center justify-center cursor-pointer">
                    <svg class="w-5 h-5 !transform-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
            </div>
        </div>

    <?php elseif ($hero_single) : ?>
        <!-- Single Hero Display Fallback -->
        <div class="relative w-full h-full flex flex-col justify-center">
            <?php if (!empty($hero_single['image']['desktop']['url'])) : ?>
                <img src="<?= esc_url($hero_single['image']['desktop']['url']); ?>" alt="<?= esc_attr($hero_single['title'] ?? 'PSB'); ?>" class="absolute inset-0 w-full h-full object-cover z-0" />
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-950/80 to-transparent rtl:bg-gradient-to-l z-10"></div>
            
            <div class="container mx-auto px-4 md:px-8 relative z-20">
                <div class="max-w-3xl space-y-6">
                    <div data-aos="fade-down" data-aos-duration="700">
                        <span class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full text-xs sm:text-sm font-bold bg-blue-500/20 text-blue-300 border border-blue-400/30 backdrop-blur-md">
                            <?= esc_html($hero_single['adjectives'] ?? 'تولید کننده تخصصی تابلوهای برق فشار قوی'); ?>
                        </span>
                    </div>
                    <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="150">
                        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white leading-tight">
                            <?= esc_html($hero_single['title'] ?? 'شرکت پارس سویچ برد'); ?>
                        </h1>
                    </div>
                    <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="250">
                        <p class="text-slate-300 text-base md:text-lg leading-relaxed max-w-2xl font-medium">
                            <?= esc_html($hero_single['subtitle'] ?? 'طراحی، ساخت و مونتاژ تابلوهای برق فشار قوی، متوسط و ضعیف با استانداردهای بین‌المللی'); ?>
                        </p>
                    </div>
                    <?php if (!empty($hero_single['button']['url'])) : ?>
                        <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="350">
                            <a href="<?= esc_url($hero_single['button']['url']); ?>" class="inline-flex items-center gap-3 px-8 py-3.5 rounded-xl font-bold bg-blue-600 hover:bg-blue-500 text-white shadow-xl shadow-blue-600/30 hover:scale-[1.02] transition-all text-base">
                                <?= esc_html($hero_single['button']['title'] ?? 'مشاهده محصولات'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else : ?>
        <!-- Corporate Fallback Banner -->
        <div class="relative w-full h-full flex flex-col justify-center items-center text-center px-4">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 z-0"></div>
            <div class="relative z-10 max-w-3xl space-y-6">
                <div data-aos="fade-down" data-aos-duration="700">
                    <span class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full text-xs sm:text-sm font-bold bg-blue-500/20 text-blue-300 border border-blue-400/30">
                        صنعت برق فشار قوی و متوسط
                    </span>
                </div>
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="150">
                    <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white leading-tight">
                        شرکت پارس سویچ برد
                    </h1>
                </div>
                <div data-aos="fade-up" data-aos-duration="800" data-aos-delay="250">
                    <p class="text-slate-300 text-base md:text-lg leading-relaxed font-medium">
                        طراحی و تولید انواع تابلوهای برق فشار قوی، تابلو پست پاساژ، کشویی و اتوماسیون صنعتی
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
