<?php
/**
 * Front Page Template
 * PSB-CO High-Voltage Electrical Panel Manufacturing Theme
 */

get_header();

if (have_posts()) {
    the_post();
}

// 1. Modern High-Voltage Hero Section
get_template_part('template-parts/homepage/hero');

// 2. Electrical Engineering Services Showcase
get_template_part('template-parts/services/services-recently');

// 3. Portfolio Showcase & Recent Projects
get_template_part('template-parts/portfolio/recently-portfolios');

// 4. Corporate Industrial About Us Section
get_template_part('template-parts/homepage/aboutus');

// 5. Official Confirmations & Vendor Approvals Component
get_template_part('template-parts/confirmation/recently-confirmations', null, array('layout' => 'slider'));

// 6. Technical Quality Certificates & ISO Standards Component
get_template_part('template-parts/certificates/recently-certificates', null, array('layout' => 'slider'));

// 7. Corporate Blog & News Slider
get_template_part('template-parts/homepage/blog-slider');

get_footer();
