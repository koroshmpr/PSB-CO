<?php
/**
 * SEO helpers loaded by inc/seo.php: one representative image per URL, the og:image fallback and
 * real descriptions for thin CPT archives.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Representative image (attachment ID) for the current request, or 0:
 * page photo fields → featured image → product gallery → first library image in the content →
 * the home hero photo. Blocked (AI-render / low-quality) attachments are skipped.
 */
function psb_seo_image_id() {
	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}
	$ok    = function ( $id ) {
		$id = (int) $id;
		return ( $id && wp_attachment_is_image( $id ) && ! ( function_exists( 'psb_image_blocked' ) && psb_image_blocked( $id ) ) ) ? $id : 0;
	};
	$front = (int) get_option( 'page_on_front' );
	$field = function ( $name, $post_id ) {
		return function_exists( 'psb_pf' ) ? psb_pf_image_id( psb_pf( $name, $post_id ) ) : 0;
	};
	$cands = array();
	if ( is_front_page() ) {
		$cands[] = $field( 'home_hero_image', $front );
	} elseif ( is_singular() ) {
		$post_id = get_queried_object_id();
		if ( is_page_template( 'about-us.php' ) ) {
			$cands[] = $field( 'about_hero_image', $post_id );
			$cands[] = $field( 'about_intro_image', $post_id );
		}
		$cands[] = get_post_thumbnail_id( $post_id );
		if ( 'product' === get_post_type( $post_id ) ) {
			$cands = array_merge( $cands, array_map( 'intval', explode( ',', (string) get_post_meta( $post_id, '_product_image_gallery', true ) ) ) );
		}
		if ( preg_match_all( '/wp-image-(\d+)/', (string) get_post_field( 'post_content', $post_id ), $m ) ) {
			$cands = array_merge( $cands, array_slice( $m[1], 0, 5 ) );
		}
	}
	$cands[] = $field( 'home_hero_image', $front ); // Site-wide fallback.
	$memo    = 0;
	foreach ( $cands as $c ) {
		if ( $ok( $c ) ) {
			$memo = (int) $c;
			break;
		}
	}
	return $memo;
}

/** ImageObject for an attachment (full size, with dimensions), or null. */
function psb_seo_image_object( $att_id ) {
	$src = $att_id ? wp_get_attachment_image_src( $att_id, 'full' ) : false;
	if ( ! $src ) {
		return null;
	}
	return array(
		'@type'  => 'ImageObject',
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
	);
}

/** og:image / twitter:image when Rank Math found none for this URL. */
foreach ( array( 'facebook', 'twitter' ) as $psb_seo_net ) {
	add_action(
		'rank_math/opengraph/' . $psb_seo_net . '/add_additional_images',
		function ( $image ) {
			if ( is_object( $image ) && method_exists( $image, 'has_images' ) && ! $image->has_images() ) {
				$id = psb_seo_image_id();
				if ( $id ) {
					$image->add_image_by_id( $id );
				}
			}
		}
	);
}
unset( $psb_seo_net );

/** About page: the hero photo field is the share image (before Rank Math adds the featured image). */
add_action(
	'rank_math/opengraph/facebook/add_images',
	function ( $image ) {
		if ( is_object( $image ) && is_page_template( 'about-us.php' ) && function_exists( 'psb_pf' ) ) {
			$id = psb_pf_image_id( psb_pf( 'about_hero_image', get_queried_object_id() ) );
			if ( $id ) {
				$image->add_image_by_id( $id );
			}
		}
	}
);

/**
 * Pages and thin CPT archives without a Rank Math description get a real one
 * (Rank Math's description meta is protected from the remote API, so these live in code).
 */
add_filter(
	'rank_math/frontend/description',
	function ( $desc ) {
		$pages = array(
			'request-quote'      => 'فرم استعلام قیمت تابلو برق فشار ضعیف، فشار متوسط و کمپکت ' . psb_brand_name() . '؛ مشخصات پروژه را ثبت کنید تا کارشناسان فنی قیمت و زمان ساخت را اعلام کنند.',
			'project-references' => 'سوابق اجرایی ' . psb_brand_name() . ': فهرست پروژه‌های ساخت و تأمین تابلو برق فشار ضعیف و فشار متوسط برای صنایع، شبکه‌های توزیع برق، ساختمان‌ها و نیروگاه‌ها.',
		);
		if ( is_page() && '' === trim( (string) $desc ) ) {
			$slug = urldecode( (string) get_post_field( 'post_name', get_queried_object_id() ) );
			if ( isset( $pages[ $slug ] ) ) {
				return $pages[ $slug ];
			}
		}
		$archive = array(
			'services'  => 'خدمات ' . psb_brand_name() . ': ورق‌کاری CNC، رنگ پودری الکترواستاتیک، مونتاژ و آزمون روتین تابلو برق، نصب و راه‌اندازی، و ' . psb_fa_num( PSB_SERVICE_YEARS ) . ' سال خدمات پس از فروش.',
			'portfolio' => 'نمونه کارهای ' . psb_brand_name() . ': تابلوهای برق فشار ضعیف و فشار متوسط ساخته‌شده برای کارخانه‌ها، شبکه‌های توزیع برق، ساختمان‌ها و صنایع کشور.',
		);
		foreach ( $archive as $type => $text ) {
			if ( is_post_type_archive( $type ) && mb_strlen( trim( (string) $desc ) ) < 80 ) {
				return $text;
			}
		}
		return $desc;
	}
);
