<?php
/**
 * Blog Main Index & Archive Page Template
 * PSB-CO High & Medium Voltage Electrical Panel Manufacturing Theme
 */

$id = is_home() ? get_option('page_for_posts') : get_queried_object_id();
$current_page = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Retrieve Page Content and FAQ if set on Blog Page
$faq = get_field('faq_list', $id);
$content = get_post_field('post_content', $id);

// Get current category / taxonomy if on category page
$current_cat_id = is_category() ? get_queried_object_id() : 0;
$categories = get_categories(array('hide_empty' => true));

get_header();
?>

<main class="bg-slate-50/70 text-slate-900 min-h-screen select-none">
    
    <!-- 1. Hero Header Section -->
    <section class="relative w-full py-16 lg:py-24 bg-slate-950 text-white overflow-hidden border-b border-slate-800">
        <!-- Background Radial Grid -->
        <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-30 pointer-events-none"></div>

        <div class="container mx-auto px-4 md:px-8 relative z-10 text-center space-y-4 max-w-3xl" data-aos="fade-up">
            <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-600/30 text-blue-300 border border-blue-500/30">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <span>مجله تخصصی تابلوسازی & پست‌های برق</span>
            </span>

            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                <?php
                if (is_home()) {
                    echo single_post_title('', false) ?: 'مقالات و اخبار تخصصی';
                } elseif (is_category() || is_tag() || is_tax()) {
                    echo single_term_title('دسته‌بندی: ', false);
                } elseif (is_search()) {
                    echo 'نتایج جستجو برای: ' . get_search_query();
                } else {
                    echo get_the_title($id);
                }
                ?>
            </h1>

            <p class="text-sm sm:text-base text-slate-300 font-medium leading-relaxed">
                آموزش‌های تخصصی، استانداردهای بین‌المللی IEC، راهنمای ساخت تابلوهای فشار قوی، متوسط و ضعیف
            </p>

            <!-- Category Pills Bar -->
            <?php if (!empty($categories)) : ?>
                <nav aria-label="دسته‌بندی‌های مقاله" class="flex flex-wrap items-center justify-center gap-2 pt-4">
                    <a href="<?= esc_url(get_permalink(get_option('page_for_posts')) ?: site_url('/blog')); ?>" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm <?= !$current_cat_id ? 'bg-blue-700 text-white border border-blue-600' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 border border-slate-800'; ?>">
                        همه مقالات
                    </a>
                    <?php foreach ($categories as $cat) : ?>
                        <a href="<?= esc_url(get_category_link($cat->term_id)); ?>" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm <?= $current_cat_id === $cat->term_id ? 'bg-blue-700 text-white border border-blue-600' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 border border-slate-800'; ?>">
                            <?= esc_html($cat->name); ?> (<?= $cat->count; ?>)
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <!-- 2. Featured & Popular Sections (Page 1 Only) -->
    <?php if ($current_page == 1 && empty($_GET)) : ?>
        <section class="container mx-auto px-4 md:px-8 pt-8 space-y-8" data-aos="fade-up">
            <?php get_template_part('template-parts/blog/archive/must-visited-posts'); ?>
            <?php get_template_part('template-parts/blog/archive/most-discussed-posts'); ?>
        </section>
    <?php endif; ?>

    <!-- 3. Posts Grid Section -->
    <section class="container mx-auto px-4 md:px-8 py-10">
        <?php
        $args2 = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'paged' => $current_page,
        );

        if ($current_cat_id) {
            $args2['cat'] = $current_cat_id;
        }

        $posts_query = new WP_Query($args2);

        if ($posts_query->have_posts()) : ?>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php
                while ($posts_query->have_posts()) :
                    $posts_query->the_post();
                    $args = array(
                        'eager' => $posts_query->current_post < 4
                    );
                    get_template_part('template-parts/blog/archive-card', null, $args);
                endwhile;
                ?>
            </div>

            <!-- Pagination -->
            <div class="mt-12 flex justify-center">
                <?php get_template_part('template-parts/global/pagination'); ?>
            </div>

            <?php wp_reset_postdata(); ?>

        <?php else : ?>
            <div class="py-16 text-center bg-white rounded-3xl border border-slate-200 shadow-sm max-w-lg mx-auto space-y-3">
                <span class="text-4xl block">📰</span>
                <h3 class="text-lg font-bold text-slate-800">مقاله‌ای در این بخش یافت نشد!</h3>
                <p class="text-xs text-slate-500">لطفاً از سایر دسته‌بندی‌ها بازدید فرمایید.</p>
                <a href="<?= esc_url(site_url('/blog')); ?>" class="inline-block px-4 py-2 rounded-xl bg-blue-700 text-white font-bold text-xs mt-2">
                    بازگشت به مقالات
                </a>
            </div>
        <?php endif; ?>
    </section>

    <!-- 4. Footer Content / FAQ if available -->
    <?php if ($current_page == 1 && empty($_GET)) : ?>
        <?php if ($content) : ?>
            <section class="container mx-auto px-4 md:px-8 py-6">
                <?php get_template_part('template-parts/global/content', null, array('content' => $content)); ?>
            </section>
        <?php endif; ?>

        <?php if ($faq) : ?>
            <section class="container mx-auto px-4 md:px-8 py-8">
                <?php get_template_part('template-parts/global/faq-list', null, array('items' => $faq)); ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>

</main>

<?php get_footer(); ?>