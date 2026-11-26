<?php
/**
 * Legacy name kept for compatibility: renders the design-system project card.
 */
get_template_part( 'template-parts/portfolio/project-card', null, array_merge( array( 'post' => get_the_ID() ), (array) ( $args ?? array() ) ) );
