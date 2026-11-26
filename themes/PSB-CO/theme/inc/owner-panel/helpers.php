<?php
/**
 * Owner panel — settings, capability, URLs, session helpers.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_PANEL_OPTION = 'psb_panel_settings';

function psb_panel_defaults() {
	return array(
		'session_days' => 180,                                           // remembered logins of panel users.
		'refresh_days' => 30,                                            // silently extend when fewer days are left.
		'roles'        => array( 'administrator', 'editor', 'shop_manager' ),
		'accent'       => '#1A3DA3',                                     // design token --color-primary.
	);
}

function psb_panel_settings() {
	$s = get_option( PSB_PANEL_OPTION, array() );
	$s = array_merge( psb_panel_defaults(), is_array( $s ) ? $s : array() );
	$s['session_days'] = min( 365, max( 1, (int) $s['session_days'] ) );
	$s['refresh_days'] = min( $s['session_days'], max( 0, (int) $s['refresh_days'] ) );
	$s['roles']        = array_values( array_filter( (array) $s['roles'], 'is_string' ) );
	$s['accent']       = sanitize_hex_color( $s['accent'] ) ? $s['accent'] : '#1A3DA3';
	return $s;
}

/**
 * May this user use the panel? Administrators (manage_options) always; others through the psb_owner_panel
 * capability, which is granted to the roles chosen in the settings.
 *
 * @param int|WP_User|null $user Defaults to the current user.
 */
function psb_panel_can( $user = null ) {
	if ( null === $user ) {
		return is_user_logged_in() && ( current_user_can( 'manage_options' ) || ( current_user_can( PSB_PANEL_CAP ) && current_user_can( 'edit_posts' ) ) );
	}
	return user_can( $user, 'manage_options' ) || ( user_can( $user, PSB_PANEL_CAP ) && user_can( $user, 'edit_posts' ) );
}

/** Roles that may be granted the panel: only staff roles that can edit_posts (security review L7). */
function psb_panel_eligible_roles() {
	$out = array();
	foreach ( wp_roles()->role_objects as $name => $role ) {
		if ( 'administrator' === $name || $role->has_cap( 'edit_posts' ) ) {
			$out[] = $name;
		}
	}
	return $out;
}

/** Add/remove the panel capability according to the role setting. */
function psb_panel_sync_caps( $roles = null ) {
	$roles    = null === $roles ? psb_panel_settings()['roles'] : (array) $roles;
	$eligible = psb_panel_eligible_roles();
	foreach ( wp_roles()->role_objects as $name => $role ) {
		if ( in_array( $name, $roles, true ) && in_array( $name, $eligible, true ) ) {
			$role->add_cap( PSB_PANEL_CAP );
		} elseif ( $role->has_cap( PSB_PANEL_CAP ) ) {
			$role->remove_cap( PSB_PANEL_CAP );
		}
	}
}

add_action(
	'init',
	static function () {
		if ( get_option( 'psb_panel_caps_ver' ) !== PSB_PANEL_VERSION ) {
			psb_panel_sync_caps();
			update_option( 'psb_panel_caps_ver', PSB_PANEL_VERSION, true );
		}
	},
	5
);
add_action(
	'update_option_' . PSB_PANEL_OPTION,
	static function ( $old, $new ) {
		psb_panel_sync_caps( is_array( $new ) && isset( $new['roles'] ) ? $new['roles'] : null );
	},
	10,
	2
);
add_action(
	'add_option_' . PSB_PANEL_OPTION,
	static function ( $name, $value ) {
		psb_panel_sync_caps( is_array( $value ) && isset( $value['roles'] ) ? $value['roles'] : null );
	},
	10,
	2
);

/** Site-relative base path of the panel, e.g. "/panel/". */
function psb_panel_base() {
	return rtrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' ) . '/' . PSB_PANEL_SLUG . '/';
}

function psb_panel_url( $sub = '' ) {
	return home_url( '/' . PSB_PANEL_SLUG . '/' . ltrim( (string) $sub, '/' ) );
}

/** Public URL of a file under theme/pwa/ with ?v=filemtime. */
function psb_panel_asset( $rel ) {
	$file = get_template_directory() . '/pwa/' . $rel;
	return get_template_directory_uri() . '/pwa/' . $rel . '?v=' . ( file_exists( $file ) ? filemtime( $file ) : PSB_PANEL_VERSION );
}

/** Cache-busting version of the panel static files. */
function psb_panel_asset_version() {
	$sig = PSB_PANEL_VERSION;
	foreach ( array( 'panel/panel.js', 'panel/panel.css', 'panel/panel-sw.js' ) as $f ) {
		$p    = get_template_directory() . '/pwa/' . $f;
		$sig .= $f . ( file_exists( $p ) ? filemtime( $p ) : '' );
	}
	return substr( md5( $sig ), 0, 10 );
}

/** Local site midnight N days ago as a GMT MySQL datetime. */
function psb_panel_day_start_gmt( $days_back = 0 ) {
	$tz       = wp_timezone();
	$midnight = new DateTime( 'today', $tz );
	if ( $days_back > 0 ) {
		$midnight->modify( '-' . (int) $days_back . ' days' );
	}
	return gmdate( 'Y-m-d H:i:s', $midnight->getTimestamp() );
}

/** MySQL GMT datetime → unix timestamp (0 for empty). */
function psb_panel_ts( $mysql_gmt ) {
	if ( ! $mysql_gmt || 0 === strpos( (string) $mysql_gmt, '0000' ) ) {
		return 0;
	}
	return (int) strtotime( $mysql_gmt . ' UTC' );
}

/** Jalali date for server-rendered places (dashboard widget). JS formats dates itself with Intl. */
function psb_panel_jdate( $ts ) {
	if ( function_exists( 'psb_rfq_jdate' ) ) {
		return psb_rfq_jdate( $ts );
	}
	return wp_date( 'Y-m-d H:i', (int) $ts );
}

/** Persian/Arabic digits → Latin. */
function psb_panel_latin( $s ) {
	return strtr( (string) $s, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9' ) );
}

/** Current session of the logged-in user: token, expiration, login time, count of active sessions. */
function psb_panel_session_info( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	$token   = wp_get_session_token();
	if ( ! $user_id || ! class_exists( 'WP_Session_Tokens' ) ) {
		return array( 'expires' => 0, 'login' => 0, 'count' => 0, 'remember' => false );
	}
	$mgr  = WP_Session_Tokens::get_instance( $user_id );
	$sess = $token ? $mgr->get( $token ) : null;
	$exp  = is_array( $sess ) && isset( $sess['expiration'] ) ? (int) $sess['expiration'] : 0;
	$in   = is_array( $sess ) && isset( $sess['login'] ) ? (int) $sess['login'] : 0;
	return array(
		'expires'  => $exp,
		'login'    => $in,
		'count'    => count( $mgr->get_all() ),
		'remember' => $exp && $in && ( $exp - $in ) > 3 * DAY_IN_SECONDS,
	);
}
