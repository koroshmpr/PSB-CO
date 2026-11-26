<?php
/**
 * AI visibility — cache invalidation and background warm-up.
 *
 * Every relevant change only marks the cache dirty (static flag). At shutdown
 * the revision counter is bumped once and a single warm-up event is scheduled
 * (deduplicated), so a burst of save_post / meta hooks costs one tiny option
 * write.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

const PSB_AI_WARM_HOOK = 'psb_ai_warm_cache';

/**
 * Mark the cache dirty for this request.
 */
function psb_ai_invalidate( $reason = '' ) {
	static $registered = false;
	$GLOBALS['psb_ai_dirty_reason'] = (string) $reason;
	if ( ! $registered ) {
		$registered = true;
		add_action( 'shutdown', 'psb_ai_commit_invalidation', 1 );
	}
}

function psb_ai_commit_invalidation() {
	$state                = psb_ai_state();
	$state['rev']         = (int) $state['rev'] + 1;
	$state['invalidated'] = time();
	$state['reason']      = substr( (string) ( $GLOBALS['psb_ai_dirty_reason'] ?? '' ), 0, 100 );
	update_option( PSB_AI_STATE_OPTION, $state, false );

	if ( ! wp_next_scheduled( PSB_AI_WARM_HOOK ) ) {
		wp_schedule_single_event( time() + 60, PSB_AI_WARM_HOOK );
	}
}

add_action(
	PSB_AI_WARM_HOOK,
	static function () {
		foreach ( array_keys( psb_ai_cached_docs() ) as $key ) {
			psb_ai_get_doc( $key );
		}
	}
);

/**
 * Whether a post change can affect the AI documents.
 */
function psb_ai_post_is_relevant( $post ) {
	$post = get_post( $post );
	if ( ! $post || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
		return false;
	}
	return in_array( $post->post_type, psb_ai_included_post_types(), true );
}

// Publish / unpublish / trash / restore.
add_action(
	'transition_post_status',
	static function ( $new, $old, $post ) {
		if ( ( 'publish' === $new || 'publish' === $old ) && psb_ai_post_is_relevant( $post ) ) {
			psb_ai_invalidate( 'status:' . $post->ID );
		}
	},
	10,
	3
);

// Edits of already published items.
add_action(
	'save_post',
	static function ( $post_id, $post ) {
		if ( $post && 'publish' === $post->post_status && psb_ai_post_is_relevant( $post ) ) {
			psb_ai_invalidate( 'save:' . $post_id );
		}
	},
	20,
	2
);

foreach ( array( 'deleted_post', 'trashed_post', 'untrashed_post' ) as $psb_ai_hook ) {
	add_action(
		$psb_ai_hook,
		static function ( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || psb_ai_post_is_relevant( $post ) ) {
				psb_ai_invalidate( 'delete:' . $post_id );
			}
		}
	);
}
unset( $psb_ai_hook );

// Rank Math meta (description, robots) — never react to other meta such as view counters.
$psb_ai_meta_cb = static function ( $meta_id, $object_id, $meta_key ) {
	if ( is_string( $meta_key ) && 0 === strpos( $meta_key, 'rank_math_' ) && psb_ai_post_is_relevant( $object_id ) ) {
		$post = get_post( $object_id );
		if ( $post && 'publish' === $post->post_status ) {
			psb_ai_invalidate( 'meta:' . $object_id );
		}
	}
};
add_action( 'added_post_meta', $psb_ai_meta_cb, 10, 3 );
add_action( 'updated_post_meta', $psb_ai_meta_cb, 10, 3 );
add_action( 'deleted_post_meta', $psb_ai_meta_cb, 10, 3 );
unset( $psb_ai_meta_cb );

// Terms (categories appear in the Optional section).
foreach ( array( 'created_term', 'edited_term', 'delete_term' ) as $psb_ai_hook ) {
	add_action(
		$psb_ai_hook,
		static function ( $term_id, $tt_id = 0, $taxonomy = '' ) {
			if ( in_array( $taxonomy, array( 'category', 'product_cat' ), true ) ) {
				psb_ai_invalidate( 'term:' . $term_id );
			}
		},
		10,
		3
	);
}
unset( $psb_ai_hook );

// ACF options page (contact info) and front page / site info.
add_action(
	'acf/save_post',
	static function ( $post_id ) {
		if ( 'options' === $post_id || 'option' === $post_id ) {
			psb_ai_invalidate( 'acf-options' );
		} elseif ( is_numeric( $post_id ) && psb_ai_post_is_relevant( (int) $post_id ) ) {
			psb_ai_invalidate( 'acf:' . $post_id );
		}
	},
	20
);

foreach ( array( 'update_option_blogname', 'update_option_blogdescription', 'update_option_page_on_front', 'update_option_sticky_posts', 'update_option_' . PSB_AI_OPTION ) as $psb_ai_hook ) {
	add_action(
		$psb_ai_hook,
		static function () {
			psb_ai_invalidate( 'option' );
		}
	);
}
unset( $psb_ai_hook );
