<?php
/**
 * AI visibility — robots.txt additions.
 *
 * Appends (never replaces) per-crawler groups to whatever WordPress core,
 * WooCommerce and Rank Math produced. Runs late on the core `robots_txt`
 * filter. Rank Math's robots.txt editor output passes through the same filter,
 * so its rules are preserved.
 *
 * A crawler that has its own `User-agent` group ignores the `*` group, so the
 * allowed-AI group repeats the `*` group's Allow/Disallow rules — the AI bots
 * keep exactly the same restrictions as everyone else (including a global
 * "Disallow: /" if the site is ever closed), and only blocked bots differ.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'robots_txt', 'psb_ai_robots_txt', 9999, 2 );

/**
 * @param string $output Current robots.txt.
 * @param bool   $public blog_public option.
 */
function psb_ai_robots_txt( $output, $public ) {
	if ( ! psb_ai_endpoint_enabled( 'robots' ) || false !== strpos( (string) $output, '# BEGIN PSB AI' ) ) {
		return $output;
	}

	$output   = rtrim( (string) $output ) . "\n";
	$base     = psb_ai_robots_star_rules( $output );
	$settings = psb_ai_setting( 'bots', array() );
	$allowed  = array();
	$blocked  = array();

	foreach ( psb_ai_bots() as $name => $bot ) {
		if ( 'disallow' === ( $settings[ $name ] ?? 'allow' ) ) {
			$blocked[] = $name;
		} else {
			$allowed[] = $name;
		}
	}

	$add  = "\n# BEGIN PSB AI\n";
	if ( psb_ai_endpoint_enabled( 'llms' ) ) {
		$add .= '# LLM-friendly summary of this site: ' . home_url( '/llms.txt' ) . "\n";
	}
	if ( psb_ai_endpoint_enabled( 'llms_full' ) ) {
		$add .= '# Full Markdown content: ' . home_url( '/llms-full.txt' ) . "\n";
	}

	if ( $allowed ) {
		$add .= "\n";
		foreach ( $allowed as $name ) {
			$add .= 'User-agent: ' . $name . "\n";
		}
		if ( $base ) {
			$add .= implode( "\n", $base ) . "\n";
		} else {
			$add .= "Allow: /\n";
		}
	}

	if ( $blocked ) {
		$add .= "\n";
		foreach ( $blocked as $name ) {
			$add .= 'User-agent: ' . $name . "\n";
		}
		$add .= "Disallow: /\n";
	}

	$add .= "# END PSB AI\n";

	return $output . $add;
}

/**
 * Allow/Disallow lines of the `User-agent: *` group(s) in an existing robots.txt.
 *
 * @return string[]
 */
function psb_ai_robots_star_rules( $robots ) {
	$rules    = array();
	$in_star  = false;
	$in_group = false; // Seen a rule line in the current group.

	foreach ( preg_split( '/\r\n|\r|\n/', (string) $robots ) as $line ) {
		$clean = trim( preg_replace( '/#.*$/', '', $line ) );
		if ( '' === $clean ) {
			continue;
		}
		if ( ! preg_match( '/^([A-Za-z-]+)\s*:\s*(.*)$/', $clean, $m ) ) {
			continue;
		}
		$field = strtolower( $m[1] );
		$value = trim( $m[2] );

		if ( 'user-agent' === $field ) {
			if ( $in_group ) {
				// A new group starts.
				$in_star  = false;
				$in_group = false;
			}
			if ( '*' === $value ) {
				$in_star = true;
			}
			continue;
		}

		if ( 'allow' === $field || 'disallow' === $field ) {
			$in_group = true;
			if ( $in_star ) {
				$rules[] = ( 'allow' === $field ? 'Allow: ' : 'Disallow: ' ) . $value;
			}
		}
	}

	// An empty "Disallow:" means allow everything — keep it out.
	$rules = array_filter(
		$rules,
		static function ( $r ) {
			return 'Disallow: ' !== $r;
		}
	);

	return array_values( array_unique( $rules ) );
}
