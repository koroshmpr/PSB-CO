<?php
/**
 * Author archive. Author archives redirect on this site (SEO plugin); if they are ever enabled,
 * they use the magazine grid. No author links are printed anywhere in the theme.
 *
 * @package bluebox
 */

get_header();
get_template_part( 'template-parts/blog/magazine', null, array( 'mode' => 'archive' ) );
get_footer();
