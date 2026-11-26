<?php
/**
 * Category archive: same magazine design as the blog home (category chip active).
 *
 * @package bluebox
 */

get_header();
get_template_part( 'template-parts/blog/magazine', null, array( 'mode' => 'category' ) );
get_footer();
