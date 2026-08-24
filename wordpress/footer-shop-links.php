<?php
/**
 * OligoPoly - repoint the footer Shop column
 *
 * THE PROBLEM
 * -----------
 * The footer's Shop column links to five categories that no longer exist:
 *
 *   Vitamins          /product-category/vitamins/          HTTP 410 Gone
 *   Longevity         /product-category/longevity/         HTTP 410 Gone
 *   Performance       /product-category/performance/       HTTP 410 Gone
 *   Cognitive Support /product-category/cognitive-support/ HTTP 410 Gone
 *   Wellness          /product-category/wellness/          HTTP 410 Gone
 *
 * Those are leftovers from the supplement catalogue. The 410 is correct and
 * deliberate - it is how a retired URL should be retired - but the footer was
 * never updated, so every page on the site carries five dead links.
 *
 * THE FIX
 * -------
 * Point the column at the categories that actually hold products once
 * `product-category-repair.php` has run. The terms are NOT hard-coded as
 * links: each candidate is checked at render time and only included if its
 * term exists and holds at least one published product. A category that is
 * empty is skipped rather than becoming the next dead link.
 *
 * If none of the candidates qualify - for instance if this is activated before
 * the category migration - the footer is left EXACTLY as it is. Replacing five
 * dead links with an empty column would be worse than leaving them.
 *
 * The rest of the footer is untouched: logo, address, the Research and Company
 * columns, the legal row.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Candidate links for the Shop column, in order.
 *
 * Label => category slug. Only those with products are rendered.
 */
function opl_fsl_candidates() {
	return array(
		'Metabolic Research'  => 'metabolic-research',
		'Cellular Research'   => 'cellular-research',
		'Longevity Research'  => 'longevity-research',
		'Cognitive Research'  => 'cognitive-research',
		'Research Stacks'     => 'research-stacks',
	);
}

/** How many links the column shows at most. */
function opl_fsl_limit() {
	return 5;
}

/**
 * The links to render, or an empty array if none qualify.
 *
 * Cached for an hour: this runs on every page render, and counting products
 * per category on each one would be a needless query on every request.
 */
function opl_fsl_links() {
	$links = get_transient( 'opl_fsl_links' );

	if ( is_array( $links ) ) {
		return $links;
	}

	$links = array();

	foreach ( opl_fsl_candidates() as $label => $slug ) {
		if ( count( $links ) >= opl_fsl_limit() ) {
			break;
		}

		$term = get_term_by( 'slug', $slug, 'product_cat' );

		if ( ! $term || is_wp_error( $term ) ) {
			continue;
		}

		// Count directly rather than trusting `$term->count`, which was
		// observed out of step with reality on this site.
		$has = get_posts(
			array(
				'post_type'        => 'product',
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'tax_query'        => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => (int) $term->term_id,
					),
				),
			)
		);

		if ( empty( $has ) ) {
			continue;
		}

		$links[ $label ] = get_term_link( $term );
	}

	// Drop anything whose permalink could not be built.
	$links = array_filter(
		$links,
		function ( $url ) {
			return is_string( $url ) && '' !== $url;
		}
	);

	set_transient( 'opl_fsl_links', $links, HOUR_IN_SECONDS );

	return $links;
}

/** Rebuild the Shop nav, keeping its heading and markup shape. */
function opl_fsl_rewrite( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, 'Shop footer links' ) ) {
		return $html;
	}

	$links = opl_fsl_links();

	// Fail safe: leave the existing column alone rather than emptying it.
	if ( empty( $links ) ) {
		return $html;
	}

	$replacement = '<nav aria-label="Shop footer links"><h3>Shop</h3>';

	foreach ( $links as $label => $url ) {
		$replacement .= '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}

	$replacement .= '</nav>';

	$out = preg_replace(
		'#<nav aria-label="Shop footer links">.*?</nav>#s',
		// Guard against $ and \ in the replacement being read as backreferences.
		str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $replacement ),
		$html,
		1
	);

	return ( null === $out ) ? $html : $out;
}

add_action( 'template_redirect', 'opl_fsl_buffer_start', PHP_INT_MAX );

function opl_fsl_buffer_start() {
	if ( is_admin() || is_feed() || is_robots() ) {
		return;
	}

	ob_start( 'opl_fsl_rewrite' );
}

/** Rebuild the cached list whenever product categories change. */
add_action( 'set_object_terms', 'opl_fsl_flush' );
add_action( 'edited_product_cat', 'opl_fsl_flush' );
add_action( 'delete_product_cat', 'opl_fsl_flush' );

function opl_fsl_flush() {
	delete_transient( 'opl_fsl_links' );
}
