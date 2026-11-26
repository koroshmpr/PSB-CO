<?php
/**
 * RFQ presentation helpers (bidi-safe labels, type → product links).
 * Loaded with require_once from the RFQ templates; inc/rfq/frontend.php uses psb_rfq_bidi() when present.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'psb_rfq_bidi' ) ) {
	/**
	 * Escape a label and isolate its Latin runs so parentheses and slashes keep their RTL order.
	 * «تابلو نیمه‌اصلی و توزیع (SMDB / DB)» → «… (<bdi dir="ltr">SMDB / DB</bdi>)».
	 * Runs that contain digits (IP54, 25 kA, Form 2b) also get the Latin face (.rfq-lat) so Peyda
	 * does not turn their digits Persian. Mirrors bidi() in js/rfq.js.
	 *
	 * @param string $text Plain text.
	 * @return string Safe HTML.
	 */
	function psb_rfq_bidi( $text ) {
		$text  = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
		$tok   = '[A-Za-z0-9](?:[A-Za-z0-9.~\-]*[A-Za-z0-9])?';
		$parts = preg_split( '/(' . $tok . '(?:\s*[\/+]\s*' . $tok . '|\s+' . $tok . ')*)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out   = '';
		foreach ( (array) $parts as $i => $part ) {
			if ( 1 === $i % 2 && preg_match( '/[A-Za-z]/', $part ) ) {
				$out .= '<bdi dir="ltr"' . ( preg_match( '/\d/', $part ) ? ' class="rfq-lat"' : '' ) . '>' . esc_html( $part ) . '</bdi>';
			} else {
				$out .= esc_html( $part );
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'psb_rfq_bidi_plain' ) ) {
	/**
	 * Plain-text variant for places that cannot hold markup (<option>): Latin runs are wrapped in
	 * LRI … PDI isolate marks instead of <bdi>. Returns escaped text.
	 *
	 * @param string $text Plain text.
	 * @return string
	 */
	function psb_rfq_bidi_plain( $text ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' );
		$tok  = '[A-Za-z0-9](?:[A-Za-z0-9.~\-]*[A-Za-z0-9])?';
		$text = (string) preg_replace_callback(
			'/' . $tok . '(?:\s*[\/+]\s*' . $tok . '|\s+' . $tok . ')*/u',
			static function ( $m ) {
				return preg_match( '/[A-Za-z]/', $m[0] ) ? "\u{2066}" . $m[0] . "\u{2069}" : $m[0];
			},
			$text
		);
		return esc_html( $text );
	}
}

if ( ! function_exists( 'psb_rfq_type_links' ) ) {
	/**
	 * Type key → permalink of the published product whose meta `_psb_rfq_type` is that key.
	 * One light query (ID, key, modified) acts as the cache fingerprint, so a new, edited, retyped or
	 * unpublished product refreshes the map without any save hook; permalinks are cached in a transient.
	 * When several products share a type, the lowest menu_order, then the newest, wins.
	 *
	 * @return array<string,string>
	 */
	function psb_rfq_type_links() {
		static $links = null;
		if ( null !== $links ) {
			return $links;
		}
		$links = array();
		if ( ! post_type_exists( 'product' ) ) {
			return $links;
		}
		global $wpdb;
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- cached below.
			$wpdb->prepare(
				"SELECT p.ID, m.meta_value AS type_key, p.post_modified_gmt
				FROM {$wpdb->postmeta} m
				INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
				WHERE m.meta_key IN ( %s, %s ) AND m.meta_value <> '' AND p.post_type = 'product' AND p.post_status = 'publish'
				ORDER BY p.menu_order ASC, p.post_date DESC, p.ID DESC",
				'_psb_rfq_type',
				'psb_rfq_type' // Public twin written by the content tools (MCP cannot write protected meta).
			),
			ARRAY_A
		);
		if ( ! $rows ) {
			return $links;
		}
		$finger = md5( wp_json_encode( $rows ) . '|' . get_option( 'permalink_structure' ) . '|' . home_url() );
		$cached = get_transient( 'psb_rfq_type_links' );
		if ( is_array( $cached ) && isset( $cached['f'], $cached['l'] ) && $cached['f'] === $finger ) {
			$links = (array) $cached['l'];
			return $links;
		}
		$types = function_exists( 'psb_rfq_types' ) ? psb_rfq_types() : array();
		foreach ( $rows as $row ) {
			$key = sanitize_key( $row['type_key'] );
			if ( '' === $key || isset( $links[ $key ] ) || ( $types && ! isset( $types[ $key ] ) ) ) {
				continue;
			}
			$url = get_permalink( (int) $row['ID'] );
			if ( $url ) {
				$links[ $key ] = $url;
			}
		}
		set_transient( 'psb_rfq_type_links', array( 'f' => $finger, 'l' => $links ), DAY_IN_SECONDS );
		return $links;
	}
}
