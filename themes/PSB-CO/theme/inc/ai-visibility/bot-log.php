<?php
/**
 * AI visibility — AI crawler activity log.
 *
 * Cost on a normal page view: one regex on the User-Agent header. Only a
 * matching AI bot triggers one read of a small non-autoloaded option, and at
 * most one write per bot per minute (the host is a fragile shared server).
 * With a persistent object cache, hits inside that minute are counted in the
 * cache and flushed with the next write; without one, the counts are a
 * per-minute sample (shown as such in the dashboard).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_AI_LOG_OPTION   = 'psb_ai_botlog';
const PSB_AI_LOG_DAYS     = 30;
const PSB_AI_LOG_THROTTLE = 60;

add_action( 'parse_request', 'psb_ai_log_request', 0 );

/**
 * Bot name for a User-Agent, or ''.
 */
function psb_ai_detect_bot( $ua ) {
	static $regex = null, $map = null;
	if ( null === $regex ) {
		$parts = array();
		$map   = array();
		foreach ( psb_ai_bots() as $name => $bot ) {
			if ( empty( $bot['ua'] ) ) {
				continue;
			}
			$parts[]                         = preg_quote( $bot['ua'], '/' );
			$map[ strtolower( $bot['ua'] ) ] = $name;
		}
		$regex = '/(' . implode( '|', $parts ) . ')/i';
	}
	if ( '' === $ua || ! preg_match( $regex, $ua, $m ) ) {
		return '';
	}
	return $map[ strtolower( $m[1] ) ] ?? '';
}

function psb_ai_log_request() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( strlen( $ua ) < 5 ) {
		return;
	}
	$bot = psb_ai_detect_bot( substr( $ua, 0, 512 ) );
	if ( '' === $bot ) {
		return;
	}
	if ( is_admin() || wp_doing_cron() || ! psb_ai_setting( 'log_enabled', 1 ) || is_user_logged_in() ) {
		return;
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$path = substr( rawurldecode( $path ?: '/' ), 0, 200 );
	$path = wp_check_invalid_utf8( $path, true );

	psb_ai_log_hit( $bot, $path );
}

function psb_ai_log_get() {
	$log = get_option( PSB_AI_LOG_OPTION, array() );
	$log = is_array( $log ) ? $log : array();
	return array_merge(
		array(
			'days' => array(),
			'bots' => array(),
		),
		$log
	);
}

function psb_ai_log_hit( $bot, $path ) {
	$now     = time();
	$ext     = wp_using_ext_object_cache();
	$log     = psb_ai_log_get();
	$entry   = $log['bots'][ $bot ] ?? array();
	$written = (int) ( $entry['written'] ?? 0 );

	if ( $now - $written < PSB_AI_LOG_THROTTLE ) {
		if ( $ext ) {
			if ( false === wp_cache_incr( 'pending_' . $bot, 1, 'psb_ai' ) ) {
				wp_cache_set( 'pending_' . $bot, 1, 'psb_ai', DAY_IN_SECONDS );
			}
		}
		return;
	}

	$hits = 1;
	if ( $ext ) {
		$pending = (int) wp_cache_get( 'pending_' . $bot, 'psb_ai' );
		if ( $pending > 0 ) {
			$hits += $pending;
			wp_cache_delete( 'pending_' . $bot, 'psb_ai' );
		}
	}

	$day = wp_date( 'Y-m-d', $now );
	if ( ! isset( $log['days'][ $day ] ) || ! is_array( $log['days'][ $day ] ) ) {
		$log['days'][ $day ] = array();
	}
	$log['days'][ $day ][ $bot ] = (int) ( $log['days'][ $day ][ $bot ] ?? 0 ) + $hits;

	$log['bots'][ $bot ] = array(
		'last'    => $now,
		'path'    => $path,
		'written' => $now,
		'total'   => (int) ( $entry['total'] ?? 0 ) + $hits,
		'first'   => (int) ( $entry['first'] ?? $now ),
	);

	// Keep the last N days only.
	if ( count( $log['days'] ) > PSB_AI_LOG_DAYS ) {
		ksort( $log['days'] );
		$log['days'] = array_slice( $log['days'], -PSB_AI_LOG_DAYS, null, true );
	}

	$log['sampled'] = ! $ext;

	update_option( PSB_AI_LOG_OPTION, $log, false );
}
