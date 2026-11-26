<?php
/**
 * Generic archive (tags, dates, other taxonomies without their own template): magazine grid.
 *
 * @package bluebox
 */

get_header();
get_template_part( 'template-parts/blog/magazine', null, array( 'mode' => 'archive' ) );
get_footer();
