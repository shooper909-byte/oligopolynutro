<?php
/**
 * Fact bullets for OligoPoly product and stack cards.
 *
 * Paste into the Code Snippets / WPCode snippet that renders the cards, then
 * call oplhub_product_facts( $product_name ) where the card's <ul> is built.
 *
 * Keys are matched as substrings against the lowercased product name, so
 * "Tirzepatide 10 mg" and "Tirzepatide 10 mg - 6 Vial Research Kit" both hit
 * the 'tirzepatide' entry. Returns '' when nothing matches, so a card for an
 * unlisted product simply keeps whatever it renders today.
 *
 * Copy is mechanism/target descriptors only - no human-use, dosing, therapeutic
 * or outcome claims - and every entry keeps its research-use-only line.
 *
 * Pair with wordpress/product-card-facts.css for the .oplhub-key highlight.
 */
function oplhub_product_facts( $product_name ) {
	$facts = array(
		// Tirzepatide
		'tirzepatide' => array(
			'Dual <strong class="oplhub-key">GIP</strong> / <strong class="oplhub-key">GLP-1</strong> receptor agonist',
			'<strong class="oplhub-key">Incretin signaling</strong> pathway studies',
			'Research-use-only material',
		),
		// Semaglutide
		'semaglutide' => array(
			'Selective <strong class="oplhub-key">GLP-1</strong> receptor agonist',
			'<strong class="oplhub-key">Incretin signaling</strong> and receptor-selectivity studies',
			'Research-use-only material',
		),
		// Retatrutide
		'retatrutide' => array(
			'Triple <strong class="oplhub-key">GIP</strong> / <strong class="oplhub-key">GLP-1</strong> / <strong class="oplhub-key">glucagon</strong> receptor agonist',
			'Multi-receptor <strong class="oplhub-key">incretin pathway</strong> studies',
			'Research-use-only material',
		),
		// Cagrilintide
		'cagrilintide' => array(
			'Long-acting <strong class="oplhub-key">amylin</strong> receptor analog',
			'<strong class="oplhub-key">Amylin</strong> and <strong class="oplhub-key">calcitonin receptor</strong> signaling studies',
			'Research-use-only material',
		),
		// NAD+
		'nad' => array(
			'<strong class="oplhub-key">Redox cofactor</strong> — nicotinamide adenine dinucleotide',
			'<strong class="oplhub-key">Mitochondrial</strong> and <strong class="oplhub-key">sirtuin</strong> pathway studies',
			'Research-use-only material',
		),
		// GHK-Cu
		'ghk-cu' => array(
			'<strong class="oplhub-key">Copper-binding</strong> tripeptide (Gly-His-Lys)',
			'<strong class="oplhub-key">Extracellular matrix</strong> and <strong class="oplhub-key">collagen</strong> signaling studies',
			'Research-use-only material',
		),
		// Selank
		'selank' => array(
			'Synthetic <strong class="oplhub-key">tuftsin</strong> analog heptapeptide',
			'<strong class="oplhub-key">GABAergic</strong> and <strong class="oplhub-key">BDNF</strong> pathway studies',
			'Research-use-only material',
		),

		// Metabolic Pathways Stack
		'metabolic-pathways' => array(
			'<strong class="oplhub-key">GIP</strong> / <strong class="oplhub-key">GLP-1</strong> / <strong class="oplhub-key">glucagon</strong> receptor coverage',
			'Multi-compound <strong class="oplhub-key">incretin</strong> study design',
			'Research-use-only materials',
		),
		// Cellular Energy Stack
		'cellular-energy' => array(
			'<strong class="oplhub-key">Mitochondrial</strong> and <strong class="oplhub-key">redox cofactor</strong> coverage',
			'<strong class="oplhub-key">Sirtuin</strong> pathway study design',
			'Research-use-only materials',
		),
		// Neurocognitive Pathways Stack
		'neurocognitive-pathways' => array(
			'<strong class="oplhub-key">GABAergic</strong> and <strong class="oplhub-key">BDNF</strong> pathway coverage',
			'<strong class="oplhub-key">Neuropeptide</strong> study design',
			'Research-use-only materials',
		),
		// Regenerative Biology Stack
		'regenerative-biology' => array(
			'<strong class="oplhub-key">Extracellular matrix</strong> and <strong class="oplhub-key">collagen</strong> signaling coverage',
			'<strong class="oplhub-key">Tissue-repair</strong> pathway study design',
			'Research-use-only materials',
		),
	);

	$needle = strtolower( $product_name );

	foreach ( $facts as $key => $lines ) {
		if ( false !== strpos( $needle, $key ) ) {
			return '<ul class="oplhub-facts"><li>' . implode( '</li><li>', $lines ) . '</li></ul>';
		}
	}

	return '';
}
