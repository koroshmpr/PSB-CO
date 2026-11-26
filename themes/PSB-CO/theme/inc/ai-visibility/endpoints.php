<?php
/**
 * AI visibility — virtual endpoints.
 *
 * No physical files and no rewrite rules: Apache already hands unknown paths to
 * index.php, so the request path is matched on `parse_request` (before the main
 * query runs) and the document is streamed straight away. Nothing to flush,
 * nothing to break when permalinks change, and zero cost for other URLs beyond
 * one string comparison.
 *
 * /llms.txt        text/plain     llmstxt.org index
 * /llms-full.txt   text/plain     full Markdown content (size-capped)
 * /llms/<id>.md    text/markdown  one item as Markdown
 * /ai.txt          text/plain     Spawning-style permission summary
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

add_action( 'parse_request', 'psb_ai_maybe_serve', 1 );

/**
 * @param WP $wp Current WordPress environment instance.
 */
function psb_ai_maybe_serve( $wp ) {
	$path = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	if ( '' === $path || strlen( $path ) > 40 ) {
		return;
	}

	switch ( $path ) {
		case 'llms.txt':
			if ( psb_ai_endpoint_enabled( 'llms' ) ) {
				$rec = psb_ai_get_doc( 'llms' );
				psb_ai_send( $rec['body'], 'text/plain', $rec['etag'], (int) $rec['generated'] );
			}
			return;

		case 'llms-full.txt':
			if ( psb_ai_endpoint_enabled( 'llms_full' ) ) {
				$rec = psb_ai_get_doc( 'llms_full' );
				psb_ai_send( $rec['body'], 'text/plain', $rec['etag'], (int) $rec['generated'] );
			}
			return;

		case 'ai.txt':
			if ( psb_ai_endpoint_enabled( 'ai_txt' ) ) {
				$body  = psb_ai_build_ai_txt();
				$state = psb_ai_state();
				psb_ai_send( $body, 'text/plain', md5( $body ), (int) ( $state['invalidated'] ?: time() ) );
			}
			return;
	}

	if ( 0 === strpos( $path, 'llms/' ) && preg_match( '#^llms/(\d{1,10})\.md$#', $path, $m ) ) {
		if ( ! psb_ai_endpoint_enabled( 'md' ) ) {
			return;
		}
		$post = get_post( (int) $m[1] );
		if ( ! $post || ! in_array( $post->post_type, psb_ai_included_post_types(), true ) || ! psb_ai_post_is_exposable( $post ) ) {
			return; // Falls through to the normal WordPress 404.
		}
		$body = psb_ai_build_item_md( $post );
		psb_ai_send( $body, 'text/markdown', md5( $body ), (int) get_post_modified_time( 'U', true, $post ), $post );
	}
}

/**
 * Stream a text document with caching headers and conditional-GET support.
 *
 * @param string       $body     Document.
 * @param string       $type     MIME type without charset.
 * @param string       $etag     Raw ETag value (unquoted).
 * @param int          $last_mod Unix timestamp.
 * @param WP_Post|null $post     Item for a Link: canonical header.
 */
function psb_ai_send( $body, $type, $etag, $last_mod, $post = null ) {
	$last_mod = $last_mod > 0 ? $last_mod : time();
	$etag_h   = '"' . $etag . '"';

	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	header_remove( 'Expires' );
	header_remove( 'Pragma' );
	header_remove( 'X-Robots-Tag' );

	header( 'Content-Type: ' . $type . '; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600, stale-while-revalidate=86400' );
	header( 'ETag: ' . $etag_h );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $last_mod ) . ' GMT' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Access-Control-Allow-Origin: *' );
	if ( $post ) {
		header( 'Link: <' . esc_url_raw( get_permalink( $post ) ) . '>; rel="canonical"' );
	}

	$inm = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? trim( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$ims = isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? strtotime( wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	$not_modified = false;
	if ( '' !== $inm ) {
		foreach ( explode( ',', $inm ) as $candidate ) {
			$candidate = trim( preg_replace( '#^W/#', '', trim( $candidate ) ) );
			if ( $candidate === $etag_h || '*' === $candidate ) {
				$not_modified = true;
				break;
			}
		}
	} elseif ( $ims && $ims >= $last_mod ) {
		$not_modified = true;
	}

	if ( $not_modified ) {
		status_header( 304 );
		exit;
	}

	status_header( 200 );
	if ( ! ini_get( 'zlib.output_compression' ) ) {
		header( 'Content-Length: ' . strlen( $body ) );
	}

	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
	if ( 'HEAD' !== $method ) {
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text document.
	}
	exit;
}
