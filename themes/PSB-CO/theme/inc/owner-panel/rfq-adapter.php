<?php
/**
 * Owner panel — adapter for the RFQ module (theme/inc/rfq, CPT psb_rfq).
 *
 * Every meta key and RFQ function the panel relies on lives here, so a change in the RFQ module is a one-file fix.
 * Uses the module's own functions when they are loaded; psb_rfq_set_status()/psb_rfq_add_note() live in
 * inc/rfq/admin.php, which is only loaded in wp-admin, so REST calls fall back to identical logic below
 * (same note format, same `psb_rfq_status_changed` action → notifications keep working).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_PANEL_RFQ_META = array(
	'code'    => '_psb_rfq_code',
	'status'  => '_psb_rfq_status',
	'mode'    => '_psb_rfq_mode',
	'data'    => '_psb_rfq_data',
	'files'   => '_psb_rfq_files',
	'name'    => '_psb_rfq_name',
	'mobile'  => '_psb_rfq_mobile',
	'company' => '_psb_rfq_company',
	'city'    => '_psb_rfq_city',
	'type'    => '_psb_rfq_type',
	'notes'   => '_psb_rfq_notes',   // array of array( t, u, txt, sys ).
	'log'     => '_psb_rfq_log',     // channel => array( ok, msg, t, n ).
);

function psb_panel_rfq_cpt() {
	return defined( 'PSB_RFQ_CPT' ) ? PSB_RFQ_CPT : 'psb_rfq';
}

function psb_panel_rfq_active() {
	return post_type_exists( psb_panel_rfq_cpt() );
}

/** Can the current user see the request list? */
function psb_panel_rfq_can() {
	if ( ! psb_panel_rfq_active() ) {
		return false;
	}
	return current_user_can( defined( 'PSB_RFQ_CAP' ) ? PSB_RFQ_CAP : 'edit_others_posts' ) || current_user_can( 'manage_options' );
}

function psb_panel_rfq_meta( $post_id, $key, $default = '' ) {
	$v = get_post_meta( $post_id, PSB_PANEL_RFQ_META[ $key ], true );
	return ( '' === $v || null === $v ) ? $default : $v;
}

/** key => array( label, color ). */
function psb_panel_rfq_statuses() {
	if ( function_exists( 'psb_rfq_statuses' ) ) {
		return psb_rfq_statuses();
	}
	return array(
		'new'    => array( 'label' => 'جدید', 'color' => '#d97706' ),
		'review' => array( 'label' => 'در حال بررسی', 'color' => '#0284c7' ),
		'quoted' => array( 'label' => 'پیش‌فاکتور ارسال شد', 'color' => '#7c3aed' ),
		'won'    => array( 'label' => 'برنده', 'color' => '#059669' ),
		'lost'   => array( 'label' => 'از دست رفته', 'color' => '#64748b' ),
	);
}

function psb_panel_rfq_status( $post_id ) {
	if ( function_exists( 'psb_rfq_status' ) ) {
		return psb_rfq_status( $post_id );
	}
	$s = psb_panel_rfq_meta( $post_id, 'status', 'new' );
	return isset( psb_panel_rfq_statuses()[ $s ] ) ? $s : 'new';
}

function psb_panel_rfq_add_note( $post_id, $text, $system = false ) {
	if ( function_exists( 'psb_rfq_add_note' ) ) {
		psb_rfq_add_note( $post_id, $text, $system );
		return;
	}
	$notes   = get_post_meta( $post_id, PSB_PANEL_RFQ_META['notes'], true );
	$notes   = is_array( $notes ) ? $notes : array();
	$user    = wp_get_current_user();
	$notes[] = array(
		't'   => time(),
		'u'   => $user && $user->exists() ? $user->display_name : 'سیستم',
		'txt' => $text,
		'sys' => (bool) $system,
	);
	update_post_meta( $post_id, PSB_PANEL_RFQ_META['notes'], $notes );
}

function psb_panel_rfq_set_status( $post_id, $status ) {
	if ( function_exists( 'psb_rfq_set_status' ) ) {
		psb_rfq_set_status( $post_id, $status );
		return;
	}
	$old = psb_panel_rfq_status( $post_id );
	if ( $old === $status ) {
		return;
	}
	update_post_meta( $post_id, PSB_PANEL_RFQ_META['status'], $status );
	$all = psb_panel_rfq_statuses();
	psb_panel_rfq_add_note( $post_id, 'وضعیت: ' . $all[ $old ]['label'] . ' ← ' . $all[ $status ]['label'], true );
	do_action( 'psb_rfq_status_changed', $post_id, $status, $old );
}

/** Structured submitted data. */
function psb_panel_rfq_data( $post_id ) {
	if ( function_exists( 'psb_rfq_get' ) ) {
		return psb_rfq_get( $post_id );
	}
	$d = get_post_meta( $post_id, PSB_PANEL_RFQ_META['data'], true );
	return array_merge( array( 'items' => array(), 'project' => array(), 'contact' => array(), 'files' => array(), 'source' => array() ), is_array( $d ) ? $d : array() );
}

function psb_panel_rfq_type_label( $type ) {
	return function_exists( 'psb_rfq_type_label' ) ? psb_rfq_type_label( $type ) : (string) $type;
}

/** Label + display value of a project/contact field. */
function psb_panel_rfq_field( $section, $key, $value ) {
	$full  = $section . '.' . $key;
	$defs  = function_exists( 'psb_rfq_field_defs' ) ? psb_rfq_field_defs() : array();
	$label = isset( $defs[ $full ]['label'] ) ? $defs[ $full ]['label'] : $key;
	$disp  = function_exists( 'psb_rfq_field_display' ) ? psb_rfq_field_display( $full, $value ) : $value;
	return array( 'label' => $label, 'value' => is_array( $disp ) ? implode( '، ', $disp ) : (string) $disp );
}

function psb_panel_rfq_item_rows( $item ) {
	if ( function_exists( 'psb_rfq_item_spec_rows' ) ) {
		return psb_rfq_item_spec_rows( $item );
	}
	$rows = array();
	foreach ( (array) ( $item['specs'] ?? array() ) as $k => $v ) {
		$rows[ $k ] = is_array( $v ) ? implode( '، ', $v ) : (string) $v;
	}
	return $rows;
}

/**
 * Staff download URL of the n-th file. Served inside the panel scope (/panel/file/…) so the installed app keeps
 * its own login cookie (iOS home-screen apps do not share cookies with Safari); falls back to the RFQ module's
 * nonce'd admin-post handler.
 */
function psb_panel_rfq_file_url( $post_id, $index ) {
	if ( function_exists( 'psb_rfq_file_path' ) ) {
		return wp_nonce_url( psb_panel_url( 'file/' . (int) $post_id . '/' . (int) $index ), 'psb_panel_file_' . (int) $post_id );
	}
	if ( function_exists( 'psb_rfq_file_url' ) ) {
		return psb_rfq_file_url( $post_id, $index );
	}
	return wp_nonce_url( admin_url( 'admin-post.php?action=psb_rfq_file&post=' . (int) $post_id . '&i=' . (int) $index ), 'psb_rfq_file_' . (int) $post_id );
}

/** Stream a request file (router: /panel/file/{post}/{index}?_wpnonce=…). Exits. */
function psb_panel_rfq_send_file( $post_id, $index ) {
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'psb_panel_file_' . (int) $post_id ) || psb_panel_rfq_cpt() !== get_post_type( $post_id ) || ! psb_panel_rfq_can() || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'دسترسی غیرمجاز', 'خطا', array( 'response' => 403 ) );
	}
	$files = get_post_meta( $post_id, PSB_PANEL_RFQ_META['files'], true );
	$f     = is_array( $files ) && isset( $files[ $index ] ) ? $files[ $index ] : null;
	$path  = ( $f && function_exists( 'psb_rfq_file_path' ) ) ? psb_rfq_file_path( $f ) : '';
	if ( ! $path ) {
		wp_die( 'فایل پیدا نشد.', 'خطا', array( 'response' => 404 ) );
	}
	$name  = ! empty( $f['name'] ) ? (string) $f['name'] : (string) $f['file'];
	$ascii = preg_replace( '/[^A-Za-z0-9._-]+/', '_', $name );
	while ( ob_get_level() ) {
		ob_end_clean();
	}
	nocache_headers();
	header( 'Cache-Control: no-store, private', true );
	header( 'Content-Type: application/octet-stream' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Length: ' . filesize( $path ) );
	header( "Content-Disposition: attachment; filename=\"{$ascii}\"; filename*=UTF-8''" . rawurlencode( $name ) );
	readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

function psb_panel_rfq_channel_labels() {
	if ( function_exists( 'psb_rfq_channel_labels' ) ) {
		return psb_rfq_channel_labels();
	}
	return array();
}

/** «اتصال بات‌ها» and «تنظیمات» pages of the RFQ module. */
function psb_panel_rfq_admin_urls() {
	$base = 'edit.php?post_type=' . psb_panel_rfq_cpt();
	return array(
		'list'     => admin_url( $base ),
		'channels' => admin_url( $base . '&page=psb-rfq-channels' ),
		'settings' => admin_url( $base . '&page=psb-rfq-settings' ),
	);
}

/** Normalised mobile (09xxxxxxxxx) and its international form (98…). */
function psb_panel_rfq_phone( $raw ) {
	$m = function_exists( 'psb_rfq_mobile' ) ? psb_rfq_mobile( $raw ) : preg_replace( '/\D+/', '', psb_panel_latin( $raw ) );
	if ( ! $m ) {
		$m = preg_replace( '/[^\d+]/', '', psb_panel_latin( $raw ) );
	}
	$intl = function_exists( 'psb_rfq_intl' ) ? psb_rfq_intl( $m ) : ( preg_match( '/^09\d{9}$/', $m ) ? '98' . substr( $m, 1 ) : ltrim( $m, '+' ) );
	return array( 'local' => $m, 'intl' => $intl );
}

/** Compact list row. */
function psb_panel_rfq_row( $post ) {
	$id    = $post->ID;
	$d     = psb_panel_rfq_data( $id );
	$types = array();
	foreach ( (array) $d['items'] as $item ) {
		$types[] = psb_panel_rfq_type_label( $item['type'] ?? '' ) . ' ×' . (int) ( $item['qty'] ?? 1 );
	}
	$files = psb_panel_rfq_meta( $id, 'files', array() );
	return array(
		'id'      => $id,
		'code'    => (string) psb_panel_rfq_meta( $id, 'code' ),
		'name'    => (string) psb_panel_rfq_meta( $id, 'name' ),
		'company' => (string) psb_panel_rfq_meta( $id, 'company' ),
		'city'    => (string) psb_panel_rfq_meta( $id, 'city' ),
		'mobile'  => (string) psb_panel_rfq_meta( $id, 'mobile' ),
		'mode'    => (string) psb_panel_rfq_meta( $id, 'mode', 'full' ),
		'status'  => psb_panel_rfq_status( $id ),
		'summary' => implode( '، ', $types ),
		'files'   => is_array( $files ) ? count( $files ) : 0,
		'ts'      => psb_panel_ts( $post->post_date_gmt ),
	);
}

/** Full detail for the panel. */
function psb_panel_rfq_detail( $post ) {
	$id   = $post->ID;
	$d    = psb_panel_rfq_data( $id );
	$row  = psb_panel_rfq_row( $post );
	$out  = $row;
	$sect = array();
	foreach ( array( 'contact' => 'اطلاعات تماس', 'project' => 'پروژه' ) as $sec => $title ) {
		$rows = array();
		foreach ( (array) $d[ $sec ] as $k => $v ) {
			if ( '' === $v || null === $v || array() === $v ) {
				continue;
			}
			$rows[] = psb_panel_rfq_field( $sec, $k, $v );
		}
		$sect[] = array( 'title' => $title, 'rows' => $rows );
	}
	$items = array();
	foreach ( (array) $d['items'] as $item ) {
		$specs = array();
		foreach ( psb_panel_rfq_item_rows( $item ) as $label => $value ) {
			$specs[] = array( 'label' => (string) $label, 'value' => (string) $value );
		}
		$items[] = array(
			'type'  => psb_panel_rfq_type_label( $item['type'] ?? '' ),
			'qty'   => (int) ( $item['qty'] ?? 1 ),
			'specs' => $specs,
		);
	}
	$files = array();
	foreach ( (array) psb_panel_rfq_meta( $id, 'files', array() ) as $i => $f ) {
		$files[] = array(
			'name' => (string) ( $f['name'] ?? ( $f['file'] ?? 'فایل' ) ),
			'size' => (int) ( $f['size'] ?? 0 ),
			'url'  => psb_panel_rfq_file_url( $id, $i ),
		);
	}
	$notes = array();
	foreach ( array_reverse( (array) psb_panel_rfq_meta( $id, 'notes', array() ) ) as $n ) {
		if ( ! is_array( $n ) ) {
			continue;
		}
		$notes[] = array(
			'ts'   => (int) ( $n['t'] ?? 0 ),
			'by'   => (string) ( $n['u'] ?? '' ),
			'text' => (string) ( $n['txt'] ?? '' ),
			'sys'  => ! empty( $n['sys'] ),
		);
	}
	$log    = array();
	$labels = psb_panel_rfq_channel_labels();
	foreach ( (array) psb_panel_rfq_meta( $id, 'log', array() ) as $ch => $l ) {
		if ( is_array( $l ) ) {
			$log[] = array( 'channel' => $labels[ $ch ] ?? (string) $ch, 'ok' => ! empty( $l['ok'] ), 'ts' => (int) ( $l['t'] ?? 0 ) );
		}
	}
	$contact  = (array) $d['contact'];
	$phone    = psb_panel_rfq_phone( $contact['mobile'] ?? $row['mobile'] );
	$username = isset( $contact['username'] ) ? preg_replace( '/[^A-Za-z0-9_.]/', '', (string) $contact['username'] ) : '';
	$src      = array_filter( array_map( 'strval', (array) $d['source'] ) );

	$out['sections'] = $sect;
	$out['items']    = $items;
	$out['fileList'] = $files;
	$out['notes']    = $notes;
	$out['log']      = $log;
	$out['source']   = $src;
	$out['channel']  = (string) ( $contact['channel'] ?? '' );
	$out['email']    = (string) ( $contact['email'] ?? '' );
	$out['phone']    = $phone['local'];
	$out['intl']     = $phone['intl'];
	$out['username'] = $username;
	$out['edit']     = admin_url( 'post.php?post=' . $id . '&action=edit' );
	return $out;
}
