<?php
/**
 * Blog home (posts page) and fallback template: magazine layout.
 *
 * @package bluebox
 */

get_header();
get_template_part( 'template-parts/blog/magazine', null, array( 'mode' => is_home() ? 'home' : 'archive' ) );
get_footer();
