<?php
/**
 * Minimal WordPress stubs so coa-library-page.php can be rendered offline.
 *
 * Test records live here and ONLY here. They are fixtures for the layout and
 * behaviour suite - they are never published, and the live page renders from
 * the empty `op_coa_record` post type. See docs/COA-LIBRARY.md.
 *
 * Set OPL_CL_FIXTURES=0 in the environment to render the real, empty state.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'JSON_UNESCAPED_SLASHES_FALLBACK', 64 );

$GLOBALS['opl_cl_posts'] = array();

if ( getenv( 'OPL_CL_FIXTURES' ) !== '0' ) {
	// Deliberately awkward on purpose: mixed capitalisation, a duplicate batch
	// ID, a forbidden "Verified" status, a record with no document, a record
	// with no batch ID at all, and enough rows to page.
	$GLOBALS['opl_cl_posts'] = array(
		901 => array(
			'title' => 'Fixture A',
			'date'  => 1750000000,
			'meta'  => array(
				'batch_id'           => 'TEST-000001-AAA',
				'product_name'       => 'Fixture Compound Alpha',
				'strength'           => '5 mg',
				'document_status'    => 'Documents Available',
				'report_date'        => '2026-07-22',
				'testing_laboratory' => 'Fixture Analytical Services',
				'test_categories'    => 'Identity, Purity',
				'certificate_url'    => 'https://example.invalid/a',
				'pdf_url'            => 'https://example.invalid/a.pdf',
				'product_category'   => 'Metabolic Research',
				'sku'                => 'FIX-A',
				'internal_note'      => 'SHOULD NEVER RENDER - supplier margin 42%',
				'supplier_email'     => 'SHOULD NEVER RENDER',
			),
		),
		902 => array(
			'title' => 'Fixture B',
			'date'  => 1749000000,
			'meta'  => array(
				'batch'            => 'test-000002-bbb',
				'product'          => 'Fixture Compound Beta',
				'strength'         => '10 mg',
				'status'           => 'Partial Documentation',
				'issue_date'       => '2026-07-19',
				'document_issuer'  => 'Fixture Supplier Co.',
				'view_url'         => 'https://example.invalid/b',
				'category'         => 'Recovery Research',
			),
		),
		903 => array(
			'title' => 'Fixture C',
			'date'  => 1748000000,
			'meta'  => array(
				// A stored status this page must refuse to print.
				'batch_id'         => 'TEST-000003-CCC',
				'product_name'     => 'Fixture Compound Gamma',
				'document_status'  => 'Verified - Passed',
				'report_date'      => '2026-07-15',
				'pdf_url'          => 'https://example.invalid/c.pdf',
				'product_category' => 'Metabolic Research',
			),
		),
		904 => array(
			'title' => 'Fixture D',
			'date'  => 1747000000,
			'meta'  => array(
				// No document at all -> must read Pending, never green.
				'batch_id'         => 'TEST-000004-DDD',
				'product_name'     => 'Fixture Compound Delta',
				'report_date'      => '2026-07-10',
				'product_category' => 'Recovery Research',
			),
		),
		905 => array(
			'title' => 'Fixture E',
			'date'  => 1746000000,
			'meta'  => array(
				'batch_id'         => 'TEST-000005-EEE',
				'product_name'     => 'Fixture Compound Epsilon',
				'document_status'  => 'Archived',
				'report_date'      => '2026-06-30',
				'certificate_url'  => 'https://example.invalid/e',
				'product_category' => 'Metabolic Research',
			),
		),
		906 => array(
			'title' => 'Fixture F',
			'date'  => 1745000000,
			'meta'  => array(
				'batch_id'         => 'TEST-000006-FFF',
				'product_name'     => 'Fixture Compound Zeta',
				'document_status'  => 'Superseded',
				'report_date'      => '2026-06-20',
				'certificate_url'  => 'https://example.invalid/f',
				'product_category' => 'Recovery Research',
			),
		),
		907 => array(
			'title' => 'Fixture G',
			'date'  => 1744000000,
			'meta'  => array(
				'batch_id'         => 'TEST-000007-GGG',
				'product_name'     => 'Fixture Compound Eta',
				'document_status'  => 'Documents Available',
				'report_date'      => '2026-06-10',
				'certificate_url'  => 'https://example.invalid/g',
				'product_category' => 'Metabolic Research',
			),
		),
		908 => array(
			// Duplicate batch ID of fixture A -> multiple-match state.
			'title' => 'Fixture H',
			'date'  => 1743000000,
			'meta'  => array(
				'batch_id'         => 'TEST-000001-AAA',
				'product_name'     => 'Fixture Compound Theta',
				'document_status'  => 'Documents Available',
				'report_date'      => '2026-05-30',
				'certificate_url'  => 'https://example.invalid/h',
				'product_category' => 'Metabolic Research',
			),
		),
		909 => array(
			// No batch ID -> must be dropped entirely.
			'title' => 'Fixture I (unmatched)',
			'date'  => 1742000000,
			'meta'  => array(
				'product_name'    => 'Fixture Compound Iota',
				'document_status' => 'Documents Available',
				'certificate_url' => 'https://example.invalid/i',
			),
		),
	);
}

function get_posts( $args ) {
	return array_keys( $GLOBALS['opl_cl_posts'] );
}

function get_post_meta( $id, $key, $single = false ) {
	$post = isset( $GLOBALS['opl_cl_posts'][ $id ] ) ? $GLOBALS['opl_cl_posts'][ $id ] : null;

	if ( ! $post || ! isset( $post['meta'][ $key ] ) ) {
		return $single ? '' : array();
	}

	return $post['meta'][ $key ];
}

function get_the_title( $id ) {
	return isset( $GLOBALS['opl_cl_posts'][ $id ] ) ? $GLOBALS['opl_cl_posts'][ $id ]['title'] : '';
}

function get_post_time( $format, $gmt, $id ) {
	return isset( $GLOBALS['opl_cl_posts'][ $id ] ) ? $GLOBALS['opl_cl_posts'][ $id ]['date'] : 0;
}

function post_type_exists( $type ) {
	return true;
}

function date_i18n( $format, $stamp ) {
	return gmdate( $format, $stamp );
}

function _n( $single, $plural, $count ) {
	return 1 === (int) $count ? $single : $plural;
}

function esc_html( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' );
}

function esc_js( $v ) {
	return str_replace( array( '\\', "'", '"' ), array( '\\\\', "\\'", '\\"' ), (string) $v );
}

function esc_url( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' );
}

function esc_url_raw( $v ) {
	return (string) $v;
}

function home_url( $path = '/' ) {
	return 'https://www.oligopolypeptides.com' . $path;
}

function rest_url( $path ) {
	return 'https://www.oligopolypeptides.com/wp-json/' . $path;
}

function wp_json_encode( $data, $flags = 0 ) {
	return json_encode( $data, $flags );
}

function wp_unslash( $v ) {
	return $v;
}

function is_page( $id ) {
	return true;
}

function add_shortcode( $tag, $cb ) {}
function add_filter( $tag, $cb, $priority = 10, $args = 1 ) {}
function add_action( $tag, $cb, $priority = 10, $args = 1 ) {}

/** Renders the `large` derivative with a srcset, matching the live behaviour. */
function wp_get_attachment_image( $id, $size, $icon, $attrs ) {
	$base = 'https://www.oligopolypeptides.com/wp-content/uploads/2026/08/';

	return '<img class="' . esc_attr( $attrs['class'] ) . '"'
		. ' src="' . $base . '5113-1024x683.png"'
		. ' srcset="' . $base . '5113-300x200.png 300w, ' . $base . '5113-768x512.png 768w, '
		. $base . '5113-1024x683.png 1024w"'
		. ' sizes="(max-width:1180px) 100vw, 1136px"'
		. ' width="1024" height="683"'
		. ' alt="' . esc_attr( $attrs['alt'] ) . '"'
		. ' loading="' . esc_attr( $attrs['loading'] ) . '"'
		. ' decoding="' . esc_attr( $attrs['decoding'] ) . '">';
}
