<?php
/**
 * Front page — B2B narrative for Pars Switch Board.
 * Sections: template-parts/homepage/sections.php
 *
 * @package bluebox
 */

get_header();

if ( have_posts() ) {
	the_post();
}

get_template_part( 'template-parts/homepage/sections' );

get_footer();
