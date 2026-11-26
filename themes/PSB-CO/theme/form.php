<?php
/* Template Name: form */
/**
 * Page with a single Gravity Form (ACF field gravity_form) in a centred panel.
 *
 * @package bluebox
 */

get_header();
while ( have_posts() ) :
	the_post();
	$psb_form_id = (int) psb_field( 'gravity_form', 0, get_the_ID() );
	get_template_part(
		'template-parts/global/page-hero',
		null,
		array(
			'variant' => 'light',
			'lead'    => has_excerpt() ? wp_strip_all_tags( get_the_excerpt() ) : '',
		)
	);
	?>
	<section class="container py-section">
		<div class="panel mx-auto max-w-2xl sm:!p-8">
			<?php if ( '' !== trim( get_the_content() ) ) : ?>
				<div class="article-prose mb-6"><?php the_content(); ?></div>
			<?php endif; ?>
			<div class="gform-psb">
				<?php
				if ( $psb_form_id && shortcode_exists( 'gravityform' ) ) {
					echo do_shortcode( '[gravityform id="' . (int) $psb_form_id . '" title="false" description="false" ajax="true"]' );
				}
				?>
			</div>
		</div>
	</section>
	<?php
endwhile;
get_footer();
