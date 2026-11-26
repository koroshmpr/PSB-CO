<?php
/**
 * Home page (spec v2 §5) in order. Light/dark map: hero (dark) · issuers (white) · products (alt) ·
 * proof (white) · factory (dark) · projects (white) · about (alt) · articles (white) · FAQ (alt).
 * The red CTA band and the footer are printed by footer.php. Every section hides itself when empty;
 * letters and certificates stay hidden until real content exists.
 */
$psb_sections = array( 'hero', 'issuers', 'products', 'approvals', 'certificates', 'capabilities', 'projects', 'letters', 'aboutus', 'blog-slider', 'faq' );
foreach ( $psb_sections as $psb_i => $psb_section ) {
	if ( $psb_i > 2 ) {
		echo '<div class="cv-auto">';
	}
	get_template_part( 'template-parts/homepage/' . $psb_section );
	if ( $psb_i > 2 ) {
		echo '</div>';
	}
}
