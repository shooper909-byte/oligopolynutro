<?php
/**
 * OligoPoly - product-card fact bullets
 *
 * Self-contained. Adds a short bulleted description, with highlighted keywords,
 * to every product card on the site - the /research/ featured cards, the
 * homepage launch grid, and the research catalog - without editing the snippets
 * that render them.
 *
 * It filters the finished HTML on `the_content` at priority 999, so it runs
 * after those snippets and after shortcodes. A card whose product name matches
 * nothing in the map is left exactly as it is.
 *
 * Copy is molecular class, receptor target and pathway descriptors only - no
 * human-use, dosing, therapeutic or outcome claims - and every card keeps its
 * research-use-only line.
 */

if ( ! function_exists( 'opl_card_facts_map' ) ) {

/** Bullet copy. Keys match as substrings of the lowercased product name. */
function opl_card_facts_map() {
	return array(
		// Tirzepatide
		'tirzepatide' => array(
			'Dual <strong class="opl-key">GIP</strong> / <strong class="opl-key">GLP-1</strong> receptor agonist',
			'<strong class="opl-key">Incretin signaling</strong> pathway studies',
			'Research-use-only material',
		),
		// Semaglutide
		'semaglutide' => array(
			'Selective <strong class="opl-key">GLP-1</strong> receptor agonist',
			'<strong class="opl-key">Incretin signaling</strong> and receptor-selectivity studies',
			'Research-use-only material',
		),
		// Retatrutide
		'retatrutide' => array(
			'Triple <strong class="opl-key">GIP</strong> / <strong class="opl-key">GLP-1</strong> / <strong class="opl-key">glucagon</strong> receptor agonist',
			'Multi-receptor <strong class="opl-key">incretin pathway</strong> studies',
			'Research-use-only material',
		),
		// Cagrilintide
		'cagrilintide' => array(
			'Long-acting <strong class="opl-key">amylin</strong> receptor analog',
			'<strong class="opl-key">Amylin</strong> and <strong class="opl-key">calcitonin receptor</strong> signaling studies',
			'Research-use-only material',
		),
		// NAD+
		'nad' => array(
			'<strong class="opl-key">Redox cofactor</strong> — nicotinamide adenine dinucleotide',
			'<strong class="opl-key">Mitochondrial</strong> and <strong class="opl-key">sirtuin</strong> pathway studies',
			'Research-use-only material',
		),
		// GHK-Cu
		'ghk-cu' => array(
			'<strong class="opl-key">Copper-binding</strong> tripeptide (Gly-His-Lys)',
			'<strong class="opl-key">Extracellular matrix</strong> and <strong class="opl-key">collagen</strong> signaling studies',
			'Research-use-only material',
		),
		// Selank
		'selank' => array(
			'Synthetic <strong class="opl-key">tuftsin</strong> analog heptapeptide',
			'<strong class="opl-key">GABAergic</strong> and <strong class="opl-key">BDNF</strong> pathway studies',
			'Research-use-only material',
		),
		// Metabolic Pathways Stack
		'metabolic-pathways' => array(
			'<strong class="opl-key">GIP</strong> / <strong class="opl-key">GLP-1</strong> / <strong class="opl-key">glucagon</strong> receptor coverage',
			'Multi-compound <strong class="opl-key">incretin</strong> study design',
			'Research-use-only materials',
		),
		// Cellular Energy Stack
		'cellular-energy' => array(
			'<strong class="opl-key">Mitochondrial</strong> and <strong class="opl-key">redox cofactor</strong> coverage',
			'<strong class="opl-key">Sirtuin</strong> pathway study design',
			'Research-use-only materials',
		),
		// Neurocognitive Pathways Stack
		'neurocognitive-pathways' => array(
			'<strong class="opl-key">GABAergic</strong> and <strong class="opl-key">BDNF</strong> pathway coverage',
			'<strong class="opl-key">Neuropeptide</strong> study design',
			'Research-use-only materials',
		),
		// Regenerative Biology Stack
		'regenerative-biology' => array(
			'<strong class="opl-key">Extracellular matrix</strong> and <strong class="opl-key">collagen</strong> signaling coverage',
			'<strong class="opl-key">Tissue-repair</strong> pathway study design',
			'Research-use-only materials',
		),
		// Build Your Research Bundle
		'build-your-research-bundle' => array(
			'Self-selected <strong class="opl-key">multi-compound</strong> research collection',
			'Volume-tiered <strong class="opl-key">3 / 6 / 9 vial</strong> configurations',
			'Research-use-only materials',
		),
	);
}

/** Build the <ul> for a product name, or '' when nothing matches. */
function opl_card_facts_html( $product_name ) {
	// Slug-normalise so "GHK-Cu 50 mg" and "Metabolic Pathways Stack" both match.
	$name = strtolower( wp_strip_all_tags( $product_name ) );
	$name = preg_replace( '/[^a-z0-9]+/', '-', $name );

	foreach ( opl_card_facts_map() as $key => $lines ) {
		if ( false !== strpos( $name, $key ) ) {
			return '<ul class="opl-facts"><li>' . implode( '</li><li>', $lines ) . '</li></ul>';
		}
	}

	return '';
}

function opl_card_facts_css() {
	return '.opl-facts{margin:10px 0 0;padding-left:18px;display:grid;gap:6px;list-style:disc}.opl-facts li{color:var(--muted,#b8c3d5);font-size:14px;line-height:1.85}.opl-facts li::marker{color:var(--violet,#bf62f0)}.opl-facts-cat{margin:8px 0 0;gap:4px}.opl-facts-cat li{font-size:12.5px;line-height:1.7}.opl-key{color:#f3dcff;font-weight:900;background:rgba(194,79,239,.18);border:1px solid rgba(194,79,239,.28);border-radius:5px;padding:1px 5px;white-space:nowrap;-webkit-box-decoration-break:clone;box-decoration-break:clone}';
}

/** How many cards the current pass rewrote. */
function opl_card_facts_count( $reset = false ) {
	static $n = 0;

	if ( $reset ) {
		$n = 0;
		return 0;
	}

	return ++$n;
}

/** Rewrite one product card. */
function opl_card_facts_card( $matches ) {
	$card = $matches[0];

	// Already processed.
	if ( false !== strpos( $card, 'opl-facts' ) ) {
		return $card;
	}

	if ( ! preg_match( '#<h3\b[^>]*>(.*?)</h3>#s', $card, $heading ) ) {
		return $card;
	}

	$facts = opl_card_facts_html( $heading[1] );

	if ( '' === $facts ) {
		return $card;
	}

	// Neutralise backreference syntax in the replacement.
	$replacement = str_replace( array( '\\', '$' ), array( '\\\\', '\$' ), $facts );

	if ( preg_match( '#<ul\b[^>]*>.*?</ul>#s', $card ) ) {
		// Card already has a placeholder list - swap it.
		$out = preg_replace( '#<ul\b[^>]*>.*?</ul>#s', $replacement, $card, 1 );
	} else {
		// No list - insert one straight after the product title.
		$out = preg_replace( '#</h3>#', '</h3>' . $replacement, $card, 1 );
	}

	if ( null === $out ) {
		return $card;
	}

	opl_card_facts_count();

	return $out;
}


/** Bullet copy for the catalog's research-division tiles. */
function opl_cat_facts_map() {
	return array(
		'cellular research' => array(
			'<strong class="opl-key">Mitochondrial</strong> and <strong class="opl-key">redox</strong> pathways',
			'<strong class="opl-key">Sirtuin</strong> signaling studies',
		),
		'cognitive research' => array(
			'<strong class="opl-key">GABAergic</strong> and <strong class="opl-key">BDNF</strong> pathways',
			'<strong class="opl-key">Neuropeptide</strong> signaling studies',
		),
		'growth hormone research' => array(
			'<strong class="opl-key">GH secretagogue</strong> and <strong class="opl-key">IGF-1</strong> pathways',
			'<strong class="opl-key">Somatotropic axis</strong> studies',
		),
		'immune research' => array(
			'<strong class="opl-key">Antimicrobial peptide</strong> and <strong class="opl-key">innate immune</strong> pathways',
			'<strong class="opl-key">Host-defense</strong> signaling studies',
		),
		'longevity research' => array(
			'<strong class="opl-key">Telomerase</strong> and <strong class="opl-key">cellular-aging</strong> pathways',
			'<strong class="opl-key">Senescence</strong> signaling studies',
		),
		'metabolic research' => array(
			'<strong class="opl-key">GIP</strong> / <strong class="opl-key">GLP-1</strong> / <strong class="opl-key">glucagon</strong> receptors',
			'<strong class="opl-key">Incretin signaling</strong> studies',
		),
		'recovery research' => array(
			'<strong class="opl-key">Tissue-repair</strong> and <strong class="opl-key">angiogenesis</strong> pathways',
			'<strong class="opl-key">Extracellular matrix</strong> signaling studies',
		),
		'research blends' => array(
			'<strong class="opl-key">Multi-compound</strong> research formulations',
			'Combined <strong class="opl-key">pathway</strong> study design',
		),
		'research stacks' => array(
			'<strong class="opl-key">Coordinated</strong> multi-pathway collections',
			'<strong class="opl-key">Study-design</strong> groupings',
		),
		'research compounds' => array(
			'<strong class="opl-key">Single-compound</strong> research materials',
			'Full <strong class="opl-key">pathway</strong> coverage',
		),
	);
}

/** Rewrite one .oligopoly-cat-card tile. */
function opl_cat_facts_card( $matches ) {
	$card = $matches[0];

	if ( false !== strpos( $card, 'opl-facts' ) ) {
		return $card;
	}

	if ( ! preg_match( '#<h3[^>]*class="[^"]*oligopoly-cat-name[^"]*"[^>]*>(.*?)</h3>#s', $card, $heading ) ) {
		return $card;
	}

	$name  = strtolower( wp_strip_all_tags( $heading[1] ) );
	$name  = preg_replace( '/[^a-z0-9]+/', '-', $name );
	$lines = null;

	foreach ( opl_cat_facts_map() as $key => $candidate ) {
		if ( false !== strpos( $name, preg_replace( '/[^a-z0-9]+/', '-', $key ) ) ) {
			$lines = $candidate;
			break;
		}
	}

	if ( null === $lines ) {
		return $card;
	}

	$facts = '<ul class="opl-facts opl-facts-cat"><li>' . implode( '</li><li>', $lines ) . '</li></ul>';
	$facts = str_replace( array( '\\', '$' ), array( '\\\\', '\$' ), $facts );

	// Place it inside the tile body, after the product-count badge.
	if ( preg_match( '#<span[^>]*class="[^"]*oligopoly-cat-count[^"]*"[^>]*>.*?</span>#s', $card ) ) {
		$out = preg_replace( '#(<span[^>]*class="[^"]*oligopoly-cat-count[^"]*"[^>]*>.*?</span>)#s', '$1' . $facts, $card, 1 );
	} else {
		$out = preg_replace( '#</h3>#', '</h3>' . $facts, $card, 1 );
	}

	if ( null === $out ) {
		return $card;
	}

	opl_card_facts_count();

	return $out;
}

/**
 * Rewrite every product card in a chunk of HTML.
 * Returns null when there is nothing to do, so callers can bail cheaply.
 */
function opl_card_facts_apply( $html ) {
	$card_classes = '(?:oplhub-product|op9-product-card|oprc-card)';

	if ( ! is_string( $html ) || '' === $html ) {
		return null;
	}

	$has_products = (bool) preg_match( '#<article\b[^>]*class="[^"]*' . $card_classes . '#', $html );
	$has_cats     = ( false !== strpos( $html, 'oligopoly-cat-card' ) );

	if ( ! $has_products && ! $has_cats ) {
		return null;
	}

	$out = preg_replace_callback(
		'#<article\b[^>]*class="[^"]*' . $card_classes . '[^"]*"[^>]*>.*?</article>#s',
		'opl_card_facts_card',
		$html
	);

	// preg_* returns null if it hits the backtrack limit - fail safe.
	if ( null === $out ) {
		$out = $html;
	}

	// Catalog research-division tiles use <a class="oligopoly-cat-card">, not <article>.
	if ( false !== strpos( $out, 'oligopoly-cat-card' ) ) {
		$cats = preg_replace_callback(
			'#<a\b[^>]*class="[^"]*oligopoly-cat-card[^"]*"[^>]*>.*?</a>#s',
			'opl_cat_facts_card',
			$out
		);

		if ( null !== $cats ) {
			$out = $cats;
		}
	}

	if ( $out === $html ) {
		return null;
	}

	return $out;
}

function opl_card_facts_style_tag() {
	return '<style id="opl-card-facts-css">' . opl_card_facts_css() . '</style>';
}

/*
 * Pass 1 - the_content. Handles the homepage grid and the /research/ cards.
 */
add_filter( 'the_content', 'opl_card_facts_filter', PHP_INT_MAX );

function opl_card_facts_filter( $content ) {
	if ( is_admin() || is_feed() ) {
		return $content;
	}

	opl_card_facts_count( true );
	$out = opl_card_facts_apply( $content );
	$GLOBALS['opl_facts_last'] = opl_card_facts_count() - 1;

	if ( null === $out ) {
		return $content;
	}

	return opl_card_facts_style_tag() . $out
		. '<!-- opl-facts v2 pass1 rewrote=' . $GLOBALS['opl_facts_last'] . ' -->';
}

/*
 * Pass 2 - final output sweep.
 *
 * The research catalog rebuilds its grid after the_content has run, so pass 1
 * never sees those cards. This catches whatever is left in the finished page.
 * Idempotent - cards already handled by pass 1 are skipped.
 */
add_action( 'template_redirect', 'opl_card_facts_buffer_start', PHP_INT_MAX );

function opl_card_facts_buffer_start() {
	if ( is_admin() || is_feed() || is_robots() ) {
		return;
	}

	ob_start( 'opl_card_facts_buffer' );
}

function opl_card_facts_buffer( $html ) {
	if ( ! is_string( $html ) || '' === $html ) {
		return $html;
	}

	$seen = preg_match_all( '#<article\b[^>]*class="[^"]*(?:oplhub-product|op9-product-card|oprc-card)#', $html );

	opl_card_facts_count( true );
	$out = opl_card_facts_apply( $html );
	$rewrote = opl_card_facts_count() - 1;

	$marker = '<!-- opl-facts v2 pass2 seen=' . (int) $seen . ' rewrote=' . $rewrote . ' -->';

	if ( null === $out ) {
		return $html . $marker;
	}

	// Pass 1 already inlined the stylesheet on most pages; add it only if absent.
	if ( false === strpos( $out, 'opl-card-facts-css' ) ) {
		$patched = preg_replace( '#</head>#i', opl_card_facts_style_tag() . '</head>', $out, 1 );

		if ( null !== $patched ) {
			$out = $patched;
		}
	}

	return $out . $marker;
}

}
