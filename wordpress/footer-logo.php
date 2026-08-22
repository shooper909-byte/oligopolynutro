<?php
/**
 * OligoPoly - footer logo swap
 *
 * The site footer is rendered by another snippet (the one that emits
 * `opl-footer-v2-css`), and its logo is hard-coded to
 * /wp-content/uploads/oligopoly/logo.webp - a 512x512 image squeezed into a
 * 42px-tall slot. This replaces it with the current brand lockup without
 * editing that snippet.
 *
 * The replacement image is found in the media library BY NAME, so no URL is
 * hard-coded and re-uploading the file cannot break it. Upload the logo with
 * the filename `oligopoly-footer-logo.webp` (slug `oligopoly-footer-logo`).
 *
 * If the attachment is missing, everything is left exactly as it is - the
 * footer keeps its existing logo rather than rendering a broken image.
 *
 * Only the footer logo is touched. The header logo, the WordPress custom logo
 * and every other image are left alone.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Slug of the uploaded replacement image. */
function opl_flogo_slug() {
	return 'oligopoly-footer-logo';
}

/** Displayed height, in CSS pixels. */
function opl_flogo_height() {
	return 96;
}

/** Resolve the replacement attachment. Returns array(url, width, height) or null. */
function opl_flogo_asset() {
	static $cache = false;

	if ( false !== $cache ) {
		return $cache;
	}

	$cache = null;

	if ( ! function_exists( 'get_posts' ) ) {
		return $cache;
	}

	$base = array(
		'post_type'        => 'attachment',
		'post_status'      => 'inherit',
		'numberposts'      => 1,
		'fields'           => 'ids',
		'suppress_filters' => false,
	);

	// Exact slug first.
	$ids = get_posts( $base + array( 'name' => opl_flogo_slug() ) );

	// WordPress appends -1, -2 ... when a filename is already taken, so fall
	// back to a search. Newest wins, which is the most recently uploaded file.
	if ( ! $ids ) {
		$ids = get_posts(
			$base + array(
				's'       => opl_flogo_slug(),
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);
	}

	if ( ! $ids ) {
		return $cache;
	}

	$url = wp_get_attachment_url( $ids[0] );

	if ( ! $url ) {
		return $cache;
	}

	$meta   = wp_get_attachment_metadata( $ids[0] );
	$width  = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
	$height = isset( $meta['height'] ) ? (int) $meta['height'] : 0;

	// Scale the intrinsic size down to the displayed height so width/height
	// stay in the correct ratio and nothing shifts while loading.
	$h = opl_flogo_height();
	$w = ( $width && $height ) ? (int) round( $width * $h / $height ) : 0;

	$cache = array(
		'url' => $url,
		'w'   => $w,
		'h'   => $h,
	);

	return $cache;
}

/** Rewrite the footer logo <img> wherever it appears in the finished page. */
function opl_flogo_rewrite( $html ) {
	if ( ! is_string( $html ) || false === strpos( $html, 'opl-footer-v2-logo' ) ) {
		return $html;
	}

	$asset = opl_flogo_asset();

	if ( ! $asset ) {
		return $html;
	}

	$out = preg_replace_callback(
		'#<img\b[^>]*class="[^"]*opl-footer-v2-logo[^"]*"[^>]*>#i',
		'opl_flogo_tag',
		$html
	);

	if ( null === $out || $out === $html ) {
		return $html;
	}

	$css = '<style id="opl-footer-logo-css">.opl-footer-v2-logo{height:'
		. (int) opl_flogo_height() . 'px!important;width:auto!important;max-width:100%;'
		. 'display:block;margin-bottom:16px}'
		. '@media(max-width:640px){.opl-footer-v2-logo{height:'
		. (int) round( opl_flogo_height() * 0.78 ) . 'px!important}}</style>';

	$patched = preg_replace( '#</head>#i', $css . '</head>', $out, 1 );

	return ( null === $patched ) ? $out : $patched;
}

/** Rebuild one <img> tag, preserving its alt text. */
function opl_flogo_tag( $matches ) {
	$asset = opl_flogo_asset();

	if ( ! $asset ) {
		return $matches[0];
	}

	$alt = 'OligoPoly Laboratories';

	if ( preg_match( '#\balt="([^"]*)"#i', $matches[0], $m ) && '' !== trim( $m[1] ) ) {
		$alt = $m[1];
	}

	$tag = '<img class="opl-footer-v2-logo" src="' . esc_url( $asset['url'] ) . '"'
		. ' alt="' . esc_attr( $alt ) . '"';

	if ( $asset['w'] && $asset['h'] ) {
		$tag .= ' width="' . (int) $asset['w'] . '" height="' . (int) $asset['h'] . '"';
	}

	// The footer sits below the fold on every template, so lazy-loading is safe.
	$tag .= ' loading="lazy" decoding="async">';

	return $tag;
}

add_action( 'template_redirect', 'opl_flogo_buffer_start', PHP_INT_MAX );

function opl_flogo_buffer_start() {
	if ( is_admin() || is_feed() || is_robots() ) {
		return;
	}

	ob_start( 'opl_flogo_rewrite' );
}
