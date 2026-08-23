<?php
/**
 * OligoPoly - COA example figure on /research/
 *
 * Inserts the certificate-of-analysis illustration full width in the
 * "Research Documentation and Traceability" section, directly below the
 * heading and its intro paragraph, above the three verification cards.
 *
 * WHY A SNIPPET
 * /research/ is rendered by the snippet that emits `oplhub-refresh-20260821`,
 * which replaces page 3038's stored content entirely. Editing the page has no
 * effect, so this rewrites the finished HTML instead.
 *
 * LAYOUT
 * `.oplhub-doc-grid` is a two-column grid whose second track holds
 * `.oplhub-coa-slot`. That slot is empty and `hidden`, so the right-hand track
 * currently renders as dead space. Collapsing the grid to one column both gives
 * the figure the full 1320px shell and removes that gap. The compliance slot is
 * left untouched and stays hidden.
 *
 * COMPLIANCE
 * The certificate in the artwork is an illustration of the documentation
 * format, not a record for a live lot, so the figure is captioned and
 * alt-described as an example. It is deliberately NOT placed in
 * `.oplhub-coa-slot`, whose own comment reserves it for a verified passing COA
 * with a real product, lot, laboratory, method and report date. Nothing here
 * asserts a test result for any batch.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Media library ID of the illustration. */
function opl_coa_attachment_id() {
	return 3544;
}

/** Used only if the attachment above cannot be read. */
function opl_coa_fallback_url() {
	return 'https://www.oligopolypeptides.com/wp-content/uploads/2026/08/5113.png';
}

/** Caption shown under the figure. Keep the example framing. */
function opl_coa_caption() {
	return 'Example certificate &mdash; illustrative of the documentation format. '
		. 'Published records vary by product and batch.';
}

/** Resolve the image. Returns array(url, w, h, alt, how) or null. */
function opl_coa_asset() {
	static $cache = false;

	if ( false !== $cache ) {
		return $cache;
	}

	$cache = null;
	$id    = opl_coa_attachment_id();
	$url   = '';
	$w     = 0;
	$h     = 0;
	$alt   = '';
	$how   = '';

	if ( $id && function_exists( 'wp_get_attachment_url' ) ) {
		$url = wp_get_attachment_url( $id );

		if ( $url ) {
			$how  = 'id';
			$meta = wp_get_attachment_metadata( $id );
			$w    = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
			$h    = isset( $meta['height'] ) ? (int) $meta['height'] : 0;
			$alt  = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		}
	}

	if ( ! $url ) {
		$url = opl_coa_fallback_url();
		$how = $url ? 'fallback-url' : '';
	}

	if ( ! $url ) {
		return $cache;
	}

	if ( '' === trim( $alt ) ) {
		$alt = 'Example Certificate of Analysis shown beside a labelled research vial, '
			. 'with callouts explaining how a batch number and QR code link a vial to its certificate.';
	}

	$cache = array(
		'url' => $url,
		'w'   => $w,
		'h'   => $h,
		'alt' => $alt,
		'how' => $how,
	);

	return $cache;
}

/** The figure markup. */
function opl_coa_figure() {
	$asset = opl_coa_asset();

	if ( ! $asset ) {
		return '';
	}

	$dims = ( $asset['w'] && $asset['h'] )
		? ' width="' . (int) $asset['w'] . '" height="' . (int) $asset['h'] . '"'
		: '';

	return '<figure class="opl-coa-fig">'
		. '<img src="' . esc_url( $asset['url'] ) . '" alt="' . esc_attr( $asset['alt'] ) . '"'
		. $dims . ' loading="lazy" decoding="async">'
		. '<figcaption>' . opl_coa_caption() . '</figcaption>'
		. '</figure>';
}

/** Scoped styles. */
function opl_coa_css() {
	return '<style id="opl-coa-fig-css">'
		. '.oplhub-doc-grid{grid-template-columns:1fr!important}'
		. '.opl-coa-fig{margin:22px 0 26px;padding:0}'
		. '.opl-coa-fig img{display:block;width:100%;height:auto;border-radius:16px;'
		. 'border:1px solid rgba(194,79,239,.3);background:#07040b}'
		. '.opl-coa-fig figcaption{margin-top:10px;font-size:13px;line-height:1.6;'
		. 'color:#d0c4d6}'
		. '@media(max-width:650px){.opl-coa-fig{margin:16px 0 20px}'
		. '.opl-coa-fig figcaption{font-size:12.5px}}'
		. '</style>';
}

/** Insert the figure ahead of the verification cards. */
function opl_coa_rewrite( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, 'oplhub-doc-cards' ) ) {
		return $html;
	}

	// Idempotent: never insert twice.
	if ( false !== strpos( $html, 'opl-coa-fig' ) ) {
		return $html . '<!-- opl-coa-fig already-present -->';
	}

	$figure = opl_coa_figure();
	$marker = '<!-- opl-coa-fig found=' . ( $figure ? opl_coa_asset()['how'] : 'none' ) . ' -->';

	if ( ! $figure ) {
		return $html . $marker;
	}

	$replacement = str_replace(
		array( '\\', '$' ),
		array( '\\\\', '\\$' ),
		$figure
	);

	$out = preg_replace(
		'#(<div class="oplhub-doc-cards">)#',
		$replacement . '$1',
		$html,
		1
	);

	if ( null === $out || $out === $html ) {
		return $html . $marker;
	}

	$patched = preg_replace( '#</head>#i', opl_coa_css() . '</head>', $out, 1 );

	return ( ( null === $patched ) ? $out : $patched ) . $marker;
}

add_action( 'template_redirect', 'opl_coa_buffer_start', PHP_INT_MAX );

function opl_coa_buffer_start() {
	if ( is_admin() || is_feed() || is_robots() ) {
		return;
	}

	ob_start( 'opl_coa_rewrite' );
}
