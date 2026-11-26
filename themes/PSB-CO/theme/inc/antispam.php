<?php
/**
 * Anti-spam for Gravity Forms and comments — no plugin, no captcha, no external service.
 *
 * Layers (a submission is spam if any layer trips):
 *  1. Honeypot: an invisible field humans never fill.
 *  2. Signed time token: missing/forged token, or submitted faster than PSB_SPAM_MIN_SECONDS.
 *  3. Content rules tuned for an Iranian B2B site: Cyrillic/CJK text, casino/crypto/SEO
 *     keywords, HTML/BBCode links, Latin-only text with links, numeric-only names,
 *     non-Iranian phone numbers.
 *
 * Spam is never silently deleted: Gravity Forms entries go to the «Spam» folder (no
 * notification e-mails), comments go to the comment spam queue. The weekly DB cleanup
 * (performance.php) purges spam comments after 30 days.
 *
 * Kill switch: define( 'PSB_ANTISPAM_DISABLE', true ) in wp-config.php.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'PSB_ANTISPAM_DISABLE' ) && PSB_ANTISPAM_DISABLE ) {
	return;
}

const PSB_SPAM_MIN_SECONDS = 3;
const PSB_SPAM_HP_FIELD    = 'psb_website_url';
const PSB_SPAM_TS_FIELD    = 'psb_form_ts';

/* -------------------------------------------------------------------------
 * Token + honeypot
 * ---------------------------------------------------------------------- */

function psb_spam_sign( $ts ) {
	return substr( hash_hmac( 'sha256', 'psb-form|' . $ts, wp_salt( 'nonce' ) ), 0, 20 );
}

/** Hidden inputs printed inside every protected form. Cache-safe (no user/session data). */
function psb_spam_fields_html() {
	$ts = time();
	return '<div aria-hidden="true" style="position:absolute!important;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);white-space:nowrap">'
		. '<label>Website<input type="text" name="' . PSB_SPAM_HP_FIELD . '" value="" tabindex="-1" autocomplete="off"></label>'
		. '</div>'
		. '<input type="hidden" name="' . PSB_SPAM_TS_FIELD . '" value="' . esc_attr( $ts . '.' . psb_spam_sign( $ts ) ) . '">';
}

/**
 * Checks the honeypot and the time token of the current POST.
 *
 * @return string Empty when OK, otherwise the reason.
 */
function psb_spam_check_request() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	if ( ! empty( $_POST[ PSB_SPAM_HP_FIELD ] ) ) {
		return 'honeypot';
	}
	$raw = isset( $_POST[ PSB_SPAM_TS_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ PSB_SPAM_TS_FIELD ] ) ) : '';
	// phpcs:enable
	if ( ! preg_match( '/^(\d{9,11})\.([a-f0-9]{20})$/', $raw, $m ) ) {
		return 'no-token';
	}
	if ( ! hash_equals( psb_spam_sign( $m[1] ), $m[2] ) ) {
		return 'bad-token';
	}
	if ( time() - (int) $m[1] < PSB_SPAM_MIN_SECONDS ) {
		return 'too-fast';
	}
	return '';
}

/* -------------------------------------------------------------------------
 * Content rules
 * ---------------------------------------------------------------------- */

function psb_spam_normalize_digits( $s ) {
	return strtr( (string) $s, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
	) );
}

/**
 * @return string Empty when the text looks legitimate, otherwise the reason.
 */
function psb_spam_check_text( $text ) {
	$text = (string) $text;
	if ( '' === trim( $text ) ) {
		return '';
	}
	if ( preg_match( '/[\p{Cyrillic}\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}\p{Thai}]/u', $text ) ) {
		return 'foreign-script';
	}
	if ( preg_match( '/<a\s|\[url[=\]]|\[link[=\]]/i', $text ) ) {
		return 'html-link';
	}
	$words = apply_filters(
		'psb_spam_keywords',
		array( 'casino', 'slot', 'bonus', 'betting', 'sportbet', 'jackpot', 'viagra', 'cialis', 'porn', 'xxx', 'escort', 'dating', 'crypto', 'bitcoin', 'forex', 'binance', 'airdrop', 'loan', 'seo service', 'backlink', 'guest post', 'rank your', 'first page of google', 'web design service', 'whatsapp me', 'telegram me', 'click here', 'unsubscribe', 'kazino', 'казино' )
	);
	foreach ( $words as $w ) {
		if ( preg_match( '/\b' . preg_quote( $w, '/' ) . '/iu', $text ) ) {
			return 'keyword';
		}
	}
	$links    = preg_match_all( '#https?://|www\.|\b[a-z0-9-]+\.(?:pw|xyz|top|site|online|click|skin|ru|icu|shop|live|buzz)\b#i', $text );
	$persian  = preg_match( '/[\x{0600}-\x{06FF}]/u', $text );
	if ( $links > 2 || ( $links > 0 && ! $persian ) ) {
		return 'links';
	}
	return '';
}

/** A person's name that is only digits/symbols (e.g. «422444»). */
function psb_spam_check_name( $name ) {
	$name = trim( psb_spam_normalize_digits( $name ) );
	return ( '' !== $name && ! preg_match( '/\p{L}/u', $name ) ) ? 'numeric-name' : '';
}

/** Iranian mobile/landline: 09xxxxxxxxx, 0xx xxxxxxxx, +98/0098 forms. */
function psb_spam_check_phone( $phone ) {
	$digits = preg_replace( '/\D+/', '', psb_spam_normalize_digits( $phone ) );
	if ( '' === $digits ) {
		return '';
	}
	$digits = preg_replace( '/^(0098|98)/', '0', $digits );
	return preg_match( '/^0\d{9,10}$/', $digits ) ? '' : 'foreign-phone';
}

function psb_spam_log( $where, $reason ) {
	$log   = get_option( 'psb_spam_stats', array() );
	$key   = $where . ':' . $reason;
	$log[ $key ] = isset( $log[ $key ] ) ? $log[ $key ] + 1 : 1;
	update_option( 'psb_spam_stats', $log, false );
}

/* -------------------------------------------------------------------------
 * Gravity Forms
 * ---------------------------------------------------------------------- */

// Hidden fields right after the opening <form> tag.
add_filter(
	'gform_form_tag',
	static function ( $tag ) {
		return $tag . psb_spam_fields_html();
	},
	20
);

// Phone fields: the US "(###) ###-####" mask blocks Iranian numbers — use free format.
$psb_gf_phone_intl = static function ( $form ) {
	if ( ! empty( $form['fields'] ) ) {
		foreach ( $form['fields'] as $field ) {
			if ( 'phone' === $field->type ) {
				$field->phoneFormat = 'international';
			}
		}
	}
	return $form;
};
add_filter( 'gform_pre_render', $psb_gf_phone_intl );
add_filter( 'gform_pre_validation', $psb_gf_phone_intl );
add_filter( 'gform_pre_submission_filter', $psb_gf_phone_intl );

add_filter(
	'gform_entry_is_spam',
	static function ( $is_spam, $form, $entry ) {
		if ( $is_spam || is_user_logged_in() ) {
			return $is_spam;
		}
		$reason = psb_spam_check_request();
		if ( '' === $reason && ! empty( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				$value = '';
				if ( ! empty( $field->inputs ) && is_array( $field->inputs ) ) {
					$parts = array();
					foreach ( $field->inputs as $input ) {
						$parts[] = rgar( $entry, (string) $input['id'] );
					}
					$value = trim( implode( ' ', array_filter( $parts ) ) );
				} else {
					$value = (string) rgar( $entry, (string) $field->id );
				}
				if ( '' === $value ) {
					continue;
				}
				switch ( $field->type ) {
					case 'name':
						$reason = psb_spam_check_name( $value ) ?: psb_spam_check_text( $value );
						break;
					case 'phone':
						$reason = psb_spam_check_phone( $value );
						break;
					case 'email':
						$reason = preg_match( '/\.(ru|pw|xyz|top|icu|click|skin)$/i', $value ) ? 'email-tld' : '';
						break;
					case 'text':
					case 'textarea':
						$reason = psb_spam_check_text( $value );
						break;
				}
				if ( '' !== $reason ) {
					break;
				}
			}
		}
		if ( '' !== $reason ) {
			psb_spam_log( 'gf', $reason );
			if ( method_exists( 'GFCommon', 'set_spam_filter' ) ) {
				GFCommon::set_spam_filter( rgar( $form, 'id' ), 'PSB anti-spam', $reason );
			}
			return true;
		}
		return false;
	},
	10,
	3
);

/* -------------------------------------------------------------------------
 * Comments
 * ---------------------------------------------------------------------- */

add_action(
	'comment_form',
	static function () {
		if ( ! is_user_logged_in() ) {
			echo psb_spam_fields_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
);

add_filter(
	'pre_comment_approved',
	static function ( $approved, $data ) {
		if ( is_wp_error( $approved ) || 'spam' === $approved || is_user_logged_in() ) {
			return $approved;
		}
		// Trackbacks/pingbacks are disabled below; anything that still arrives is spam.
		if ( ! empty( $data['comment_type'] ) && in_array( $data['comment_type'], array( 'pingback', 'trackback' ), true ) ) {
			return 'spam';
		}
		$reason = psb_spam_check_request()
			?: psb_spam_check_name( $data['comment_author'] ?? '' )
			?: psb_spam_check_text( ( $data['comment_author'] ?? '' ) . ' ' . ( $data['comment_content'] ?? '' ) )
			?: ( ! empty( $data['comment_author_url'] ) ? 'author-url' : '' );
		if ( '' !== $reason ) {
			psb_spam_log( 'comment', $reason );
			return 'spam';
		}
		// Any link in a guest comment waits for moderation.
		if ( preg_match( '#https?://|www\.#i', (string) ( $data['comment_content'] ?? '' ) ) ) {
			return 0;
		}
		return $approved;
	},
	20,
	2
);

// No pingbacks/trackbacks (a classic spam channel for B2B sites).
add_filter( 'pings_open', '__return_false', 20 );
add_filter(
	'wp_headers',
	static function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);
add_filter(
	'xmlrpc_methods',
	static function ( $methods ) {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
		return $methods;
	}
);

// Comments only on blog posts — never on pages, products' WP comments aside (Woo reviews use their own type), or media.
add_filter(
	'comments_open',
	static function ( $open, $post_id ) {
		$type = get_post_type( $post_id );
		if ( in_array( $type, array( 'attachment', 'page' ), true ) ) {
			return false;
		}
		return $open;
	},
	20,
	2
);

// Gravity Forms' stock English confirmation → formal Persian.
add_filter(
	'gform_confirmation',
	static function ( $confirmation ) {
		if ( is_string( $confirmation ) && false !== stripos( $confirmation, 'Thanks for contacting us' ) ) {
			$confirmation = preg_replace( '/Thanks for contacting us![^<]*/i', 'پیام شما با موفقیت ثبت شد. کارشناسان شرکت در اولین فرصت با شما تماس خواهند گرفت.', $confirmation );
		}
		return $confirmation;
	},
	20
);
