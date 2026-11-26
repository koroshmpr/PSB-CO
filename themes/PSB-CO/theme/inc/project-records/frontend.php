<?php
/**
 * Project records — front-end routing and assets.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Is this the «سوابق اجرایی» page (template chosen, or slug project-references with the default template)? */
function psb_pr_is_page() {
	if ( is_page_template( 'page-project-references.php' ) ) {
		return true;
	}
	if ( ! is_page( 'project-references' ) ) {
		return false;
	}
	$tpl = get_page_template_slug( get_queried_object_id() );
	return '' === $tpl || 'default' === $tpl;
}

add_filter(
	'template_include',
	static function ( $template ) {
		if ( psb_pr_is_page() && ! is_page_template( 'page-project-references.php' ) ) {
			$t = locate_template( 'page-project-references.php' );
			return $t ? $t : $template;
		}
		return $template;
	},
	20
);

/** Theme-relative asset URL + filemtime version. */
function psb_pr_asset( $rel ) {
	$path = get_template_directory() . '/' . $rel;
	return array( get_template_directory_uri() . '/' . $rel, PSB_PR_VERSION . ( file_exists( $path ) ? '.' . filemtime( $path ) : '' ) );
}

/**
 * Stylesheet (safe to call from a template part: prints the <link> in place when <head> is already out).
 */
function psb_pr_enqueue_style() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done              = true;
	list( $css, $ver ) = psb_pr_asset( 'css/project-records.css' );
	if ( did_action( 'wp_head' ) && ! doing_action( 'wp_enqueue_scripts' ) ) {
		echo '<link rel="stylesheet" id="psb-pr-css" href="' . esc_url( add_query_arg( 'ver', $ver, $css ) ) . '">' . "\n"; // phpcs:ignore WordPress.WP.EnqueuedResources
	} else {
		wp_enqueue_style( 'psb-pr', $css, array(), $ver );
	}
}

/** Script for the filterable list (page only). */
function psb_pr_enqueue_script() {
	list( $js, $ver ) = psb_pr_asset( 'js/project-records.js' );
	wp_enqueue_script( 'psb-pr', $js, array(), $ver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( psb_pr_is_page() ) {
			psb_pr_enqueue_style();
			psb_pr_enqueue_script();
		}
	},
	20
);

/**
 * Render the type chips for a row.
 *
 * @param string[] $types Keys.
 * @param int      $max   Max chips (0 = all).
 * @return string HTML.
 */
function psb_pr_type_chips( $types, $max = 0 ) {
	$tl   = psb_pr_type_labels();
	$html = '';
	$n    = 0;
	foreach ( (array) $types as $t ) {
		if ( ! isset( $tl[ $t ] ) ) {
			continue;
		}
		if ( $max && $n >= $max ) {
			$html .= '<span class="pr-tag pr-tag--more">' . esc_html( psb_pr_fa( count( $types ) - $max ) ) . ' مورد دیگر</span>';
			break;
		}
		$html .= '<span class="pr-tag">' . esc_html( $tl[ $t ] ) . '</span>';
		++$n;
	}
	return $html;
}

/**
 * One result row (<tr>) — the same markup js/project-records.js renders.
 *
 * @param array $r Row.
 * @return string HTML.
 */
function psb_pr_row_html( $r ) {
	$names  = psb_pr_sector_names();
	$sector = $names[ $r['sector'] ] ?? '';
	$title  = psb_pr_lat( $r['title'] );
	if ( $r['url'] ) {
		$title = '<a class="pr-link" href="' . esc_url( $r['url'] ) . '">' . $title . '</a>';
	}
	$year   = $r['year'] ? psb_pr_fa( $r['year'] ) : '';
	$client = '' !== $r['client'] ? psb_pr_lat( $r['client'] ) : '';
	ob_start();
	?>
	<tr class="pr-row<?php echo $r['anon'] ? ' is-anon' : ''; ?>">
		<th scope="row" class="pr-c-title"><span class="pr-title"><?php echo $title; // phpcs:ignore ?></span></th>
		<td class="pr-c-client" data-l="کارفرما"><?php echo $client ? ( $r['anon'] ? '<span class="pr-anon">' . $client . '</span>' : $client ) : '<span class="pr-na">—</span>'; // phpcs:ignore ?></td>
		<td class="pr-c-loc" data-l="محل"><?php echo '' !== $r['loc'] ? esc_html( $r['loc'] ) : '<span class="pr-na">—</span>'; ?></td>
		<td class="pr-c-year" data-l="سال"><?php echo $year ? esc_html( $year ) : '<span class="pr-na">—</span>'; ?></td>
		<td class="pr-c-sector" data-l="حوزه"><span class="pr-sector"><?php echo esc_html( $sector ); ?></span></td>
		<td class="pr-c-types" data-l="نوع تابلو"><?php echo psb_pr_type_chips( $r['types'] ); // phpcs:ignore ?></td>
	</tr>
	<?php
	return (string) ob_get_clean();
}
