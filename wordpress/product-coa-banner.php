<?php
/**
 * OligoPoly — "COA Available" product banner.
 *
 * Renders a small purple banner directly under the product title on the research
 * products that have certificate-of-analysis documentation on file: seven single
 * vials plus the six 6-vial kits of the same materials.
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

if ( ! function_exists( 'opl_coa_banner_products' ) ) {
	/**
	 * The products that carry the banner, keyed by SKU.
	 *
	 * Keyed by SKU rather than post ID so the list stays readable and survives a
	 * post-ID change. `id` is recorded only as a comment aid — matching is by SKU.
	 *
	 * To add a product: add a row. To remove one: delete its row.
	 *
	 * 'label' is the material name, used for the banner's accessible name (the
	 * visible text does not repeat it — see opl_coa_banner_render). 'href' is the
	 * documentation target; omit it to fall back to the documentation library.
	 *
	 * @return array<string, array{label:string, id:int}>
	 */
	function opl_coa_banner_products() {
		$products = array(
			// Single vials.
			'OP-REC-KLOW-80MG'    => array( 'label' => 'KLOW Research Blend 80 mg', 'id' => 1948 ),
			'OP-LON-GHKCU-50MG'   => array( 'label' => 'GHK-Cu 50 mg',              'id' => 441 ),
			'OP-AUX-NAD-500MG'    => array( 'label' => 'NAD+ 500 mg',               'id' => 63 ),
			'OP-MET-RETA-5MG'     => array( 'label' => 'Retatrutide 5 mg',          'id' => 3395 ),
			'OP-COG-SELANK-5MG'   => array( 'label' => 'Selank 5 mg',               'id' => 447 ),
			'OP-MET-SEMA-5MG'     => array( 'label' => 'Semaglutide 5 mg',          'id' => 3397 ),
			'OP-MET-TIRZ-10MG'    => array( 'label' => 'Tirzepatide 10 mg',         'id' => 39 ),

			// 6-vial kits of the same materials. KLOW has no kit.
			'OP-KIT-GHKCU-50MG-6' => array( 'label' => 'GHK-Cu 50 mg – 6 Vial Research Kit',      'id' => 3468 ),
			'OP-KIT-NAD-500MG-6'  => array( 'label' => 'NAD+ 500 mg – 6 Vial Research Kit',       'id' => 3459 ),
			'OP-KIT-RETA-5MG-6'   => array( 'label' => 'Retatrutide 5 mg – 6 Vial Research Kit',  'id' => 3465 ),
			'OP-KIT-SELANK-5MG-6' => array( 'label' => 'Selank 5 mg – 6 Vial Research Kit',       'id' => 3463 ),
			'OP-KIT-SEMA-5MG-6'   => array( 'label' => 'Semaglutide 5 mg – 6 Vial Research Kit',  'id' => 3457 ),
			'OP-KIT-TIRZ-10MG-6'  => array( 'label' => 'Tirzepatide 10 mg – 6 Vial Research Kit', 'id' => 3454 ),
		);

		/**
		 * Filter the SKU list, so the set can be changed without editing this file.
		 *
		 * @param array $products Banner products keyed by SKU.
		 */
		return apply_filters( 'opl_coa_banner_products', $products );
	}
}

if ( ! function_exists( 'opl_coa_banner_doc_url' ) ) {
	/**
	 * Where the banner's link points. Site-relative so it follows the domain.
	 *
	 * @return string
	 */
	function opl_coa_banner_doc_url() {
		return apply_filters( 'opl_coa_banner_doc_url', home_url( '/research-peptides-with-coa/' ) );
	}
}

if ( ! function_exists( 'opl_coa_banner_render' ) ) {
	/**
	 * Print the banner on a single-product page, if this product is on the list.
	 *
	 * Hooked at priority 6: WooCommerce prints the title at 5 and the price at 10,
	 * so the banner lands between the two.
	 */
	function opl_coa_banner_render() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$sku      = $product->get_sku();
		$products = opl_coa_banner_products();

		if ( '' === $sku || ! isset( $products[ $sku ] ) ) {
			return;
		}

		$label = $products[ $sku ]['label'];
		$href  = isset( $products[ $sku ]['href'] ) ? $products[ $sku ]['href'] : opl_coa_banner_doc_url();

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
		   href="<?php echo esc_url( $href ); ?>"
		   aria-label="<?php echo esc_attr( sprintf( 'Certificate of Analysis available for %s — view documentation', $label ) ); ?>">
			<span class="opl-coa-banner__badge">COA Available</span>
			<span class="opl-coa-banner__text">Certificate of Analysis on file</span>
			<span class="opl-coa-banner__cta" aria-hidden="true">View&nbsp;COA&nbsp;&rarr;</span>
		</a>
		<?php
	}
	add_action( 'woocommerce_single_product_summary', 'opl_coa_banner_render', 6 );
}

if ( ! function_exists( 'opl_coa_banner_styles' ) ) {
	/**
	 * Print the banner stylesheet once per request, and only when a banner renders.
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
		@media (prefers-reduced-motion:reduce){
			.opl-coa-banner{transition:none;}
			.opl-coa-banner:hover,
			.opl-coa-banner:focus-visible{transform:none;}
		}
		</style>
		<?php
	}
}
