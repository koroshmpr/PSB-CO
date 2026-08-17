<?php
/**
 * Generic Archive Page Template (Categories, Tags, Taxonomies)
 * PSB-CO High & Medium Voltage Electrical Panel Theme
 */

get_header();
$current_term = get_queried_object();
$term_id = $current_term->term_id ?? 0;
$categories = get_categories(array('hide_empty' => true));
?>

<main class="bg-slate-50/70 text-slate-900 min-h-screen select-none">
    
    <!-- 1. Hero Header Section -->
    <section class="relative w-full py-16 lg:py-24 bg-slate-950 text-white overflow-hidden border-b border-slate-800">
        <!-- Background Radial Grid -->
        <div class="absolute inset-0 bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:24px_24px] opacity-30 pointer-events-none"></div>

        <div class="container mx-auto px-4 md:px-8 relative z-10 text-center space-y-4 max-w-3xl" data-aos="fade-up">
            <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold bg-blue-600/30 text-blue-300 border border-blue-500/30">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                <span>آرشیو مقالات و اخبار</span>
            </span>

            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                <?php the_archive_title(); ?>
            </h1>

            <?php if (get_the_archive_description()) : ?>
                <div class="text-sm sm:text-base text-slate-300 font-medium leading-relaxed max-w-2xl mx-auto">
                    <?php the_archive_description(); ?>
                </div>
            <?php endif; ?>

            <!-- Category Pills Bar -->
            <?php if (!empty($categories)) : ?>
                <nav aria-label="دسته‌بندی‌های مقاله" class="flex flex-wrap items-center justify-center gap-2 pt-4">
                    <a href="<?= esc_url(get_permalink(get_option('page_for_posts')) ?: site_url('/blog')); ?>" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm bg-slate-900 text-slate-300 hover:bg-slate-800 border border-slate-800">
                        همه مقالات
                    </a>
                    <?php foreach ($categories as $cat) : ?>
                        <a href="<?= esc_url(get_category_link($cat->term_id)); ?>" 
                           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm <?= $term_id === $cat->term_id ? 'bg-blue-700 text-white border border-blue-600' : 'bg-slate-900 text-slate-300 hover:bg-slate-800 border border-slate-800'; ?>">
                            <?= esc_html($cat->name); ?> (<?= $cat->count; ?>)
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <!-- 2. Posts Grid Section -->
    <section class="container mx-auto px-4 md:px-8 py-12">
        <?php if (have_posts()) : ?>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('template-parts/blog/archive-card', null, array('eager' => false));
                endwhile;
                ?>
            </div>

            <!-- Pagination -->
            <div class="mt-12 flex justify-center">
                <?php bluebox_the_posts_navigation(); ?>
            </div>

        <?php else : ?>
            <div class="py-16 text-center bg-white rounded-3xl border border-slate-200 shadow-sm max-w-lg mx-auto space-y-3">
                <span class="text-4xl block">📰</span>
                <h3 class="text-lg font-bold text-slate-800">مقاله‌ای در این آرشیو یافت نشد!</h3>
                <p class="text-xs text-slate-500">لطفاً از سایر دسته‌بندی‌ها بازدید فرمایید.</p>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php get_footer(); ?>
