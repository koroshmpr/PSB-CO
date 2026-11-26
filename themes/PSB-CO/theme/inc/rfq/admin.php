<?php
/**
 * RFQ — admin: list table, filters, search, detail meta boxes, notes, resend, CSV export, dashboard widget.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Status pill HTML. */
function psb_rfq_status_pill( $status ) {
	$all = psb_rfq_statuses();
	$st  = isset( $all[ $status ] ) ? $all[ $status ] : $all['new'];
	return sprintf( '<span class="psb-rfq-pill" style="--c:%1$s">%2$s</span>', esc_attr( $st['color'] ), esc_html( $st['label'] ) );
}

/** Shared admin CSS (tiny, inline). */
add_action(
	'admin_head',
	static function () {
		$screen = get_current_screen();
		if ( ! $screen || ( PSB_RFQ_CPT !== $screen->post_type && 'dashboard' !== $screen->id && false === strpos( (string) $screen->id, 'psb-rfq' ) ) ) {
			return;
		}
		?>
		<style>
			.psb-rfq-pill{display:inline-block;padding:2px 10px;border-radius:999px;font-weight:700;font-size:12px;color:var(--c);background:color-mix(in srgb,var(--c) 14%,#fff);border:1px solid color-mix(in srgb,var(--c) 35%,#fff);white-space:nowrap}
			.psb-rfq-tbl{width:100%;border-collapse:collapse;margin:0 0 14px}
			.psb-rfq-tbl th,.psb-rfq-tbl td{padding:7px 10px;border-bottom:1px solid #eef0f3;text-align:start;vertical-align:top}
			.psb-rfq-tbl th{width:190px;color:#50575e;font-weight:600}
			.psb-rfq-h{margin:18px 0 6px;font-size:14px;border-bottom:2px solid #1a3da3;padding-bottom:4px;display:inline-block}
			.psb-rfq-item{border:1px solid #dcdcde;border-radius:8px;padding:10px 12px;margin:0 0 10px;background:#fbfbfc}
			.psb-rfq-item h4{margin:0 0 6px}
			.psb-rfq-ltr{direction:ltr;unicode-bidi:embed}
			.psb-rfq-log li{border-bottom:1px solid #f0f0f1;padding:6px 0;margin:0}
			.psb-rfq-log .ok{color:#008a20;font-weight:700}.psb-rfq-log .bad{color:#b32d2e;font-weight:700}
			.psb-rfq-note{background:#f6f7f7;border-radius:6px;padding:8px 10px;margin:0 0 8px;white-space:pre-wrap}
			.psb-rfq-note small{display:block;color:#646970;margin-bottom:2px}
			.psb-rfq-counts{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin:0 0 12px}
			.psb-rfq-counts a{display:block;text-align:center;border:1px solid #dcdcde;border-radius:8px;padding:8px 4px;text-decoration:none}
			.psb-rfq-counts b{display:block;font-size:20px;line-height:1.3}
			.column-psb_code{width:130px}.column-psb_status{width:140px}.column-psb_mobile{width:120px}
		</style>
		<?php
	}
);

/* -------------------------------------------------------------------------
 * List table
 * ---------------------------------------------------------------------- */

add_filter(
	'manage_' . PSB_RFQ_CPT . '_posts_columns',
	static function () {
		return array(
			'cb'          => '<input type="checkbox" />',
			'psb_code'    => 'کد پیگیری',
			'title'       => 'مشتری',
			'psb_company' => 'شرکت',
			'psb_mobile'  => 'موبایل',
			'psb_types'   => 'نوع تابلو',
			'psb_city'    => 'شهر',
			'psb_status'  => 'وضعیت',
			'date'        => 'تاریخ',
		);
	}
);

add_action(
	'manage_' . PSB_RFQ_CPT . '_posts_custom_column',
	static function ( $col, $post_id ) {
		switch ( $col ) {
			case 'psb_code':
				echo '<code>' . esc_html( get_post_meta( $post_id, '_psb_rfq_code', true ) ) . '</code>';
				if ( 'quick' === get_post_meta( $post_id, '_psb_rfq_mode', true ) ) {
					echo '<br><small>درخواست سریع</small>';
				}
				$log = get_post_meta( $post_id, '_psb_rfq_log', true );
				if ( is_array( $log ) ) {
					$bad = array_keys( array_filter( $log, static function ( $l ) {
						return empty( $l['ok'] );
					} ) );
					if ( $bad ) {
						$labels = psb_rfq_channel_labels();
						echo '<br><small style="color:#b32d2e">ناموفق: ' . esc_html( implode( '، ', array_map( static function ( $c ) use ( $labels ) {
							return isset( $labels[ $c ] ) ? $labels[ $c ] : $c;
						}, $bad ) ) ) . '</small>';
					}
				}
				break;
			case 'psb_company':
				echo esc_html( get_post_meta( $post_id, '_psb_rfq_company', true ) ?: '—' );
				break;
			case 'psb_mobile':
				$m = get_post_meta( $post_id, '_psb_rfq_mobile', true );
				if ( $m ) {
					echo '<a class="psb-rfq-ltr" href="' . esc_url( 'tel:' . $m ) . '">' . esc_html( $m ) . '</a>';
				}
				break;
			case 'psb_types':
				$d = psb_rfq_get( $post_id );
				echo esc_html( psb_rfq_items_summary( $d['items'] ) );
				$n = count( (array) get_post_meta( $post_id, '_psb_rfq_files', true ) );
				if ( $n && get_post_meta( $post_id, '_psb_rfq_files', true ) ) {
					echo '<br><small>' . esc_html( psb_rfq_fa( $n ) . ' فایل پیوست' ) . '</small>';
				}
				break;
			case 'psb_city':
				echo esc_html( get_post_meta( $post_id, '_psb_rfq_city', true ) ?: '—' );
				break;
			case 'psb_status':
				echo psb_rfq_status_pill( psb_rfq_status( $post_id ) ); // phpcs:ignore -- escaped in helper.
				break;
		}
	},
	10,
	2
);

// Jalali date in the date column.
add_filter(
	'post_date_column_time',
	static function ( $time, $post ) {
		return PSB_RFQ_CPT === get_post_type( $post ) ? esc_html( psb_rfq_jdate( get_post_time( 'U', true, $post ) ) ) : $time;
	},
	10,
	2
);
add_filter(
	'post_date_column_status',
	static function ( $status, $post ) {
		return PSB_RFQ_CPT === get_post_type( $post ) ? '' : $status;
	},
	10,
	2
);

// No quick edit; add "WhatsApp" row action.
add_filter(
	'post_row_actions',
	static function ( $actions, $post ) {
		if ( PSB_RFQ_CPT !== $post->post_type ) {
			return $actions;
		}
		unset( $actions['inline hide-if-no-js'], $actions['view'] );
		$m = get_post_meta( $post->ID, '_psb_rfq_mobile', true );
		if ( $m ) {
			$actions['psb_wa'] = '<a target="_blank" rel="noopener" href="' . esc_url( 'https://wa.me/' . psb_rfq_intl( $m ) ) . '">واتساپ</a>';
		}
		return $actions;
	},
	10,
	2
);

// Status views with counts.
add_filter(
	'views_edit-' . PSB_RFQ_CPT,
	static function ( $views ) {
		$counts  = psb_rfq_status_counts();
		$current = isset( $_GET['psb_status'] ) ? sanitize_key( $_GET['psb_status'] ) : ''; // phpcs:ignore
		foreach ( psb_rfq_statuses() as $key => $st ) {
			$url             = add_query_arg( array( 'post_type' => PSB_RFQ_CPT, 'psb_status' => $key ), admin_url( 'edit.php' ) );
			$views[ 'psb_' . $key ] = sprintf( '<a href="%s"%s>%s <span class="count">(%s)</span></a>', esc_url( $url ), $current === $key ? ' class="current" aria-current="page"' : '', esc_html( $st['label'] ), esc_html( psb_rfq_fa( isset( $counts[ $key ] ) ? $counts[ $key ] : 0 ) ) );
		}
		return $views;
	}
);

/** Count per status (published requests). */
function psb_rfq_status_counts() {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT pm.meta_value s, COUNT(*) n FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_psb_rfq_status' AND p.post_type = %s AND p.post_status = 'publish' GROUP BY pm.meta_value", PSB_RFQ_CPT ) ); // phpcs:ignore
	$out  = array();
	foreach ( (array) $rows as $r ) {
		$out[ $r->s ] = (int) $r->n;
	}
	return $out;
}

// Filters + export button.
add_action(
	'restrict_manage_posts',
	static function ( $post_type, $which ) {
		if ( PSB_RFQ_CPT !== $post_type || 'top' !== $which ) {
			return;
		}
		$status = isset( $_GET['psb_status'] ) ? sanitize_key( $_GET['psb_status'] ) : ''; // phpcs:ignore
		$type   = isset( $_GET['psb_type'] ) ? sanitize_key( $_GET['psb_type'] ) : ''; // phpcs:ignore
		echo '<select name="psb_status"><option value="">همه وضعیت‌ها</option>';
		foreach ( psb_rfq_statuses() as $k => $st ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $status, $k, false ), esc_html( $st['label'] ) );
		}
		echo '</select><select name="psb_type"><option value="">همه انواع تابلو</option>';
		foreach ( psb_rfq_types() as $k => $t ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $type, $k, false ), esc_html( $t['label'] ) );
		}
		echo '</select>';
		$export = wp_nonce_url(
			add_query_arg(
				array(
					'action'     => 'psb_rfq_export',
					'psb_status' => $status,
					'psb_type'   => $type,
					'm'          => isset( $_GET['m'] ) ? absint( $_GET['m'] ) : '', // phpcs:ignore
				),
				admin_url( 'admin-post.php' )
			),
			'psb_rfq_export'
		);
		echo '<a class="button" style="margin-inline-end:6px" href="' . esc_url( $export ) . '">خروجی CSV (اکسل)</a>';
	},
	10,
	2
);

/** Apply list filters + digit-normalised search. */
add_action(
	'pre_get_posts',
	static function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || PSB_RFQ_CPT !== $q->get( 'post_type' ) ) {
			return;
		}
		$mq = psb_rfq_filter_meta_query( $_GET ); // phpcs:ignore
		if ( $mq ) {
			$q->set( 'meta_query', $mq );
		}
		$s = $q->get( 's' );
		if ( $s ) {
			$s = psb_rfq_latin( $s );
			if ( preg_match( '/^[\d\s\-+()]+$/', $s ) ) {
				$digits = preg_replace( '/\D+/', '', $s );
				$s      = psb_rfq_mobile( $digits ) ? psb_rfq_mobile( $digits ) : $digits;
			}
			$q->set( 's', $s );
		}
	}
);

/** meta_query from request filters. */
function psb_rfq_filter_meta_query( $src ) {
	$mq     = array();
	$status = isset( $src['psb_status'] ) ? sanitize_key( $src['psb_status'] ) : '';
	$type   = isset( $src['psb_type'] ) ? sanitize_key( $src['psb_type'] ) : '';
	if ( $status && isset( psb_rfq_statuses()[ $status ] ) ) {
		$mq[] = array( 'key' => '_psb_rfq_status', 'value' => $status );
	}
	if ( $type ) {
		$mq[] = array( 'key' => '_psb_rfq_type', 'value' => $type );
	}
	return $mq;
}

// Bulk status change.
add_filter(
	'bulk_actions-edit-' . PSB_RFQ_CPT,
	static function ( $actions ) {
		unset( $actions['edit'] );
		foreach ( psb_rfq_statuses() as $k => $st ) {
			$actions[ 'psb_status_' . $k ] = 'تغییر وضعیت به: ' . $st['label'];
		}
		return $actions;
	}
);
add_filter(
	'handle_bulk_actions-edit-' . PSB_RFQ_CPT,
	static function ( $redirect, $action, $ids ) {
		if ( 0 !== strpos( $action, 'psb_status_' ) ) {
			return $redirect;
		}
		$status = substr( $action, 11 );
		if ( ! isset( psb_rfq_statuses()[ $status ] ) ) {
			return $redirect;
		}
		$n = 0;
		foreach ( $ids as $id ) {
			if ( current_user_can( 'edit_post', $id ) ) {
				psb_rfq_set_status( $id, $status );
				$n++;
			}
		}
		return add_query_arg( 'psb_bulk', $n, $redirect );
	},
	10,
	3
);

/** Change status and record it in the notes timeline. */
function psb_rfq_set_status( $post_id, $status ) {
	$old = psb_rfq_status( $post_id );
	if ( $old === $status ) {
		return;
	}
	update_post_meta( $post_id, '_psb_rfq_status', $status );
	$all = psb_rfq_statuses();
	psb_rfq_add_note( $post_id, 'وضعیت: ' . $all[ $old ]['label'] . ' ← ' . $all[ $status ]['label'], true );
	do_action( 'psb_rfq_status_changed', $post_id, $status, $old );
}

/** Append an internal note. */
function psb_rfq_add_note( $post_id, $text, $system = false ) {
	$notes   = get_post_meta( $post_id, '_psb_rfq_notes', true );
	$notes   = is_array( $notes ) ? $notes : array();
	$user    = wp_get_current_user();
	$notes[] = array(
		't'   => time(),
		'u'   => $user && $user->exists() ? $user->display_name : 'سیستم',
		'txt' => $text,
		'sys' => $system,
	);
	update_post_meta( $post_id, '_psb_rfq_notes', $notes );
}

/* -------------------------------------------------------------------------
 * Edit screen
 * ---------------------------------------------------------------------- */

add_action(
	'add_meta_boxes_' . PSB_RFQ_CPT,
	static function () {
		remove_meta_box( 'slugdiv', PSB_RFQ_CPT, 'normal' );
		add_meta_box( 'psb_rfq_details', 'جزئیات درخواست', 'psb_rfq_box_details', PSB_RFQ_CPT, 'normal', 'high' );
		add_meta_box( 'psb_rfq_notes', 'یادداشت‌های داخلی و تاریخچه', 'psb_rfq_box_notes', PSB_RFQ_CPT, 'normal', 'default' );
		add_meta_box( 'psb_rfq_status', 'وضعیت پیگیری', 'psb_rfq_box_status', PSB_RFQ_CPT, 'side', 'high' );
		add_meta_box( 'psb_rfq_log', 'وضعیت ارسال اعلان‌ها', 'psb_rfq_box_log', PSB_RFQ_CPT, 'side', 'default' );
	}
);

/** Table of label => (already escaped HTML) rows. */
function psb_rfq_rows_html( $rows ) {
	$rows = array_filter( $rows, 'strlen' );
	if ( ! $rows ) {
		return '';
	}
	$out = '<table class="psb-rfq-tbl"><tbody>';
	foreach ( $rows as $label => $html ) {
		$out .= '<tr><th>' . esc_html( $label ) . '</th><td>' . $html . '</td></tr>';
	}
	return $out . '</tbody></table>';
}

function psb_rfq_box_details( $post ) {
	$d   = psb_rfq_get( $post->ID );
	$c   = $d['contact'];
	$pr  = $d['project'];
	$e   = 'esc_html';
	$chs = psb_rfq_contact_channels();

	$m        = isset( $c['mobile'] ) ? $c['mobile'] : '';
	$contact  = array(
		'کد پیگیری'       => '<code style="font-size:14px">' . $e( $d['code'] ) . '</code>' . ( 'quick' === $d['mode'] ? ' — درخواست سریع' : '' ),
		'نام'             => $e( isset( $c['name'] ) ? $c['name'] : '' ),
		'شرکت / سازمان'   => $e( isset( $c['company'] ) ? $c['company'] : '' ),
		'سمت'             => $e( isset( $c['position'] ) ? $c['position'] : '' ),
		'موبایل'          => $m ? '<a class="psb-rfq-ltr" href="' . esc_url( 'tel:' . $m ) . '">' . $e( $m ) . '</a> · <a target="_blank" rel="noopener" href="' . esc_url( 'https://wa.me/' . psb_rfq_intl( $m ) ) . '">واتساپ</a>' : '',
		'ایمیل'           => ! empty( $c['email'] ) ? '<a href="' . esc_url( 'mailto:' . $c['email'] ) . '">' . $e( $c['email'] ) . '</a>' : '',
		'ارتباط ترجیحی'   => ! empty( $c['channel'] ) ? $e( isset( $chs[ $c['channel'] ] ) ? $chs[ $c['channel'] ] : $c['channel'] ) : '',
		'نام کاربری'      => '',
		'آیدی بله'        => ! empty( $c['bale'] ) ? '<a class="psb-rfq-ltr" target="_blank" rel="noopener" href="' . esc_url( 'https://ble.ir/' . $c['bale'] ) . '">@' . $e( $c['bale'] ) . '</a>' : '',
	);
	if ( ! empty( $c['username'] ) ) {
		$u                     = $c['username'];
		$link                  = ( isset( $c['channel'] ) && 'bale' === $c['channel'] ) ? 'https://ble.ir/' . $u : 'https://t.me/' . $u;
		$contact['نام کاربری'] = '<a class="psb-rfq-ltr" target="_blank" rel="noopener" href="' . esc_url( $link ) . '">@' . $e( $u ) . '</a>';
	}
	echo '<h3 class="psb-rfq-h">مشتری</h3>' . psb_rfq_rows_html( $contact ); // phpcs:ignore

	echo '<h3 class="psb-rfq-h">اقلام درخواست</h3>';
	foreach ( $d['items'] as $i => $item ) {
		$rows = array();
		foreach ( psb_rfq_item_spec_rows( $item ) as $label => $value ) {
			$rows[ $label ] = nl2br( $e( $value ) );
		}
		echo '<div class="psb-rfq-item"><h4>' . esc_html( psb_rfq_fa( $i + 1 ) . '. ' . psb_rfq_type_label( $item['type'] ) . ' — تعداد: ' . psb_rfq_fa( (int) $item['qty'] ) ) . '</h4>';
		echo $rows ? psb_rfq_rows_html( $rows ) : '<em>مشخصات فنی وارد نشده است.</em>'; // phpcs:ignore
		echo '</div>';
	}

	$prow = array();
	foreach ( psb_rfq_field_defs() as $key => $def ) {
		if ( 0 !== strpos( $key, 'project.' ) ) {
			continue;
		}
		$name = substr( $key, 8 );
		if ( ! empty( $pr[ $name ] ) ) {
			$prow[ $def['label'] ] = nl2br( $e( psb_rfq_field_display( $key, $pr[ $name ] ) ) );
		}
	}
	if ( $prow ) {
		echo '<h3 class="psb-rfq-h">پروژه</h3>' . psb_rfq_rows_html( $prow ); // phpcs:ignore
	}

	echo '<h3 class="psb-rfq-h">مدارک</h3>';
	$files = get_post_meta( $post->ID, '_psb_rfq_files', true );
	if ( is_array( $files ) && $files ) {
		echo '<ul>';
		foreach ( $files as $i => $f ) {
			$exists = (bool) psb_rfq_file_path( $f );
			echo '<li>' . ( $exists ? '<a class="button button-small" href="' . esc_url( psb_rfq_file_url( $post->ID, $i ) ) . '">دانلود</a> ' : '<em>(فایل حذف شده)</em> ' ) . esc_html( $f['name'] ) . ' <small>(' . esc_html( psb_rfq_size( $f['size'] ) ) . ')</small></li>';
		}
		echo '</ul>';
	} else {
		echo '<p><em>فایلی پیوست نشده است.</em></p>';
	}

	$s     = $d['source'];
	$extra = array(
		'زمان ثبت'     => $e( psb_rfq_jdate( get_post_time( 'U', true, $post ) ) ),
		'صفحه ثبت'     => ! empty( $s['page'] ) ? '<a target="_blank" rel="noopener" href="' . esc_url( $s['page'] ) . '">' . $e( urldecode( $s['page'] ) ) . '</a>' : '',
		'محصول'        => $e( isset( $s['product'] ) ? $s['product'] : '' ),
		'ارجاع از'     => ! empty( $s['referrer'] ) ? $e( urldecode( $s['referrer'] ) ) : '',
		'UTM'          => $e( isset( $s['utm'] ) ? $s['utm'] : '' ),
		'IP / مرورگر'  => '<span class="psb-rfq-ltr">' . $e( get_post_meta( $post->ID, '_psb_rfq_ip', true ) ) . '</span><br><small>' . $e( get_post_meta( $post->ID, '_psb_rfq_ua', true ) ) . '</small>',
	);
	echo '<h3 class="psb-rfq-h">اطلاعات ثبت</h3>' . psb_rfq_rows_html( $extra ); // phpcs:ignore
}

function psb_rfq_box_status( $post ) {
	wp_nonce_field( 'psb_rfq_save', 'psb_rfq_nonce' );
	$current = psb_rfq_status( $post->ID );
	echo '<p>' . psb_rfq_status_pill( $current ) . '</p>'; // phpcs:ignore
	echo '<label for="psb_rfq_status_sel" class="screen-reader-text">وضعیت</label><select id="psb_rfq_status_sel" name="psb_rfq_status" style="width:100%">';
	foreach ( psb_rfq_statuses() as $k => $st ) {
		printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $current, $k, false ), esc_html( $st['label'] ) );
	}
	echo '</select><p class="description">با دکمه «به‌روزرسانی» ذخیره می‌شود.</p>';
}

function psb_rfq_box_notes( $post ) {
	$notes = get_post_meta( $post->ID, '_psb_rfq_notes', true );
	echo '<p><label for="psb_rfq_note"><strong>یادداشت جدید</strong> (فقط برای همکاران قابل مشاهده است)</label><textarea id="psb_rfq_note" name="psb_rfq_note" rows="3" style="width:100%" placeholder="مثلاً: تماس گرفته شد، نقشه نهایی تا شنبه ارسال می‌شود …"></textarea></p>';
	if ( is_array( $notes ) && $notes ) {
		foreach ( array_reverse( $notes ) as $n ) {
			echo '<div class="psb-rfq-note"' . ( ! empty( $n['sys'] ) ? ' style="background:#fff;border:1px dashed #dcdcde"' : '' ) . '><small>' . esc_html( $n['u'] . ' — ' . psb_rfq_jdate( $n['t'] ) ) . '</small>' . esc_html( $n['txt'] ) . '</div>';
		}
	}
}

function psb_rfq_box_log( $post ) {
	$log    = get_post_meta( $post->ID, '_psb_rfq_log', true );
	$log    = is_array( $log ) ? $log : array();
	$labels = psb_rfq_channel_labels();
	$active = psb_rfq_enabled_channels();
	$base   = admin_url( 'admin-post.php?action=psb_rfq_resend&post=' . $post->ID );
	if ( ! $log && ! $active ) {
		echo '<p>هیچ کانال اعلانی فعال نیست. <a href="' . esc_url( psb_rfq_settings_url( 'psb-rfq-channels' ) ) . '">اتصال بات‌ها</a></p>';
		return;
	}
	if ( ! $log ) {
		echo '<p><em>' . ( wp_next_scheduled( 'psb_rfq_notify', array( $post->ID ) ) ? 'در صف ارسال …' : 'هنوز ارسالی ثبت نشده است.' ) . '</em></p>';
	}
	echo '<ul class="psb-rfq-log">';
	foreach ( array_unique( array_merge( array_keys( $log ), $active ) ) as $ch ) {
		if ( ! isset( $labels[ $ch ] ) ) {
			continue;
		}
		$l = isset( $log[ $ch ] ) ? $log[ $ch ] : null;
		echo '<li><strong>' . esc_html( $labels[ $ch ] ) . '</strong> ';
		if ( $l ) {
			echo '<span class="' . ( $l['ok'] ? 'ok">موفق' : 'bad">ناموفق' ) . '</span><br><small>' . esc_html( $l['msg'] ) . '<br>' . esc_html( psb_rfq_jdate( $l['t'] ) . ( $l['n'] > 1 ? ' — تلاش ' . psb_rfq_fa( $l['n'] ) : '' ) ) . '</small><br>';
		} else {
			echo '<small>ارسال نشده</small><br>';
		}
		echo '<a class="button button-small" href="' . esc_url( wp_nonce_url( $base . '&ch=' . $ch, 'psb_rfq_resend_' . $post->ID ) ) . '">ارسال مجدد</a></li>';
	}
	echo '</ul>';
	if ( $active ) {
		echo '<p><a class="button" href="' . esc_url( wp_nonce_url( $base . '&ch=all', 'psb_rfq_resend_' . $post->ID ) ) . '">ارسال مجدد به همه کانال‌های فعال</a></p>';
	}
}

add_action(
	'save_post_' . PSB_RFQ_CPT,
	static function ( $post_id ) {
		if ( ! isset( $_POST['psb_rfq_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['psb_rfq_nonce'] ) ), 'psb_rfq_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$status = isset( $_POST['psb_rfq_status'] ) ? sanitize_key( $_POST['psb_rfq_status'] ) : '';
		if ( isset( psb_rfq_statuses()[ $status ] ) ) {
			psb_rfq_set_status( $post_id, $status );
		}
		$note = isset( $_POST['psb_rfq_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['psb_rfq_note'] ) ) : '';
		if ( '' !== trim( $note ) ) {
			psb_rfq_add_note( $post_id, mb_substr( $note, 0, 3000 ) );
		}
	}
);

// Resend.
add_action(
	'admin_post_psb_rfq_resend',
	static function () {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( 'psb_rfq_resend_' . $post_id );
		if ( ! $post_id || PSB_RFQ_CPT !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( 'دسترسی غیرمجاز', 403 );
		}
		$ch = isset( $_GET['ch'] ) ? sanitize_key( $_GET['ch'] ) : 'all';
		psb_rfq_dispatch( $post_id, 'all' === $ch ? null : $ch, true );
		if ( 'all' === $ch ) {
			foreach ( psb_rfq_enabled_channels() as $c ) {
				psb_rfq_reset_breaker( psb_rfq_channel_url( $c ) );
			}
		}
		wp_safe_redirect( add_query_arg( 'psb_resent', 1, psb_rfq_edit_url( $post_id ) ) );
		exit;
	}
);

add_action(
	'admin_notices',
	static function () {
		$screen = get_current_screen();
		if ( ! $screen || PSB_RFQ_CPT !== $screen->post_type ) {
			return;
		}
		if ( isset( $_GET['psb_resent'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-info is-dismissible"><p>ارسال مجدد انجام شد. نتیجه هر کانال در کادر «وضعیت ارسال اعلان‌ها» آمده است.</p></div>';
		}
		if ( isset( $_GET['psb_bulk'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( 'وضعیت ' . psb_rfq_fa( absint( $_GET['psb_bulk'] ) ) . ' درخواست تغییر کرد.' ) . '</p></div>'; // phpcs:ignore
		}
	}
);

/* -------------------------------------------------------------------------
 * CSV export
 * ---------------------------------------------------------------------- */

add_action(
	'admin_post_psb_rfq_export',
	static function () {
		check_admin_referer( 'psb_rfq_export' );
		if ( ! current_user_can( PSB_RFQ_CAP ) ) {
			wp_die( 'دسترسی غیرمجاز', 403 );
		}
		$args = array(
			'post_type'      => PSB_RFQ_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 5000,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'fields'         => 'ids',
			'meta_query'     => psb_rfq_filter_meta_query( $_GET ), // phpcs:ignore
		);
		$m = isset( $_GET['m'] ) ? absint( $_GET['m'] ) : 0; // phpcs:ignore
		if ( $m ) {
			$args['m'] = $m;
		}
		$ids = get_posts( $args );

		$pdefs = array_filter(
			psb_rfq_field_defs(),
			static function ( $d, $k ) {
				return 0 === strpos( $k, 'project.' );
			},
			ARRAY_FILTER_USE_BOTH
		);
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="rfq-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore -- BOM for Excel.
		$head = array( 'کد پیگیری', 'تاریخ', 'وضعیت', 'نوع', 'نام', 'شرکت', 'سمت', 'موبایل', 'ایمیل', 'ارتباط ترجیحی', 'نام کاربری', 'اقلام', 'مشخصات فنی' );
		foreach ( $pdefs as $def ) {
			$head[] = $def['label'];
		}
		$head = array_merge( $head, array( 'تعداد فایل', 'صفحه ثبت', 'لینک پنل' ) );
		fputcsv( $out, $head );
		$st  = psb_rfq_statuses();
		$chs = psb_rfq_contact_channels();
		foreach ( $ids as $id ) {
			$d     = psb_rfq_get( $id );
			$c     = $d['contact'];
			$specs = array();
			foreach ( $d['items'] as $i => $item ) {
				$rows = array();
				foreach ( psb_rfq_item_spec_rows( $item ) as $l => $v ) {
					$rows[] = $l . ': ' . $v;
				}
				$specs[] = ( $i + 1 ) . ') ' . psb_rfq_type_label( $item['type'] ) . ( $rows ? ' — ' . implode( ' | ', $rows ) : '' );
			}
			$row = array(
				$d['code'],
				psb_rfq_jdate( get_post_time( 'U', true, $id ), true, false ),
				$st[ psb_rfq_status( $id ) ]['label'],
				'quick' === $d['mode'] ? 'سریع' : 'کامل',
				isset( $c['name'] ) ? $c['name'] : '',
				isset( $c['company'] ) ? $c['company'] : '',
				isset( $c['position'] ) ? $c['position'] : '',
				isset( $c['mobile'] ) ? $c['mobile'] : '',
				isset( $c['email'] ) ? $c['email'] : '',
				! empty( $c['channel'] ) && isset( $chs[ $c['channel'] ] ) ? $chs[ $c['channel'] ] : '',
				isset( $c['username'] ) ? $c['username'] : '',
				psb_rfq_items_summary( $d['items'] ),
				implode( "\n", $specs ),
			);
			foreach ( array_keys( $pdefs ) as $key ) {
				$name  = substr( $key, 8 );
				$row[] = isset( $d['project'][ $name ] ) ? psb_rfq_field_display( $key, $d['project'][ $name ] ) : '';
			}
			$row[] = count( (array) $d['files'] );
			$row[] = isset( $d['source']['page'] ) ? urldecode( $d['source']['page'] ) : '';
			$row[] = psb_rfq_edit_url( $id );
			// Neutralise spreadsheet formulas.
			$row = array_map(
				static function ( $v ) {
					$v = (string) $v;
					return ( '' !== $v && in_array( $v[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) ? "'" . $v : $v; // Security review L2: no numeric exception.
				},
				$row
			);
			fputcsv( $out, $row );
		}
		fclose( $out ); // phpcs:ignore
		exit;
	}
);

/* -------------------------------------------------------------------------
 * Menu badge + dashboard widget
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	static function () {
		global $menu;
		if ( ! current_user_can( PSB_RFQ_CAP ) || ! is_array( $menu ) ) {
			return;
		}
		$counts = psb_rfq_status_counts();
		$n      = isset( $counts['new'] ) ? (int) $counts['new'] : 0;
		if ( ! $n ) {
			return;
		}
		foreach ( $menu as $i => $item ) {
			if ( isset( $item[2] ) && 'edit.php?post_type=' . PSB_RFQ_CPT === $item[2] ) {
				$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>'; // phpcs:ignore
				break;
			}
		}
	},
	99
);

add_action(
	'wp_dashboard_setup',
	static function () {
		if ( ! current_user_can( PSB_RFQ_CAP ) ) {
			return;
		}
		wp_add_dashboard_widget( 'psb_rfq_widget', 'استعلام‌های قیمت', 'psb_rfq_dashboard_widget' );
		// Move to the top of the first column.
		global $wp_meta_boxes;
		if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['psb_rfq_widget'] ) ) {
			$w = array( 'psb_rfq_widget' => $wp_meta_boxes['dashboard']['normal']['core']['psb_rfq_widget'] );
			unset( $wp_meta_boxes['dashboard']['normal']['core']['psb_rfq_widget'] );
			$wp_meta_boxes['dashboard']['normal']['core'] = array_merge( $w, $wp_meta_boxes['dashboard']['normal']['core'] ); // phpcs:ignore
		}
	}
);

function psb_rfq_dashboard_widget() {
	$counts = psb_rfq_status_counts();
	echo '<div class="psb-rfq-counts">';
	foreach ( psb_rfq_statuses() as $k => $st ) {
		printf(
			'<a href="%s" style="color:%s"><b>%s</b>%s</a>',
			esc_url( admin_url( 'edit.php?post_type=' . PSB_RFQ_CPT . '&psb_status=' . $k ) ),
			esc_attr( $st['color'] ),
			esc_html( psb_rfq_fa( isset( $counts[ $k ] ) ? $counts[ $k ] : 0 ) ),
			esc_html( $st['label'] )
		);
	}
	echo '</div>';
	$week = new WP_Query(
		array(
			'post_type'      => PSB_RFQ_CPT,
			'post_status'    => 'publish',
			'date_query'     => array( array( 'after' => '7 days ago' ) ),
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);
	echo '<p>' . esc_html( 'در ۷ روز گذشته: ' . psb_rfq_fa( (int) $week->found_posts ) . ' درخواست' ) . '</p>';
	if ( ! psb_rfq_enabled_channels() ) {
		echo '<p style="color:#b45309">هیچ کانال اعلانی فعال نیست. <a href="' . esc_url( psb_rfq_settings_url( 'psb-rfq-channels' ) ) . '">اتصال بات‌ها</a></p>';
	}
	$q = new WP_Query(
		array(
			'post_type'      => PSB_RFQ_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			'no_found_rows'  => true,
		)
	);
	if ( ! $q->have_posts() ) {
		echo '<p>هنوز درخواستی ثبت نشده است.</p>';
		return;
	}
	echo '<table class="widefat striped"><tbody>';
	foreach ( $q->posts as $p ) {
		$d = psb_rfq_get( $p->ID );
		printf(
			'<tr><td><a href="%s"><strong>%s</strong></a><br><small><code>%s</code> · %s</small></td><td>%s<br><small>%s</small></td></tr>',
			esc_url( psb_rfq_edit_url( $p->ID ) ),
			esc_html( get_the_title( $p ) ),
			esc_html( $d['code'] ),
			esc_html( psb_rfq_items_summary( $d['items'] ) ),
			psb_rfq_status_pill( psb_rfq_status( $p->ID ) ), // phpcs:ignore
			esc_html( human_time_diff( get_post_time( 'U', true, $p ) ) . ' پیش' )
		);
	}
	echo '</tbody></table><p><a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=' . PSB_RFQ_CPT ) ) . '">همه درخواست‌ها</a></p>';
}
