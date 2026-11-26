<?php
/**
 * PSB SEO: structured data (JSON-LD) that complements Rank Math.
 *
 * What Rank Math already prints (checked on the live site, 2026-11):
 *   - Global graph on pages/posts/products/archives: Person/Organization (#person), WebSite, WebPage/ItemPage/
 *     CollectionPage, Article (also on static pages), BlogPosting on posts, Product on products.
 *   - NOTHING at all on single confirmation / type-test / services / portfolio items (schema type "None").
 * What the theme already prints:
 *   - BreadcrumbList on single posts (template-parts/blog/single/breadcrumb-arrow.php) and in
 *     template-parts/global/breadcrumb.php when that partial is used.
 *   - FAQPage wherever template-parts/global/faq-list.php renders a visible FAQ block.
 *
 * This file therefore:
 *   1. Enriches Rank Math's organisation node instead of adding a second one: Organization type, correct name,
 *      foundingDate (۱۳۵۵ = 1976, stated on the home page), address / phones / email / socials from the ACF
 *      options page "site-setting", office + factory locations with geo from the stored Google-Maps embeds.
 *   2. Fixes page-level types: no Article on static pages, AboutPage / ContactPage on those templates.
 *   3. Cleans the Product node for catalogue products: plain product name, brand + manufacturer = the
 *      organisation, description, no empty offers (catalogue has no prices).
 *   4. Where Rank Math printed nothing (CPT singles), prints a minimal graph: Organization, WebSite, WebPage.
 *   5. Prints BreadcrumbList in the footer only when neither Rank Math nor a theme breadcrumb partial did.
 *   FAQPage is intentionally not printed here (faq-list.php already does it next to the visible FAQ).
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/** Shared state for one request. */
function psb_seo_state( $key = null, $value = null ) {
	static $state = array(
		'rm_ran'        => false, // rank_math/json_ld filter ran (Rank Math printed a graph).
		'rm_breadcrumb' => false, // Rank Math graph contained a BreadcrumbList.
		'theme_crumb'   => false, // a theme breadcrumb partial (with its own JSON-LD) was rendered.
	);
	if ( null === $key ) {
		return $state;
	}
	if ( null !== $value ) {
		$state[ $key ] = $value;
	}
	return $state[ $key ] ?? null;
}

function psb_seo_org_id() {
	return home_url( '/#organization' );
}

function psb_seo_website_id() {
	return home_url( '/#website' );
}

/** Persian/Arabic digits to Latin. */
function psb_seo_latin_digits( $str ) {
	return strtr(
		(string) $str,
		array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		)
	);
}

/** "02166707011" -> "+982166707011" (E.164). Returns '' when not a usable Iranian number. */
function psb_seo_phone( $raw ) {
	$digits = preg_replace( '/\D+/', '', psb_seo_latin_digits( $raw ) );
	if ( '' === $digits ) {
		return '';
	}
	if ( 0 === strpos( $digits, '0098' ) ) {
		$digits = substr( $digits, 4 );
	} elseif ( 0 === strpos( $digits, '98' ) && strlen( $digits ) > 10 ) {
		$digits = substr( $digits, 2 );
	} elseif ( 0 === strpos( $digits, '0' ) ) {
		$digits = substr( $digits, 1 );
	}
	return strlen( $digits ) >= 8 ? '+98' . $digits : '';
}

function psb_seo_option( $name ) {
	return function_exists( 'get_field' ) ? get_field( $name, 'option' ) : null;
}

/** Extract lat/lng from a Google Maps embed iframe (…!2d<lng>!3d<lat>…). */
function psb_seo_geo_from_embed( $html ) {
	if ( is_string( $html ) && preg_match( '/!2d(-?[0-9.]+)!3d(-?[0-9.]+)/', $html, $m ) ) {
		return array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $m[2],
			'longitude' => (float) $m[1],
		);
	}
	return null;
}

function psb_seo_postal_address( $text ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $text ) ) );
	if ( '' === $text ) {
		return null;
	}
	$address = array(
		'@type'          => 'PostalAddress',
		'streetAddress'  => $text,
		'addressCountry' => 'IR',
	);
	if ( 0 === mb_strpos( $text, 'تهران' ) ) {
		$address['addressLocality'] = 'تهران';
		$address['addressRegion']   = 'استان تهران';
	}
	return $address;
}

/**
 * Organisation properties built from the ACF options page (site-setting) and facts stated on the site.
 * Only non-empty values are returned.
 */
function psb_seo_org_data() {
	static $org = null;
	if ( null !== $org ) {
		return $org;
	}

	$name = 'پارس سوئیچ بورد';
	$org  = array(
		'@type'         => 'Organization',
		'@id'           => psb_seo_org_id(),
		'name'          => $name,
		// Legal name as written in the Tavanir / Tehran-Bozorg lists and EPIL reports hosted on the site.
		'legalName'     => 'شرکت تولیدی و صنعتی پارس سوئیچ بورد',
		'alternateName' => array( 'Pars Switch Board', 'PSB' ),
		'url'           => home_url( '/' ),
		'foundingDate'  => '1976', // سال ۱۳۵۵ — stated on the home page ("با تاسیس شرکت در سال 1355").
		'description'   => 'طراح و سازنده تابلو برق فشار ضعیف، فشار متوسط، کمپکت و تابلوهای کنترل صنعتی.',
		'knowsAbout'    => array( 'تابلو برق فشار ضعیف', 'تابلو برق فشار متوسط', 'تابلو کمپکت', 'تابلو فرمان و کنترل', 'تابلو اندازه‌گیری (کنتوری)', 'IEC 61439' ),
		'areaServed'    => array( '@type' => 'Country', 'name' => 'Iran' ),
	);

	$brand = psb_seo_option( 'brand_name' );
	if ( is_string( $brand ) && '' !== trim( $brand ) && trim( $brand ) !== $name ) {
		$org['alternateName'][] = trim( $brand );
	}

	// Logo: Rank Math's logo node is kept when enriching; this is only used by the fallback graph.
	$logo_id = function_exists( 'get_theme_mod' ) ? (int) get_theme_mod( 'custom_logo' ) : 0;
	$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	if ( $logo ) {
		$org['logo'] = $logo;
	}

	// Email.
	$emails = psb_seo_option( 'emails' );
	if ( is_array( $emails ) ) {
		foreach ( $emails as $row ) {
			$email = sanitize_email( trim( (string) ( $row['email'] ?? '' ) ) );
			if ( $email ) {
				$org['email'] = $email;
				break;
			}
		}
	}

	// Phones: first non-fax number is the main telephone; all go into contactPoint.
	$phones         = psb_seo_option( 'phones' );
	$contact_points = array();
	if ( is_array( $phones ) ) {
		foreach ( $phones as $row ) {
			$title  = trim( (string) ( $row['title'] ?? '' ) );
			$number = psb_seo_phone( $row['number'] ?? '' );
			if ( '' === $number ) {
				continue;
			}
			if ( false !== mb_strpos( $title, 'فاکس' ) ) {
				$org['faxNumber'] = $number;
				continue;
			}
			if ( empty( $org['telephone'] ) ) {
				$org['telephone'] = $number;
			}
			$contact_points[] = array(
				'@type'             => 'ContactPoint',
				'telephone'         => $number,
				'contactType'       => $title ? $title : 'customer service',
				'areaServed'        => 'IR',
				'availableLanguage' => array( 'fa' ),
			);
		}
	}
	if ( empty( $org['telephone'] ) ) {
		$fallback = psb_seo_phone( psb_seo_option( 'phone' ) );
		if ( $fallback ) {
			$org['telephone'] = $fallback;
		}
	}
	if ( $contact_points ) {
		$org['contactPoint'] = $contact_points;
	}

	// Addresses: "contact" repeater (office, factory) with map embeds; fall back to the single "address" field.
	$contacts  = psb_seo_option( 'contact' );
	$locations = array();
	if ( is_array( $contacts ) ) {
		foreach ( $contacts as $row ) {
			$address = psb_seo_postal_address( $row['address'] ?? '' );
			if ( ! $address ) {
				continue;
			}
			$title = trim( (string) ( $row['title'] ?? '' ) );
			$place = array(
				'@type'   => 'Place',
				'name'    => trim( ( $title ? $title . ' ' : '' ) . $name ),
				'address' => $address,
			);
			$geo = psb_seo_geo_from_embed( $row['map'] ?? '' );
			if ( $geo ) {
				$place['geo'] = $geo;
			}
			if ( ! empty( $row['map-link'] ) ) {
				$place['hasMap'] = esc_url_raw( $row['map-link'] );
			}
			$locations[] = $place;
			if ( empty( $org['address'] ) ) {
				$org['address'] = $address; // First row is the head office.
			}
		}
	}
	if ( empty( $org['address'] ) ) {
		$address = psb_seo_postal_address( psb_seo_option( 'address' ) );
		if ( $address ) {
			$org['address'] = $address;
		}
	}
	if ( $locations ) {
		$org['location'] = $locations;
	}

	// Social profiles (messaging deep links such as wa.me are not profiles, so they are skipped).
	$social  = psb_seo_option( 'social' );
	$same_as = array();
	if ( is_array( $social ) ) {
		foreach ( $social as $row ) {
			$link = $row['link'] ?? '';
			$url  = is_array( $link ) ? ( $link['url'] ?? '' ) : (string) $link;
			$url  = esc_url_raw( trim( $url ) );
			if ( $url && ! preg_match( '#^https?://(wa\.me|api\.whatsapp\.com)/#i', $url ) ) {
				$same_as[] = $url;
			}
		}
	}
	if ( $same_as ) {
		$org['sameAs'] = array_values( array_unique( $same_as ) );
	}

	return $org;
}

/** Recursively rewrite {"@id": old} references to new. */
function psb_seo_replace_ids( $node, $old, $new ) {
	if ( is_array( $node ) ) {
		foreach ( $node as $k => $v ) {
			if ( '@id' === $k && $v === $old ) {
				$node[ $k ] = $new;
			} elseif ( is_array( $v ) ) {
				$node[ $k ] = psb_seo_replace_ids( $v, $old, $new );
			}
		}
	}
	return $node;
}

function psb_seo_types( $node ) {
	$type = $node['@type'] ?? array();
	return is_array( $type ) ? $type : array( $type );
}

function psb_seo_description( $post_id ) {
	$desc = get_post_meta( $post_id, 'rank_math_description', true );
	if ( ! $desc ) {
		$desc = get_the_excerpt( $post_id );
	}
	return trim( wp_strip_all_tags( (string) $desc ) );
}

/**
 * 1–3: enrich / correct Rank Math's graph.
 */
add_filter(
	'rank_math/json_ld',
	function ( $data, $jsonld = null ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		psb_seo_state( 'rm_ran', true );

		$org     = psb_seo_org_data();
		$org_id  = psb_seo_org_id();
		$old_ids = array();

		foreach ( $data as $key => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}
			$types = psb_seo_types( $node );
			$id    = $node['@id'] ?? '';

			// Organisation node (Rank Math uses #person / #organization).
			if ( in_array( 'Organization', $types, true ) && ( substr( $id, -7 ) === '#person' || substr( $id, -13 ) === '#organization' ) ) {
				if ( $id && $id !== $org_id ) {
					$old_ids[] = $id;
				}
				$merged = array_merge( $node, $org );
				// Keep Rank Math's logo/image objects when present.
				if ( ! empty( $node['logo'] ) ) {
					$merged['logo'] = $node['logo'];
				}
				if ( ! empty( $node['image'] ) ) {
					$merged['image'] = $node['image'];
				}
				$data[ $key ] = $merged;
				continue;
			}

			// WebSite name: Rank Math's setting is spelled «پارس سوییچ بورد»; use the brand spelling used in titles.
			if ( in_array( 'WebSite', $types, true ) ) {
				$data[ $key ]['name'] = $org['name'];
				$data[ $key ]['publisher'] = array( '@id' => $org_id );
				continue;
			}

			if ( in_array( 'BreadcrumbList', $types, true ) ) {
				psb_seo_state( 'rm_breadcrumb', true );
			}

			// Static pages are not articles.
			if ( is_page() && in_array( 'Article', $types, true ) ) {
				unset( $data[ $key ] );
				continue;
			}

			// AboutPage / ContactPage.
			if ( is_page() && in_array( 'WebPage', $types, true ) ) {
				if ( is_page_template( 'about-us.php' ) || is_page( 'about-us' ) ) {
					$data[ $key ]['@type'] = 'AboutPage';
				} elseif ( is_page_template( 'contact-us.php' ) || is_page( 'contact-us' ) ) {
					$data[ $key ]['@type'] = 'ContactPage';
				}
			}

			// Catalogue products.
			if ( in_array( 'Product', $types, true ) && is_singular( 'product' ) ) {
				$post_id                       = get_queried_object_id();
				$data[ $key ]['name']          = wp_strip_all_tags( get_the_title( $post_id ) );
				$data[ $key ]['url']           = get_permalink( $post_id );
				$data[ $key ]['brand']         = array(
					'@type' => 'Brand',
					'name'  => $org['name'],
				);
				$data[ $key ]['manufacturer']  = array( '@id' => $org_id );
				if ( empty( $data[ $key ]['description'] ) ) {
					$desc = psb_seo_description( $post_id );
					if ( $desc ) {
						$data[ $key ]['description'] = $desc;
					}
				}
				// No public prices in catalogue mode: drop offers that carry no price.
				$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
				if ( isset( $data[ $key ]['offers'] ) && ( ! $product || '' === (string) $product->get_price() ) ) {
					unset( $data[ $key ]['offers'] );
				}
			}
		}

		foreach ( array_unique( $old_ids ) as $old ) {
			$data = psb_seo_replace_ids( $data, $old, $org_id );
		}

		return $data;
	},
	120,
	2
);

/**
 * 5: remember when a theme partial already printed a BreadcrumbList.
 */
foreach ( array( 'template-parts/global/breadcrumb', 'template-parts/blog/single/breadcrumb-arrow' ) as $psb_seo_crumb_slug ) {
	add_action(
		'get_template_part_' . $psb_seo_crumb_slug,
		function () {
			psb_seo_state( 'theme_crumb', true );
		}
	);
}
unset( $psb_seo_crumb_slug );

/**
 * Breadcrumb trail for the current request: list of [name, url].
 */
function psb_seo_breadcrumb_trail() {
	if ( is_front_page() || is_404() || is_search() || is_feed() ) {
		return array();
	}

	$trail = array( array( 'خانه', home_url( '/' ) ) );

	// Archive "parents" per post type. services → the /service/ page (same content as the CPT archive).
	$parents = array(
		'confirmation' => 'تأییدیه‌ها',
		'type-test'    => 'تایپ تست',
		'services'     => 'خدمات',
		'portfolio'    => 'نمونه کارها',
		'certificates' => 'گواهینامه‌ها',
	);
	$parent_link = function ( $post_type ) use ( $parents ) {
		if ( 'services' === $post_type ) {
			$page = get_page_by_path( 'service' );
			if ( $page && 'publish' === $page->post_status ) {
				return array( $parents['services'], get_permalink( $page ) );
			}
		}
		if ( 'product' === $post_type && function_exists( 'wc_get_page_permalink' ) ) {
			return array( 'محصولات', wc_get_page_permalink( 'shop' ) );
		}
		if ( 'post' === $post_type ) {
			$blog = (int) get_option( 'page_for_posts' );
			return $blog ? array( get_the_title( $blog ), get_permalink( $blog ) ) : null;
		}
		$link = get_post_type_archive_link( $post_type );
		if ( ! $link ) {
			return null;
		}
		$obj   = get_post_type_object( $post_type );
		$label = $parents[ $post_type ] ?? ( $obj ? $obj->labels->name : $post_type );
		return array( $label, $link );
	};

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return array();
		}
		if ( 'page' === $post->post_type ) {
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
				$trail[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
			}
		} else {
			$parent = $parent_link( $post->post_type );
			if ( $parent ) {
				$trail[] = $parent;
			}
		}
		$trail[] = array( get_the_title( $post ), get_permalink( $post ) );
	} elseif ( is_home() ) {
		$blog = (int) get_option( 'page_for_posts' );
		$trail[] = array( $blog ? get_the_title( $blog ) : 'مقالات', $blog ? get_permalink( $blog ) : home_url( '/blog/' ) );
	} elseif ( is_post_type_archive() ) {
		$post_type = get_query_var( 'post_type' );
		$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			$trail[] = array( 'محصولات', wc_get_page_permalink( 'shop' ) );
		} else {
			$parent = $parent_link( $post_type );
			if ( $parent ) {
				$trail[] = array( $parent[0], get_post_type_archive_link( $post_type ) );
			}
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return array();
		}
		$tax = get_taxonomy( $term->taxonomy );
		$pt  = ( $tax && ! empty( $tax->object_type ) ) ? $tax->object_type[0] : 'post';
		$parent = $parent_link( $pt );
		if ( $parent ) {
			$trail[] = $parent;
		}
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, $term->taxonomy );
			if ( $ancestor instanceof WP_Term ) {
				$trail[] = array( $ancestor->name, get_term_link( $ancestor ) );
			}
		}
		$trail[] = array( $term->name, get_term_link( $term ) );
	} else {
		return array();
	}

	return count( $trail ) > 1 ? $trail : array();
}

function psb_seo_breadcrumb_node() {
	$trail = psb_seo_breadcrumb_trail();
	if ( ! $trail ) {
		return null;
	}
	$items = array();
	foreach ( array_values( $trail ) as $i => $crumb ) {
		if ( is_wp_error( $crumb[1] ) || ! $crumb[1] ) {
			continue;
		}
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => count( $items ) + 1,
			'name'     => wp_strip_all_tags( html_entity_decode( (string) $crumb[0], ENT_QUOTES, 'UTF-8' ) ),
			'item'     => esc_url_raw( $crumb[1] ),
		);
	}
	if ( count( $items ) < 2 ) {
		return null;
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => esc_url_raw( end( $trail )[1] ) . '#breadcrumb',
		'itemListElement' => $items,
	);
}

function psb_seo_print( array $graph ) {
	$graph = array_values( array_filter( $graph ) );
	if ( ! $graph ) {
		return;
	}
	$json = wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
	);
	if ( $json ) {
		echo "\n<script type=\"application/ld+json\" class=\"psb-seo-schema\">" . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with JSON_HEX_TAG.
	}
}

/**
 * 4: minimal graph where Rank Math printed nothing (e.g. CPT singles with schema type "None").
 * Rank Math prints at wp_head priority 1 (rank_math/head), so by priority 99 we know.
 */
add_action(
	'wp_head',
	function () {
		if ( is_admin() || is_feed() || is_404() || psb_seo_state( 'rm_ran' ) ) {
			return;
		}

		$org   = psb_seo_org_data();
		$graph = array(
			$org,
			array(
				'@type'      => 'WebSite',
				'@id'        => psb_seo_website_id(),
				'url'        => home_url( '/' ),
				'name'       => $org['name'],
				'publisher'  => array( '@id' => psb_seo_org_id() ),
				'inLanguage' => 'fa-IR',
			),
		);

		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			$url     = get_permalink( $post_id );
			$page    = array(
				'@type'         => 'WebPage',
				'@id'           => $url . '#webpage',
				'url'           => $url,
				'name'          => wp_strip_all_tags( get_the_title( $post_id ) ),
				'isPartOf'      => array( '@id' => psb_seo_website_id() ),
				'about'         => array( '@id' => psb_seo_org_id() ),
				'publisher'     => array( '@id' => psb_seo_org_id() ),
				'inLanguage'    => 'fa-IR',
				'datePublished' => get_post_time( 'c', true, $post_id ),
				'dateModified'  => get_post_modified_time( 'c', true, $post_id ),
			);
			$desc = psb_seo_description( $post_id );
			if ( $desc ) {
				$page['description'] = $desc;
			}
			$thumb = get_the_post_thumbnail_url( $post_id, 'full' );
			if ( $thumb ) {
				$page['primaryImageOfPage'] = array(
					'@type' => 'ImageObject',
					'url'   => $thumb,
				);
			}
			$graph[] = $page;
		}

		psb_seo_print( $graph );
	},
	99
);

/**
 * 5: BreadcrumbList, printed late so theme breadcrumb partials rendered in the body can be detected first.
 */
add_action(
	'wp_footer',
	function () {
		if ( is_admin() || is_feed() || psb_seo_state( 'rm_breadcrumb' ) || psb_seo_state( 'theme_crumb' ) ) {
			return;
		}
		psb_seo_print( array( psb_seo_breadcrumb_node() ) );
	},
	5
);
