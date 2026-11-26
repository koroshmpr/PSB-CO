<?php
/**
 * AI visibility — minimal, dependency-free HTML → Markdown converter.
 *
 * Handles headings, paragraphs, lists (nested), tables (GFM), links, images
 * with alt text, emphasis, blockquotes, code and line breaks. Scripts, styles,
 * forms, iframes, SVGs and shortcodes are dropped.
 *
 * @package PSB-CO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Clean raw post HTML (classic editor) and convert it to Markdown.
 *
 * @param string $html         Raw post_content.
 * @param int    $heading_base Level that an <h1>/<h2> in the content maps to.
 */
function psb_ai_html_to_markdown( $html, $heading_base = 3 ) {
	$html = (string) $html;
	if ( '' === trim( $html ) ) {
		return '';
	}

	// Strip shortcodes without executing them (no side effects, no fatals).
	$html = strip_shortcodes( $html );
	$html = preg_replace( '/\[\/?[a-z0-9_\-]+[^\]]*\]/i', '', $html );
	$html = preg_replace( '#<(script|style|noscript|iframe|svg|form|template|button|select|textarea)\b[^>]*>.*?</\1>#is', '', $html );
	$html = preg_replace( '#<!--.*?-->#s', '', $html );
	$html = wpautop( $html );

	if ( ! class_exists( 'DOMDocument' ) ) {
		return psb_ai_plain_text( $html );
	}

	$prev = libxml_use_internal_errors( true );
	$doc  = new DOMDocument();
	$ok   = $doc->loadHTML( '<?xml encoding="utf-8"?><div id="psb-ai-root">' . $html . '</div>', LIBXML_NONET | LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );

	if ( ! $ok ) {
		return psb_ai_plain_text( $html );
	}

	$root = null;
	foreach ( $doc->childNodes as $child ) {
		if ( $child instanceof DOMElement ) {
			$root = $child;
			break;
		}
	}
	if ( ! $root ) {
		return psb_ai_plain_text( $html );
	}

	$md = psb_ai_md_children( $root, array( 'hbase' => max( 1, min( 6, (int) $heading_base ) ), 'list' => 0 ) );

	return psb_ai_md_tidy( $md );
}

/**
 * Plain-text fallback.
 */
function psb_ai_plain_text( $html ) {
	$text = wp_strip_all_tags( str_replace( array( '</p>', '<br>', '<br />', '</li>', '</h2>', '</h3>' ), "\n", (string) $html ) );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return psb_ai_md_tidy( $text );
}

/**
 * Collapse runs of blank lines / trailing spaces.
 */
function psb_ai_md_tidy( $md ) {
	$md = str_replace( "\xC2\xA0", ' ', (string) $md );
	$md = preg_replace( '/(?<=\S)[ \t]{2,}(?=\S)/u', ' ', $md );
	$md = preg_replace( "/[ \t]+\n/", "\n", $md );
	$md = preg_replace( "/\n{3,}/", "\n\n", $md );
	return trim( $md );
}

function psb_ai_md_children( DOMNode $node, array $ctx ) {
	$out = '';
	foreach ( $node->childNodes as $child ) {
		$out .= psb_ai_md_node( $child, $ctx );
	}
	return $out;
}

/**
 * Inline text of a node, collapsed to one line.
 */
function psb_ai_md_inline( DOMNode $node, array $ctx ) {
	$text = psb_ai_md_children( $node, $ctx );
	return trim( preg_replace( '/\s+/u', ' ', $text ) );
}

function psb_ai_md_abs_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url || 0 === strpos( $url, '#' ) || preg_match( '#^(javascript|data):#i', $url ) ) {
		return '';
	}
	if ( 0 === strpos( $url, '//' ) ) {
		return 'https:' . $url;
	}
	if ( 0 === strpos( $url, '/' ) ) {
		return home_url( $url );
	}
	return $url;
}

function psb_ai_md_node( DOMNode $node, array $ctx ) {
	if ( XML_TEXT_NODE === $node->nodeType || XML_CDATA_SECTION_NODE === $node->nodeType ) {
		$text = preg_replace( '/[ \t\r\n]+/u', ' ', $node->nodeValue );
		return $text;
	}
	if ( ! ( $node instanceof DOMElement ) ) {
		return '';
	}

	$tag = strtolower( $node->nodeName );

	switch ( $tag ) {
		case 'h1':
		case 'h2':
		case 'h3':
		case 'h4':
		case 'h5':
		case 'h6':
			$level = (int) substr( $tag, 1 );
			$level = max( $ctx['hbase'], $ctx['hbase'] + $level - 2 );
			$level = min( 6, $level );
			$text  = psb_ai_md_inline( $node, $ctx );
			return '' === $text ? '' : "\n\n" . str_repeat( '#', $level ) . ' ' . $text . "\n\n";

		case 'p':
		case 'div':
		case 'section':
		case 'article':
		case 'header':
		case 'footer':
		case 'main':
		case 'aside':
		case 'figure':
		case 'center':
			$inner = psb_ai_md_children( $node, $ctx );
			return "\n\n" . trim( $inner ) . "\n\n";

		case 'figcaption':
			$text = psb_ai_md_inline( $node, $ctx );
			return '' === $text ? '' : "\n\n_" . $text . "_\n\n";

		case 'br':
			return "  \n";

		case 'hr':
			return "\n\n---\n\n";

		case 'strong':
		case 'b':
			$text = psb_ai_md_inline( $node, $ctx );
			return '' === $text ? '' : ' **' . $text . '** ';

		case 'em':
		case 'i':
			$text = psb_ai_md_inline( $node, $ctx );
			return '' === $text ? '' : ' _' . $text . '_ ';

		case 'code':
			if ( $node->parentNode && 'pre' === strtolower( $node->parentNode->nodeName ) ) {
				return $node->textContent;
			}
			return '`' . trim( $node->textContent ) . '`';

		case 'pre':
			return "\n\n```\n" . rtrim( $node->textContent ) . "\n```\n\n";

		case 'blockquote':
			$inner = psb_ai_md_tidy( psb_ai_md_children( $node, $ctx ) );
			if ( '' === $inner ) {
				return '';
			}
			return "\n\n> " . str_replace( "\n", "\n> ", $inner ) . "\n\n";

		case 'a':
			$text = psb_ai_md_inline( $node, $ctx );
			$href = psb_ai_md_abs_url( $node->getAttribute( 'href' ) );
			if ( '' === $text ) {
				return '';
			}
			if ( '' === $href ) {
				return $text;
			}
			return '[' . psb_ai_md_escape_label( $text ) . '](' . psb_ai_md_escape_url( $href ) . ')';

		case 'img':
			$alt = trim( $node->getAttribute( 'alt' ) );
			$src = psb_ai_md_abs_url( $node->getAttribute( 'src' ) );
			if ( '' === $alt || '' === $src ) {
				return '';
			}
			return '![' . psb_ai_md_escape_label( $alt ) . '](' . psb_ai_md_escape_url( $src ) . ')';

		case 'ul':
		case 'ol':
			return psb_ai_md_list( $node, $ctx, 'ol' === $tag );

		case 'li':
			// Stray <li> outside a list.
			return "\n- " . psb_ai_md_inline( $node, $ctx ) . "\n";

		case 'table':
			return psb_ai_md_table( $node, $ctx );

		case 'dl':
			return "\n\n" . trim( psb_ai_md_children( $node, $ctx ) ) . "\n\n";
		case 'dt':
			return "\n**" . psb_ai_md_inline( $node, $ctx ) . "**\n";
		case 'dd':
			return ': ' . psb_ai_md_inline( $node, $ctx ) . "\n";

		case 'script':
		case 'style':
		case 'noscript':
		case 'iframe':
		case 'svg':
		case 'form':
		case 'input':
		case 'button':
		case 'select':
		case 'textarea':
		case 'video':
		case 'audio':
		case 'canvas':
		case 'object':
		case 'embed':
			return '';

		default:
			return psb_ai_md_children( $node, $ctx );
	}
}

function psb_ai_md_escape_label( $text ) {
	return str_replace( array( '[', ']' ), array( '\[', '\]' ), $text );
}

function psb_ai_md_escape_url( $url ) {
	return str_replace( array( ' ', '(', ')' ), array( '%20', '%28', '%29' ), $url );
}

function psb_ai_md_list( DOMElement $list, array $ctx, $ordered ) {
	$depth  = (int) $ctx['list'];
	$indent = str_repeat( '  ', $depth );
	$i      = 0;
	$out    = '';

	foreach ( $list->childNodes as $item ) {
		if ( ! ( $item instanceof DOMElement ) || 'li' !== strtolower( $item->nodeName ) ) {
			continue;
		}
		++$i;
		$text   = '';
		$nested = '';
		foreach ( $item->childNodes as $child ) {
			$name = $child instanceof DOMElement ? strtolower( $child->nodeName ) : '';
			if ( 'ul' === $name || 'ol' === $name ) {
				$nested .= psb_ai_md_list( $child, array_merge( $ctx, array( 'list' => $depth + 1 ) ), 'ol' === $name );
			} else {
				$text .= psb_ai_md_node( $child, $ctx );
			}
		}
		$text = trim( preg_replace( '/\s+/u', ' ', $text ) );
		if ( '' === $text && '' === $nested ) {
			continue;
		}
		$marker = $ordered ? $i . '. ' : '- ';
		$out   .= $indent . $marker . $text . "\n" . $nested;
	}

	if ( '' === $out ) {
		return '';
	}
	return 0 === $depth ? "\n\n" . $out . "\n" : $out;
}

function psb_ai_md_table( DOMElement $table, array $ctx ) {
	$rows = array();

	$xpath = new DOMXPath( $table->ownerDocument );
	foreach ( $xpath->query( './/tr', $table ) as $tr ) {
		$cells = array();
		foreach ( $tr->childNodes as $cell ) {
			if ( ! ( $cell instanceof DOMElement ) ) {
				continue;
			}
			$name = strtolower( $cell->nodeName );
			if ( 'td' !== $name && 'th' !== $name ) {
				continue;
			}
			$text    = psb_ai_md_inline( $cell, $ctx );
			$cells[] = str_replace( '|', '\|', $text );
		}
		if ( $cells ) {
			$rows[] = $cells;
		}
	}

	if ( ! $rows ) {
		return '';
	}

	$cols = 0;
	foreach ( $rows as $r ) {
		$cols = max( $cols, count( $r ) );
	}
	foreach ( $rows as &$r ) {
		$r = array_pad( $r, $cols, '' );
	}
	unset( $r );

	// Without a header row, use the first row as header (GFM needs one).
	$header = array_shift( $rows );
	$out    = '| ' . implode( ' | ', $header ) . " |\n";
	$out   .= '|' . str_repeat( ' --- |', $cols ) . "\n";
	foreach ( $rows as $r ) {
		$out .= '| ' . implode( ' | ', $r ) . " |\n";
	}

	return "\n\n" . $out . "\n";
}
