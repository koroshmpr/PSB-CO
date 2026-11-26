<?php
/**
 * Archive: customer letters (رضایت‌نامه‌ها / حسن انجام کار / تقدیرنامه). Also used for letter_type terms.
 * Filter chips are plain links (work without JS); the scan opens in the shared lightbox.
 * No Review / AggregateRating schema on purpose (self-serving reviews are not eligible).
 *
 * @package bluebox
 */

get_header();

$psb_term  = is_tax( 'letter_type' ) ? get_queried_object() : null;
$psb_terms = taxonomy_exists( 'letter_type' ) ? get_terms(
	array(
		'taxonomy'   => 'letter_type',
		'hide_empty' => true,
	)
) : array();
$psb_all   = psb_archive_url( 'customer_letter', '/customer-letters/' );

get_template_part(
	'template-parts/global/page-hero',
	null,
	array(
		'eyebrow' => 'اعتبارنامه‌ها',
		'title'   => $psb_term ? ( false !== mb_strpos( $psb_term->name, 'نامه' ) ? $psb_term->name . '‌ها' : 'نامه‌های ' . $psb_term->name ) : 'رضایت‌نامه‌ها و تقدیرنامه‌ها',
		'lead'    => 'نامه‌های رضایت، حسن انجام کار و تقدیر کارفرمایان از پروژه‌های ' . psb_brand_name() . '. تصویر اسکن هر نامه و متن کامل آن در دسترس است.',
	)
);
get_template_part( 'template-parts/certificates/cred-nav', null, array( 'current' => 'customer_letter' ) );
?>
<section class="container py-section" aria-labelledby="letters-list-title">
	<h2 id="letters-list-title" class="sr-only">فهرست نامه‌ها</h2>
	<?php if ( have_posts() ) : ?>
		<?php if ( ! is_wp_error( $psb_terms ) && count( $psb_terms ) > 1 ) : ?>
			<nav class="mb-8 flex flex-wrap gap-2" aria-label="نوع نامه">
				<a class="chip" href="<?php echo esc_url( $psb_all ); ?>" <?php echo $psb_term ? '' : 'aria-current="page" aria-pressed="true"'; ?>>همه</a>
				<?php foreach ( $psb_terms as $psb_t ) : ?>
					<?php $psb_on = $psb_term && $psb_term->term_id === $psb_t->term_id; ?>
					<a class="chip" href="<?php echo esc_url( get_term_link( $psb_t ) ); ?>" <?php echo $psb_on ? 'aria-current="page" aria-pressed="true"' : ''; ?>>
						<?php echo esc_html( $psb_t->name ); ?>
						<span class="opacity-70"><?php echo esc_html( psb_fa_num( $psb_t->count ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
		<div class="grid gap-gutter sm:grid-cols-2 lg:grid-cols-3">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/letters/letter-card', null, array( 'post' => get_the_ID() ) );
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
				'icon'    => 'doc',
				'title'   => 'نامه‌های کارفرمایان به‌زودی در این بخش منتشر می‌شود',
				'text'    => 'تصویر و متن رضایت‌نامه‌ها و نامه‌های حسن انجام کار پس از دریافت مجوز انتشار از کارفرما، در این صفحه قرار می‌گیرد. تا آن زمان، پروژه‌های اجراشده و تأییدیه‌های رسمی شرکت را ببینید.',
				'actions' => array(
					array( psb_archive_url( 'portfolio', '/portfolios/' ), 'پروژه‌های اجراشده', 'btn-primary' ),
					array( psb_archive_url( 'confirmation' ), 'تأییدیه‌ها', 'btn-outline' ),
				),
			)
		);
		?>
	<?php endif; ?>
</section>
<?php
get_footer();
