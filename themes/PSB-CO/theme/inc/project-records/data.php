<?php
/**
 * Project records — public data layer: cached rows, stats, filtering/sorting (shared by SSR and the JSON payload).
 *
 * Every string here is already public-safe: anonymised records never carry their real title, client or city.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Display title for a record.
 *
 * @param array  $types    Type keys.
 * @param string $sector   Sector slug.
 * @param string $province Province.
 * @return string Generic title for an anonymised record.
 */
function psb_pr_generic_title( $types, $sector, $province ) {
	$labels = array(
		'mv'        => 'فشار متوسط',
		'lv'        => 'فشار ضعیف',
		'mcc'       => 'MCC',
		'control'   => 'فرمان و کنترل',
		'capacitor' => 'خازن',
		'ats'       => 'اضطراری',
		'rmu'       => 'کمپکت',
		'meter'     => 'کنتوری',
		'hospital'  => 'ایزوله بیمارستانی',
		'drawout'   => 'کشویی',
		'lighting'  => 'روشنایی',
	);
	$parts  = array();
	foreach ( (array) $types as $t ) {
		if ( isset( $labels[ $t ] ) ) {
			$parts[] = $labels[ $t ];
		}
	}
	$parts = array_slice( array_unique( $parts ), 0, 3 );
	$what  = $parts ? 'ساخت تابلوهای ' . implode( count( $parts ) > 2 ? '، ' : ' و ', $parts ) : 'ساخت تابلوهای برق';
	if ( count( $parts ) > 2 ) {
		$last  = array_pop( $parts );
		$what  = 'ساخت تابلوهای ' . implode( '، ', $parts ) . ' و ' . $last;
	}
	$sectors = psb_pr_sectors();
	$who     = $sectors[ $sector ]['anon'] ?? $sectors['other']['anon'];
	$where   = '';
	if ( $province ) {
		$where = psb_pr_abroad_label() === $province ? ' در خارج از کشور' : ' در استان ' . $province;
	}
	return $what . ' برای ' . $who . $where;
}

/**
 * Location label: «اندیمشک، خوزستان», «تهران، شهرک غرب», «کردستان».
 *
 * @param string $city     City text.
 * @param string $province Province.
 * @return string
 */
function psb_pr_location_label( $city, $province ) {
	$city = trim( (string) $city );
	if ( '' === $city ) {
		return (string) $province;
	}
	if ( '' === $province || false !== strpos( psb_pr_key( $city ), psb_pr_key( $province ) ) ) {
		return $city;
	}
	return $city . '، ' . $province;
}

/**
 * All published records as public-safe arrays (cached). Order: as stored (seq).
 *
 * @return array{rows:array,stats:array,sectors:array}
 */
function psb_pr_dataset() {
	static $mem = null;
	if ( null !== $mem ) {
		return $mem;
	}
	$ver = (string) get_option( 'psb_pr_cache_ver', '0' );
	$key = 'psb_pr_data_' . md5( $ver . PSB_PR_VERSION );
	$hit = get_transient( $key );
	if ( is_array( $hit ) && isset( $hit['rows'] ) ) {
		$mem = $hit;
		return $mem;
	}
	$mem = psb_pr_build_dataset();
	set_transient( $key, $mem, WEEK_IN_SECONDS );
	return $mem;
}

/** Build the dataset from the DB (uncached). */
function psb_pr_build_dataset() {
	$ids = get_posts(
		array(
			'post_type'              => PSB_PR_CPT,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
			'update_post_meta_cache' => true,
			'suppress_filters'       => true,
		)
	);
	if ( $ids ) {
		update_meta_cache( 'post', $ids );
		update_object_term_cache( $ids, PSB_PR_CPT );
	}
	$m       = psb_pr_meta_keys();
	$sectors = psb_pr_sectors();
	$typedef = psb_pr_types();
	$rows    = array();
	foreach ( $ids as $id ) {
		$terms  = get_the_terms( $id, PSB_PR_TAX );
		$sector = 'other';
		if ( $terms && ! is_wp_error( $terms ) ) {
			$sector = $terms[0]->slug;
			if ( ! isset( $sectors[ $sector ] ) ) {
				$sector = 'other';
			}
		}
		$types = array_values( array_intersect( (array) get_post_meta( $id, $m['types'], true ), array_keys( $typedef ) ) );
		$show  = get_post_meta( $id, $m['show_client'], true );
		$anon  = ( '0' === (string) $show );
		$prov  = (string) get_post_meta( $id, $m['province'], true );
		$city  = (string) get_post_meta( $id, $m['city'], true );
		$cli   = (string) get_post_meta( $id, $m['client'], true );
		$year  = (int) get_post_meta( $id, $m['year'], true );
		$pf    = (int) get_post_meta( $id, $m['portfolio'], true );
		$url   = '';
		$img   = 0;
		if ( $pf && 'publish' === get_post_status( $pf ) ) {
			$url = get_permalink( $pf );
			$img = (int) get_post_thumbnail_id( $pf );
		}
		if ( $anon ) {
			$pt    = (string) get_post_meta( $id, $m['public_title'], true );
			$title = '' !== $pt ? $pt : psb_pr_generic_title( $types, $sector, $prov );
			$cli   = $sectors[ $sector ]['anon'] ?? $sectors['other']['anon'];
			$city  = '';
			$url   = '';
			$img   = 0;
		} else {
			$title = get_the_title( $id );
		}
		$rows[] = array(
			'id'       => (int) $id,
			'title'    => html_entity_decode( $title, ENT_QUOTES, 'UTF-8' ),
			'client'   => $cli,
			'anon'     => $anon,
			'loc'      => $anon ? $prov : psb_pr_location_label( $city, $prov ),
			'province' => $prov,
			'year'     => $year,
			'sector'   => $sector,
			'types'    => $types,
			'qty'      => $anon ? '' : (string) get_post_meta( $id, $m['quantity'], true ),
			'scope'    => $anon ? '' : (string) get_post_meta( $id, $m['scope'], true ),
			'featured' => (bool) get_post_meta( $id, $m['featured'], true ),
			'url'      => $url,
			'img'      => $img,
			'seq'      => count( $rows ),
			'ck'       => ( $anon || '' === trim( $cli ) ) ? '' : psb_pr_key( $cli ),
		);
	}
	return array(
		'rows'  => $rows,
		'stats' => psb_pr_compute_stats( $rows ),
		'built' => time(),
	);
}

/** Stats from rows. */
function psb_pr_compute_stats( $rows ) {
	$clients = array();
	$sectors = array();
	$provs   = array();
	$years   = array();
	$abroad  = false;
	foreach ( $rows as $r ) {
		if ( '' !== $r['ck'] ) {
			$clients[ $r['ck'] ] = true;
		}
		if ( 'other' !== $r['sector'] ) {
			$sectors[ $r['sector'] ] = true;
		}
		if ( $r['province'] ) {
			if ( psb_pr_abroad_label() === $r['province'] ) {
				$abroad = true;
			} else {
				$provs[ $r['province'] ] = true;
			}
		}
		if ( $r['year'] ) {
			$years[] = $r['year'];
		}
	}
	$founded = defined( 'PSB_FOUNDED_JALALI' ) ? (int) PSB_FOUNDED_JALALI : 0;
	return array(
		'projects'   => count( $rows ),
		'clients'    => count( $clients ),
		'sectors'    => count( $sectors ),
		'provinces'  => count( $provs ),
		'abroad'     => $abroad,
		'dated'      => count( $years ),
		'year_min'   => $years ? min( $years ) : 0,
		'year_max'   => $years ? max( $years ) : 0,
		// The dossier («رزومه کاری از ۱۳۵۵ تاکنون») spans the company's whole history.
		'span_from'  => $founded ? ( $years ? min( $founded, min( $years ) ) : $founded ) : ( $years ? min( $years ) : 0 ),
		// No end year by default: the dossier list stops around 1389 while the company is still active, so we
		// claim only the start. Set an end with update_option( 'psb_pr_span_to', 1405 ) or the filter.
		'span_to'    => (int) apply_filters( 'psb_pr_span_to', (int) get_option( 'psb_pr_span_to', 0 ), $years ),
	);
}

/**
 * Public stats for other templates (home, about): «۷۲۷ پروژه اجراشده».
 *
 * @return array{projects:int,clients:int,sectors:int,provinces:int,abroad:bool,dated:int,year_min:int,year_max:int,span_from:int,span_to:int}
 */
function psb_pr_stats() {
	return psb_pr_dataset()['stats'];
}

/** All public rows. */
function psb_pr_records() {
	return psb_pr_dataset()['rows'];
}

/** Sector slug => name, in vocabulary order (taxonomy names win so renames in admin show up). */
function psb_pr_sector_names() {
	static $out = null;
	if ( null !== $out ) {
		return $out;
	}
	$out = array();
	foreach ( psb_pr_sectors() as $slug => $s ) {
		$out[ $slug ] = $s['name'];
	}
	$terms = get_terms( array( 'taxonomy' => PSB_PR_TAX, 'hide_empty' => false ) );
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$out[ $t->slug ] = $t->name;
		}
	}
	return $out;
}

/** Show the «سال» column / decade filter only when enough rows are dated. */
function psb_pr_years_useful( $stats = null ) {
	$stats = $stats ? $stats : psb_pr_stats();
	return $stats['projects'] > 0 && $stats['dated'] >= max( 10, (int) ceil( $stats['projects'] * 0.25 ) );
}

/* -------------------------------------------------------------------------
 * Query state (shared contract with js/project-records.js): ?q=&sector=&prov=&type=a,b&dec=1370&sort=new|old|az&pg=N
 * ---------------------------------------------------------------------- */

/** Read and sanitise the filter state from $_GET. */
function psb_pr_state_from_request() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only public filters.
	$g     = wp_unslash( $_GET );
	$types = array_keys( psb_pr_types() );
	$state = array(
		'q'      => isset( $g['q'] ) && is_string( $g['q'] ) ? mb_substr( sanitize_text_field( (string) $g['q'] ), 0, 80 ) : '',
		'sector' => isset( $g['sector'] ) && is_string( $g['sector'] ) ? sanitize_key( (string) $g['sector'] ) : '',
		'prov'   => isset( $g['prov'] ) && is_string( $g['prov'] ) ? sanitize_text_field( $g['prov'] ) : '',
		'type'   => isset( $g['type'] ) ? array_values( array_intersect( array_map( 'sanitize_key', is_array( $g['type'] ) ? $g['type'] : explode( ',', (string) $g['type'] ) ), $types ) ) : array(),
		'dec'    => isset( $g['dec'] ) ? (int) $g['dec'] : 0,
		'sort'   => isset( $g['sort'] ) && in_array( $g['sort'], array( 'new', 'old', 'az' ), true ) ? (string) $g['sort'] : 'new',
		'pg'     => isset( $g['pg'] ) ? max( 1, min( 100, (int) $g['pg'] ) ) : 1,
	);
	// phpcs:enable
	if ( $state['sector'] && ! isset( psb_pr_sectors()[ $state['sector'] ] ) ) {
		$state['sector'] = '';
	}
	return $state;
}

/** Search haystack for a row (mirrors JS). */
function psb_pr_row_haystack( $r ) {
	$names = psb_pr_sector_names();
	$tl    = psb_pr_type_labels();
	$parts = array( $r['title'], $r['client'], $r['loc'], $names[ $r['sector'] ] ?? '', $r['year'] ? (string) $r['year'] : '' );
	foreach ( $r['types'] as $t ) {
		$parts[] = $tl[ $t ] ?? '';
	}
	return psb_pr_key_spaced( implode( ' ', $parts ) );
}

/**
 * Does a row match the state? $skip = facet to ignore (for facet counts).
 *
 * @param array  $r     Row.
 * @param array  $state State.
 * @param string $skip  'sector' | 'type' | 'prov' | 'dec' | ''.
 * @return bool
 */
function psb_pr_row_matches( $r, $state, $skip = '' ) {
	if ( 'sector' !== $skip && $state['sector'] && $r['sector'] !== $state['sector'] ) {
		return false;
	}
	if ( 'prov' !== $skip && '' !== $state['prov'] && $r['province'] !== $state['prov'] ) {
		return false;
	}
	if ( 'dec' !== $skip && $state['dec'] && ( ! $r['year'] || (int) ( floor( $r['year'] / 10 ) * 10 ) !== $state['dec'] ) ) {
		return false;
	}
	if ( 'type' !== $skip && $state['type'] && ! array_intersect( $state['type'], $r['types'] ) ) {
		return false;
	}
	if ( '' !== $state['q'] ) {
		$hay = ' ' . psb_pr_row_haystack( $r );
		foreach ( preg_split( '/\s+/u', psb_pr_key_spaced( $state['q'] ) ) as $tok ) {
			if ( '' !== $tok && false === strpos( $hay, $tok ) ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * Filter + sort rows.
 *
 * @param array $rows  Rows.
 * @param array $state State.
 * @return array
 */
function psb_pr_query( $rows, $state ) {
	$out = array();
	foreach ( $rows as $r ) {
		if ( psb_pr_row_matches( $r, $state ) ) {
			$out[] = $r;
		}
	}
	$coll = class_exists( 'Collator' ) ? new Collator( 'fa_IR' ) : null;
	usort(
		$out,
		static function ( $a, $b ) use ( $state, $coll ) {
			if ( 'az' === $state['sort'] ) {
				$ea = ( $a['anon'] || '' === $a['client'] ) ? 1 : 0;
				$eb = ( $b['anon'] || '' === $b['client'] ) ? 1 : 0;
				if ( $ea !== $eb ) {
					return $ea - $eb;
				}
				$c = $coll ? $coll->compare( $a['client'], $b['client'] ) : strcmp( $a['client'], $b['client'] );
				return $c ? $c : $a['seq'] - $b['seq'];
			}
			$ya = $a['year'];
			$yb = $b['year'];
			if ( ( $ya ? 0 : 1 ) !== ( $yb ? 0 : 1 ) ) {
				return ( $ya ? 0 : 1 ) - ( $yb ? 0 : 1 );
			}
			if ( 'old' === $state['sort'] ) {
				return ( $ya - $yb ) ? $ya - $yb : $a['seq'] - $b['seq'];
			}
			return ( $yb - $ya ) ? $yb - $ya : $b['seq'] - $a['seq'];
		}
	);
	return $out;
}

/**
 * Facet counts given the state (each facet ignores its own selection).
 *
 * @param array $rows  Rows.
 * @param array $state State.
 * @return array{sector:array,type:array,prov:array,dec:array}
 */
function psb_pr_facets( $rows, $state ) {
	$f = array( 'sector' => array(), 'type' => array(), 'prov' => array(), 'dec' => array() );
	foreach ( $rows as $r ) {
		if ( psb_pr_row_matches( $r, $state, 'sector' ) ) {
			$f['sector'][ $r['sector'] ] = ( $f['sector'][ $r['sector'] ] ?? 0 ) + 1;
		}
		if ( psb_pr_row_matches( $r, $state, 'type' ) ) {
			foreach ( $r['types'] as $t ) {
				$f['type'][ $t ] = ( $f['type'][ $t ] ?? 0 ) + 1;
			}
		}
		if ( $r['province'] && psb_pr_row_matches( $r, $state, 'prov' ) ) {
			$f['prov'][ $r['province'] ] = ( $f['prov'][ $r['province'] ] ?? 0 ) + 1;
		}
		if ( $r['year'] && psb_pr_row_matches( $r, $state, 'dec' ) ) {
			$d                = (int) ( floor( $r['year'] / 10 ) * 10 );
			$f['dec'][ $d ] = ( $f['dec'][ $d ] ?? 0 ) + 1;
		}
	}
	return $f;
}

/** Totals over all rows (no filters): sector / type / province / decade counts. */
function psb_pr_totals() {
	static $t = null;
	if ( null === $t ) {
		$t = psb_pr_facets( psb_pr_records(), array( 'q' => '', 'sector' => '', 'prov' => '', 'type' => array(), 'dec' => 0, 'sort' => 'new', 'pg' => 1 ) );
		arsort( $t['type'] );
		arsort( $t['prov'] );
		ksort( $t['dec'] );
	}
	return $t;
}

/**
 * Compact JSON payload for js/project-records.js.
 * r = [ id, title, client, anon, loc, provinceIdx, year, sectorIdx, typeBits, url ]
 *
 * @return array
 */
function psb_pr_payload() {
	$names = psb_pr_sector_names();
	$slugs = array_keys( psb_pr_sectors() );
	$types = array_keys( psb_pr_types() );
	$tl    = psb_pr_type_labels();
	$provs = array_values( array_unique( array_merge( psb_pr_provinces(), array( psb_pr_abroad_label() ) ) ) );
	$pidx  = array_flip( $provs );
	$sidx  = array_flip( $slugs );
	$tidx  = array_flip( $types );
	$rows  = array();
	foreach ( psb_pr_records() as $r ) {
		$bits = 0;
		foreach ( $r['types'] as $t ) {
			$bits |= 1 << $tidx[ $t ];
		}
		if ( $r['province'] && ! isset( $pidx[ $r['province'] ] ) ) {
			$provs[]                 = $r['province'];
			$pidx[ $r['province'] ] = count( $provs ) - 1;
		}
		$rows[] = array(
			$r['id'],
			$r['title'],
			$r['client'],
			$r['anon'] ? 1 : 0,
			$r['loc'],
			$r['province'] ? $pidx[ $r['province'] ] : -1,
			$r['year'],
			$sidx[ $r['sector'] ] ?? $sidx['other'],
			$bits,
			$r['url'],
		);
	}
	$s = array();
	foreach ( $slugs as $slug ) {
		$s[] = array( $slug, $names[ $slug ] ?? $slug );
	}
	$t = array();
	foreach ( $types as $k ) {
		$t[] = array( $k, $tl[ $k ] );
	}
	return array(
		's'    => $s,
		'p'    => $provs,
		't'    => $t,
		'y'    => psb_pr_years_useful() ? 1 : 0,
		'r'    => $rows,
		'page' => 30,
	);
}

/**
 * Records for the highlights strip: flagged «featured» first, then (if needed) an automatic, sector-diverse
 * pick of the most complete non-anonymised rows (photo > several panel types > city > year).
 *
 * @param int $count How many.
 * @return array
 */
function psb_pr_featured( $count = 6 ) {
	$rows  = psb_pr_records();
	$state = array( 'sort' => 'new', 'q' => '', 'sector' => '', 'prov' => '', 'type' => array(), 'dec' => 0, 'pg' => 1 );
	$flag  = array_values( array_filter( psb_pr_query( $rows, $state ), static function ( $r ) {
		return $r['featured'];
	} ) );
	$out   = array_slice( $flag, 0, $count );
	if ( count( $out ) >= $count ) {
		return $out;
	}
	$taken = wp_list_pluck( $out, 'id' );
	$cands = array();
	foreach ( $rows as $r ) {
		if ( $r['anon'] || '' === $r['client'] || in_array( $r['id'], $taken, true ) || in_array( $r['sector'], array( 'other', 'government' ), true ) ) {
			continue;
		}
		$score  = $r['img'] ? 20 : 0;
		$score += 2 * min( 3, count( array_diff( $r['types'], array( 'install', 'design' ) ) ) );
		$score += in_array( 'install', $r['types'], true ) ? 2 : 0;
		$score += $r['province'] ? 2 : 0;
		$score += $r['year'] ? 1 : 0;
		$score += ( $r['qty'] || $r['scope'] ) ? 1 : 0;
		$score += 'other' === $r['sector'] ? -10 : 0;
		$score += 'government' === $r['sector'] ? -10 : 0;
		$r['_score'] = $score;
		$cands[ $r['sector'] ][] = $r;
	}
	foreach ( $cands as &$list ) {
		usort( $list, static function ( $a, $b ) {
			return ( $b['_score'] - $a['_score'] ) ? $b['_score'] - $a['_score'] : $b['seq'] - $a['seq'];
		} );
	}
	unset( $list );
	// Sectors with most records first, so the strip reflects the real mix.
	uksort( $cands, static function ( $a, $b ) use ( $cands ) {
		return count( $cands[ $b ] ) - count( $cands[ $a ] );
	} );
	$used_clients = array();
	foreach ( $out as $r ) {
		$used_clients[ $r['ck'] ] = true;
	}
	while ( count( $out ) < $count && $cands ) {
		foreach ( array_keys( $cands ) as $sec ) {
			while ( $cands[ $sec ] ) {
				$r = array_shift( $cands[ $sec ] );
				if ( empty( $used_clients[ $r['ck'] ] ) ) {
					$used_clients[ $r['ck'] ] = true;
					$out[]                    = $r;
					break;
				}
			}
			if ( ! $cands[ $sec ] ) {
				unset( $cands[ $sec ] );
			}
			if ( count( $out ) >= $count ) {
				break;
			}
		}
	}
	return $out;
}
