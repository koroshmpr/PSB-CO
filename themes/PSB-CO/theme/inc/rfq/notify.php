<?php
/**
 * RFQ — notifications.
 *
 * Channels: bale, telegram (configurable base URL / relay), whatsapp (Cloud API), webhook (generic JSON),
 * email (wp_mail), sms (customer confirmation via a generic HTTP template).
 *
 * Flow: the REST handler stores the request, schedules `psb_rfq_notify` for "now" and, after the
 * response has been flushed to the browser (fastcgi_finish_request / litespeed_finish_request),
 * sends right away; otherwise WP-Cron is spawned. A post-meta lock prevents double sending.
 * Each channel's result is logged in `_psb_rfq_log` and shown in the admin with «ارسال مجدد».
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Channel keys → labels. */
function psb_rfq_channel_labels() {
	return array(
		'bale'     => 'بات بله',
		'telegram' => 'بات تلگرام',
		'whatsapp' => 'واتساپ (Cloud API)',
		'webhook'  => 'وب‌هوک عمومی',
		'email'    => 'ایمیل',
		'sms'      => 'پیامک تأیید به مشتری',
	);
}

/* -------------------------------------------------------------------------
 * Message model: lines with bold / code markers, rendered per channel.
 * ---------------------------------------------------------------------- */

function psb_rfq_b( $s ) {
	return "\x01" . $s . "\x02";
}
function psb_rfq_c( $s ) {
	return "\x03" . $s . "\x04";
}

/**
 * Render marker lines.
 *
 * @param string[] $lines Lines.
 * @param string   $mode  html | markdown | wa | plain.
 */
function psb_rfq_render( $lines, $mode ) {
	$t = implode( "\n", $lines );
	switch ( $mode ) {
		case 'html':
			$t = htmlspecialchars( $t, ENT_NOQUOTES, 'UTF-8' );
			return strtr( $t, array( "\x01" => '<b>', "\x02" => '</b>', "\x03" => '<code>', "\x04" => '</code>' ) );
		case 'markdown':
			$parts = preg_split( '#(https?://\S+)#u', $t, -1, PREG_SPLIT_DELIM_CAPTURE );
			foreach ( $parts as $i => $part ) {
				if ( 0 === $i % 2 ) {
					$parts[ $i ] = preg_replace( '/([_*`\[])/u', '\\\\$1', $part ); // URLs (odd parts) stay untouched.
				}
			}
			$t = implode( '', $parts );
			return strtr( $t, array( "\x01" => '*', "\x02" => '*', "\x03" => '`', "\x04" => '`' ) );
		case 'wa':
			return strtr( $t, array( "\x01" => '*', "\x02" => '*', "\x03" => '', "\x04" => '' ) );
		default:
			return strtr( $t, array( "\x01" => '', "\x02" => '', "\x03" => '', "\x04" => '' ) );
	}
}

/** Remove markers from a user value (so customers cannot inject formatting). */
function psb_rfq_m( $s ) {
	return str_replace( array( "\x01", "\x02", "\x03", "\x04" ), '', (string) $s );
}

/**
 * Notification lines for a request.
 *
 * @param int   $post_id 0 for test messages.
 * @param array $d       Data (psb_rfq_get()).
 */
function psb_rfq_message_lines( $post_id, $d ) {
	$m     = 'psb_rfq_m';
	$c     = $d['contact'];
	$pr    = $d['project'];
	$lines = array();

	$lines[] = psb_rfq_b( 'quick' === $d['mode'] ? 'درخواست سریع قیمت' : 'استعلام قیمت جدید' ) . '  ' . psb_rfq_c( $m( $d['code'] ) );
	$lines[] = '';
	$who     = psb_rfq_b( $m( isset( $c['name'] ) ? $c['name'] : '' ) );
	if ( ! empty( $c['company'] ) ) {
		$who .= ' — ' . $m( $c['company'] );
	}
	if ( ! empty( $c['position'] ) ) {
		$who .= ' (' . $m( $c['position'] ) . ')';
	}
	$lines[] = 'مشتری: ' . $who;
	if ( ! empty( $c['mobile'] ) ) {
		$lines[] = 'موبایل: ' . $m( $c['mobile'] );
	}
	if ( ! empty( $c['email'] ) ) {
		$lines[] = 'ایمیل: ' . $m( $c['email'] );
	}
	if ( ! empty( $c['channel'] ) ) {
		$ch      = psb_rfq_contact_channels();
		$lines[] = 'ارتباط ترجیحی: ' . ( isset( $ch[ $c['channel'] ] ) ? $ch[ $c['channel'] ] : $m( $c['channel'] ) ) . ( ! empty( $c['username'] ) ? ' — تلگرام: @' . $m( $c['username'] ) : '' );
		if ( ! empty( $c['bale'] ) ) {
			$lines[] = 'آیدی بله: @' . $m( $c['bale'] );
		}
	}

	$lines[] = '';
	$lines[] = psb_rfq_b( 'اقلام درخواست' );
	foreach ( $d['items'] as $i => $item ) {
		$lines[] = psb_rfq_fa( $i + 1 ) . '. ' . psb_rfq_b( $m( psb_rfq_type_label( $item['type'] ) ) ) . ' — تعداد: ' . (int) $item['qty'];
		$rows    = psb_rfq_item_spec_rows( $item );
		$note    = '';
		$specs   = array();
		foreach ( $rows as $label => $value ) {
			if ( psb_rfq_spec_defs()['note']['label'] === $label ) {
				$note = $value;
				continue;
			}
			$specs[] = $label . ': ' . $m( $value );
		}
		if ( $specs ) {
			$lines[] = '   ' . implode( ' · ', $specs );
		}
		if ( $note ) {
			$lines[] = '   توضیح: ' . $m( mb_substr( $note, 0, 300 ) );
		}
	}

	$plines = array();
	foreach ( psb_rfq_field_defs() as $key => $def ) {
		if ( 0 !== strpos( $key, 'project.' ) || 'project.description' === $key ) {
			continue;
		}
		$name = substr( $key, 8 );
		if ( ! empty( $pr[ $name ] ) ) {
			$plines[] = $def['label'] . ': ' . $m( psb_rfq_field_display( $key, $pr[ $name ] ) );
		}
	}
	if ( $plines || ! empty( $pr['description'] ) ) {
		$lines[] = '';
		$lines[] = psb_rfq_b( 'پروژه' );
		$lines   = array_merge( $lines, $plines );
		if ( ! empty( $pr['description'] ) ) {
			$lines[] = 'توضیحات: ' . $m( mb_substr( $pr['description'], 0, 900 ) );
		}
	}

	if ( ! empty( $d['files'] ) ) {
		$lines[] = '';
		$lines[] = psb_rfq_b( 'مدارک' ) . ': ' . psb_rfq_fa( count( $d['files'] ) ) . ' فایل — ' . $m( implode( '، ', wp_list_pluck( $d['files'], 'name' ) ) );
	}

	$lines[] = '';
	if ( ! empty( $d['source']['product'] ) ) {
		$lines[] = 'محصول: ' . $m( $d['source']['product'] );
	}
	if ( ! empty( $d['source']['page'] ) ) {
		$lines[] = 'صفحه ثبت: ' . $m( urldecode( $d['source']['page'] ) );
	}
	$lines[] = 'زمان ثبت: ' . psb_rfq_jdate( $post_id ? get_post_time( 'U', true, $post_id ) : time() );
	$lines[] = 'پنل: ' . ( $post_id ? psb_rfq_edit_url( $post_id ) : psb_rfq_settings_url( 'psb-rfq-channels' ) );
	return $lines;
}

/** Inline keyboard (only http(s) URLs are allowed by Telegram/Bale). */
function psb_rfq_buttons( $post_id, $d ) {
	$row = array();
	$c   = $d['contact'];
	if ( ! empty( $c['mobile'] ) ) {
		$row[] = array( 'text' => 'واتساپ مشتری', 'url' => 'https://wa.me/' . psb_rfq_intl( $c['mobile'] ) );
	}
	if ( ! empty( $c['bale'] ) && preg_match( '/^[A-Za-z][A-Za-z0-9_]{3,31}$/', $c['bale'] ) ) {
		$row[] = array( 'text' => 'بله مشتری', 'url' => 'https://ble.ir/' . $c['bale'] );
	}
	if ( ! empty( $c['username'] ) && preg_match( '/^[A-Za-z][A-Za-z0-9_]{3,31}$/', $c['username'] ) ) {
		if ( 'bale' === ( isset( $c['channel'] ) ? $c['channel'] : '' ) && empty( $c['bale'] ) ) {
			$row[] = array( 'text' => 'بله مشتری', 'url' => 'https://ble.ir/' . $c['username'] );
		} else {
			$row[] = array( 'text' => 'تلگرام مشتری', 'url' => 'https://t.me/' . $c['username'] );
		}
	}
	$admin = $post_id ? psb_rfq_edit_url( $post_id ) : psb_rfq_settings_url( 'psb-rfq-channels' );
	$rows  = array();
	if ( $row ) {
		$rows[] = $row;
	}
	if ( preg_match( '#^https?://#', $admin ) ) {
		$rows[] = array( array( 'text' => 'مشاهده در پنل', 'url' => $admin ) );
	}
	return $rows;
}

/* -------------------------------------------------------------------------
 * Transport helpers
 * ---------------------------------------------------------------------- */

/** Normalised HTTP result: array( ok, message, json ). */
function psb_rfq_http( $url, $args, $secret_mask = array() ) {
	$args = array_merge(
		array(
			'timeout'     => 8,
			'redirection' => 0,
			'user-agent'  => 'PSB-RFQ/' . PSB_RFQ_VERSION . '; ' . wp_parse_url( home_url(), PHP_URL_HOST ),
		),
		$args
	);
	$safe = ! empty( $args['psb_safe'] );
	unset( $args['psb_safe'] );
	if ( $safe ) {
		// Admin-entered URLs (webhook, SMS API): wp_safe_remote_request() refuses loopback / private / reserved
		// addresses (security review L4). The explicit port of this URL is allowed so a public HTTPS API on a
		// non-standard port keeps working; the private-address check still applies.
		$port      = (int) wp_parse_url( $url, PHP_URL_PORT );
		$allow_one = static function ( $ports ) use ( $port ) {
			$ports[] = $port;
			return $ports;
		};
		if ( $port ) {
			add_filter( 'http_allowed_safe_ports', $allow_one );
		}
		$res = wp_safe_remote_request( $url, $args );
		if ( $port ) {
			remove_filter( 'http_allowed_safe_ports', $allow_one );
		}
	} else {
		$res = wp_remote_request( $url, $args );
	}
	$mask = static function ( $s ) use ( $secret_mask ) {
		foreach ( array_filter( (array) $secret_mask ) as $secret ) {
			$s = str_replace( $secret, '***', $s );
		}
		return $s;
	};
	if ( is_wp_error( $res ) ) {
		$msg = $res->get_error_message();
		if ( 'psb_http_breaker' === $res->get_error_code() ) {
			$msg = 'این میزبان به‌دلیل خطای اتصال قبلی موقتاً مسدود شده است (circuit breaker). دکمه «ارسال پیام تست» آن را باز می‌کند.';
		}
		return array( false, $mask( 'خطای اتصال: ' . $msg ), null, 0 );
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = (string) wp_remote_retrieve_body( $res );
	$json = json_decode( $body, true );
	$ok   = $code >= 200 && $code < 300;
	if ( is_array( $json ) && array_key_exists( 'ok', $json ) && ! $json['ok'] ) {
		$ok = false;
	}
	if ( $ok ) {
		return array( true, 'HTTP ' . $code, $json, $code );
	}
	$desc = '';
	if ( is_array( $json ) ) {
		if ( isset( $json['description'] ) ) {
			$desc = (string) $json['description'];
		} elseif ( isset( $json['error']['message'] ) ) {
			$desc = (string) $json['error']['message'];
		} elseif ( isset( $json['message'] ) && is_string( $json['message'] ) ) {
			$desc = $json['message'];
		}
	}
	if ( '' === $desc ) {
		$desc = mb_substr( trim( wp_strip_all_tags( $body ) ), 0, 300 );
	}
	return array( false, $mask( 'HTTP ' . $code . ( $desc ? ': ' . $desc : '' ) ), $json, $code );
}

/** Relay header for a channel config. */
function psb_rfq_relay_headers( $cfg ) {
	$h = array();
	if ( ! empty( $cfg['relay_secret'] ) ) {
		$name       = preg_replace( '/[^A-Za-z0-9-]/', '', isset( $cfg['relay_header'] ) && $cfg['relay_header'] ? $cfg['relay_header'] : 'X-Relay-Secret' );
		$h[ $name ] = $cfg['relay_secret'];
	}
	return $h;
}

/** Bot API base for bale / telegram. */
function psb_rfq_bot_base( $ch, $cfg ) {
	$def  = psb_rfq_default_channels();
	$base = ! empty( $cfg['base'] ) ? $cfg['base'] : $def[ $ch ]['base'];
	return untrailingslashit( esc_url_raw( $base ) );
}

/** One Bot API call (JSON). */
function psb_rfq_bot_call( $ch, $cfg, $method, $params ) {
	$url = psb_rfq_bot_base( $ch, $cfg ) . '/bot' . $cfg['token'] . '/' . $method;
	return psb_rfq_http(
		$url,
		array(
			'method'  => 'POST',
			'headers' => array_merge( array( 'Content-Type' => 'application/json' ), psb_rfq_relay_headers( $cfg ) ),
			'body'    => wp_json_encode( $params ),
		),
		array( $cfg['token'], isset( $cfg['relay_secret'] ) ? $cfg['relay_secret'] : '' )
	);
}

/** sendDocument (multipart). */
function psb_rfq_bot_document( $ch, $cfg, $chat, $path, $filename, $caption ) {
	$boundary = 'psbrfq' . wp_generate_password( 24, false, false );
	$eol      = "\r\n";
	$body     = '';
	foreach ( array( 'chat_id' => $chat, 'caption' => $caption ) as $k => $v ) {
		$body .= '--' . $boundary . $eol . 'Content-Disposition: form-data; name="' . $k . '"' . $eol . $eol . $v . $eol;
	}
	$body .= '--' . $boundary . $eol . 'Content-Disposition: form-data; name="document"; filename="' . str_replace( array( '"', "\r", "\n" ), '', $filename ) . '"' . $eol . 'Content-Type: application/octet-stream' . $eol . $eol;
	$body .= (string) file_get_contents( $path ) . $eol; // phpcs:ignore
	$body .= '--' . $boundary . '--' . $eol;
	$url   = psb_rfq_bot_base( $ch, $cfg ) . '/bot' . $cfg['token'] . '/sendDocument';
	return psb_rfq_http(
		$url,
		array(
			'method'  => 'POST',
			'headers' => array_merge( array( 'Content-Type' => 'multipart/form-data; boundary=' . $boundary ), psb_rfq_relay_headers( $cfg ) ),
			'body'    => $body,
		),
		array( $cfg['token'], isset( $cfg['relay_secret'] ) ? $cfg['relay_secret'] : '' )
	);
}

/**
 * Bale / Telegram: sendMessage to every chat (+ documents).
 *
 * @return array( ok, msg )
 */
function psb_rfq_send_bot( $ch, $cfg, $lines, $buttons, $files = array(), $code = '' ) {
	if ( empty( $cfg['token'] ) ) {
		return array( false, 'توکن ربات وارد نشده است.' );
	}
	$chats = psb_rfq_lines( isset( $cfg['chats'] ) ? $cfg['chats'] : '' );
	if ( ! $chats ) {
		return array( false, 'هیچ Chat ID واردنشده است.' );
	}
	$parse  = isset( $cfg['parse'] ) ? $cfg['parse'] : 'html';
	$text   = psb_rfq_render( $lines, in_array( $parse, array( 'html', 'markdown' ), true ) ? $parse : 'plain' );
	$base   = array(
		'text'                     => mb_substr( $text, 0, 4000 ),
		'disable_web_page_preview' => true,
	);
	if ( 'html' === $parse ) {
		$base['parse_mode'] = 'HTML';
	} elseif ( 'markdown' === $parse ) {
		$base['parse_mode'] = 'Markdown';
	}
	if ( $buttons ) {
		$base['reply_markup'] = array( 'inline_keyboard' => $buttons );
	}

	$ok_chats = array();
	$errors   = array();
	$notes    = array();
	foreach ( $chats as $chat ) {
		$params = array_merge( array( 'chat_id' => $chat ), $base );
		$r      = psb_rfq_bot_call( $ch, $cfg, 'sendMessage', $params );
		if ( ! $r[0] && isset( $params['reply_markup'] ) && preg_match( '/button|url|markup|keyboard/i', $r[1] ) ) {
			unset( $params['reply_markup'] );
			$r = psb_rfq_bot_call( $ch, $cfg, 'sendMessage', $params );
			if ( $r[0] ) {
				$notes[] = 'دکمه‌ها پذیرفته نشد و پیام بدون دکمه ارسال شد';
			}
		}
		if ( ! $r[0] && isset( $params['parse_mode'] ) && preg_match( '/parse|entit/i', $r[1] ) ) {
			unset( $params['parse_mode'] );
			$params['text'] = mb_substr( psb_rfq_render( $lines, 'plain' ), 0, 4000 );
			$r              = psb_rfq_bot_call( $ch, $cfg, 'sendMessage', $params );
			if ( $r[0] ) {
				$notes[] = 'قالب‌بندی پذیرفته نشد و متن ساده ارسال شد';
			}
		}
		if ( $r[0] ) {
			$ok_chats[] = $chat;
		} else {
			$errors[] = $chat . ' ← ' . $r[1];
		}
	}

	// Documents.
	if ( $ok_chats && ! empty( $cfg['files'] ) && $files ) {
		$all      = psb_rfq_channels();
		$max      = max( 1, (int) $all['files_max_mb'] ) * 1048576;
		$sent     = 0;
		$skipped  = 0;
		foreach ( $files as $f ) {
			$path = psb_rfq_file_path( $f );
			if ( ! $path || filesize( $path ) > $max ) {
				$skipped++;
				continue;
			}
			foreach ( $ok_chats as $chat ) {
				$r = psb_rfq_bot_document( $ch, $cfg, $chat, $path, $f['name'], $code . ' — ' . $f['name'] );
				if ( $r[0] ) {
					$sent++;
				} else {
					$errors[] = 'فایل ' . $f['name'] . ' ← ' . $r[1];
					// A slow upload must not trip the breaker for text messages.
					psb_rfq_reset_breaker( psb_rfq_bot_base( $ch, $cfg ) );
				}
			}
		}
		if ( $sent ) {
			$notes[] = psb_rfq_fa( $sent ) . ' فایل ارسال شد';
		}
		if ( $skipped ) {
			$notes[] = psb_rfq_fa( $skipped ) . ' فایل به‌دلیل حجم زیاد ارسال نشد (فقط در پنل)';
		}
	}

	$msg = $ok_chats ? 'ارسال شد به ' . psb_rfq_fa( count( $ok_chats ) ) . ' چت' : 'ارسال نشد';
	if ( $notes ) {
		$msg .= ' — ' . implode( '؛ ', $notes );
	}
	if ( $errors ) {
		$msg .= ' | خطا: ' . implode( ' | ', $errors );
	}
	return array( count( $ok_chats ) === count( $chats ), $msg );
}

/** Recent chats seen by a bot (getUpdates) — helper to find Chat IDs. */
function psb_rfq_bot_updates( $ch, $cfg ) {
	if ( empty( $cfg['token'] ) ) {
		return array( false, 'ابتدا توکن ربات را وارد و ذخیره کنید.' );
	}
	$r = psb_rfq_bot_call( $ch, $cfg, 'getUpdates', array( 'limit' => 50 ) );
	if ( ! $r[0] ) {
		return array( false, $r[1] );
	}
	$chats = array();
	foreach ( isset( $r[2]['result'] ) && is_array( $r[2]['result'] ) ? $r[2]['result'] : array() as $u ) {
		foreach ( array( 'message', 'channel_post', 'my_chat_member', 'edited_message' ) as $k ) {
			if ( isset( $u[ $k ]['chat']['id'] ) ) {
				$chat = $u[ $k ]['chat'];
				$name = isset( $chat['title'] ) ? $chat['title'] : trim( ( isset( $chat['first_name'] ) ? $chat['first_name'] : '' ) . ' ' . ( isset( $chat['username'] ) ? '@' . $chat['username'] : '' ) );
				$chats[ (string) $chat['id'] ] = $chat['id'] . ' — ' . $name . ' (' . ( isset( $chat['type'] ) ? $chat['type'] : '' ) . ')';
			}
		}
	}
	if ( ! $chats ) {
		return array( false, 'هنوز پیامی به ربات نرسیده است. ابتدا در ' . ( 'bale' === $ch ? 'بله' : 'تلگرام' ) . ' به ربات پیام دهید (یا آن را به گروه اضافه و در گروه پیامی بفرستید) و دوباره امتحان کنید.' );
	}
	return array( true, 'چت‌های شناسایی‌شده: ' . implode( ' | ', $chats ) );
}

/** WhatsApp Cloud API. */
function psb_rfq_send_whatsapp( $cfg, $lines, $d ) {
	foreach ( array( 'phone_id' => 'Phone number ID', 'token' => 'Access token', 'to' => 'شماره گیرنده' ) as $k => $label ) {
		if ( empty( $cfg[ $k ] ) ) {
			return array( false, '«' . $label . '» وارد نشده است.' );
		}
	}
	$url = untrailingslashit( esc_url_raw( $cfg['base'] ? $cfg['base'] : 'https://graph.facebook.com' ) ) . '/' . trim( $cfg['version'] ? $cfg['version'] : 'v21.0', '/' ) . '/' . rawurlencode( $cfg['phone_id'] ) . '/messages';
	$ok  = array();
	$err = array();
	foreach ( psb_rfq_lines( $cfg['to'] ) as $to ) {
		$to   = psb_rfq_intl( $to );
		$body = array(
			'messaging_product' => 'whatsapp',
			'to'                => $to,
		);
		if ( ! empty( $cfg['template'] ) ) {
			$params         = array( $d['code'], isset( $d['contact']['name'] ) ? $d['contact']['name'] : '-', isset( $d['contact']['mobile'] ) ? $d['contact']['mobile'] : '-', psb_rfq_items_summary( $d['items'] ) );
			$body['type']     = 'template';
			$body['template'] = array(
				'name'       => $cfg['template'],
				'language'   => array( 'code' => $cfg['lang'] ? $cfg['lang'] : 'fa' ),
				'components' => array(
					array(
						'type'       => 'body',
						'parameters' => array_map( static function ( $v ) {
							return array( 'type' => 'text', 'text' => mb_substr( (string) $v, 0, 900 ) );
						}, $params ),
					),
				),
			);
		} else {
			$body['type'] = 'text';
			$body['text'] = array( 'preview_url' => false, 'body' => mb_substr( psb_rfq_render( $lines, 'wa' ), 0, 4000 ) );
		}
		$r = psb_rfq_http(
			$url,
			array(
				'method'  => 'POST',
				'headers' => array_merge( array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $cfg['token'] ), psb_rfq_relay_headers( $cfg ) ),
				'body'    => wp_json_encode( $body ),
			),
			array( $cfg['token'], $cfg['relay_secret'] )
		);
		if ( $r[0] ) {
			$ok[] = $to;
		} else {
			$err[] = $to . ' ← ' . $r[1];
		}
	}
	$msg = $ok ? 'ارسال شد به ' . implode( '، ', $ok ) : 'ارسال نشد';
	if ( $err ) {
		$msg .= ' | خطا: ' . implode( ' | ', $err );
	}
	return array( $ok && ! $err, $msg );
}

/** Webhook payload (structured). */
function psb_rfq_webhook_payload( $post_id, $d, $lines ) {
	$items = array();
	foreach ( $d['items'] as $item ) {
		$items[] = array(
			'type'       => $item['type'],
			'type_label' => psb_rfq_type_label( $item['type'] ),
			'qty'        => (int) $item['qty'],
			'specs'      => $item['specs'],
			'specs_text' => psb_rfq_item_spec_rows( $item ),
		);
	}
	$project = array();
	foreach ( (array) $d['project'] as $k => $v ) {
		$project[ $k ] = array( 'value' => $v, 'text' => psb_rfq_field_display( 'project.' . $k, $v ) );
	}
	$ts = $post_id ? (int) get_post_time( 'U', true, $post_id ) : time();
	return array(
		'event'        => 'rfq.created',
		'id'           => (int) $post_id,
		'code'         => $d['code'],
		'mode'         => $d['mode'],
		'status'       => $post_id ? psb_rfq_status( $post_id ) : 'new',
		'created_at'   => gmdate( 'c', $ts ),
		'created_jalali' => psb_rfq_jdate( $ts, true, false ),
		'admin_url'    => $post_id ? psb_rfq_edit_url( $post_id ) : '',
		'contact'      => $d['contact'],
		'contact_wa'   => ! empty( $d['contact']['mobile'] ) ? 'https://wa.me/' . psb_rfq_intl( $d['contact']['mobile'] ) : '',
		'items'        => $items,
		'project'      => $project,
		'files'        => array_map( static function ( $f ) {
			return array( 'name' => $f['name'], 'size' => (int) $f['size'] );
		}, (array) $d['files'] ),
		'source'       => $d['source'],
		'text'         => psb_rfq_render( $lines, 'plain' ),
		'text_wa'      => psb_rfq_render( $lines, 'wa' ),
		'site'         => home_url( '/' ),
	);
}

/** Generic JSON webhook. */
function psb_rfq_send_webhook( $cfg, $payload ) {
	if ( empty( $cfg['url'] ) || ! wp_http_validate_url( $cfg['url'] ) ) {
		return array( false, 'آدرس وب‌هوک معتبر نیست.' );
	}
	$json    = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$headers = array(
		'Content-Type' => 'application/json; charset=utf-8',
		'X-PSB-Event'  => $payload['event'],
	);
	if ( ! empty( $cfg['secret'] ) ) {
		$headers['X-PSB-Signature'] = 'sha256=' . hash_hmac( 'sha256', $json, $cfg['secret'] );
	}
	foreach ( psb_rfq_header_lines( isset( $cfg['headers'] ) ? $cfg['headers'] : '', array() ) as $k => $v ) {
		$headers[ $k ] = $v;
	}
	$r = psb_rfq_http( $cfg['url'], array( 'method' => 'POST', 'headers' => $headers, 'body' => $json, 'psb_safe' => true ), array( $cfg['secret'] ) );
	return array( $r[0], $r[0] ? 'ارسال شد (' . $r[1] . ')' : $r[1] );
}

/** "Name: value" lines → headers, with placeholder replacement. */
function psb_rfq_header_lines( $text, $vars ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		if ( false === strpos( $line, ':' ) ) {
			continue;
		}
		list( $k, $v ) = array_map( 'trim', explode( ':', $line, 2 ) );
		$k             = preg_replace( '/[^A-Za-z0-9-]/', '', $k );
		if ( $k ) {
			$out[ $k ] = strtr( $v, $vars );
		}
	}
	return $out;
}

/** Email via wp_mail (HTML), with the exact PHPMailer error on failure. */
function psb_rfq_send_email( $cfg, $post_id, $d, $lines ) {
	$to = array_filter( psb_rfq_lines( isset( $cfg['to'] ) ? $cfg['to'] : '' ), 'is_email' );
	if ( ! $to ) {
		return array( false, 'هیچ ایمیل معتبری برای دریافت وارد نشده است.' );
	}
	$c       = $d['contact'];
	$subject = '[استعلام قیمت] ' . $d['code'] . ' — ' . ( isset( $c['name'] ) ? $c['name'] : '' ) . ( ! empty( $c['company'] ) ? ' — ' . $c['company'] : '' );
	$link    = $post_id ? psb_rfq_edit_url( $post_id ) : psb_rfq_settings_url( 'psb-rfq-channels' );
	$html    = '<div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;font-size:14px;line-height:1.9;color:#0f172a">'
		. nl2br( psb_rfq_render( $lines, 'html' ) )
		. '<p style="margin-top:20px"><a href="' . esc_url( $link ) . '" style="background:#1a3da3;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">مشاهده درخواست در پنل</a></p></div>';
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( ! empty( $c['email'] ) && is_email( $c['email'] ) ) {
		$headers[] = 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>', ',' ), '', isset( $c['name'] ) ? $c['name'] : '' ) . ' <' . $c['email'] . '>';
	}
	$attach = array();
	if ( ! empty( $cfg['attach'] ) && ! empty( $d['files'] ) ) {
		$total = 0;
		foreach ( $d['files'] as $f ) {
			$path = psb_rfq_file_path( $f );
			if ( $path && $total + filesize( $path ) <= 15 * 1048576 ) {
				$total                 += filesize( $path );
				$attach[ $f['name'] ] = $path; // WP 6.2+: array keys become attachment names.
			}
		}
	}
	$error = '';
	$catch = static function ( $wp_error ) use ( &$error ) {
		$error = $wp_error->get_error_message();
	};
	add_action( 'wp_mail_failed', $catch );
	$sent = wp_mail( $to, $subject, $html, $headers, $attach );
	remove_action( 'wp_mail_failed', $catch );
	if ( $sent ) {
		return array( true, 'ارسال شد به ' . implode( '، ', $to ) . ( $attach ? ' با ' . psb_rfq_fa( count( $attach ) ) . ' پیوست' : '' ) );
	}
	return array( false, 'wp_mail ناموفق: ' . ( $error ? $error : 'تنظیمات ارسال ایمیل سرور (SMTP) را بررسی کنید.' ) );
}

/** Customer SMS confirmation via a generic HTTP template. */
function psb_rfq_send_sms( $cfg, $mobile, $d ) {
	$mobile = psb_rfq_mobile( $mobile );
	if ( ! $mobile ) {
		return array( false, 'شماره موبایل مقصد معتبر نیست.' );
	}
	if ( empty( $cfg['url'] ) ) {
		return array( false, 'آدرس سرویس پیامک وارد نشده است.' );
	}
	$base_vars = array(
		'{name}'          => isset( $d['contact']['name'] ) ? $d['contact']['name'] : '',
		'{code}'          => $d['code'],
		'{mobile}'        => $mobile,
		'{mobile_intl}'   => psb_rfq_intl( $mobile ),
		'{response_time}' => (string) psb_rfq_opt( 'response_time' ),
		'{api_key}'       => isset( $cfg['api_key'] ) ? (string) $cfg['api_key'] : '',
	);
	$message              = strtr( (string) $cfg['message'], $base_vars );
	$vars                 = $base_vars;
	$vars['{message}']    = $message;
	$enc                  = static function ( $fn ) use ( $vars ) {
		return array_map( $fn, $vars );
	};
	$url = strtr( (string) $cfg['url'], $enc( 'rawurlencode' ) );
	$args = array(
		'method'   => 'GET' === strtoupper( $cfg['method'] ) ? 'GET' : 'POST',
		'headers'  => psb_rfq_header_lines( isset( $cfg['headers'] ) ? $cfg['headers'] : '', $vars ),
		'psb_safe' => true,
	);
	if ( 'POST' === $args['method'] ) {
		if ( 'form' === $cfg['format'] ) {
			$args['body']                    = strtr( (string) $cfg['body'], $enc( 'rawurlencode' ) );
			$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
		} else {
			$args['body'] = strtr(
				(string) $cfg['body'],
				$enc(
					static function ( $v ) {
						return substr( wp_json_encode( (string) $v, JSON_UNESCAPED_UNICODE ), 1, -1 );
					}
				)
			);
			$args['headers']['Content-Type'] = 'application/json';
		}
	}
	$r = psb_rfq_http( $url, $args, array( $base_vars['{api_key}'] ) );
	if ( $r[0] ) {
		$body = is_array( $r[2] ) ? wp_json_encode( $r[2], JSON_UNESCAPED_UNICODE ) : '';
		return array( true, 'ارسال شد به ' . $mobile . ' (' . $r[1] . ( $body ? ' — ' . mb_substr( str_replace( $base_vars['{api_key}'] ? $base_vars['{api_key}'] : "\0", '***', $body ), 0, 200 ) : '' ) . ')' );
	}
	return array( false, $r[1] );
}

/* -------------------------------------------------------------------------
 * Dispatch
 * ---------------------------------------------------------------------- */

/** Channels that are switched on. */
function psb_rfq_enabled_channels() {
	$cfg = psb_rfq_channels();
	$out = array();
	foreach ( array_keys( psb_rfq_channel_labels() ) as $ch ) {
		if ( ! empty( $cfg[ $ch ]['enabled'] ) ) {
			$out[] = $ch;
		}
	}
	return $out;
}

/**
 * Anti-pumping limits for the automatic customer SMS (security review M2): one SMS per mobile per 24 h and a
 * site-wide daily cap (setting `sms_daily_cap`, default 50). Reserves the slot before sending.
 *
 * @return true|string True when allowed, otherwise the reason (logged on the request).
 */
function psb_rfq_sms_reserve( $mobile ) {
	$mobile = psb_rfq_mobile( $mobile );
	if ( ! $mobile ) {
		return 'شماره موبایل مقصد معتبر نیست.';
	}
	$cap = (int) psb_rfq_opt( 'sms_daily_cap', 50 );
	$mk  = 'psb_rfq_sms_m_' . substr( psb_rfq_hmac( 'sms|' . $mobile ), 0, 20 );
	if ( get_transient( $mk ) ) {
		return 'ارسال نشد: به این شماره در ۲۴ ساعت گذشته پیامک تأیید ارسال شده است (سقف ضد سوءاستفاده).';
	}
	$dk   = 'psb_rfq_sms_day_' . gmdate( 'Ymd' );
	$sent = (int) get_transient( $dk );
	if ( $sent >= $cap ) {
		return 'ارسال نشد: سقف روزانه پیامک تأیید (' . $cap . ') پر شده است.';
	}
	set_transient( $dk, $sent + 1, DAY_IN_SECONDS + HOUR_IN_SECONDS );
	set_transient( $mk, time(), DAY_IN_SECONDS );
	return true;
}

/** Send one channel for a request (or test data with $post_id = 0). $force = manual resend by staff. */
function psb_rfq_send_channel( $ch, $post_id, $d, $sms_to = '', $force = false ) {
	$cfg   = psb_rfq_channels();
	$lines = psb_rfq_message_lines( $post_id, $d );
	try {
		switch ( $ch ) {
			case 'bale':
			case 'telegram':
				$files = $post_id ? (array) get_post_meta( $post_id, '_psb_rfq_files', true ) : array();
				return psb_rfq_send_bot( $ch, $cfg[ $ch ], $lines, psb_rfq_buttons( $post_id, $d ), array_filter( $files ), $d['code'] );
			case 'whatsapp':
				return psb_rfq_send_whatsapp( $cfg['whatsapp'], $lines, $d );
			case 'webhook':
				return psb_rfq_send_webhook( $cfg['webhook'], psb_rfq_webhook_payload( $post_id, $d, $lines ) );
			case 'email':
				return psb_rfq_send_email( $cfg['email'], $post_id, $d, $lines );
			case 'sms':
				$to = $sms_to ? $sms_to : ( isset( $d['contact']['mobile'] ) ? $d['contact']['mobile'] : '' );
				if ( $post_id && ! $force ) {
					$ok = psb_rfq_sms_reserve( $to );
					if ( true !== $ok ) {
						return array( false, $ok );
					}
				}
				return psb_rfq_send_sms( $cfg['sms'], $to, $d );
		}
	} catch ( Throwable $e ) {
		return array( false, 'خطای داخلی: ' . $e->getMessage() );
	}
	return array( false, 'کانال ناشناخته' );
}

/**
 * Send notifications of a request and log the result per channel.
 *
 * @param int         $post_id Request.
 * @param string|null $only    Single channel (resend).
 * @param bool        $force   Ignore the "already sent" lock (manual resend).
 */
function psb_rfq_dispatch( $post_id, $only = null, $force = false ) {
	$post_id = (int) $post_id;
	if ( PSB_RFQ_CPT !== get_post_type( $post_id ) ) {
		return;
	}
	if ( ! $force && ! add_post_meta( $post_id, '_psb_rfq_notify_lock', time(), true ) ) {
		return; // Already handled by the shutdown path or a previous cron run.
	}
	wp_clear_scheduled_hook( 'psb_rfq_notify', array( $post_id ) );
	ignore_user_abort( true );
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 120 ); // phpcs:ignore
	}
	$d        = psb_rfq_get( $post_id );
	$log      = get_post_meta( $post_id, '_psb_rfq_log', true );
	$log      = is_array( $log ) ? $log : array();
	$channels = $only ? array( $only ) : psb_rfq_enabled_channels();
	foreach ( $channels as $ch ) {
		if ( ! isset( psb_rfq_channel_labels()[ $ch ] ) ) {
			continue;
		}
		if ( $force ) {
			psb_rfq_reset_breaker( psb_rfq_channel_url( $ch ) );
		}
		$r          = psb_rfq_send_channel( $ch, $post_id, $d, '', $force );
		$log[ $ch ] = array(
			'ok'  => (bool) $r[0],
			'msg' => (string) $r[1],
			't'   => time(),
			'n'   => isset( $log[ $ch ]['n'] ) ? (int) $log[ $ch ]['n'] + 1 : 1,
		);
	}
	update_post_meta( $post_id, '_psb_rfq_log', $log );
	do_action( 'psb_rfq_notified', $post_id, $log );
}
add_action( 'psb_rfq_notify', 'psb_rfq_dispatch', 10, 1 );

/** Main endpoint URL of a channel (for breaker reset / diagnostics). */
function psb_rfq_channel_url( $ch ) {
	$cfg = psb_rfq_channels();
	switch ( $ch ) {
		case 'bale':
		case 'telegram':
			return psb_rfq_bot_base( $ch, $cfg[ $ch ] );
		case 'whatsapp':
			return $cfg['whatsapp']['base'] ? $cfg['whatsapp']['base'] : 'https://graph.facebook.com';
		case 'webhook':
			return $cfg['webhook']['url'];
		case 'sms':
			return preg_replace( '/\{[a-z_]+\}/', 'x', (string) $cfg['sms']['url'] );
	}
	return '';
}

/**
 * Queue notifications without delaying the HTTP response.
 */
function psb_rfq_queue_notifications( $post_id ) {
	if ( ! psb_rfq_enabled_channels() ) {
		return;
	}
	if ( ! wp_next_scheduled( 'psb_rfq_notify', array( (int) $post_id ) ) ) {
		wp_schedule_single_event( time(), 'psb_rfq_notify', array( (int) $post_id ) );
	}
	add_action(
		'shutdown',
		static function () use ( $post_id ) {
			if ( function_exists( 'fastcgi_finish_request' ) ) {
				fastcgi_finish_request();
				psb_rfq_dispatch( $post_id );
			} elseif ( function_exists( 'litespeed_finish_request' ) ) {
				litespeed_finish_request();
				psb_rfq_dispatch( $post_id );
			} elseif ( function_exists( 'spawn_cron' ) ) {
				spawn_cron();
			}
		},
		1000
	);
}

/** Sample request for test messages. */
function psb_rfq_sample_data() {
	$types = array_keys( psb_rfq_types() );
	return array(
		'code'    => strtoupper( (string) psb_rfq_opt( 'prefix', 'PSB' ) ) . '-TEST',
		'mode'    => 'full',
		'items'   => array(
			array(
				'type'  => $types ? $types[0] : 'other',
				'qty'   => 1,
				'specs' => array( 'current' => '1600' ),
			),
		),
		'project' => array( 'name' => 'پیام آزمایشی اتصال' ),
		'contact' => array(
			'name'    => 'پیام تست',
			'company' => wp_parse_url( home_url(), PHP_URL_HOST ),
			'mobile'  => '09120000000',
		),
		'files'   => array(),
		'source'  => array( 'page' => home_url( '/' ) ),
	);
}

/**
 * Admin test of a channel. Resets the circuit breaker first so the admin can retry immediately.
 *
 * @return array( ok, msg )
 */
function psb_rfq_test_channel( $ch ) {
	psb_rfq_reset_breaker( psb_rfq_channel_url( $ch ) );
	$cfg = psb_rfq_channels();
	if ( 'sms' === $ch ) {
		if ( empty( $cfg['sms']['test_mobile'] ) ) {
			return array( false, 'برای تست پیامک، «شماره موبایل تست» را وارد و ذخیره کنید.' );
		}
		return psb_rfq_send_channel( 'sms', 0, psb_rfq_sample_data(), $cfg['sms']['test_mobile'] );
	}
	return psb_rfq_send_channel( $ch, 0, psb_rfq_sample_data() );
}
