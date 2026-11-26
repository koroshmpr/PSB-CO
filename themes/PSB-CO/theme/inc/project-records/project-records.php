<?php
/**
 * «سوابق اجرایی» — project reference list (module entry).
 *
 * - CPT `project_record` (non-public, admin UI only) + taxonomy `project_sector` (non-public).
 * - Meta box, admin columns/filters/quick edit, CSV/XLSX importer, CSV export.
 * - Front end: page template `page-project-references.php` (also used for the page with slug
 *   `project-references`), template parts in `template-parts/project-records/`,
 *   assets `css/project-records.css` + `js/project-records.js` (plain files, no build step).
 * - Public API: psb_pr_stats(), psb_pr_records(), psb_pr_import_csv( $path, $args ),
 *   get_template_part( 'template-parts/project-records/highlights', null, array( 'count' => 6 ) ).
 *
 * Require from functions.php:
 *   require_once get_template_directory() . '/inc/project-records/project-records.php';
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'PSB_PR_VERSION' ) ) {
	return;
}

define( 'PSB_PR_VERSION', '1.0.1' );
define( 'PSB_PR_DIR', __DIR__ );
define( 'PSB_PR_CPT', 'project_record' );
define( 'PSB_PR_TAX', 'project_sector' );

require_once PSB_PR_DIR . '/helpers.php';
require_once PSB_PR_DIR . '/cpt.php';
require_once PSB_PR_DIR . '/data.php';
require_once PSB_PR_DIR . '/importer.php';
require_once PSB_PR_DIR . '/frontend.php';

if ( is_admin() ) {
	require_once PSB_PR_DIR . '/admin.php';
	require_once PSB_PR_DIR . '/admin-import.php';
}
