<?php
/**
 * Archive: projects (CPT portfolio) + portfolio category archives (taxonomy-portfolio-category.php).
 *
 * @package bluebox
 */

get_header();

$psb_term  = is_tax( 'portfolio_categories' ) ? get_queried_object() : null;
$psb_terms = get_terms(
	array(
		'taxonomy'   => 'portfolio_categories',
		'hide_empty' => true,
	)
);

get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'eyebrow' => 'پروژه‌ها',
		'title'   => $psb_term ? 'پروژه‌های ' . $psb_term->name : 'پروژه‌های اجراشده',
		'lead'    => $psb_term && $psb_term->description ? wp_strip_all_tags( $psb_term->description ) : 'نمونه‌ای از تابلوهای برق طراحی و ساخته‌شده توسط ' . psb_brand_name() . ' برای مجتمع‌های تجاری، کارخانه‌ها و تأسیسات، همراه با مشخصات فنی هر پروژه.',
	)
);
?>
<section class="container py-section" aria-labelledby="projects-list-title">
	<h2 id="projects-list-title" class="sr-only">فهرست پروژه‌ها</h2>
	<?php if ( ! is_wp_error( $psb_terms ) && count( $psb_terms ) > 1 ) : ?>
		<nav class="mb-8 flex gap-2 overflow-x-auto pb-1" aria-label="دسته‌بندی پروژه‌ها">
			<a class="chip shrink-0" href="<?php echo esc_url( psb_archive_url( 'portfolio', '/portfolios/' ) ); ?>" <?php echo $psb_term ? '' : 'aria-current="page" aria-pressed="true"'; ?>>همه پروژه‌ها</a>
			<?php foreach ( $psb_terms as $psb_t ) : ?>
				<?php $psb_on = $psb_term && (int) $psb_term->term_id === (int) $psb_t->term_id; ?>
				<a class="chip shrink-0" href="<?php echo esc_url( get_term_link( $psb_t ) ); ?>" <?php echo $psb_on ? 'aria-current="page" aria-pressed="true"' : ''; ?>><?php echo esc_html( $psb_t->name ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>
	<?php if ( have_posts() ) : ?>
		<div class="grid gap-gutter sm:grid-cols-2 lg:grid-cols-3">
			<?php
			global $wp_query;
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/portfolio/project-card', null, array( 'post' => get_the_ID(), 'eager' => 0 === (int) $wp_query->current_post ) );
			endwhile;
			?>
		</div>
		<?php psb_pagination(); ?>
	<?php else : ?>
		<?php
		get_template_part(
			'template-parts/certificates/empty-state',
			null,
			array(
				'icon'    => 'factory',
				'title'   => 'پروژه‌ای در این دسته ثبت نشده است',
				'actions' => array( array( psb_archive_url( 'portfolio', '/portfolios/' ), 'همه پروژه‌ها', 'btn-outline' ) ),
			)
		);
		?>
	<?php endif; ?>
</section>
<?php
get_footer();
