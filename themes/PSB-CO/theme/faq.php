<?php
/* Template Name: faq */
/**
 * FAQ page: groups from ACF repeater faq_cat (title, image, faq_list) as anchor chips + accordions,
 * one FAQPage JSON-LD for all questions. Falls back to the page's own faq_list.
 *
 * @package bluebox
 */

get_header();

while ( have_posts() ) :
	the_post();
	$psb_groups = psb_field( 'faq_cat', array(), get_the_ID() );
	$psb_groups = is_array( $psb_groups ) ? array_values(
		array_filter(
			$psb_groups,
			function ( $g ) {
				return ! empty( $g['faq_list'] ) && is_array( $g['faq_list'] );
			}
		)
	) : array();
	if ( ! $psb_groups ) {
		$psb_own = psb_field( 'faq_list', array(), get_the_ID() );
		if ( is_array( $psb_own ) && $psb_own ) {
			$psb_groups = array(
				array(
					'title'    => '',
					'faq_list' => $psb_own,
				),
			);
		}
	}

	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'variant' => 'light',
			'eyebrow' => 'پرسش و پاسخ',
			'lead'    => has_excerpt() ? wp_strip_all_tags( get_the_excerpt() ) : 'پاسخ پرسش‌های رایج درباره طراحی، ساخت، آزمون، تحویل و استعلام قیمت تابلوهای برق.',
		)
	);
	?>
	<section class="container py-section">
		<div class="mx-auto max-w-content">
			<?php if ( '' !== trim( get_the_content() ) ) : ?>
				<div class="article-prose mb-10"><?php the_content(); ?></div>
			<?php endif; ?>

			<?php if ( count( $psb_groups ) > 1 ) : ?>
				<nav class="mb-10 flex flex-wrap gap-2" aria-label="موضوعات">
					<?php foreach ( $psb_groups as $psb_i => $psb_g ) : ?>
						<a class="chip" href="#faq-group-<?php echo (int) $psb_i; ?>"><?php echo esc_html( $psb_g['title'] ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php
			$psb_all = array();
			foreach ( $psb_groups as $psb_i => $psb_g ) :
				$psb_all = array_merge( $psb_all, $psb_g['faq_list'] );
				?>
				<section id="faq-group-<?php echo (int) $psb_i; ?>" class="mb-12 scroll-mt-28">
					<?php
					get_template_part(
						'template-parts/global/faq-list',
						null,
						array(
							'items'          => $psb_g['faq_list'],
							'title'          => $psb_g['title'] ? $psb_g['title'] : '',
							'disable_schema' => true,
							'open_first'     => 0 === $psb_i,
						)
					);
					?>
				</section>
			<?php endforeach; ?>

			<?php if ( ! $psb_groups ) : ?>
				<?php get_template_part( 'template-parts/certificates/empty-state', null, array( 'icon' => 'doc', 'title' => 'پرسش‌ها به‌زودی اضافه می‌شوند', 'actions' => array( array( psb_contact_url(), 'ارسال پرسش', 'btn-outline' ) ) ) ); ?>
			<?php endif; ?>

			<div class="panel mt-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
				<div>
					<p class="font-bold text-dark">پاسخ پرسش خود را پیدا نکردید؟</p>
					<p class="mt-1 text-sm text-slate-600">کارشناسان فنی در ساعات اداری پاسخ‌گو هستند.</p>
				</div>
				<a class="btn btn-primary shrink-0" href="<?php echo esc_url( psb_contact_url() ); ?>">تماس با شرکت</a>
			</div>
		</div>
	</section>
	<?php
	$psb_all = array_filter(
		$psb_all,
		function ( $q ) {
			return ! empty( $q['question'] ) && ! empty( $q['answer'] );
		}
	);
	if ( $psb_all ) :
		$psb_schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array(),
		);
		foreach ( $psb_all as $psb_q ) {
			$psb_schema['mainEntity'][] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $psb_q['question'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $psb_q['answer'] ),
				),
			);
		}
		?>
		<script type="application/ld+json"><?php echo wp_json_encode( $psb_schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
