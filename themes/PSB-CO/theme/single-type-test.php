<?php
/**
 * Single Template: Electrical Type-Test Reports & Standards
 * CPT: type-test
 * PSB-CO High-Voltage Electrical Panel Manufacturing Theme
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

$issuing_body = get_field('issuing_body') ?: 'آزمایشگاه آکردیته IEC';
$standard_number = get_field('standard_number');
$tested_equipment = get_field('tested_equipment');
$test_parameters = get_field('test_parameters');
$validity_date = get_field('validity_date');
$type_test_file = get_field('type_test_file') ?: get_field('certificate_file');

// Extract File URL & title
$file_url = '';
$file_title = get_the_title();
$file_ext = '';

if (!empty($type_test_file)) {
    if (is_array($type_test_file)) {
        $file_url = $type_test_file['url'] ?? '';
        $file_title = $type_test_file['title'] ?? get_the_title();
    } elseif (is_string($type_test_file)) {
        $file_url = $type_test_file;
    }
}

if (empty($file_url)) {
    $file_url = get_the_post_thumbnail_url(get_the_ID(), 'full') ?: '';
}

if ($file_url) {
    $file_ext = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
}

$archive_link = get_post_type_archive_link('type-test') ?: site_url('/type-test');
?>

<article class="bg-slate-50/70 text-slate-900 min-h-screen py-8 md:py-12 select-none">
    <div class="container mx-auto px-4 md:px-8 max-w-5xl space-y-8">
        
        <!-- Top Navigation Bar & Back Button -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm" data-aos="fade-up">
            <a href="<?= esc_url($archive_link); ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-indigo-700 transition-colors shrink-0">
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>بازگشت به فهرست تایپ‌تست‌ها</span>
            </a>

            <div class="flex flex-wrap items-center gap-2">
                <span class="px-3.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200">
                    🔬 <?= esc_html($issuing_body); ?>
                </span>
                <?php if ($standard_number) : ?>
                    <span class="px-3.5 py-1 rounded-full text-xs font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        📜 <?= esc_html($standard_number); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Document Details Box -->
        <section class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-6" data-aos="fade-up">
            <div class="space-y-2 border-b border-slate-100 pb-5">
                <span class="inline-block px-3 py-1 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800">
                    گزارش رسمى تایپ‌تست تجهیزات تابلوسازی برق
                </span>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 leading-tight">
                    <?php the_title(); ?>
                </h1>
            </div>

            <!-- Technical Key Specifications Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-xs">
                <?php if ($tested_equipment) : ?>
                    <div class="space-y-1">
                        <span class="text-slate-500 block font-medium">تجهیز تست شده:</span>
                        <strong class="text-slate-900 font-bold block text-sm"><?= esc_html($tested_equipment); ?></strong>
                    </div>
                <?php endif; ?>

                <?php if ($test_parameters) : ?>
                    <div class="space-y-1">
                        <span class="text-slate-500 block font-medium">پارامترهای فنی آزمون:</span>
                        <strong class="text-indigo-700 font-bold block text-sm"><?= esc_html($test_parameters); ?></strong>
                    </div>
                <?php endif; ?>

                <?php if ($validity_date) : ?>
                    <div class="space-y-1">
                        <span class="text-slate-500 block font-medium">تاریخ / سال آزمون:</span>
                        <strong class="text-slate-800 font-mono font-bold block text-sm"><?= esc_html($validity_date); ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Content Body if available -->
            <?php if (get_the_content()) : ?>
                <div class="prose prose-slate max-w-none text-xs sm:text-sm leading-relaxed text-slate-700 pt-2">
                    <?php the_content(); ?>
                </div>
            <?php endif; ?>

            <!-- PDF / File Download Action Bar -->
            <?php if ($file_url) : ?>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-indigo-50/50 border border-indigo-200/80">
                    <div class="space-y-0.5">
                        <span class="text-xs font-bold text-slate-800 block">فایل اصلی گزارش تایپ‌تست معتبر</span>
                        <span class="text-[11px] text-slate-500 block font-mono"><?= esc_html($file_title); ?></span>
                    </div>

                    <a href="<?= esc_url($file_url); ?>" target="_blank" download class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white font-bold text-xs shadow-md shadow-indigo-700/20 transition-all shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>دانلود گزارش تایپ‌تست PDF</span>
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <!-- PDF / Document Embedded Viewer Frame -->
        <?php if ($file_url) : ?>
            <section class="bg-white p-4 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4" data-aos="fade-up">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>🔬</span>
                        <span>پیش‌نمایش سند تایپ‌تست</span>
                    </h3>
                    <a href="<?= esc_url($file_url); ?>" target="_blank" class="text-xs font-bold text-indigo-700 hover:underline">
                        باز کردن در صفحه جدید ↗
                    </a>
                </div>

                <div class="w-full h-[600px] sm:h-[750px] rounded-2xl overflow-hidden border border-slate-200 bg-slate-100">
                    <?php if ($file_ext === 'pdf') : ?>
                        <iframe src="<?= esc_url($file_url); ?>" class="w-full h-full border-0" title="<?= esc_attr(get_the_title()); ?>"></iframe>
                    <?php else : ?>
                        <div class="w-full h-full flex items-center justify-center p-4">
                            <img src="<?= esc_url($file_url); ?>" alt="<?= esc_attr(get_the_title()); ?>" class="max-w-full max-h-full object-contain shadow-md rounded-xl" />
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

    </div>
</article>

<?php
endwhile; endif;
get_footer();
?>
