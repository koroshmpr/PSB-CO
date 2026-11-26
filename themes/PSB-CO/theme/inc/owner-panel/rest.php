<?php
/**
 * Owner panel — REST API psb-panel/v1/*.
 *
 * Auth: cookie + X-WP-Nonce (wp_rest). Every route requires psb_panel_can(); each action also checks the
 * matching WordPress capability (edit_post, publish_posts, moderate_comments, …). Responses: no-store.
 * Every optional source (RFQ module, Gravity Forms, Cache Enabler, AI log) degrades to "not available".
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

function psb_panel_err( $message, $status = 400, $code = 'psb_panel' ) {
	return new WP_Error( $code, $message, array( 'status' => $status ) );
}

add_filter(
	'rest_post_dispatch',
	static function ( $response, $server, $request ) {
		if ( 0 === strpos( (string) $request->get_route(), '/psb-panel/v1' ) && $response instanceof WP_REST_Response ) {
			$response->header( 'Cache-Control', 'no-store, private' );
			$response->header( 'X-Robots-Tag', 'noindex' );
		}
		return $response;
	},
	10,
	3
);

add_action(
	'rest_api_init',
	static function () {
		$r = static function ( $path, $methods, $cb, $args = array() ) {
			register_rest_route(
				'psb-panel/v1',
				$path,
				array(
					'methods'             => $methods,
					'callback'            => $cb,
					'permission_callback' => static function () {
						return psb_panel_can() ? true : psb_panel_err( 'دسترسی ندارید.', rest_authorization_required_code(), 'rest_forbidden' );
					},
					'args'                => $args,
				)
			);
		};
		$id = array(
			'id' => array(
				'type'     => 'integer',
				'required' => true,
				'minimum'  => 1,
			),
		);

		$r( '/me', 'GET', 'psb_panel_api_me' );
		$r( '/dashboard', 'GET', 'psb_panel_api_dashboard' );
		$r(
			'/rfq',
			'GET',
			'psb_panel_api_rfq_list',
			array(
				'status' => array( 'type' => 'string', 'default' => '' ),
				's'      => array( 'type' => 'string', 'default' => '' ),
				'page'   => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
			)
		);
		$r( '/rfq/(?P<id>\d+)', 'GET', 'psb_panel_api_rfq_get', $id );
		$r( '/rfq/(?P<id>\d+)/status', 'POST', 'psb_panel_api_rfq_status', $id + array( 'status' => array( 'type' => 'string', 'required' => true ) ) );
		$r( '/rfq/(?P<id>\d+)/note', 'POST', 'psb_panel_api_rfq_note', $id + array( 'text' => array( 'type' => 'string', 'required' => true ) ) );
		$r( '/content', 'GET', 'psb_panel_api_content' );
		$r(
			'/post/(?P<id>\d+)',
			'POST',
			'psb_panel_api_post',
			$id + array(
				'op'   => array( 'type' => 'string', 'required' => true, 'enum' => array( 'publish', 'schedule' ) ),
				'date' => array( 'type' => 'string', 'default' => '' ),
			)
		);
		$r( '/comment/(?P<id>\d+)', 'POST', 'psb_panel_api_comment', $id + array( 'op' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'approve', 'spam', 'trash' ) ) ) );
		$r( '/forms', 'GET', 'psb_panel_api_forms', array( 'form' => array( 'type' => 'integer', 'default' => 0 ) ) );
		$r( '/cache/clear', 'POST', 'psb_panel_api_cache_clear' );
		$r( '/sessions', 'POST', 'psb_panel_api_sessions', array( 'scope' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'others', 'all' ) ) ) );
	}
);

/* =========================================================================
 * Shared counters
 * ====================================================================== */

/** RFQ counts: created today / 7d / 30d, per status, plus "new" badge. */
function psb_panel_rfq_counts() {
	global $wpdb;
	$cpt = psb_panel_rfq_cpt();
	$out = array( 'today' => 0, 'week' => 0, 'month' => 0, 'status' => array(), 'new' => 0 );
	foreach ( array( 'today' => 0, 'week' => 6, 'month' => 29 ) as $k => $days ) {
		$out[ $k ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish','private') AND post_date_gmt >= %s", $cpt, psb_panel_day_start_gmt( $days ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT COALESCE(m.meta_value,'new') AS s, COUNT(*) AS n FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s WHERE p.post_type = %s AND p.post_status IN ('publish','private') GROUP BY s", PSB_PANEL_RFQ_META['status'], $cpt ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( (array) $rows as $row ) {
		$out['status'][ $row->s ] = (int) $row->n;
	}
	$out['new'] = (int) ( $out['status']['new'] ?? 0 );
	return $out;
}

/** May the current user see Gravity Forms data (counts included)? */
function psb_panel_can_view_forms() {
	return class_exists( 'GFAPI' ) && ( current_user_can( 'gravityforms_view_entries' ) || current_user_can( 'manage_options' ) );
}

function psb_panel_gf_counts() {
	if ( ! psb_panel_can_view_forms() ) {
		return null; // No GF data for users without gravityforms_view_entries (security review L7).
	}
	$active = array( 'status' => 'active' );
	// Gregorian local dates (wp_date() is filtered to Jalali on this site, GF needs Y-m-d Gregorian).
	$day    = static function ( $back ) {
		$d = new DateTime( 'today', wp_timezone() );
		return $back ? $d->modify( '-' . (int) $back . ' days' )->format( 'Y-m-d' ) : $d->format( 'Y-m-d' );
	};
	$unread = (int) GFAPI::count_entries( 0, $active + array( 'field_filters' => array( array( 'key' => 'is_read', 'value' => 0 ) ) ) );
	$today  = (int) GFAPI::count_entries( 0, $active + array( 'start_date' => $day( 0 ) ) );
	$week   = (int) GFAPI::count_entries( 0, $active + array( 'start_date' => $day( 6 ) ) );
	return array( 'unread' => $unread, 'today' => $today, 'week' => $week );
}

function psb_panel_cache_available() {
	return class_exists( 'Cache_Enabler' ) || has_action( 'cache_enabler_clear_complete_cache' );
}

function psb_panel_can_clear_cache() {
	return (bool) apply_filters( 'user_can_clear_cache', current_user_can( 'manage_options' ) );
}

/** Post types managers work with in the content tab. */
function psb_panel_content_types() {
	$types = get_post_types( array( 'public' => true ), 'names' );
	unset( $types['attachment'] );
	return array_values( apply_filters( 'psb_panel_content_types', $types ) );
}

/** Last "PHP Fatal error" line in the PHP error log (tail only, ≤ 256 KB read). */
function psb_panel_last_fatal() {
	$candidates = array_filter( array( (string) ini_get( 'error_log' ), WP_CONTENT_DIR . '/debug.log', ABSPATH . 'error_log' ) );
	foreach ( $candidates as $file ) {
		if ( ! @is_file( $file ) || ! @is_readable( $file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			continue;
		}
		$size = (int) @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$fh   = @fopen( $file, 'rb' ); // phpcs:ignore
		if ( ! $fh ) {
			continue;
		}
		$len = min( $size, 262144 );
		fseek( $fh, -$len, SEEK_END );
		$tail = (string) fread( $fh, $len ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( preg_match_all( '/^\[([^\]]+)\][^\n]*PHP Fatal error:\s*([^\n]{0,180})/m', $tail, $m ) && $m[0] ) {
			$i  = count( $m[0] ) - 1;
			$ts = strtotime( $m[1][ $i ] );
			$msg = preg_replace( '#' . preg_quote( ABSPATH, '#' ) . '#', '/', $m[2][ $i ] );
			return array( 'ts' => $ts ? $ts : 0, 'msg' => wp_strip_all_tags( $msg ), 'file' => basename( $file ) );
		}
		return array( 'ts' => 0, 'msg' => '', 'file' => basename( $file ) );
	}
	return null;
}

/* =========================================================================
 * Endpoints
 * ====================================================================== */

function psb_panel_api_me() {
	return array(
		'session' => psb_panel_session_info(),
		'badges'  => psb_panel_badges(),
	);
}

function psb_panel_badges() {
	$c = wp_count_comments();
	return array(
		'rfq'      => psb_panel_rfq_can() ? psb_panel_rfq_counts()['new'] : 0,
		'comments' => current_user_can( 'moderate_comments' ) ? (int) $c->moderated : 0,
		'forms'    => psb_panel_can_view_forms() ? (int) ( psb_panel_gf_counts()['unread'] ?? 0 ) : 0,
	);
}

function psb_panel_api_dashboard() {
	$out = array();

	// Quote requests.
	if ( psb_panel_rfq_can() ) {
		$counts           = psb_panel_rfq_counts();
		$counts['labels'] = psb_panel_rfq_statuses();
		$latest           = get_posts(
			array(
				'post_type'      => psb_panel_rfq_cpt(),
				'post_status'    => array( 'publish', 'private' ),
				'posts_per_page' => 3,
				'no_found_rows'  => true,
			)
		);
		$counts['latest'] = array_map( 'psb_panel_rfq_row', $latest );
		$out['rfq']       = $counts;
	}

	// Gravity Forms.
	$out['forms'] = psb_panel_gf_counts();

	// Content.
	$types     = psb_panel_content_types();
	$scheduled = get_posts(
		array(
			'post_type'      => $types,
			'post_status'    => 'future',
			'posts_per_page' => 5,
			'orderby'        => 'date',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	$drafts  = 0;
	$pending = 0;
	foreach ( $types as $t ) {
		$c        = wp_count_posts( $t );
		$drafts  += (int) ( $c->draft ?? 0 );
		$pending += (int) ( $c->pending ?? 0 );
	}
	$comments       = wp_count_comments();
	$out['content'] = array(
		'scheduled' => array_map(
			static function ( $p ) {
				return array( 'id' => $p->ID, 'title' => get_the_title( $p ), 'ts' => psb_panel_ts( $p->post_date_gmt ) );
			},
			$scheduled
		),
		'drafts'    => $drafts,
		'pending'   => $pending,
		'comments'  => (int) $comments->moderated,
	);

	// Site health.
	$cron           = (int) get_option( 'psb_real_cron_seen', 0 );
	$health         = array(
		'php'        => PHP_VERSION,
		'wp'         => get_bloginfo( 'version' ),
		'cache'      => array(
			'plugin' => class_exists( 'Cache_Enabler' ),
			'wpc'    => defined( 'WP_CACHE' ) && WP_CACHE,
		),
		'cron'       => $cron,
		'cronOk'     => $cron && ( time() - $cron ) < 20 * MINUTE_IN_SECONDS,
		'wpCronOff'  => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		'objectCache' => wp_using_ext_object_cache(),
		'debug'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
	);
	if ( current_user_can( 'manage_options' ) ) {
		$health['fatal'] = psb_panel_last_fatal();
	}
	$out['health'] = $health;

	// AI crawlers (inc/ai-visibility bot log).
	$log = get_option( 'psb_ai_botlog', null );
	if ( is_array( $log ) && isset( $log['days'] ) ) {
		$today = wp_date( 'Y-m-d' );
		$from  = wp_date( 'Y-m-d', time() - 6 * DAY_IN_SECONDS );
		$t     = 0;
		$w     = 0;
		$m     = 0;
		foreach ( (array) $log['days'] as $day => $bots ) {
			$sum = array_sum( array_map( 'intval', (array) $bots ) );
			$m  += $sum;
			if ( $day >= $from ) {
				$w += $sum;
			}
			if ( $day === $today ) {
				$t += $sum;
			}
		}
		$bots = array();
		foreach ( (array) ( $log['bots'] ?? array() ) as $name => $b ) {
			$bots[] = array( 'name' => (string) $name, 'last' => (int) ( $b['last'] ?? 0 ), 'total' => (int) ( $b['total'] ?? 0 ), 'path' => (string) ( $b['path'] ?? '' ) );
		}
		usort(
			$bots,
			static function ( $a, $b ) {
				return $b['last'] <=> $a['last'];
			}
		);
		$out['ai'] = array( 'today' => $t, 'week' => $w, 'month' => $m, 'bots' => array_slice( $bots, 0, 6 ), 'sampled' => ! empty( $log['sampled'] ) );
	} else {
		$out['ai'] = null;
	}

	// Installed-app funnel (inc/pwa).
	$out['pwa']    = function_exists( 'psb_pwa_stats' ) ? psb_pwa_stats() : null;
	$out['badges'] = array(
		'rfq'      => (int) ( $out['rfq']['new'] ?? 0 ),
		'comments' => current_user_can( 'moderate_comments' ) ? (int) $comments->moderated : 0,
		'forms'    => (int) ( $out['forms']['unread'] ?? 0 ),
	);
	return $out;
}

function psb_panel_api_rfq_list( WP_REST_Request $req ) {
	if ( ! psb_panel_rfq_can() ) {
		return psb_panel_err( 'ماژول استعلام قیمت فعال نیست یا دسترسی ندارید.', 404 );
	}
	$status = sanitize_key( (string) $req['status'] );
	$search = trim( psb_panel_latin( sanitize_text_field( (string) $req['s'] ) ) );
	$args   = array(
		'post_type'      => psb_panel_rfq_cpt(),
		'post_status'    => array( 'publish', 'private' ),
		'posts_per_page' => 20,
		'paged'          => max( 1, (int) $req['page'] ),
	);
	if ( $status && isset( psb_panel_rfq_statuses()[ $status ] ) ) {
		$args['meta_query'] = 'new' === $status // phpcs:ignore WordPress.DB.SlowDBQuery
			? array(
				'relation' => 'OR',
				array( 'key' => PSB_PANEL_RFQ_META['status'], 'value' => 'new' ),
				array( 'key' => PSB_PANEL_RFQ_META['status'], 'compare' => 'NOT EXISTS' ),
			)
			: array( array( 'key' => PSB_PANEL_RFQ_META['status'], 'value' => $status ) );
	}
	if ( '' !== $search ) {
		$args['s'] = mb_substr( $search, 0, 80 );
	}
	$q = new WP_Query( $args );
	return array(
		'items'    => array_map( 'psb_panel_rfq_row', $q->posts ),
		'total'    => (int) $q->found_posts,
		'pages'    => (int) $q->max_num_pages,
		'statuses' => psb_panel_rfq_statuses(),
		'counts'   => psb_panel_rfq_counts()['status'],
	);
}

function psb_panel_rfq_post_or_error( $id ) {
	$post = get_post( (int) $id );
	if ( ! psb_panel_rfq_can() || ! $post || psb_panel_rfq_cpt() !== $post->post_type || 'trash' === $post->post_status ) {
		return psb_panel_err( 'درخواست پیدا نشد.', 404 );
	}
	if ( ! current_user_can( 'edit_post', $post->ID ) ) {
		return psb_panel_err( 'اجازه دسترسی به این درخواست را ندارید.', 403 );
	}
	return $post;
}

function psb_panel_api_rfq_get( WP_REST_Request $req ) {
	$post = psb_panel_rfq_post_or_error( $req['id'] );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	$out             = psb_panel_rfq_detail( $post );
	$out['statuses'] = psb_panel_rfq_statuses();
	return $out;
}

function psb_panel_api_rfq_status( WP_REST_Request $req ) {
	$post = psb_panel_rfq_post_or_error( $req['id'] );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	$status = sanitize_key( (string) $req['status'] );
	if ( ! isset( psb_panel_rfq_statuses()[ $status ] ) ) {
		return psb_panel_err( 'وضعیت نامعتبر است.' );
	}
	psb_panel_rfq_set_status( $post->ID, $status );
	return psb_panel_api_rfq_get( $req );
}

function psb_panel_api_rfq_note( WP_REST_Request $req ) {
	$post = psb_panel_rfq_post_or_error( $req['id'] );
	if ( is_wp_error( $post ) ) {
		return $post;
	}
	$text = mb_substr( trim( sanitize_textarea_field( (string) $req['text'] ) ), 0, 2000 );
	if ( '' === $text ) {
		return psb_panel_err( 'متن یادداشت خالی است.' );
	}
	psb_panel_rfq_add_note( $post->ID, $text, false );
	return psb_panel_api_rfq_get( $req );
}

function psb_panel_post_row( $p ) {
	$type = get_post_type_object( $p->post_type );
	return array(
		'id'      => $p->ID,
		'title'   => get_the_title( $p ) ? html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ) : '(بدون عنوان)',
		'type'    => $type ? $type->labels->singular_name : $p->post_type,
		'status'  => $p->post_status,
		'ts'      => psb_panel_ts( $p->post_date_gmt ) ? psb_panel_ts( $p->post_date_gmt ) : (int) get_post_time( 'U', true, $p ),
		'mod'     => psb_panel_ts( $p->post_modified_gmt ),
		'author'  => get_the_author_meta( 'display_name', $p->post_author ),
		'edit'    => current_user_can( 'edit_post', $p->ID ) ? get_edit_post_link( $p->ID, 'raw' ) : '',
		'preview' => get_preview_post_link( $p ),
		'canPub'  => $type && current_user_can( 'edit_post', $p->ID ) && current_user_can( $type->cap->publish_posts ),
	);
}

function psb_panel_api_content() {
	$types = psb_panel_content_types();
	$base  = array(
		'post_type'      => $types,
		'posts_per_page' => 30,
		'no_found_rows'  => true,
	);
	// Only what this user may edit (WP_Query's `perm` misbehaves with several CPTs that map their own caps).
	$editable = static function ( $posts ) {
		return array_values(
			array_map(
				'psb_panel_post_row',
				array_filter(
					$posts,
					static function ( $p ) {
						return current_user_can( 'edit_post', $p->ID );
					}
				)
			)
		);
	};
	$out = array(
		'scheduled' => $editable( get_posts( $base + array( 'post_status' => 'future', 'orderby' => 'date', 'order' => 'ASC' ) ) ),
		'drafts'    => $editable( get_posts( $base + array( 'post_status' => array( 'pending', 'draft' ), 'orderby' => 'modified', 'order' => 'DESC' ) ) ),
		'comments'  => array(),
	);
	if ( current_user_can( 'moderate_comments' ) ) {
		foreach ( get_comments( array( 'status' => 'hold', 'number' => 30, 'type' => 'comment' ) ) as $c ) {
			$out['comments'][] = array(
				'id'     => (int) $c->comment_ID,
				'author' => $c->comment_author,
				'text'   => wp_trim_words( wp_strip_all_tags( $c->comment_content ), 60 ),
				'post'   => html_entity_decode( get_the_title( $c->comment_post_ID ), ENT_QUOTES, 'UTF-8' ),
				'link'   => get_permalink( $c->comment_post_ID ),
				'ts'     => psb_panel_ts( $c->comment_date_gmt ),
			);
		}
	}
	return $out;
}

function psb_panel_api_post( WP_REST_Request $req ) {
	$post = get_post( (int) $req['id'] );
	if ( ! $post || ! in_array( $post->post_type, psb_panel_content_types(), true ) ) {
		return psb_panel_err( 'نوشته پیدا نشد.', 404 );
	}
	$type = get_post_type_object( $post->post_type );
	if ( ! current_user_can( 'edit_post', $post->ID ) || ! current_user_can( $type->cap->publish_posts ) ) {
		return psb_panel_err( 'اجازه انتشار این مورد را ندارید.', 403 );
	}
	if ( ! in_array( $post->post_status, array( 'draft', 'pending', 'future' ), true ) ) {
		return psb_panel_err( 'این مورد قبلاً منتشر شده است.' );
	}
	if ( 'publish' === $req['op'] ) {
		$now = current_time( 'mysql' );
		$res = wp_update_post(
			array(
				'ID'            => $post->ID,
				'post_status'   => 'publish',
				'post_date'     => $now,
				'post_date_gmt' => get_gmt_from_date( $now ),
				'edit_date'     => true,
			),
			true
		);
	} else {
		$raw = trim( psb_panel_latin( (string) $req['date'] ) );
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})$/', $raw, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
			return psb_panel_err( 'تاریخ و ساعت انتشار معتبر نیست.' );
		}
		$local = sprintf( '%s-%s-%s %s:%s:00', $m[1], $m[2], $m[3], $m[4], $m[5] );
		$gmt   = get_gmt_from_date( $local );
		if ( strtotime( $gmt . ' UTC' ) <= time() + 60 ) {
			return psb_panel_err( 'زمان انتشار باید در آینده باشد.' );
		}
		$res = wp_update_post(
			array(
				'ID'            => $post->ID,
				'post_status'   => 'future',
				'post_date'     => $local,
				'post_date_gmt' => $gmt,
				'edit_date'     => true,
			),
			true
		);
	}
	if ( is_wp_error( $res ) ) {
		return psb_panel_err( $res->get_error_message() );
	}
	return array( 'ok' => true, 'item' => psb_panel_post_row( get_post( $post->ID ) ), 'url' => get_permalink( $post->ID ) );
}

function psb_panel_api_comment( WP_REST_Request $req ) {
	$id = (int) $req['id'];
	$c  = get_comment( $id );
	if ( ! $c ) {
		return psb_panel_err( 'دیدگاه پیدا نشد.', 404 );
	}
	if ( ! current_user_can( 'moderate_comments' ) || ! current_user_can( 'edit_comment', $id ) ) {
		return psb_panel_err( 'اجازه مدیریت دیدگاه را ندارید.', 403 );
	}
	switch ( $req['op'] ) {
		case 'approve':
			$ok = wp_set_comment_status( $id, 'approve' );
			break;
		case 'spam':
			$ok = wp_spam_comment( $id );
			break;
		default:
			$ok = wp_trash_comment( $id );
	}
	return $ok ? array( 'ok' => true ) : psb_panel_err( 'انجام نشد.' );
}

function psb_panel_api_forms( WP_REST_Request $req ) {
	if ( ! class_exists( 'GFAPI' ) ) {
		return psb_panel_err( 'افزونه Gravity Forms فعال نیست.', 404 );
	}
	if ( ! current_user_can( 'gravityforms_view_entries' ) && ! current_user_can( 'manage_options' ) ) {
		return psb_panel_err( 'اجازه دیدن ورودی‌های فرم را ندارید.', 403 );
	}
	$forms = array();
	foreach ( (array) GFAPI::get_forms() as $f ) {
		$forms[ (int) $f['id'] ] = $f;
	}
	$form_id = (int) $req['form'];
	$form_id = isset( $forms[ $form_id ] ) ? $form_id : 0;
	$entries = GFAPI::get_entries( $form_id, array( 'status' => 'active' ), array( 'key' => 'date_created', 'direction' => 'DESC' ), array( 'offset' => 0, 'page_size' => 25 ) );
	$items   = array();
	foreach ( is_array( $entries ) ? $entries : array() as $e ) {
		$form   = $forms[ (int) $e['form_id'] ] ?? null;
		$fields = array();
		if ( $form ) {
			foreach ( (array) $form['fields'] as $field ) {
				if ( in_array( $field->type, array( 'html', 'section', 'page', 'captcha', 'honeypot' ), true ) ) {
					continue;
				}
				$val = method_exists( $field, 'get_value_export' ) ? $field->get_value_export( $e, $field->id, true ) : rgar( $e, (string) $field->id );
				$val = trim( wp_strip_all_tags( (string) $val ) );
				if ( '' === $val ) {
					continue;
				}
				$fields[] = array(
					'label' => wp_strip_all_tags( (string) ( $field->adminLabel ? $field->adminLabel : $field->label ) ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName
					'value' => mb_substr( $val, 0, 1500 ),
					'type'  => (string) $field->type,
				);
			}
		}
		$items[] = array(
			'id'     => (int) $e['id'],
			'form'   => $form ? (string) $form['title'] : '#' . (int) $e['form_id'],
			'ts'     => psb_panel_ts( $e['date_created'] ),
			'read'   => ! empty( $e['is_read'] ),
			'fields' => $fields,
			'link'   => admin_url( 'admin.php?page=gf_entries&view=entry&id=' . (int) $e['form_id'] . '&lid=' . (int) $e['id'] ),
		);
	}
	$list = array();
	foreach ( $forms as $id => $f ) {
		$list[] = array( 'id' => $id, 'title' => (string) $f['title'] );
	}
	return array( 'forms' => $list, 'form' => $form_id, 'items' => $items, 'counts' => psb_panel_gf_counts() );
}

function psb_panel_api_cache_clear() {
	if ( ! psb_panel_cache_available() ) {
		return psb_panel_err( 'افزونه Cache Enabler فعال نیست.', 404 );
	}
	if ( ! psb_panel_can_clear_cache() ) {
		return psb_panel_err( 'اجازه پاک کردن کش را ندارید.', 403 );
	}
	do_action( 'cache_enabler_clear_complete_cache' );
	return array( 'ok' => true );
}

function psb_panel_api_sessions( WP_REST_Request $req ) {
	$others = 'others' === $req['scope'];
	if ( ! psb_panel_destroy_sessions( $others ) ) {
		return psb_panel_err( 'انجام نشد.' );
	}
	return array( 'ok' => true, 'loggedOut' => ! $others, 'session' => $others ? psb_panel_session_info() : null );
}
