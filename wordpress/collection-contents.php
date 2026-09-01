<?php
/**
 * OligoPoly — "What's inside" for bundles and collections.
 *
 * Bundles and collections currently say nothing about what they contain. A card
 * reading "SKU OP-STACK-METABOLIC · Review the product record for specifications"
 * gives a buyer no reason to click. This renders the real contents:
 *
 *   - single product pages: a panel above the price listing the materials
 *   - shop / category / search listings: a one-line summary on the card
 *
 * EVERY CONTENT LINE COMES FROM THE PRODUCT'S OWN WooCommerce Mix and Match
 * CONFIGURATION. Nothing is hard-coded or inferred, so it cannot drift from what
 * the customer actually receives, and editing the bundle in wp-admin updates the
 * display automatically. A product with no configured contents renders nothing at
 * all rather than a guess — see docs/COLLECTION-CONTENTS.md for the two products
 * where that is currently the case.
 *
 * Scope: front-end display only. No product, cart or WooCommerce data is written.
 *
 * Install: a SECOND WPCode PHP snippet, separate from the COA banner. Paste
 * without the opening <?php line.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'opl_cc_focus_lines' ) ) {
	/**
	 * The one-line "what it is for" shown under the title, keyed by SKU.
	 *
	 * Deliberately categorical — the research area each collection is organised
	 * around, in the site's own existing language. These describe scope, not effect:
	 * no outcome, benefit, dosing or human-use claim belongs here, on a research-use
	 * catalogue.
	 *
	 * Anything not listed simply shows no focus line.
	 *
	 * @return array<string, string>
	 */
	function opl_cc_focus_lines() {
		return (array) apply_filters(
			'opl_cc_focus_lines',
			array(
				'OP-STACK-METABOLIC'           => 'Metabolic pathway research',
				'OP-STACK-CELLULAR'            => 'Cellular energy and mitochondrial pathway research',
				'OP-STACK-NEURO'               => 'Neurocognitive pathway research',
				'OP-STACK-REGEN'               => 'Regenerative and tissue-pathway research',
				'OP-STK-LONGEVITY'             => 'Cellular longevity and mitochondrial pathway research',
				'OP-STK-ADVANCED-MULTIPATHWAY' => 'Multi-pathway research across categories',
				'OP-BUNDLE-3'                  => 'Choose any three research materials',
				'OP-BUNDLE-6'                  => 'Choose any six research materials',
				'OP-BUNDLE-9'                  => 'Choose any nine research materials',
			)
		);
	}
}

if ( ! function_exists( 'opl_cc_read_contents' ) ) {
	/**
	 * Read a container's contents straight out of its Mix and Match configuration.
	 *
	 * Returns null whenever the product is not a configured container, so callers
	 * render nothing rather than inventing a list. Every access is guarded: this has
	 * to survive the Mix and Match plugin being updated, deactivated, or absent.
	 *
	 * @param mixed $product Expected WC_Product.
	 * @return array{names:string[], min:int, max:int, fixed:bool}|null
	 */
	function opl_cc_read_contents( $product ) {
		if ( ! $product instanceof WC_Product || ! method_exists( $product, 'get_child_items' ) ) {
			return null;
		}

		$children = $product->get_child_items();
		if ( empty( $children ) || ! is_array( $children ) ) {
			return null;
		}

		$names = array();
		foreach ( $children as $child ) {
			if ( ! is_object( $child ) || ! method_exists( $child, 'get_product' ) ) {
				continue;
			}
			$item = $child->get_product();
			if ( $item instanceof WC_Product ) {
				$names[] = $item->get_name();
			}
		}

		if ( empty( $names ) ) {
			return null;
		}

		$min = method_exists( $product, 'get_min_container_size' ) ? (int) $product->get_min_container_size() : 0;
		$max = method_exists( $product, 'get_max_container_size' ) ? (int) $product->get_max_container_size() : 0;

		return array(
			'names' => $names,
			'min'   => $min,
			'max'   => $max,
			/*
			 * When the buyer must take every option, the container is a fixed set and
			 * can be stated as "contains". When they pick fewer than are offered it is
			 * a choice, and saying "contains" would be a lie — hence the distinction.
			 */
			'fixed' => ( $min > 0 && $min === $max && $min === count( $names ) ),
		);
	}
}

if ( ! function_exists( 'opl_cc_summary_line' ) ) {
	/**
	 * One-sentence summary of the contents, for a listing card.
	 *
	 * @param array $c Result of opl_cc_read_contents().
	 * @return string
	 */
	function opl_cc_summary_line( array $c ) {
		$count = count( $c['names'] );

		if ( $c['fixed'] ) {
			return sprintf(
				/* translators: 1: number of materials, 2: comma-separated list */
				_n( 'Contains %1$d material: %2$s', 'Contains %1$d materials: %2$s', $count, 'default' ),
				$count,
				implode( ', ', $c['names'] )
			);
		}

		$pick = $c['min'] > 0 ? $c['min'] : $c['max'];
		if ( $pick > 0 ) {
			return sprintf( 'Choose %1$d from %2$d research materials', $pick, $count );
		}

		return sprintf( 'Choose from %d research materials', $count );
	}
}

if ( ! function_exists( 'opl_cc_render_single' ) ) {
	/**
	 * Panel on the single product page, between the title and the price.
	 *
	 * Priority 7 sits just after the COA banner (6) and before the price (10).
	 */
	function opl_cc_render_single() {
		global $product;

		$c = opl_cc_read_contents( $product );
		if ( null === $c ) {
			return;
		}

		$focus = opl_cc_focus_lines();
		$sku   = $product->get_sku();
		$line  = isset( $focus[ $sku ] ) ? $focus[ $sku ] : '';

		opl_cc_styles();
		?>
		<section class="opl-cc" aria-label="What this collection contains">
			<?php if ( '' !== $line ) : ?>
				<p class="opl-cc__focus"><?php echo esc_html( $line ); ?></p>
			<?php endif; ?>
			<p class="opl-cc__head">
				<?php echo esc_html( $c['fixed'] ? 'What this contains' : 'What you choose from' ); ?>
				<span class="opl-cc__count"><?php echo esc_html( opl_cc_count_label( $c ) ); ?></span>
			</p>
			<ul class="opl-cc__list">
				<?php foreach ( $c['names'] as $name ) : ?>
					<li><?php echo esc_html( $name ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}
	add_action( 'woocommerce_single_product_summary', 'opl_cc_render_single', 7 );
}

if ( ! function_exists( 'opl_cc_count_label' ) ) {
	/**
	 * Short badge text: "6 materials" for a fixed set, "pick 3 of 24" for a choice.
	 *
	 * @param array $c Result of opl_cc_read_contents().
	 * @return string
	 */
	function opl_cc_count_label( array $c ) {
		$count = count( $c['names'] );
		if ( $c['fixed'] ) {
			return sprintf( _n( '%d material', '%d materials', $count, 'default' ), $count );
		}
		$pick = $c['min'] > 0 ? $c['min'] : $c['max'];
		return $pick > 0 ? sprintf( 'pick %1$d of %2$d', $pick, $count ) : sprintf( '%d options', $count );
	}
}

if ( ! function_exists( 'opl_cc_render_loop' ) ) {
	/**
	 * One-line summary on a listing card.
	 *
	 * A <p>, not a link: this hook fires inside WooCommerce's own product-card <a>,
	 * where a nested anchor is invalid markup. Priority 8 puts it after the COA
	 * pill (9 is taken on the sibling snippet) and before the price (10).
	 */
	function opl_cc_render_loop() {
		global $product;

		$c = opl_cc_read_contents( $product );
		if ( null === $c ) {
			return;
		}

		opl_cc_styles();
		?>
		<span class="opl-cc-line"><?php echo esc_html( opl_cc_summary_line( $c ) ); ?></span>
		<?php
	}
	add_action( 'woocommerce_after_shop_loop_item_title', 'opl_cc_render_loop', 8 );
}

if ( ! function_exists( 'opl_cc_styles' ) ) {
	/**
	 * Print the stylesheet once per request, only when something renders.
	 */
	function opl_cc_styles() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		?>
		<style id="opl-cc-styles">
		.opl-cc{
			box-sizing:border-box;
			margin:12px 0 14px;
			padding:12px 14px;
			border:1px solid rgba(168,85,247,.34);
			border-radius:10px;
			background:rgba(147,51,234,.06);
			font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
		}
		.opl-cc__focus{
			margin:0 0 7px;
			color:#d8b4fe!important;
			font-size:12px!important;
			font-weight:700;
			letter-spacing:.05em;
			text-transform:uppercase;
			line-height:1.4;
		}
		.opl-cc__head{
			display:flex;
			flex-wrap:wrap;
			align-items:baseline;
			gap:8px;
			margin:0 0 6px;
			color:inherit;
			font-size:13px!important;
			font-weight:750;
		}
		.opl-cc__count{
			padding:2px 8px;
			border-radius:999px;
			border:1px solid rgba(216,180,254,.45);
			color:#d8b4fe!important;
			font-size:10.5px!important;
			font-weight:700;
			letter-spacing:.06em;
			text-transform:uppercase;
			white-space:nowrap;
		}
		.opl-cc__list{
			margin:0;
			padding:0 0 0 18px;
			font-size:13px!important;
			line-height:1.6;
		}
		.opl-cc__list li{margin:0 0 2px;}

		/* Listing card: one line, clamped so a long set cannot stretch the grid. */
		.opl-cc-line{
			display:-webkit-box;
			-webkit-line-clamp:2;
			-webkit-box-orient:vertical;
			overflow:hidden;
			margin:4px 0 6px;
			font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
			font-size:11.5px!important;
			line-height:1.45;
			opacity:.85;
		}
		</style>
		<?php
	}
}
