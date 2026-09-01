<?php
/**
 * OligoPoly — "COA Available" banner.
 *
 * Shows COA availability everywhere a product appears:
 *   - single product pages: a purple banner between the title and the price
 *   - shop / category / search / related-product listings: a compact pill on the card
 *
 * Applies to every product by default. It used to carry a hand-maintained list of
 * thirteen SKUs, which meant every product added afterwards silently had no banner;
 * the rule is now inverted, so new products are covered automatically and only the
 * exceptions need naming. See opl_coa_banner_excluded_skus() to exclude any.
 *
 * Scope: front-end display only. This does not touch product data, pricing,
 * inventory, cart, checkout, or any WooCommerce setting. Removing the snippet
 * removes every trace of the banner.
 *
 * Install: paste into a WPCode / Code Snippets PHP snippet (run everywhere), or
 * append to the child theme's functions.php. See docs/COA-BANNER.md.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'opl_coa_banner_excluded_skus' ) ) {
	/**
	 * SKUs that must NOT show the banner.
	 *
	 * Empty by default: every product is covered. Add a SKU here to drop that one
	 * product, or filter the list from elsewhere without editing this file:
	 *
	 *     add_filter( 'opl_coa_banner_exclude_skus', function ( $skus ) {
	 *         $skus[] = 'OP-SUP-BACWATER-10ML';
	 *         return $skus;
	 *     } );
	 *
	 * @return string[]
	 */
	function opl_coa_banner_excluded_skus() {
		return (array) apply_filters( 'opl_coa_banner_exclude_skus', array() );
	}
}

if ( ! function_exists( 'opl_coa_banner_applies' ) ) {
	/**
	 * Whether a given product should carry the banner.
	 *
	 * @param mixed $product Expected to be a WC_Product; anything else is rejected.
	 * @return bool
	 */
	function opl_coa_banner_applies( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		$sku = $product->get_sku();
		if ( '' !== $sku && in_array( $sku, opl_coa_banner_excluded_skus(), true ) ) {
			return false;
		}

		/**
		 * Final say on whether this product shows the banner, for rules a SKU list
		 * cannot express (a category, a stock state, a custom field).
		 *
		 * @param bool       $applies Whether to show it.
		 * @param WC_Product $product The product being rendered.
		 */
		return (bool) apply_filters( 'opl_coa_banner_applies', true, $product );
	}
}

if ( ! function_exists( 'opl_coa_banner_doc_url' ) ) {
	/**
	 * Where the banner's link points. Built from home_url() so it follows the domain.
	 *
	 * @return string
	 */
	function opl_coa_banner_doc_url() {
		return apply_filters( 'opl_coa_banner_doc_url', home_url( '/research-peptides-with-coa/' ) );
	}
}

if ( ! function_exists( 'opl_coa_banner_render' ) ) {
	/**
	 * Print the full banner on a single-product page.
	 *
	 * Hooked at priority 6: WooCommerce prints the title at 5 and the price at 10,
	 * so the banner lands between the two.
	 */
	function opl_coa_banner_render() {
		global $product;

		if ( ! opl_coa_banner_applies( $product ) ) {
			return;
		}

		opl_coa_banner_styles();

		/*
		 * The visible line deliberately does not repeat the product name — the banner
		 * sits directly under the product title, so naming it again is redundant and
		 * pushes the CTA onto a second line on longer titles (the 6-vial kits).
		 * The name still reaches screen readers via aria-label, which is read instead
		 * of the link's contents.
		 */
		?>
		<a class="opl-coa-banner"
		   href="<?php echo esc_url( opl_coa_banner_doc_url() ); ?>"
		   aria-label="<?php echo esc_attr( sprintf( 'Certificate of Analysis available for %s — view documentation', $product->get_name() ) ); ?>">
			<span class="opl-coa-banner__badge">COA Available</span>
			<span class="opl-coa-banner__text">Certificate of Analysis on file</span>
			<span class="opl-coa-banner__cta" aria-hidden="true">View&nbsp;COA&nbsp;&rarr;</span>
		</a>
		<?php
	}
	add_action( 'woocommerce_single_product_summary', 'opl_coa_banner_render', 6 );
}

if ( ! function_exists( 'opl_coa_banner_render_loop' ) ) {
	/**
	 * Print the compact pill on a product card in shop / category / search listings.
	 *
	 * Deliberately a <span>, not a link: this hook fires inside WooCommerce's own
	 * product-card <a>, and nesting an anchor there is invalid markup that browsers
	 * silently restructure. The card's existing link already takes the reader to the
	 * product, where the full banner links on to the documentation.
	 *
	 * Priority 9 puts it after the title and rating (5) and before the price (10),
	 * matching the single-product placement.
	 */
	function opl_coa_banner_render_loop() {
		global $product;

		if ( ! opl_coa_banner_applies( $product ) ) {
			return;
		}

		opl_coa_banner_styles();
		?>
		<span class="opl-coa-pill">COA Available</span>
		<?php
	}
	add_action( 'woocommerce_after_shop_loop_item_title', 'opl_coa_banner_render_loop', 9 );
}

if ( ! function_exists( 'opl_coa_banner_styles' ) ) {
	/**
	 * Print the stylesheet once per request, and only when something renders.
	 *
	 * Kept inline and `opl-coa-` prefixed so nothing loads sitewide and nothing can
	 * collide with theme or plugin CSS.
	 */
	function opl_coa_banner_styles() {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		?>
		<style id="opl-coa-banner-styles">
		/* Purple fill runs #9333ea -> #ae3ada. White text clears 4.5:1 (WCAG AA)
		   against both ends, so contrast holds across the whole gradient. */
		.opl-coa-banner{
			box-sizing:border-box;
			display:flex;
			align-items:center;
			gap:10px;
			flex-wrap:wrap;
			margin:12px 0 14px;
			padding:9px 14px;
			border:1px solid #d8b4fe;
			border-radius:9px;
			background:linear-gradient(100deg,#9333ea,#ae3ada);
			box-shadow:0 0 0 1px rgba(216,180,254,.28), 0 6px 18px rgba(147,51,234,.34);
			color:#fff!important;
			text-decoration:none!important;
			font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
			line-height:1.4;
			transition:box-shadow .18s ease, transform .18s ease;
		}
		.opl-coa-banner:hover,
		.opl-coa-banner:focus-visible{
			transform:translateY(-1px);
			box-shadow:0 0 0 1px #f0c8ff, 0 10px 24px rgba(147,51,234,.46);
			text-decoration:none!important;
		}
		.opl-coa-banner:focus-visible{
			outline:2px solid #f0c8ff;
			outline-offset:2px;
		}
		.opl-coa-banner__badge{
			flex:0 0 auto;
			padding:3px 9px;
			border-radius:999px;
			background:#fff;
			color:#7e22ce!important;
			font-size:11px!important;
			font-weight:800;
			letter-spacing:.07em;
			text-transform:uppercase;
			white-space:nowrap;
		}
		.opl-coa-banner__text{
			flex:1 1 auto;
			min-width:0;
			color:#fff!important;
			font-size:13px!important;
			font-weight:500;
		}
		.opl-coa-banner__cta{
			/* margin-left:auto keeps the CTA hard right even if the line above wraps,
			   so it never ends up orphaned under the badge in a narrow summary column. */
			flex:0 0 auto;
			margin-left:auto;
			color:#fff!important;
			font-size:12px!important;
			font-weight:700;
			white-space:nowrap;
			opacity:.95;
		}
		@media (max-width:480px){
			.opl-coa-banner{gap:8px;padding:8px 11px;}
			.opl-coa-banner__text{flex-basis:100%;font-size:12.5px!important;}
		}

		/* Listing pill. A product card is small and repeats down a grid, so this is
		   the badge alone — no supporting line, no CTA — sized to sit on one line at
		   any card width. */
		.opl-coa-pill{
			display:inline-block;
			margin:6px 0 4px;
			padding:3px 9px;
			border-radius:999px;
			border:1px solid #d8b4fe;
			background:linear-gradient(100deg,#9333ea,#ae3ada);
			box-shadow:0 0 0 1px rgba(216,180,254,.22);
			color:#fff!important;
			font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
			font-size:10.5px!important;
			font-weight:800;
			line-height:1.5;
			letter-spacing:.07em;
			text-transform:uppercase;
			text-decoration:none!important;
			white-space:nowrap;
			vertical-align:middle;
		}
		@media (prefers-reduced-motion:reduce){
			.opl-coa-banner{transition:none;}
			.opl-coa-banner:hover,
			.opl-coa-banner:focus-visible{transform:none;}
		}
		</style>
		<?php
	}
}
