<?php
/**
 * Legacy admin-ajax search action ("custom_ajax_search"), kept for backwards compatibility.
 * Delegates to the theme search engine (inc/search-route.php); the header search uses the
 * REST endpoint /wp-json/psb/v1/search instead.
 *
 * @package bluebox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_custom_ajax_search', 'handle_custom_ajax_search' );
add_action( 'wp_ajax_nopriv_custom_ajax_search', 'handle_custom_ajax_search' );

function handle_custom_ajax_search() {
	$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$type    = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification

	// Short result cache + light per-IP limit on cache misses, like the REST route (security review L8).
	$ckey   = 'psb_sqa_' . md5( psb_search_normalize( $keyword ) . '|' . $type . '|' . get_option( PSB_SEARCH_VER_KEY, 1 ) . '|' . PSB_SEARCH_INDEX_KEY );
	$cached = get_transient( $ckey );
	if ( is_array( $cached ) ) {
		wp_send_json_success( $cached );
	}
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rlk  = 'psb_srla_' . md5( $ip . gmdate( 'YmdHi' ) );
	$hits = (int) get_transient( $rlk );
	if ( $hits >= 30 ) {
		wp_send_json_error( array( 'message' => 'تعداد جستجوها زیاد است؛ یک دقیقه دیگر دوباره تلاش کنید.' ), 429 );
	}
	set_transient( $rlk, $hits + 1, 70 );

	$res    = psb_search_run( $keyword, $type );
	$counts = array_merge( array( 'all' => array_sum( $res['counts'] ) ), $res['counts'] );
	$items  = psb_search_items( array_slice( $res['hits'], 0, 12 ), $res['units'] );

	ob_start();
	foreach ( $items as $item ) {
		printf(
			'<a class="card card-hover p-4" href="%1$s"><span class="text-sm font-bold text-dark">%2$s</span><span class="mt-1 line-clamp-2 text-xs text-slate-500">%3$s</span></a>',
			esc_url( $item['url'] ),
			$item['title'], // phpcs:ignore -- escaped + highlighted in psb_search_highlight().
			$item['snippet'] // phpcs:ignore
		);
	}
	if ( ! $items ) {
		echo '<p class="col-span-full py-10 text-center text-sm text-slate-500">نتیجه‌ای یافت نشد.</p>';
	}
	$data = array(
		'html'   => ob_get_clean(),
		'counts' => $counts,
	);
	set_transient( $ckey, $data, 5 * MINUTE_IN_SECONDS );
	wp_send_json_success( $data );
}
