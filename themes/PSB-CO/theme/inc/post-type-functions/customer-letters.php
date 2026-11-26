<?php
/**
 * «رضایت‌نامه‌ها» — customer letters CPT (customer_letter) + letter_type taxonomy,
 * an ACF-free meta box, the version-gated rewrite flush and the shared scan lightbox.
 *
 * Meta keys (all single):
 * - _psb_letter_org          client / organisation name
 * - _psb_letter_signer       signer name (optional)
 * - _psb_letter_signer_role  signer position (optional)
 * - _psb_letter_project      related portfolio post ID
 * - _psb_letter_date         letter date, Jalali text (e.g. ۱۴۰۳/۰۶/۱۲)
 * - _psb_letter_pdf          PDF attachment ID (optional; the scan itself is the featured image)
 * - _psb_letter_quote        highlight quote (1–2 sentences, shown on cards)
 * The full transcription of the letter goes in the editor (SEO + accessibility).
 *
 * @package bluebox
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Bump when CPTs / rewrite rules / default terms change (triggers one flush on admin_init). */
if ( ! defined( 'PSB_CPT_VERSION' ) ) {
	define( 'PSB_CPT_VERSION', '2026.11.26-letters-series' );
}

/** Default letter types (slug => name). */
function psb_letter_default_types() {
	return array(
		'satisfaction'    => 'رضایت‌نامه',
		'good-performance' => 'حسن انجام کار',
		'appreciation'    => 'تقدیرنامه',
	);
}

add_action(
	'init',
	function () {
		// Taxonomy first: its rules (customer-letters/type/…) must precede the CPT's attachment rules.
		register_taxonomy(
			'letter_type',
			'customer_letter',
			array(
				'labels'            => array(
					'name'          => 'نوع نامه',
					'singular_name' => 'نوع نامه',
					'menu_name'     => 'نوع نامه',
					'all_items'     => 'همه انواع',
					'add_new_item'  => 'افزودن نوع نامه',
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'       => 'customer-letters/type',
					'with_front' => false,
				),
			)
		);
		register_post_type(
			'customer_letter',
			array(
				'labels'        => array(
					'name'               => 'رضایت‌نامه‌ها',
					'singular_name'      => 'رضایت‌نامه',
					'menu_name'          => 'رضایت‌نامه‌ها',
					'all_items'          => 'همه رضایت‌نامه‌ها',
					'add_new'            => 'افزودن نامه',
					'add_new_item'       => 'افزودن رضایت‌نامه / تقدیرنامه',
					'edit_item'          => 'ویرایش نامه',
					'new_item'           => 'نامه جدید',
					'view_item'          => 'مشاهده نامه',
					'search_items'       => 'جستجوی نامه‌ها',
					'not_found'          => 'نامه‌ای پیدا نشد',
					'featured_image'     => 'اسکن نامه',
					'set_featured_image' => 'انتخاب اسکن نامه',
				),
				'public'        => true,
				'has_archive'   => 'customer-letters',
				'rewrite'       => array(
					'slug'       => 'customer-letters',
					'with_front' => false,
				),
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'menu_icon'     => 'dashicons-format-quote',
				'menu_position' => 26,
				'show_in_rest'  => false,
			)
		);

	},
	11
);

/**
 * One-time setup after a CPT change: default terms + rewrite flush. Runs in wp-admin only and only
 * when PSB_CPT_VERSION differs from the stored option, so it never flushes on normal page loads.
 */
add_action(
	'admin_init',
	function () {
		if ( get_option( 'psb_cpt_version' ) === PSB_CPT_VERSION || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( taxonomy_exists( 'letter_type' ) ) {
			foreach ( psb_letter_default_types() as $slug => $name ) {
				if ( ! term_exists( $slug, 'letter_type' ) && ! term_exists( $name, 'letter_type' ) ) {
					wp_insert_term( $name, 'letter_type', array( 'slug' => $slug ) );
				}
			}
		}
		flush_rewrite_rules( false );
		update_option( 'psb_cpt_version', PSB_CPT_VERSION, true );
	}
);

/* -------------------------------------------------------------------------
 * Data
 * ---------------------------------------------------------------------- */

/**
 * Normalised data of a letter.
 *
 * @return array org, signer, role, project (WP_Post|null), date, quote, pdf (psb_doc|null), scan (attachment ID), type (WP_Term|null)
 */
function psb_letter_data( $post_id ) {
	$project_id = (int) get_post_meta( $post_id, '_psb_letter_project', true );
	$project    = $project_id ? get_post( $project_id ) : null;
	if ( $project && 'publish' !== $project->post_status ) {
		$project = null;
	}
	$pdf_id = (int) get_post_meta( $post_id, '_psb_letter_pdf', true );
	$types  = get_the_terms( $post_id, 'letter_type' );
	return array(
		'org'     => trim( (string) get_post_meta( $post_id, '_psb_letter_org', true ) ),
		'signer'  => trim( (string) get_post_meta( $post_id, '_psb_letter_signer', true ) ),
		'role'    => trim( (string) get_post_meta( $post_id, '_psb_letter_signer_role', true ) ),
		'project' => $project,
		'date'    => trim( (string) get_post_meta( $post_id, '_psb_letter_date', true ) ),
		'quote'   => trim( (string) get_post_meta( $post_id, '_psb_letter_quote', true ) ),
		'pdf'     => $pdf_id ? psb_doc( $pdf_id ) : null,
		'scan'    => (int) get_post_thumbnail_id( $post_id ),
		'type'    => ( $types && ! is_wp_error( $types ) ) ? $types[0] : null,
	);
}

/**
 * Letters linked to a portfolio project.
 *
 * @return WP_Post[]
 */
function psb_project_letters( $project_id, $limit = 6 ) {
	if ( ! post_type_exists( 'customer_letter' ) ) {
		return array();
	}
	return get_posts(
		array(
			'post_type'   => 'customer_letter',
			'post_status' => 'publish',
			'numberposts' => $limit,
			'meta_key'    => '_psb_letter_project', // phpcs:ignore
			'meta_value'  => (int) $project_id, // phpcs:ignore
		)
	);
}

/* -------------------------------------------------------------------------
 * Lightbox (one shared native <dialog>, ~1 KB of vanilla JS, printed only when used)
 * Trigger: <button type="button" data-lightbox="{full image url}" data-lightbox-title="…" data-lightbox-pdf="{url}">
 * ---------------------------------------------------------------------- */

function psb_lightbox() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	add_action( 'wp_footer', 'psb_lightbox_markup', 30 );
}

function psb_lightbox_markup() {
	?>
	<dialog class="lightbox" id="psb-lightbox" aria-labelledby="psb-lightbox-title">
		<div class="lightbox-head">
			<p class="text-sm font-bold text-dark" id="psb-lightbox-title"></p>
			<button type="button" class="btn btn-ghost btn-icon" data-lightbox-close aria-label="بستن"><?php echo psb_icon( 'close' ); // phpcs:ignore ?></button>
		</div>
		<div class="lightbox-body"><img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt="" width="1" height="1" decoding="async"></div>
		<div class="lightbox-foot">
			<a class="btn btn-outline btn-sm hidden" data-lightbox-pdf-link href="#" target="_blank" rel="noopener"><?php echo psb_icon( 'doc', 'size-4' ); // phpcs:ignore ?> مشاهده فایل PDF</a>
			<a class="btn btn-ghost btn-sm" data-lightbox-full href="#" target="_blank" rel="noopener"><?php echo psb_icon( 'external', 'size-4' ); // phpcs:ignore ?> اندازه اصلی</a>
		</div>
	</dialog>
	<script>
	(function(){var d=document.getElementById('psb-lightbox');if(!d||!d.showModal)return;var img=d.querySelector('img'),t=d.querySelector('#psb-lightbox-title'),pdf=d.querySelector('[data-lightbox-pdf-link]'),full=d.querySelector('[data-lightbox-full]');
	document.addEventListener('click',function(e){var b=e.target.closest('[data-lightbox]');if(b){e.preventDefault();img.src=b.getAttribute('data-lightbox');img.alt=b.getAttribute('data-lightbox-title')||'';img.removeAttribute('width');img.removeAttribute('height');t.textContent=b.getAttribute('data-lightbox-title')||'';full.href=b.getAttribute('data-lightbox');var p=b.getAttribute('data-lightbox-pdf');pdf.classList.toggle('hidden',!p);if(p)pdf.href=p;d.showModal();return;}
	if(e.target.closest('[data-lightbox-close]')||e.target===d){d.close();}});
	d.addEventListener('close',function(){img.src='data:image/gif;base64,R0lGODlhAQABAAAAACw=';});})();
	</script>
	<?php
}

/* -------------------------------------------------------------------------
 * Admin meta box
 * ---------------------------------------------------------------------- */

add_action(
	'add_meta_boxes_customer_letter',
	function () {
		add_meta_box( 'psb-letter-details', 'مشخصات نامه', 'psb_letter_meta_box', 'customer_letter', 'normal', 'high' );
	}
);

function psb_letter_meta_box( $post ) {
	wp_nonce_field( 'psb_letter_save', 'psb_letter_nonce' );
	$v        = function ( $key ) use ( $post ) {
		return (string) get_post_meta( $post->ID, '_psb_letter_' . $key, true );
	};
	$projects = post_type_exists( 'portfolio' ) ? get_posts(
		array(
			'post_type'   => 'portfolio',
			'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
			'numberposts' => 200,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	) : array();
	$pdf_id   = (int) $v( 'pdf' );
	$pdf_url  = $pdf_id ? wp_get_attachment_url( $pdf_id ) : '';
	?>
	<style>.psb-mb{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))}.psb-mb label{display:block;font-weight:600;margin-bottom:4px}.psb-mb input[type=text],.psb-mb select,.psb-mb textarea{width:100%}.psb-mb .full{grid-column:1/-1}.psb-mb .desc{color:#646970;font-size:12px;margin-top:4px}</style>
	<p class="desc" style="margin-top:0">اسکن نامه را به‌عنوان «تصویر شاخص» (اسکن نامه) قرار دهید و متن کامل نامه را در ویرایشگر بنویسید (برای سئو و دسترس‌پذیری). فقط نامه‌های واقعی و دارای مجوز انتشار را وارد کنید.</p>
	<div class="psb-mb">
		<div><label for="psb_letter_org">نام کارفرما / سازمان</label><input type="text" id="psb_letter_org" name="psb_letter[org]" value="<?php echo esc_attr( $v( 'org' ) ); ?>"></div>
		<div><label for="psb_letter_date">تاریخ نامه (شمسی)</label><input type="text" id="psb_letter_date" name="psb_letter[date]" value="<?php echo esc_attr( $v( 'date' ) ); ?>" placeholder="۱۴۰۳/۰۶/۱۲" dir="ltr"></div>
		<div><label for="psb_letter_signer">نام امضاکننده (اختیاری)</label><input type="text" id="psb_letter_signer" name="psb_letter[signer]" value="<?php echo esc_attr( $v( 'signer' ) ); ?>"></div>
		<div><label for="psb_letter_signer_role">سمت امضاکننده (اختیاری)</label><input type="text" id="psb_letter_signer_role" name="psb_letter[signer_role]" value="<?php echo esc_attr( $v( 'signer_role' ) ); ?>"></div>
		<div>
			<label for="psb_letter_project">پروژه مرتبط</label>
			<select id="psb_letter_project" name="psb_letter[project]">
				<option value="">— بدون پروژه —</option>
				<?php foreach ( $projects as $p ) : ?>
					<option value="<?php echo (int) $p->ID; ?>" <?php selected( (int) $v( 'project' ), $p->ID ); ?>><?php echo esc_html( get_the_title( $p ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label>فایل PDF نامه (اختیاری)</label>
			<input type="hidden" id="psb_letter_pdf" name="psb_letter[pdf]" value="<?php echo $pdf_id ? (int) $pdf_id : ''; ?>">
			<button type="button" class="button" id="psb_letter_pdf_btn">انتخاب PDF</button>
			<button type="button" class="button-link-delete" id="psb_letter_pdf_clear" <?php echo $pdf_id ? '' : 'hidden'; ?>>حذف</button>
			<div class="desc" id="psb_letter_pdf_name"><?php echo $pdf_url ? esc_html( basename( $pdf_url ) ) : 'فایلی انتخاب نشده است'; ?></div>
		</div>
		<div class="full"><label for="psb_letter_quote">جمله منتخب (یک یا دو جمله از متن نامه، روی کارت‌ها نمایش داده می‌شود)</label><textarea id="psb_letter_quote" name="psb_letter[quote]" rows="3"><?php echo esc_textarea( $v( 'quote' ) ); ?></textarea><div class="desc">عین عبارت نامه را وارد کنید؛ متن ساختگی یا بازنویسی‌شده ننویسید.</div></div>
	</div>
	<script>
	jQuery(function($){var f;$('#psb_letter_pdf_btn').on('click',function(e){e.preventDefault();if(f){f.open();return;}f=wp.media({title:'انتخاب فایل PDF نامه',library:{type:'application/pdf'},button:{text:'انتخاب'},multiple:false});f.on('select',function(){var a=f.state().get('selection').first().toJSON();$('#psb_letter_pdf').val(a.id);$('#psb_letter_pdf_name').text(a.filename||a.title);$('#psb_letter_pdf_clear').prop('hidden',false);});f.open();});
	$('#psb_letter_pdf_clear').on('click',function(e){e.preventDefault();$('#psb_letter_pdf').val('');$('#psb_letter_pdf_name').text('فایلی انتخاب نشده است');$(this).prop('hidden',true);});});
	</script>
	<?php
}

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'customer_letter' === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
		}
	}
);

add_action(
	'save_post_customer_letter',
	function ( $post_id ) {
		if ( ! isset( $_POST['psb_letter_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['psb_letter_nonce'] ) ), 'psb_letter_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in     = isset( $_POST['psb_letter'] ) && is_array( $_POST['psb_letter'] ) ? wp_unslash( $_POST['psb_letter'] ) : array(); // phpcs:ignore
		$fields = array(
			'org'         => 'sanitize_text_field',
			'signer'      => 'sanitize_text_field',
			'signer_role' => 'sanitize_text_field',
			'date'        => 'sanitize_text_field',
			'quote'       => 'sanitize_textarea_field',
			'project'     => 'absint',
			'pdf'         => 'absint',
		);
		foreach ( $fields as $key => $cb ) {
			$value = isset( $in[ $key ] ) ? call_user_func( $cb, $in[ $key ] ) : '';
			if ( 'pdf' === $key && $value && 'application/pdf' !== get_post_mime_type( $value ) ) {
				$value = '';
			}
			if ( 'project' === $key && $value && 'portfolio' !== get_post_type( $value ) ) {
				$value = '';
			}
			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, '_psb_letter_' . $key );
			} else {
				update_post_meta( $post_id, '_psb_letter_' . $key, $value );
			}
		}
	}
);
