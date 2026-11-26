<?php
/**
 * Project records — meta box, list columns, filters, quick edit.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Meta box
 * ---------------------------------------------------------------------- */

add_action(
	'add_meta_boxes_' . PSB_PR_CPT,
	static function () {
		add_meta_box( 'psb-pr-details', 'مشخصات سابقه اجرایی', 'psb_pr_meta_box', PSB_PR_CPT, 'normal', 'high' );
	}
);

/** Meta box markup. */
function psb_pr_meta_box( $post ) {
	$m   = psb_pr_meta_keys();
	$get = static function ( $k ) use ( $post, $m ) {
		return get_post_meta( $post->ID, $m[ $k ], true );
	};
	wp_nonce_field( 'psb_pr_save', 'psb_pr_nonce' );
	$types    = (array) $get( 'types' );
	$show     = '0' !== (string) $get( 'show_client' );
	$pf       = (int) $get( 'portfolio' );
	$pf_posts = get_posts( array( 'post_type' => 'portfolio', 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
	$provs    = array_merge( psb_pr_provinces(), array( psb_pr_abroad_label() ) );
	$cur_prov = (string) $get( 'province' );
	if ( $cur_prov && ! in_array( $cur_prov, $provs, true ) ) {
		$provs[] = $cur_prov;
	}
	?>
	<style>
		.psb-pr-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px 18px}
		.psb-pr-grid label{display:block;font-weight:600;margin-bottom:4px}
		.psb-pr-grid input[type=text],.psb-pr-grid input[type=number],.psb-pr-grid select,.psb-pr-grid textarea{width:100%}
		.psb-pr-types{display:flex;flex-wrap:wrap;gap:6px 14px}
		.psb-pr-types label{font-weight:400}
		.psb-pr-full{grid-column:1/-1}
		.psb-pr-hint{color:#646970;font-size:12px;margin:4px 0 0}
		.psb-pr-box{border:1px solid #dcdcde;padding:10px 12px;background:#f6f7f7}
	</style>
	<div class="psb-pr-grid">
		<p><label for="pr-client">کارفرما</label><input type="text" id="pr-client" name="pr[client]" value="<?php echo esc_attr( $get( 'client' ) ); ?>"></p>
		<p><label for="pr-province">استان</label>
			<select id="pr-province" name="pr[province]">
				<option value="">—</option>
				<?php foreach ( $provs as $p ) : ?>
					<option value="<?php echo esc_attr( $p ); ?>" <?php selected( $cur_prov, $p ); ?>><?php echo esc_html( $p ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p><label for="pr-city">شهر / محل</label><input type="text" id="pr-city" name="pr[city]" value="<?php echo esc_attr( $get( 'city' ) ); ?>"></p>
		<p><label for="pr-year">سال اجرا (شمسی)</label><input type="number" id="pr-year" name="pr[year]" min="1300" max="1500" placeholder="۱۳۹۸" value="<?php echo $get( 'year' ) ? esc_attr( $get( 'year' ) ) : ''; ?>"></p>
		<p><label for="pr-qty">تعداد / حجم</label><input type="text" id="pr-qty" name="pr[quantity]" placeholder="مثلاً ۱۲ سلول" value="<?php echo esc_attr( $get( 'quantity' ) ); ?>"></p>
		<p><label for="pr-role">نوع قرارداد / نقش</label><input type="text" id="pr-role" name="pr[role]" placeholder="ساخت، EPC، نصب و راه‌اندازی…" value="<?php echo esc_attr( $get( 'role' ) ); ?>"></p>
		<div class="psb-pr-full"><label>نوع تابلو / دامنه کار</label>
			<div class="psb-pr-types">
				<?php foreach ( psb_pr_type_labels() as $k => $l ) : ?>
					<label><input type="checkbox" name="pr[types][]" value="<?php echo esc_attr( $k ); ?>" <?php checked( in_array( $k, $types, true ) ); ?>> <?php echo esc_html( $l ); ?></label>
				<?php endforeach; ?>
			</div>
		</div>
		<p class="psb-pr-full"><label for="pr-scope">شرح کار (در سایت نمایش داده می‌شود)</label><textarea id="pr-scope" name="pr[scope]" rows="2"><?php echo esc_textarea( $get( 'scope' ) ); ?></textarea></p>
		<div class="psb-pr-full psb-pr-box">
			<label><input type="checkbox" name="pr[show_client]" value="1" <?php checked( $show ); ?>> نمایش نام کارفرما</label>
			<p class="psb-pr-hint">برای کارفرماهای حساس تیک را بردارید: عنوان، کارفرما و شهر در سایت نمایش داده نمی‌شوند و به‌جای آن عبارتی عمومی مثل «ساخت تابلوهای فشار متوسط برای یک مجموعه صنعت نفت، گاز و پتروشیمی در استان خوزستان» نمایش داده می‌شود.</p>
			<p><label for="pr-ptitle" style="font-weight:400">عنوان عمومی دلخواه (اختیاری؛ فقط وقتی نام کارفرما مخفی است)</label><input type="text" id="pr-ptitle" name="pr[public_title]" value="<?php echo esc_attr( $get( 'public_title' ) ); ?>" style="width:100%"></p>
		</div>
		<p><label><input type="checkbox" name="pr[featured]" value="1" <?php checked( (bool) $get( 'featured' ) ); ?>> پروژه شاخص</label><span class="psb-pr-hint">در بخش «پروژه‌های شاخص» به‌صورت کارت بزرگ نمایش داده می‌شود.</span></p>
		<p><label for="pr-pf">نمونه‌کار مرتبط (برای عکس)</label>
			<select id="pr-pf" name="pr[portfolio]">
				<option value="0">—</option>
				<?php foreach ( $pf_posts as $p ) : ?>
					<option value="<?php echo (int) $p->ID; ?>" <?php selected( $pf, $p->ID ); ?>><?php echo esc_html( get_the_title( $p ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p class="psb-pr-full"><label for="pr-note">یادداشت داخلی (نمایش داده نمی‌شود)</label><textarea id="pr-note" name="pr[note]" rows="2"><?php echo esc_textarea( $get( 'note' ) ); ?></textarea>
			<?php if ( $get( 'inferred' ) ) : ?>
				<span class="psb-pr-hint">حوزه این ردیف هنگام ورود از روی عنوان حدس زده شده است؛ لطفاً بررسی کنید.</span>
			<?php endif; ?>
		</p>
	</div>
	<?php
}

add_action(
	'save_post_' . PSB_PR_CPT,
	static function ( $post_id ) {
		if ( ! isset( $_POST['psb_pr_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['psb_pr_nonce'] ) ), 'psb_pr_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['pr'] ) && is_array( $_POST['pr'] ) ? wp_unslash( $_POST['pr'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$m  = psb_pr_meta_keys();
		foreach ( array( 'client', 'province', 'city', 'quantity', 'role', 'public_title' ) as $k ) {
			update_post_meta( $post_id, $m[ $k ], psb_pr_clean( sanitize_text_field( $in[ $k ] ?? '' ) ) );
		}
		foreach ( array( 'scope', 'note' ) as $k ) {
			update_post_meta( $post_id, $m[ $k ], psb_pr_clean( sanitize_textarea_field( $in[ $k ] ?? '' ) ) );
		}
		update_post_meta( $post_id, $m['year'], psb_pr_parse_year( $in['year'] ?? '' ) );
		update_post_meta( $post_id, $m['types'], array_values( array_intersect( array_map( 'sanitize_key', (array) ( $in['types'] ?? array() ) ), array_keys( psb_pr_types() ) ) ) );
		update_post_meta( $post_id, $m['show_client'], empty( $in['show_client'] ) ? '0' : '1' );
		update_post_meta( $post_id, $m['featured'], empty( $in['featured'] ) ? '' : '1' );
		update_post_meta( $post_id, $m['portfolio'], absint( $in['portfolio'] ?? 0 ) );
		$title = get_post_field( 'post_title', $post_id );
		update_post_meta( $post_id, $m['key'], psb_pr_record_key( $title, get_post_meta( $post_id, $m['client'], true ), (int) get_post_meta( $post_id, $m['year'], true ) ) );
		if ( has_term( '', PSB_PR_TAX, $post_id ) ) {
			delete_post_meta( $post_id, $m['inferred'] );
		}
		psb_pr_flush_cache();
	},
	20
);

/* -------------------------------------------------------------------------
 * List table: columns, sorting, filters
 * ---------------------------------------------------------------------- */

add_filter(
	'manage_' . PSB_PR_CPT . '_posts_columns',
	static function ( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			if ( 'date' === $k ) {
				continue;
			}
			$new[ $k ] = $v;
			if ( 'title' === $k ) {
				$new['pr_client'] = 'کارفرما';
				$new['pr_loc']    = 'محل';
				$new['pr_year']   = 'سال';
				$new['pr_types']  = 'نوع تابلو';
				$new['pr_flags']  = 'نمایش';
			}
		}
		return $new;
	}
);

add_action(
	'manage_' . PSB_PR_CPT . '_posts_custom_column',
	static function ( $col, $post_id ) {
		$m = psb_pr_meta_keys();
		switch ( $col ) {
			case 'pr_client':
				echo esc_html( get_post_meta( $post_id, $m['client'], true ) );
				break;
			case 'pr_loc':
				$prov = get_post_meta( $post_id, $m['province'], true );
				$city = get_post_meta( $post_id, $m['city'], true );
				echo esc_html( psb_pr_location_label( $city, $prov ) );
				if ( $city && ! $prov ) {
					echo ' <span style="color:#b32d2e" title="استان شناسایی نشد">(بدون استان)</span>';
				}
				break;
			case 'pr_year':
				$y = (int) get_post_meta( $post_id, $m['year'], true );
				echo $y ? esc_html( $y ) : '—';
				break;
			case 'pr_types':
				$tl  = psb_pr_type_labels();
				$out = array();
				foreach ( (array) get_post_meta( $post_id, $m['types'], true ) as $t ) {
					if ( isset( $tl[ $t ] ) ) {
						$out[] = $tl[ $t ];
					}
				}
				echo esc_html( implode( '، ', $out ) );
				break;
			case 'pr_flags':
				$flags = array();
				if ( '0' === (string) get_post_meta( $post_id, $m['show_client'], true ) ) {
					$flags[] = '<span title="نام کارفرما مخفی است">ناشناس</span>';
				}
				if ( get_post_meta( $post_id, $m['featured'], true ) ) {
					$flags[] = '<strong>شاخص</strong>';
				}
				if ( get_post_meta( $post_id, $m['inferred'], true ) ) {
					$flags[] = '<span style="color:#996800">حوزه حدسی</span>';
				}
				if ( (int) get_post_meta( $post_id, $m['portfolio'], true ) ) {
					$flags[] = 'عکس';
				}
				echo wp_kses_post( implode( ' · ', $flags ) );
				// Inline data for quick edit.
				$data = array(
					'client'      => get_post_meta( $post_id, $m['client'], true ),
					'province'    => get_post_meta( $post_id, $m['province'], true ),
					'city'        => get_post_meta( $post_id, $m['city'], true ),
					'year'        => (int) get_post_meta( $post_id, $m['year'], true ),
					'show_client' => '0' === (string) get_post_meta( $post_id, $m['show_client'], true ) ? 0 : 1,
					'featured'    => get_post_meta( $post_id, $m['featured'], true ) ? 1 : 0,
				);
				echo '<script type="application/json" class="psb-pr-qe">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
				break;
		}
	},
	10,
	2
);

add_filter(
	'manage_edit-' . PSB_PR_CPT . '_sortable_columns',
	static function ( $cols ) {
		$cols['pr_client'] = 'pr_client';
		$cols['pr_year']   = 'pr_year';
		return $cols;
	}
);

add_action(
	'restrict_manage_posts',
	static function ( $post_type ) {
		if ( PSB_PR_CPT !== $post_type ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$cur_sector = isset( $_GET['pr_sector'] ) ? sanitize_key( wp_unslash( $_GET['pr_sector'] ) ) : '';
		$cur_prov   = isset( $_GET['pr_prov'] ) ? sanitize_text_field( wp_unslash( $_GET['pr_prov'] ) ) : '';
		$cur_flag   = isset( $_GET['pr_flag'] ) ? sanitize_key( wp_unslash( $_GET['pr_flag'] ) ) : '';
		// phpcs:enable
		echo '<select name="pr_sector"><option value="">همه حوزه‌ها</option>';
		foreach ( psb_pr_sector_names() as $slug => $name ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $slug ), selected( $cur_sector, $slug, false ), esc_html( $name ) );
		}
		echo '</select><select name="pr_prov"><option value="">همه استان‌ها</option><option value="__none" ' . selected( $cur_prov, '__none', false ) . '>بدون استان</option>';
		foreach ( array_merge( psb_pr_provinces(), array( psb_pr_abroad_label() ) ) as $p ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $p ), selected( $cur_prov, $p, false ), esc_html( $p ) );
		}
		echo '</select><select name="pr_flag"><option value="">همه</option>';
		foreach ( array( 'featured' => 'شاخص', 'anon' => 'ناشناس', 'inferred' => 'حوزه حدسی', 'noyear' => 'بدون سال' ) as $k => $l ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $cur_flag, $k, false ), esc_html( $l ) );
		}
		echo '</select>';
	}
);

add_action(
	'pre_get_posts',
	static function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || PSB_PR_CPT !== $q->get( 'post_type' ) ) {
			return;
		}
		$m = psb_pr_meta_keys();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$mq = array();
		if ( ! empty( $_GET['pr_sector'] ) ) {
			$q->set( 'tax_query', array( array( 'taxonomy' => PSB_PR_TAX, 'field' => 'slug', 'terms' => sanitize_key( wp_unslash( $_GET['pr_sector'] ) ) ) ) );
		}
		if ( ! empty( $_GET['pr_prov'] ) ) {
			$p    = sanitize_text_field( wp_unslash( $_GET['pr_prov'] ) );
			$mq[] = '__none' === $p ? array( 'relation' => 'OR', array( 'key' => $m['province'], 'value' => '' ), array( 'key' => $m['province'], 'compare' => 'NOT EXISTS' ) ) : array( 'key' => $m['province'], 'value' => $p );
		}
		$flag = isset( $_GET['pr_flag'] ) ? sanitize_key( wp_unslash( $_GET['pr_flag'] ) ) : '';
		// phpcs:enable
		if ( 'featured' === $flag ) {
			$mq[] = array( 'key' => $m['featured'], 'value' => '1' );
		} elseif ( 'anon' === $flag ) {
			$mq[] = array( 'key' => $m['show_client'], 'value' => '0' );
		} elseif ( 'inferred' === $flag ) {
			$mq[] = array( 'key' => $m['inferred'], 'value' => '1' );
		} elseif ( 'noyear' === $flag ) {
			$mq[] = array( 'relation' => 'OR', array( 'key' => $m['year'], 'value' => '0' ), array( 'key' => $m['year'], 'compare' => 'NOT EXISTS' ) );
		}
		if ( $mq ) {
			$q->set( 'meta_query', $mq );
		}
		$orderby = $q->get( 'orderby' );
		if ( 'pr_year' === $orderby ) {
			$q->set( 'meta_key', $m['year'] );
			$q->set( 'orderby', 'meta_value_num' );
		} elseif ( 'pr_client' === $orderby ) {
			$q->set( 'meta_key', $m['client'] );
			$q->set( 'orderby', 'meta_value' );
		} elseif ( ! $orderby ) {
			$q->set( 'orderby', array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) );
		}
	}
);

/* -------------------------------------------------------------------------
 * Quick edit
 * ---------------------------------------------------------------------- */

add_action(
	'quick_edit_custom_box',
	static function ( $col, $post_type ) {
		if ( PSB_PR_CPT !== $post_type || 'pr_flags' !== $col ) {
			return;
		}
		wp_nonce_field( 'psb_pr_qe', 'psb_pr_qe_nonce' );
		?>
		<fieldset class="inline-edit-col-right psb-pr-qe-box"><div class="inline-edit-col">
			<label><span class="title">کارفرما</span><span class="input-text-wrap"><input type="text" name="prq[client]"></span></label>
			<label><span class="title">استان</span><span class="input-text-wrap"><select name="prq[province]"><option value="">—</option>
				<?php foreach ( array_merge( psb_pr_provinces(), array( psb_pr_abroad_label() ) ) as $p ) : ?>
					<option value="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></option>
				<?php endforeach; ?>
			</select></span></label>
			<label><span class="title">شهر</span><span class="input-text-wrap"><input type="text" name="prq[city]"></span></label>
			<label><span class="title">سال</span><span class="input-text-wrap"><input type="number" name="prq[year]" min="1300" max="1500"></span></label>
			<label class="alignleft"><input type="checkbox" name="prq[show_client]" value="1"> <span class="checkbox-title">نمایش نام کارفرما</span></label>
			<label class="alignleft"><input type="checkbox" name="prq[featured]" value="1"> <span class="checkbox-title">شاخص</span></label>
		</div></fieldset>
		<?php
	},
	10,
	2
);

add_action(
	'admin_footer-edit.php',
	static function () {
		if ( PSB_PR_CPT !== get_current_screen()->post_type ) {
			return;
		}
		?>
		<script>
		(function(){
			if(!window.inlineEditPost)return;
			var orig=inlineEditPost.edit;
			inlineEditPost.edit=function(id){
				orig.apply(this,arguments);
				var pid=typeof id==='object'?parseInt(this.getId(id),10):id;
				var row=document.getElementById('post-'+pid),ed=document.getElementById('edit-'+pid);
				if(!row||!ed)return;
				var s=row.querySelector('.psb-pr-qe');if(!s)return;
				var d=JSON.parse(s.textContent);
				ed.querySelector('[name="prq[client]"]').value=d.client||'';
				ed.querySelector('[name="prq[province]"]').value=d.province||'';
				ed.querySelector('[name="prq[city]"]').value=d.city||'';
				ed.querySelector('[name="prq[year]"]').value=d.year||'';
				ed.querySelector('[name="prq[show_client]"]').checked=!!d.show_client;
				ed.querySelector('[name="prq[featured]"]').checked=!!d.featured;
			};
		})();
		</script>
		<?php
	}
);

add_action(
	'save_post_' . PSB_PR_CPT,
	static function ( $post_id ) {
		if ( ! isset( $_POST['psb_pr_qe_nonce'], $_POST['prq'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['psb_pr_qe_nonce'] ) ), 'psb_pr_qe' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = (array) wp_unslash( $_POST['prq'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$m  = psb_pr_meta_keys();
		foreach ( array( 'client', 'province', 'city' ) as $k ) {
			update_post_meta( $post_id, $m[ $k ], psb_pr_clean( sanitize_text_field( $in[ $k ] ?? '' ) ) );
		}
		update_post_meta( $post_id, $m['year'], psb_pr_parse_year( $in['year'] ?? '' ) );
		update_post_meta( $post_id, $m['show_client'], empty( $in['show_client'] ) ? '0' : '1' );
		update_post_meta( $post_id, $m['featured'], empty( $in['featured'] ) ? '' : '1' );
		update_post_meta( $post_id, $m['key'], psb_pr_record_key( get_post_field( 'post_title', $post_id ), get_post_meta( $post_id, $m['client'], true ), (int) get_post_meta( $post_id, $m['year'], true ) ) );
		psb_pr_flush_cache();
	},
	21
);
