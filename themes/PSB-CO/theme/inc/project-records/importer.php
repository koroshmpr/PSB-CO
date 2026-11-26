<?php
/**
 * Project records — CSV/XLSX reader, column auto-mapping, importer core, CSV export.
 *
 * Script use (no WP-CLI needed), e.g. from a one-off PHP file that loads wp-load.php:
 *   $report = psb_pr_import_csv( '/path/projects.csv', array( 'dry_run' => true ) );
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Fields and header detection
 * ---------------------------------------------------------------------- */

/**
 * Importable fields: key => [ label, synonyms[] ] (synonyms are compared with psb_pr_key()).
 *
 * @return array
 */
function psb_pr_import_fields() {
	return array(
		'title'        => array( 'عنوان پروژه (الزامی)', array( 'title', 'عنوان', 'عنوان پروژه', 'نام پروژه', 'پروژه', 'شرح پروژه', 'project', 'project name', 'name' ) ),
		'client'       => array( 'کارفرما', array( 'client/employer', 'client', 'employer', 'کارفرما', 'نام کارفرما', 'مشتری', 'خریدار', 'owner', 'customer' ) ),
		'location'     => array( 'محل اجرا (شهر/استان در یک ستون)', array( 'location', 'محل', 'محل اجرا', 'مکان', 'محل پروژه', 'place', 'site' ) ),
		'province'     => array( 'استان', array( 'province', 'استان', 'state' ) ),
		'city'         => array( 'شهر', array( 'city', 'شهر', 'شهرستان' ) ),
		'year'         => array( 'سال (شمسی)', array( 'year (jalali)', 'year', 'سال', 'سال اجرا', 'سال تحویل', 'تاریخ', 'date' ) ),
		'sector'       => array( 'حوزه / صنعت', array( 'sector', 'حوزه', 'صنعت', 'بخش', 'گروه', 'حوزه کاری', 'industry' ) ),
		'types'        => array( 'نوع تابلوها', array( 'scope/panel types', 'panel types', 'panel type', 'types', 'نوع تابلو', 'نوع تابلوها', 'تابلوها', 'نوع', 'محصول', 'محصولات' ) ),
		'quantity'     => array( 'تعداد', array( 'quantity', 'qty', 'تعداد', 'تعداد تابلو', 'تعداد سلول' ) ),
		'scope'        => array( 'شرح کار (عمومی)', array( 'scope', 'شرح کار', 'دامنه کار', 'شرح', 'description' ) ),
		'role'         => array( 'نوع قرارداد / نقش', array( 'contract/role', 'contract', 'role', 'نوع قرارداد', 'نقش', 'نوع همکاری' ) ),
		'note'         => array( 'یادداشت داخلی (نمایش داده نمی‌شود)', array( 'notes', 'note', 'یادداشت', 'توضیحات', 'ملاحظات', 'منبع', 'source' ) ),
		'sensitive'    => array( 'محرمانه (y/n)', array( 'sensitive?(y/n)', 'sensitive (y/n)', 'sensitive', 'محرمانه', 'حساس' ) ),
		'show_client'  => array( 'نمایش نام کارفرما (y/n)', array( 'show_client', 'show client', 'نمایش کارفرما', 'نمایش نام کارفرما' ) ),
		'public_title' => array( 'عنوان عمومی (برای ردیف‌های محرمانه)', array( 'public_title', 'public title', 'عنوان عمومی' ) ),
		'featured'     => array( 'شاخص (y/n)', array( 'featured', 'شاخص', 'ویژه', 'منتخب' ) ),
		'portfolio'    => array( 'نمونه‌کار مرتبط (شناسه یا نشانی)', array( 'portfolio', 'نمونه کار', 'نمونه‌کار', 'portfolio id' ) ),
		'status'       => array( 'وضعیت (publish/draft)', array( 'status', 'وضعیت' ) ),
	);
}

/**
 * Guess field => column index from a header row.
 *
 * @param string[] $headers Header cells.
 * @return array field => int
 */
function psb_pr_detect_map( $headers ) {
	$hk    = array_map( 'psb_pr_key', (array) $headers );
	$map   = array();
	$used  = array();
	$cands = array();
	foreach ( psb_pr_import_fields() as $field => $def ) {
		foreach ( $def[1] as $syn ) {
			$sk = psb_pr_key( $syn );
			if ( '' === $sk ) {
				continue;
			}
			foreach ( $hk as $i => $h ) {
				if ( '' === $h ) {
					continue;
				}
				if ( $h === $sk ) {
					$cands[] = array( 1000 + mb_strlen( $sk ), $field, $i );
				} elseif ( mb_strlen( $sk ) >= 3 && false !== strpos( $h, $sk ) ) {
					$cands[] = array( mb_strlen( $sk ), $field, $i );
				}
			}
		}
	}
	usort( $cands, static function ( $a, $b ) {
		return $b[0] - $a[0];
	} );
	foreach ( $cands as $c ) {
		if ( isset( $map[ $c[1] ] ) || isset( $used[ $c[2] ] ) ) {
			continue;
		}
		$map[ $c[1] ] = $c[2];
		$used[ $c[2] ] = true;
	}
	return $map;
}

/* -------------------------------------------------------------------------
 * File readers
 * ---------------------------------------------------------------------- */

/**
 * Read a CSV/TSV/XLSX file into rows of cells (first row = header).
 *
 * @param string $path File path.
 * @param string $ext  Optional extension hint ('csv'|'xlsx').
 * @return array|WP_Error
 */
function psb_pr_read_table( $path, $ext = '' ) {
	if ( ! is_readable( $path ) ) {
		return new WP_Error( 'psb_pr_read', 'فایل خوانده نشد.' );
	}
	$fh    = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$magic = $fh ? fread( $fh, 4 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( $fh ) {
		fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	if ( 'xlsx' === $ext || "PK\x03\x04" === $magic ) {
		return psb_pr_read_xlsx( $path );
	}
	return psb_pr_read_csv( $path );
}

/**
 * CSV reader: UTF-8 (with/without BOM), UTF-16 (Excel «Unicode text»), or Windows-1256; «,» «;» or tab.
 *
 * @param string $path File.
 * @return array|WP_Error
 */
function psb_pr_read_csv( $path ) {
	$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === $raw || '' === $raw ) {
		return new WP_Error( 'psb_pr_read', 'فایل خالی است.' );
	}
	if ( "\xFF\xFE" === substr( $raw, 0, 2 ) || "\xFE\xFF" === substr( $raw, 0, 2 ) ) {
		$enc = "\xFF\xFE" === substr( $raw, 0, 2 ) ? 'UTF-16LE' : 'UTF-16BE';
		$raw = function_exists( 'mb_convert_encoding' ) ? mb_convert_encoding( substr( $raw, 2 ), 'UTF-8', $enc ) : substr( $raw, 2 );
	}
	if ( "\xEF\xBB\xBF" === substr( $raw, 0, 3 ) ) {
		$raw = substr( $raw, 3 );
	}
	if ( ! preg_match( '//u', $raw ) ) {
		$raw = psb_pr_to_utf8( $raw );
	}
	$raw = str_replace( array( "\r\n", "\r" ), "\n", $raw );

	// Delimiter: the most frequent of , ; \t in the first non-empty line (outside quotes).
	$first = '';
	foreach ( explode( "\n", $raw, 20 ) as $line ) {
		if ( '' !== trim( $line ) ) {
			$first = preg_replace( '/"[^"]*"/u', '', $line );
			break;
		}
	}
	$counts = array(
		','  => substr_count( $first, ',' ),
		';'  => substr_count( $first, ';' ),
		"\t" => substr_count( $first, "\t" ),
		'،'  => substr_count( $first, '،' ),
	);
	arsort( $counts );
	$delim = (string) key( $counts );
	if ( '،' === $delim ) {
		$raw   = str_replace( '،', "\x1F", $raw ); // multibyte delimiter → single byte.
		$delim = "\x1F";
	}

	$h = fopen( 'php://temp', 'w+b' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fwrite( $h, $raw ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	rewind( $h );
	$rows = array();
	while ( false !== ( $cells = fgetcsv( $h, 0, $delim, '"', '' ) ) ) {
		if ( array( null ) === $cells ) {
			continue;
		}
		$cells = array_map( static function ( $c ) {
			return str_replace( "\x1F", '،', (string) $c );
		}, $cells );
		if ( '' === trim( implode( '', $cells ) ) ) {
			continue;
		}
		$rows[] = $cells;
	}
	fclose( $h ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return $rows;
}

/**
 * Minimal XLSX reader (first worksheet) with ZipArchive + SimpleXML, no libraries.
 *
 * @param string $path File.
 * @return array|WP_Error
 */
function psb_pr_read_xlsx( $path ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'psb_pr_zip', 'افزونه ZipArchive روی سرور فعال نیست؛ فایل را به CSV (UTF-8) تبدیل کنید.' );
	}
	$zip = new ZipArchive();
	if ( true !== $zip->open( $path ) ) {
		return new WP_Error( 'psb_pr_zip', 'فایل XLSX باز نشد.' );
	}
	$prev = libxml_use_internal_errors( true );

	$shared = array();
	$ss     = $zip->getFromName( 'xl/sharedStrings.xml' );
	if ( $ss ) {
		$x = simplexml_load_string( $ss, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT );
		if ( $x ) {
			foreach ( $x->si as $si ) {
				if ( isset( $si->t ) ) {
					$shared[] = (string) $si->t;
				} else {
					$s = '';
					foreach ( $si->r as $r ) {
						$s .= (string) $r->t;
					}
					$shared[] = $s;
				}
			}
		}
	}

	// First sheet in workbook order.
	$sheet_path = 'xl/worksheets/sheet1.xml';
	$wb         = $zip->getFromName( 'xl/workbook.xml' );
	$rels       = $zip->getFromName( 'xl/_rels/workbook.xml.rels' );
	if ( $wb && $rels ) {
		$wx = simplexml_load_string( $wb, 'SimpleXMLElement', LIBXML_NONET );
		$rx = simplexml_load_string( $rels, 'SimpleXMLElement', LIBXML_NONET );
		if ( $wx && $rx && isset( $wx->sheets->sheet[0] ) ) {
			$rid = (string) $wx->sheets->sheet[0]->attributes( 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' )->id;
			foreach ( $rx->Relationship as $rel ) {
				if ( (string) $rel['Id'] === $rid ) {
					$target     = ltrim( (string) $rel['Target'], '/' );
					$sheet_path = 0 === strpos( $target, 'xl/' ) ? $target : 'xl/' . $target;
					break;
				}
			}
		}
	}
	$sx = $zip->getFromName( $sheet_path );
	$zip->close();
	if ( ! $sx ) {
		libxml_use_internal_errors( $prev );
		return new WP_Error( 'psb_pr_zip', 'برگه اول فایل XLSX پیدا نشد.' );
	}
	$x = simplexml_load_string( $sx, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT );
	libxml_use_internal_errors( $prev );
	if ( ! $x || ! isset( $x->sheetData ) ) {
		return new WP_Error( 'psb_pr_zip', 'ساختار XLSX نامعتبر است.' );
	}
	$rows = array();
	foreach ( $x->sheetData->row as $row ) {
		$cells = array();
		$next  = 0;
		foreach ( $row->c as $c ) {
			$ref = (string) $c['r'];
			$col = $next;
			if ( $ref && preg_match( '/^([A-Z]+)/', $ref, $m ) ) {
				$col = 0;
				foreach ( str_split( $m[1] ) as $ch ) {
					$col = $col * 26 + ( ord( $ch ) - 64 );
				}
				--$col;
			}
			$t = (string) $c['t'];
			if ( 's' === $t ) {
				$v = $shared[ (int) $c->v ] ?? '';
			} elseif ( 'inlineStr' === $t ) {
				$v = isset( $c->is->t ) ? (string) $c->is->t : '';
				if ( '' === $v && isset( $c->is->r ) ) {
					foreach ( $c->is->r as $r ) {
						$v .= (string) $r->t;
					}
				}
			} elseif ( 'b' === $t ) {
				$v = ( '1' === (string) $c->v ) ? 'y' : 'n';
			} else {
				$v = (string) $c->v;
				if ( is_numeric( $v ) && false !== strpos( $v, '.' ) && (float) $v === floor( (float) $v ) ) {
					$v = (string) (int) $v;
				}
			}
			$cells[ $col ] = $v;
			$next          = $col + 1;
		}
		if ( ! $cells || '' === trim( implode( '', $cells ) ) ) {
			continue;
		}
		$max  = max( array_keys( $cells ) );
		$line = array();
		for ( $i = 0; $i <= $max; $i++ ) {
			$line[] = $cells[ $i ] ?? '';
		}
		$rows[] = $line;
	}
	return $rows;
}

/**
 * Header row + mapped assoc rows.
 *
 * @param array $table Rows (first = header).
 * @param array $map   field => column index (null = auto-detect).
 * @return array{headers:array,map:array,rows:array}
 */
function psb_pr_map_table( $table, $map = null ) {
	$headers = array_map( 'psb_pr_clean', (array) array_shift( $table ) );
	if ( null === $map ) {
		$map = psb_pr_detect_map( $headers );
	}
	$rows = array();
	foreach ( $table as $cells ) {
		$row = array();
		foreach ( $map as $field => $col ) {
			if ( '' === $col || null === $col || $col < 0 ) {
				continue;
			}
			$cell = isset( $cells[ $col ] ) ? (string) $cells[ $col ] : '';
			// Undo the export's formula guard ('= '+ '- '@ …) so an export re-imports unchanged.
			if ( strlen( $cell ) > 1 && "'" === $cell[0] && in_array( $cell[1], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
				$cell = substr( $cell, 1 );
			}
			$row[ $field ] = $cell;
		}
		$rows[] = $row;
	}
	return array(
		'headers' => $headers,
		'map'     => $map,
		'rows'    => $rows,
	);
}

/* -------------------------------------------------------------------------
 * Import core
 * ---------------------------------------------------------------------- */

/** Default import options. */
function psb_pr_import_defaults() {
	return array(
		'mode'           => 'skip',     // Existing record (same title + client + year): 'skip' | 'update'.
		'dry_run'        => false,
		'sensitive'      => 'anon',     // Rows marked sensitive: 'anon' (publish, hide client/title/city) | 'draft' | 'skip'.
		'do_not_publish' => 'draft',    // Rows whose note says «منتشر نشود»: 'draft' | 'skip'.
		'infer_sector'   => true,       // Empty sector → guess from the title (flagged «حدسی» in admin).
		'infer_types'    => true,       // Empty panel types → detect from the title.
		'status'         => 'publish',
		'map'            => null,       // field => column index; null = auto-detect from the header row.
		'limit'          => 0,          // Only process the first N data rows (preview).
	);
}

/**
 * Normalise one mapped row into record data (no DB writes).
 *
 * @param array $row  field => raw text.
 * @param array $args Options.
 * @param array $rep  Report (by ref) for warnings.
 * @return array|null Null when the row has no title.
 */
function psb_pr_normalise_row( $row, $args, &$rep ) {
	$title = psb_pr_clean( $row['title'] ?? '' );
	if ( '' === $title ) {
		return null;
	}
	$client = psb_pr_clean( $row['client'] ?? '' );

	// Location.
	$province = '';
	$city     = psb_pr_clean( $row['city'] ?? '' );
	if ( ! empty( $row['province'] ) ) {
		list( $province ) = psb_pr_resolve_location( $row['province'] );
		if ( '' === $province ) {
			$province = psb_pr_clean( $row['province'] );
		}
	}
	$loc = psb_pr_clean( $row['location'] ?? '' );
	if ( '' !== $loc ) {
		list( $p2, $c2 ) = psb_pr_resolve_location( $loc );
		$province        = $province ? $province : $p2;
		$city            = $city ? $city : $c2;
	} elseif ( '' !== $city && '' === $province ) {
		list( $province ) = psb_pr_resolve_location( $city );
	}
	if ( '' === $province && '' !== ( $city . $loc ) ) {
		$rep['unmapped_locations'][ $city ? $city : $loc ] = true;
	}
	if ( $province && psb_pr_key( $city ) === psb_pr_key( $province ) ) {
		$city = $province;
	}

	// Sector.
	$sector_cell = psb_pr_clean( $row['sector'] ?? '' );
	$sector      = psb_pr_match_sector( $sector_cell );
	$inferred    = false;
	if ( '' === $sector && '' !== $sector_cell ) {
		$rep['unknown_sectors'][ $sector_cell ] = ( $rep['unknown_sectors'][ $sector_cell ] ?? 0 ) + 1;
	}
	if ( '' === $sector && $args['infer_sector'] ) {
		$sector = psb_pr_infer_sector( $title . ' ' . $client );
		if ( $sector ) {
			$inferred = true;
			++$rep['inferred_sector'];
		}
	}
	if ( '' === $sector ) {
		$sector = 'other';
	}

	// Types.
	$unknown = array();
	$types   = psb_pr_match_types( $row['types'] ?? '', $unknown );
	foreach ( $unknown as $u ) {
		$rep['unknown_types'][ $u ] = ( $rep['unknown_types'][ $u ] ?? 0 ) + 1;
	}
	if ( ! $types && $args['infer_types'] ) {
		$t_only = $client ? str_replace( $client, ' ', $title ) : $title;
		$types  = array_values( array_diff( psb_pr_match_types( $t_only ), array( 'design' ) ) );
	}

	// Visibility.
	$note      = psb_pr_clean( $row['note'] ?? '' );
	$sensitive = ! empty( $row['sensitive'] ) && psb_pr_truthy( $row['sensitive'] );
	$show      = ! $sensitive;
	if ( isset( $row['show_client'] ) && '' !== trim( (string) $row['show_client'] ) ) {
		$show = psb_pr_truthy( $row['show_client'] );
	}
	$status = in_array( $args['status'], array( 'publish', 'draft', 'pending', 'private' ), true ) ? $args['status'] : 'publish';
	if ( ! empty( $row['status'] ) && in_array( strtolower( trim( $row['status'] ) ), array( 'publish', 'draft', 'pending', 'private' ), true ) ) {
		$status = strtolower( trim( $row['status'] ) );
	}
	$action = 'import';
	if ( $sensitive ) {
		if ( 'skip' === $args['sensitive'] ) {
			$action = 'skip_sensitive';
		} elseif ( 'draft' === $args['sensitive'] ) {
			$status = 'draft';
		}
	}
	if ( false !== mb_strpos( $note, 'منتشر نشود' ) ) {
		if ( 'skip' === $args['do_not_publish'] ) {
			$action = 'skip_sensitive';
		} else {
			$status = 'draft';
		}
		$show = false;
	}

	$portfolio = 0;
	$pf_raw    = trim( (string) ( $row['portfolio'] ?? '' ) );
	if ( '' !== $pf_raw ) {
		$portfolio = ctype_digit( psb_pr_latin_digits( $pf_raw ) ) ? (int) psb_pr_latin_digits( $pf_raw ) : (int) url_to_postid( $pf_raw );
	}

	$year = psb_pr_parse_year( $row['year'] ?? '' );
	return array(
		'title'        => $title,
		'client'       => $client,
		'province'     => $province,
		'city'         => $city,
		'year'         => $year,
		'sector'       => $sector,
		'inferred'     => $inferred,
		'types'        => $types,
		'quantity'     => psb_pr_clean( $row['quantity'] ?? '' ),
		'scope'        => psb_pr_clean( $row['scope'] ?? '' ),
		'role'         => psb_pr_clean( $row['role'] ?? '' ),
		'note'         => $note,
		'show_client'  => $show,
		'public_title' => psb_pr_clean( $row['public_title'] ?? '' ),
		'featured'     => ! empty( $row['featured'] ) && psb_pr_truthy( $row['featured'] ),
		'portfolio'    => $portfolio,
		'status'       => $status,
		'action'       => $action,
		'key'          => psb_pr_record_key( $title, $client, $year ),
	);
}

/** De-duplication key: title + client + year. */
function psb_pr_record_key( $title, $client, $year ) {
	return md5( psb_pr_key( $title ) . '|' . psb_pr_key( $client ) . '|' . (int) $year );
}

/** Existing records: key => post ID (any status except trash). */
function psb_pr_existing_keys() {
	global $wpdb;
	$out  = array();
	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT pm.meta_value AS k, p.ID AS id FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status <> 'trash'",
			'_pr_key',
			PSB_PR_CPT
		)
	);
	foreach ( (array) $rows as $r ) {
		$out[ $r->k ] = (int) $r->id;
	}
	return $out;
}

/**
 * Import mapped rows.
 *
 * @param array $rows field => raw text rows.
 * @param array $args See psb_pr_import_defaults().
 * @return array Report.
 */
function psb_pr_import_rows( $rows, $args = array() ) {
	$args = wp_parse_args( $args, psb_pr_import_defaults() );
	$rep  = array(
		'dry_run'            => (bool) $args['dry_run'],
		'mode'               => $args['mode'],
		'rows'               => 0,
		'created'            => 0,
		'updated'            => 0,
		'skipped_existing'   => 0,
		'duplicates_in_file' => 0,
		'empty'              => 0,
		'skipped_sensitive'  => 0,
		'anonymised'         => 0,
		'drafts'             => 0,
		'inferred_sector'    => 0,
		'no_province'        => 0,
		'with_year'          => 0,
		'errors'             => array(),
		'unmapped_locations' => array(),
		'unknown_types'      => array(),
		'unknown_sectors'    => array(),
		'by_sector'          => array(),
		'by_province'        => array(),
		'preview'            => array(),
	);
	$existing = psb_pr_existing_keys();
	$seen     = array();
	$m        = psb_pr_meta_keys();
	$limit    = (int) $args['limit'];

	if ( ! $args['dry_run'] ) {
		wp_defer_term_counting( true );
		$GLOBALS['psb_pr_importing'] = true;
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
	}
	$base_order = 0;
	if ( ! $args['dry_run'] ) {
		global $wpdb;
		$base_order = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(menu_order) FROM {$wpdb->posts} WHERE post_type = %s", PSB_PR_CPT ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	foreach ( array_values( $rows ) as $i => $raw ) {
		if ( $limit && $i >= $limit ) {
			break;
		}
		++$rep['rows'];
		$rec = psb_pr_normalise_row( $raw, $args, $rep );
		if ( null === $rec ) {
			++$rep['empty'];
			continue;
		}
		// Owner policy: security-related projects (military, defence, aerospace, prisons, nuclear…) are never imported.
		if ( psb_pr_is_restricted( implode( ' ', array_map( 'strval', array_filter( (array) $raw, 'is_scalar' ) ) ) ) || 'government' === ( $rec['sector'] ?? '' ) ) {
			$rep['skipped_restricted'] = ( $rep['skipped_restricted'] ?? 0 ) + 1;
			continue;
		}
		if ( count( $rep['preview'] ) < 20 ) {
			$rep['preview'][] = $rec;
		}
		if ( 'skip_sensitive' === $rec['action'] ) {
			++$rep['skipped_sensitive'];
			continue;
		}
		if ( isset( $seen[ $rec['key'] ] ) ) {
			++$rep['duplicates_in_file'];
			continue;
		}
		$seen[ $rec['key'] ] = true;
		$post_id             = $existing[ $rec['key'] ] ?? 0;
		if ( $post_id && 'update' !== $args['mode'] ) {
			++$rep['skipped_existing'];
			continue;
		}
		// Counters for the would-be result.
		$post_id ? ++$rep['updated'] : ++$rep['created'];
		if ( ! $rec['show_client'] && 'publish' === $rec['status'] ) {
			++$rep['anonymised'];
		}
		if ( 'publish' !== $rec['status'] ) {
			++$rep['drafts'];
		}
		if ( '' === $rec['province'] ) {
			++$rep['no_province'];
		}
		if ( $rec['year'] ) {
			++$rep['with_year'];
		}
		if ( 'publish' === $rec['status'] ) {
			$rep['by_sector'][ $rec['sector'] ] = ( $rep['by_sector'][ $rec['sector'] ] ?? 0 ) + 1;
			if ( $rec['province'] ) {
				$rep['by_province'][ $rec['province'] ] = ( $rep['by_province'][ $rec['province'] ] ?? 0 ) + 1;
			}
		}
		if ( $args['dry_run'] ) {
			continue;
		}

		$postarr = array(
			'post_type'   => PSB_PR_CPT,
			'post_title'  => $rec['title'],
			'post_status' => $rec['status'],
			'menu_order'  => $post_id ? (int) get_post_field( 'menu_order', $post_id ) : $base_order + $i + 1,
		);
		if ( $post_id ) {
			$postarr['ID'] = $post_id;
			$res           = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$res = wp_insert_post( wp_slash( $postarr ), true );
		}
		if ( is_wp_error( $res ) ) {
			$rep['errors'][] = sprintf( 'ردیف %d: %s', $i + 2, $res->get_error_message() );
			$post_id ? --$rep['updated'] : --$rep['created'];
			continue;
		}
		$post_id = (int) $res;
		psb_pr_save_record_meta( $post_id, $rec );
		$existing[ $rec['key'] ] = $post_id;
	}

	if ( ! $args['dry_run'] ) {
		wp_defer_term_counting( false );
		unset( $GLOBALS['psb_pr_importing'] );
		psb_pr_flush_cache();
	}
	arsort( $rep['by_sector'] );
	arsort( $rep['by_province'] );
	arsort( $rep['unknown_types'] );
	$rep['unmapped_locations'] = array_keys( $rep['unmapped_locations'] );
	return $rep;
}

/**
 * Write all record meta + sector term.
 *
 * @param int   $post_id Post.
 * @param array $rec     Normalised record.
 */
function psb_pr_save_record_meta( $post_id, $rec ) {
	$m = psb_pr_meta_keys();
	update_post_meta( $post_id, $m['client'], $rec['client'] );
	update_post_meta( $post_id, $m['province'], $rec['province'] );
	update_post_meta( $post_id, $m['city'], $rec['city'] );
	update_post_meta( $post_id, $m['year'], (int) $rec['year'] );
	update_post_meta( $post_id, $m['types'], array_values( $rec['types'] ) );
	update_post_meta( $post_id, $m['quantity'], $rec['quantity'] );
	update_post_meta( $post_id, $m['scope'], $rec['scope'] );
	update_post_meta( $post_id, $m['role'], $rec['role'] );
	update_post_meta( $post_id, $m['note'], $rec['note'] );
	update_post_meta( $post_id, $m['show_client'], $rec['show_client'] ? '1' : '0' );
	update_post_meta( $post_id, $m['public_title'], $rec['public_title'] );
	update_post_meta( $post_id, $m['featured'], $rec['featured'] ? '1' : '' );
	update_post_meta( $post_id, $m['portfolio'], (int) $rec['portfolio'] );
	update_post_meta( $post_id, $m['inferred'], $rec['inferred'] ? '1' : '' );
	update_post_meta( $post_id, $m['key'], psb_pr_record_key( $rec['title'], $rec['client'], $rec['year'] ) );
	$tid = psb_pr_sector_term_id( $rec['sector'] );
	if ( $tid ) {
		wp_set_object_terms( $post_id, array( $tid ), PSB_PR_TAX, false );
	}
}

/**
 * Import a CSV/XLSX file (script-friendly; no WP-CLI). Returns the report array (or WP_Error).
 *
 * @param string $path File path.
 * @param array  $args See psb_pr_import_defaults(); 'map' = field => column index or field => header text.
 * @return array|WP_Error
 */
function psb_pr_import_csv( $path, $args = array() ) {
	$args  = wp_parse_args( $args, psb_pr_import_defaults() );
	$table = psb_pr_read_table( $path, strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) );
	if ( is_wp_error( $table ) ) {
		return $table;
	}
	if ( count( $table ) < 2 ) {
		return new WP_Error( 'psb_pr_empty', 'فایل ردیف داده ندارد.' );
	}
	$map = $args['map'];
	if ( is_array( $map ) ) {
		// Allow header names as values.
		$hk = array_map( 'psb_pr_key', $table[0] );
		foreach ( $map as $f => $col ) {
			if ( ! is_int( $col ) && ! ctype_digit( (string) $col ) ) {
				$idx       = array_search( psb_pr_key( $col ), $hk, true );
				$map[ $f ] = false === $idx ? -1 : $idx;
			} else {
				$map[ $f ] = (int) $col;
			}
		}
	}
	$mapped = psb_pr_map_table( $table, $map );
	if ( ! isset( $mapped['map']['title'] ) ) {
		return new WP_Error( 'psb_pr_map', 'ستون عنوان پروژه پیدا نشد.' );
	}
	$rep            = psb_pr_import_rows( $mapped['rows'], $args );
	$rep['map']     = $mapped['map'];
	$rep['headers'] = $mapped['headers'];
	return $rep;
}

/* -------------------------------------------------------------------------
 * Export
 * ---------------------------------------------------------------------- */

/** Neutralise spreadsheet formulas in an exported cell (security review L2). The importer strips the quote again. */
function psb_pr_csv_cell( $v ) {
	$v = (string) $v;
	return ( '' !== $v && in_array( $v[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) ? "'" . $v : $v;
}

/** Stream all records as CSV (UTF-8 with BOM; same headers the importer detects). */
function psb_pr_export_csv() {
	$m   = psb_pr_meta_keys();
	$tl  = psb_pr_type_labels();
	$ids = get_posts(
		array(
			'post_type'      => PSB_PR_CPT,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
		)
	);
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=project-records-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $out, array( 'title', 'client/employer', 'province', 'city', 'year (Jalali)', 'sector', 'panel types', 'quantity', 'scope', 'contract/role', 'notes', 'show_client', 'public_title', 'featured', 'portfolio', 'status', 'id' ), ',', '"', '' );
	foreach ( $ids as $id ) {
		$terms = get_the_terms( $id, PSB_PR_TAX );
		$types = array();
		foreach ( (array) get_post_meta( $id, $m['types'], true ) as $t ) {
			if ( isset( $tl[ $t ] ) ) {
				$types[] = $tl[ $t ];
			}
		}
		$year = (int) get_post_meta( $id, $m['year'], true );
		fputcsv(
			$out,
			array_map(
				'psb_pr_csv_cell',
				array(
				html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
				get_post_meta( $id, $m['client'], true ),
				get_post_meta( $id, $m['province'], true ),
				get_post_meta( $id, $m['city'], true ),
				$year ? $year : '',
				( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '',
				implode( '؛ ', $types ),
				get_post_meta( $id, $m['quantity'], true ),
				get_post_meta( $id, $m['scope'], true ),
				get_post_meta( $id, $m['role'], true ),
				get_post_meta( $id, $m['note'], true ),
				'0' === (string) get_post_meta( $id, $m['show_client'], true ) ? 'n' : 'y',
				get_post_meta( $id, $m['public_title'], true ),
				get_post_meta( $id, $m['featured'], true ) ? 'y' : 'n',
				(int) get_post_meta( $id, $m['portfolio'], true ) ? (int) get_post_meta( $id, $m['portfolio'], true ) : '',
				get_post_status( $id ),
				$id,
				)
			),
			',',
			'"',
			''
		);
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

add_action(
	'admin_post_psb_pr_export',
	static function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی ندارید.', 403 );
		}
		check_admin_referer( 'psb_pr_export' );
		psb_pr_export_csv();
	}
);
