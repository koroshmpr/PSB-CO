<?php
/**
 * Article series («سری مقالات»): taxonomy `series` on posts, ordered parts, pillar, accent colour.
 * No ACF dependency. Schema is not output here (inc/seo.php owns JSON-LD); a future `isPartOf`
 * can use psb_series_position().
 *
 * Term meta: _series_color (palette key), _series_icon (psb_icon name), _series_description, _series_pillar (post ID)
 * Post meta: _series_order (int, 1 = first part)
 *
 * @package bluebox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {
		register_taxonomy(
			'series',
			'post',
			array(
				'labels'            => array(
					'name'          => 'سری مقالات',
					'singular_name' => 'سری مقالات',
					'menu_name'     => 'سری مقالات',
					'all_items'     => 'همه سری‌ها',
					'edit_item'     => 'ویرایش سری',
					'add_new_item'  => 'افزودن سری جدید',
					'search_items'  => 'جستجوی سری‌ها',
					'not_found'     => 'سری‌ای پیدا نشد',
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'       => 'series',
					'with_front' => false,
				),
			)
		);
	},
	11
);

/* -------------------------------------------------------------------------
 * Palette (literal class strings so Tailwind compiles them)
 * ---------------------------------------------------------------------- */

function psb_series_palettes() {
	// Spec v2: red is the only accent. Keys are kept for stored term meta; all map to graphite + a red rule,
	// series differ by icon and name.
	$base = array( 'text' => 'text-foreground', 'bg' => 'bg-primary', 'soft' => 'bg-slate-100', 'border' => 'border-line', 'hex' => '#D60F1A' );
	return array(
		'blue'    => array( 'label' => 'پیش‌فرض' ) + $base,
		'amber'   => array( 'label' => 'پیش‌فرض (۲)' ) + $base,
		'emerald' => array( 'label' => 'پیش‌فرض (۳)' ) + $base,
		'violet'  => array( 'label' => 'پیش‌فرض (۴)' ) + $base,
		'rose'    => array( 'label' => 'پیش‌فرض (۵)' ) + $base,
		'slate'   => array( 'label' => 'پیش‌فرض (۶)' ) + $base,
	);
}

function psb_series_palette( $key ) {
	$p = psb_series_palettes();
	return $p[ $key ] ?? $p['blue'];
}

/* -------------------------------------------------------------------------
 * Data
 * ---------------------------------------------------------------------- */

/** Series term of a post (first one), or null. */
function psb_series_of( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	if ( ! $post_id || ! taxonomy_exists( 'series' ) ) {
		return null;
	}
	$terms = get_the_terms( $post_id, 'series' );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0] : null;
}

/**
 * Published post IDs of a series, ordered by _series_order (missing = last), then date.
 *
 * @return int[]
 */
function psb_series_posts( $term_id ) {
	static $cache = array();
	$term_id      = (int) $term_id;
	if ( isset( $cache[ $term_id ] ) ) {
		return $cache[ $term_id ];
	}
	$ids = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => (array) apply_filters( 'psb_series_statuses', array( 'publish' ) ), // Local preview may add drafts.
			'posts_per_page'   => 200,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'orderby'          => 'date',
			'order'            => 'ASC',
			'tax_query'        => array( // phpcs:ignore
				array(
					'taxonomy' => 'series',
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		)
	);
	if ( $ids ) {
		update_meta_cache( 'post', $ids );
	}
	$rows = array();
	foreach ( $ids as $pos => $id ) {
		$order  = get_post_meta( $id, '_series_order', true );
		$rows[] = array( (int) $id, ( '' === $order || null === $order ) ? PHP_INT_MAX : (int) $order, $pos );
	}
	usort(
		$rows,
		function ( $a, $b ) {
			return ( $a[1] <=> $b[1] ) ? ( $a[1] <=> $b[1] ) : ( $a[2] <=> $b[2] );
		}
	);
	$cache[ $term_id ] = array_column( $rows, 0 );
	return $cache[ $term_id ];
}

/**
 * Series info: name, description, colour, icon, url, posts, count, minutes, pillar.
 */
function psb_series_info( $term ) {
	$term = is_object( $term ) ? $term : get_term( (int) $term, 'series' );
	if ( ! $term || is_wp_error( $term ) ) {
		return null;
	}
	$posts   = psb_series_posts( $term->term_id );
	$minutes = 0;
	foreach ( $posts as $id ) {
		$minutes += psb_reading_minutes( $id );
	}
	$pillar = (int) get_term_meta( $term->term_id, '_series_pillar', true );
	if ( ! $pillar || ! in_array( $pillar, $posts, true ) ) {
		$pillar = $posts[0] ?? 0;
	}
	$desc  = trim( (string) get_term_meta( $term->term_id, '_series_description', true ) );
	$icon  = sanitize_key( (string) get_term_meta( $term->term_id, '_series_icon', true ) );
	$color = (string) get_term_meta( $term->term_id, '_series_color', true );
	return array(
		'id'          => (int) $term->term_id,
		'term'        => $term,
		'name'        => $term->name,
		'description' => $desc ? $desc : wp_strip_all_tags( term_description( $term ) ),
		'color'       => isset( psb_series_palettes()[ $color ] ) ? $color : 'blue',
		'palette'     => psb_series_palette( $color ),
		'icon'        => ( $icon && psb_icon( $icon ) ) ? $icon : 'doc',
		'url'         => get_term_link( $term ),
		'posts'       => $posts,
		'count'       => count( $posts ),
		'minutes'     => $minutes,
		'pillar'      => $pillar,
	);
}

/** All series with at least one published post, biggest first. */
function psb_series_all() {
	if ( ! taxonomy_exists( 'series' ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'series',
			'hide_empty' => false, // Counted below with psb_series_posts() (term counts ignore preview statuses).
		)
	);
	$out   = array();
	foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
		$info = psb_series_info( $t );
		if ( $info && $info['count'] ) {
			$out[] = $info;
		}
	}
	usort(
		$out,
		function ( $a, $b ) {
			return $b['count'] <=> $a['count'];
		}
	);
	return $out;
}

/**
 * Position of a post in its series.
 *
 * @return array|null info, index, number, total, prev, next
 */
function psb_series_position( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	$term    = psb_series_of( $post_id );
	if ( ! $term ) {
		return null;
	}
	$info  = psb_series_info( $term );
	$index = array_search( $post_id, $info['posts'], true );
	if ( false === $index ) {
		return null;
	}
	return array(
		'info'   => $info,
		'index'  => $index,
		'number' => $index + 1,
		'total'  => $info['count'],
		'prev'   => $info['posts'][ $index - 1 ] ?? 0,
		'next'   => $info['posts'][ $index + 1 ] ?? 0,
	);
}

/** Series archives list every part on one page (the template orders them with psb_series_posts()). */
add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->is_tax( 'series' ) ) {
			$q->set( 'posts_per_page', 100 );
		}
	}
);

/* -------------------------------------------------------------------------
 * Admin: term meta fields
 * ---------------------------------------------------------------------- */

function psb_series_term_fields( $term = null ) {
	$get   = function ( $key ) use ( $term ) {
		return $term ? (string) get_term_meta( $term->term_id, $key, true ) : '';
	};
	$posts = $term ? psb_series_posts( $term->term_id ) : array();
	$rows  = array(
		'color'       => array( 'رنگ شاخص', '' ),
		'icon'        => array( 'آیکون', 'یکی از: bolt, factory, flask, shield, doc, cog, award, grid' ),
		'description' => array( 'توضیح کوتاه', 'یک یا دو جمله برای کارت سری و بالای صفحه سری.' ),
		'pillar'      => array( 'مقاله ستون (Pillar)', 'مقاله اصلی سری؛ در صفحه سری اول نمایش داده می‌شود.' ),
	);
	wp_nonce_field( 'psb_series_term', 'psb_series_term_nonce' );
	foreach ( $rows as $key => $row ) {
		$name  = 'psb_series[' . $key . ']';
		$value = $get( '_series_' . $key );
		ob_start();
		if ( 'color' === $key ) {
			echo '<select name="' . esc_attr( $name ) . '" id="psb-series-color">';
			foreach ( psb_series_palettes() as $pk => $pv ) {
				echo '<option value="' . esc_attr( $pk ) . '" ' . selected( $value ? $value : 'blue', $pk, false ) . '>' . esc_html( $pv['label'] ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'description' === $key ) {
			echo '<textarea name="' . esc_attr( $name ) . '" rows="3" class="large-text">' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'pillar' === $key ) {
			echo '<select name="' . esc_attr( $name ) . '"><option value="">— خودکار (قسمت اول) —</option>';
			foreach ( $posts as $pid ) {
				echo '<option value="' . (int) $pid . '" ' . selected( (int) $value, $pid, false ) . '>' . esc_html( get_the_title( $pid ) ) . '</option>';
			}
			echo '</select>';
			if ( ! $term ) {
				echo '<p class="description">پس از افزودن مقاله‌ها به سری قابل انتخاب است.</p>';
			}
		} else {
			echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" dir="ltr">';
		}
		$field = ob_get_clean();
		if ( $term ) {
			echo '<tr class="form-field"><th scope="row"><label>' . esc_html( $row[0] ) . '</label></th><td>' . $field . ( $row[1] ? '<p class="description">' . esc_html( $row[1] ) . '</p>' : '' ) . '</td></tr>'; // phpcs:ignore
		} else {
			echo '<div class="form-field"><label>' . esc_html( $row[0] ) . '</label>' . $field . ( $row[1] ? '<p>' . esc_html( $row[1] ) . '</p>' : '' ) . '</div>'; // phpcs:ignore
		}
	}
}
add_action( 'series_add_form_fields', 'psb_series_term_fields' );
add_action( 'series_edit_form_fields', 'psb_series_term_fields' );

function psb_series_save_term( $term_id ) {
	if ( ! isset( $_POST['psb_series_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['psb_series_term_nonce'] ) ), 'psb_series_term' ) || ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$in = isset( $_POST['psb_series'] ) && is_array( $_POST['psb_series'] ) ? wp_unslash( $_POST['psb_series'] ) : array(); // phpcs:ignore
	$v  = array(
		'color'       => isset( psb_series_palettes()[ $in['color'] ?? '' ] ) ? $in['color'] : 'blue',
		'icon'        => sanitize_key( $in['icon'] ?? '' ),
		'description' => sanitize_textarea_field( $in['description'] ?? '' ),
		'pillar'      => absint( $in['pillar'] ?? 0 ),
	);
	foreach ( $v as $key => $value ) {
		if ( '' === $value || 0 === $value ) {
			delete_term_meta( $term_id, '_series_' . $key );
		} else {
			update_term_meta( $term_id, '_series_' . $key, $value );
		}
	}
}
add_action( 'created_series', 'psb_series_save_term' );
add_action( 'edited_series', 'psb_series_save_term' );

/* -------------------------------------------------------------------------
 * Admin: post meta box (part number)
 * ---------------------------------------------------------------------- */

add_action(
	'add_meta_boxes_post',
	function () {
		add_meta_box(
			'psb-series-order',
			'ترتیب در سری مقالات',
			function ( $post ) {
				wp_nonce_field( 'psb_series_order', 'psb_series_order_nonce' );
				$order = get_post_meta( $post->ID, '_series_order', true );
				echo '<p><label for="psb_series_order">شماره قسمت (۱ = اول)</label></p>';
				echo '<input type="number" min="1" step="1" id="psb_series_order" name="psb_series_order" value="' . esc_attr( $order ) . '" style="width:6rem">';
				echo '<p class="description">سری را از جعبه «سری مقالات» انتخاب کنید. بدون شماره، مقاله بر اساس تاریخ در انتهای سری قرار می‌گیرد.</p>';
			},
			'post',
			'side',
			'default'
		);
	}
);

add_action(
	'save_post_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['psb_series_order_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['psb_series_order_nonce'] ) ), 'psb_series_order' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$order = isset( $_POST['psb_series_order'] ) ? absint( $_POST['psb_series_order'] ) : 0;
		if ( $order ) {
			update_post_meta( $post_id, '_series_order', $order );
		} else {
			delete_post_meta( $post_id, '_series_order' );
		}
	}
);

/** Admin column for the part number. */
add_filter(
	'manage_post_posts_columns',
	function ( $cols ) {
		$cols['psb_series_order'] = 'قسمت';
		return $cols;
	}
);
add_action(
	'manage_post_posts_custom_column',
	function ( $col, $post_id ) {
		if ( 'psb_series_order' === $col ) {
			$o = get_post_meta( $post_id, '_series_order', true );
			echo $o ? esc_html( psb_fa_num( $o ) ) : '—';
		}
	},
	10,
	2
);

/* -------------------------------------------------------------------------
 * Front-end script (printed once in the footer, only on pages that use series parts)
 * localStorage "psbRead" = array of read post IDs (a post counts as read at 60% scroll).
 * ---------------------------------------------------------------------- */

function psb_series_script() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	add_action( 'wp_footer', 'psb_series_script_print', 40 );
}

function psb_series_script_print() {
	?>
	<script>
	(function(){
	var K='psbRead',read=[];try{read=JSON.parse(localStorage.getItem(K)||'[]')||[];}catch(e){}
	function save(){try{localStorage.setItem(K,JSON.stringify(read.slice(-500)));}catch(e){}}
	function has(id){return read.indexOf(String(id))>-1;}
	function paint(){
		document.querySelectorAll('[data-series-item]').forEach(function(li){li.classList.toggle('is-read',has(li.getAttribute('data-series-item')));});
		document.querySelectorAll('[data-series-progress]').forEach(function(bar){var ids=bar.getAttribute('data-series-progress').split(',').filter(Boolean),n=ids.filter(has).length;bar.style.width=(ids.length?Math.round(n/ids.length*100):0)+'%';});
		document.querySelectorAll('[data-series-progress-label]').forEach(function(l){var ids=l.getAttribute('data-series-progress-label').split(',').filter(Boolean),n=ids.filter(has).length;if(n){l.textContent=n.toLocaleString('fa-IR')+' از '+ids.length.toLocaleString('fa-IR')+' خوانده‌شده';}});
	}
	paint();
	var cur=document.querySelector('[data-series-current]'),bar=document.querySelector('[data-series-bar]'),art=document.querySelector('[data-article]');
	if(!art)return;
	var id=cur?cur.getAttribute('data-series-current'):art.getAttribute('data-article'),ticking=false;
	function onScroll(){ticking=false;var r=art.getBoundingClientRect(),h=r.height-window.innerHeight,p=h>0?Math.min(1,Math.max(0,-r.top/h)):1;
		if(p>=0.6&&id&&!has(id)){read.push(String(id));save();paint();}
		if(bar)bar.classList.toggle('is-visible',p>=0.6&&r.bottom>window.innerHeight*0.5);}
	window.addEventListener('scroll',function(){if(!ticking){ticking=true;requestAnimationFrame(onScroll);}},{passive:true});onScroll();
	})();
	</script>
	<?php
}
