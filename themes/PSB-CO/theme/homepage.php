<?php
/**
 * Template Name: Home
 *
 * Same narrative as the front page (front-page.php takes precedence on the
 * actual front page; this template lets any other page reuse it).
 *
 * @package bluebox
 */

get_header();

if ( have_posts() ) {
	the_post();
}

get_template_part( 'template-parts/homepage/sections' );

get_footer();
