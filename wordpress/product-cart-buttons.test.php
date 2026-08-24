<?php
/**
 * Runs opl_pcb_rewrite() over real captured HTML from the live site.
 *
 *   php wordpress/product-cart-buttons.test.php <homepage.html> <catalog.html>
 *
 * Product data below mirrors what the live store reports, including the
 * "not sold separately" flag on individual compounds and the container sizes
 * that decide whether a kit has one valid selection or several.
 */

define( 'ABSPATH', __DIR__ . '/' );

$PASS = 0;
$FAIL = array();

function ok( $name, $cond, $detail = '' ) {
	global $PASS, $FAIL;

	if ( $cond ) {
		$PASS++;
		return;
	}

	$FAIL[] = $name . ( $detail ? ' -> ' . $detail : '' );
}

/* ---------------------------------------------------------------- fixtures */

/**
 * id => [name, slug, type, in_stock, price, bundled_only, min, max, children]
 * children: child_id => max quantity that child may contribute
 */
$PRODUCTS = array(
	// Individual compounds - real prices, but not sold separately.
	39   => array( 'Tirzepatide 10mg Research Peptide', 'tirzepatide-10mg-research-peptide', 'simple', true, '74.99', true, 0, 0, array() ),
	447  => array( 'Selank 5 mg', 'selank-5mg-research-peptide', 'simple', true, '79.99', true, 0, 0, array() ),
	3395 => array( 'Retatrutide 5 mg', 'retatrutide-5mg-research-peptide', 'simple', true, '94.99', true, 0, 0, array() ),
	63   => array( 'NAD+ 500mg Research Peptide', 'nad-500mg-research-compound', 'simple', true, '64.99', true, 0, 0, array() ),

	436  => array( 'Cagrilintide 5mg Research Peptide', 'cagrilintide-5mg-research-peptide', 'simple', true, '84.99', true, 0, 0, array() ),
	441  => array( 'GHK-Cu 50mg Research Peptide', 'ghk-cu-50mg-research-peptide', 'simple', true, '69.99', true, 0, 0, array() ),
	3397 => array( 'Semaglutide 5 mg', 'semaglutide-5mg-research-peptide', 'simple', true, '75.99', true, 0, 0, array() ),

	// A curated stack also appears in the homepage grid.
	3480 => array( 'Neurocognitive Pathways Stack', 'neurocognitive-pathways-stack', 'mix-and-match', true, '399.94', false, 6, 6, array( 447 => 6, 63 => 6, 441 => 6, 39 => 6, 436 => 6, 3395 => 6 ) ),

	// Single-compound kits: one child, min == max == 6 -> exactly one selection.
	3454 => array( 'Tirzepatide 10 mg - 6 Vial Research Kit', 'tirzepatide-10-mg-6-vial-research-kit', 'mix-and-match', true, '413.94', false, 6, 6, array( 39 => 6 ) ),
	3457 => array( 'Semaglutide 5 mg - 6 Vial Research Kit', 'semaglutide-5-mg-6-vial-research-kit', 'mix-and-match', true, '419.94', false, 6, 6, array( 3397 => 6 ) ),
	3463 => array( 'Selank 5 mg - 6 Vial Research Kit', 'selank-5-mg-6-vial-research-kit', 'mix-and-match', true, '441.54', false, 6, 6, array( 447 => 6 ) ),
	3459 => array( 'NAD+ 500 mg - 6 Vial Research Kit', 'nad-500-mg-6-vial-research-kit', 'mix-and-match', true, '359.94', false, 6, 6, array( 63 => 6 ) ),
	3465 => array( 'Retatrutide 5 mg - 6 Vial Research Kit', 'retatrutide-5-mg-6-vial-research-kit', 'mix-and-match', true, '524.94', false, 6, 6, array( 3395 => 6 ) ),
	3468 => array( 'GHK-Cu 50 mg - 6 Vial Research Kit', 'ghk-cu-50-mg-6-vial-research-kit', 'mix-and-match', true, '386.94', false, 6, 6, array( 441 => 6 ) ),
	3472 => array( 'Cagrilintide 5 mg - 6 Vial Research Kit', 'cagrilintide-5-mg-6-vial-research-kit', 'mix-and-match', true, '469.94', false, 6, 6, array( 436 => 6 ) ),

	// Curated stack: six children, each may contribute up to 6 -> many selections.
	3474 => array( 'Metabolic Pathways Stack', 'metabolic-pathways-stack', 'mix-and-match', true, '413.94', false, 6, 6, array( 3395 => 6, 39 => 6, 436 => 6, 63 => 6, 447 => 6, 441 => 6 ) ),

	// Build your own: eight children -> many selections.
	3447 => array( 'Build Your Research Bundle - 3 Vials', 'build-your-research-bundle-3-vials', 'mix-and-match', true, '294.47', false, 3, 3, array( 39 => 3, 436 => 3, 63 => 3, 441 => 3, 3397 => 3, 447 => 3, 3395 => 3, 3396 => 3 ) ),
	3452 => array( 'Build Your Research Bundle - 9 Vials', 'build-your-research-bundle-9-vials', 'mix-and-match', true, '820.00', false, 9, 9, array( 39 => 9, 436 => 9, 63 => 9, 441 => 9, 3397 => 9, 447 => 9, 3395 => 9, 3396 => 9 ) ),

	// A kit whose only child is out of stock.
	3470 => array( 'Retatrutide 20 mg - 6 Vial Research Kit', 'retatrutide-20-mg-6-vial-research-kit', 'mix-and-match', true, '700.00', false, 6, 6, array( 3396 => 6 ) ),
	3396 => array( 'Retatrutide 20 mg', 'retatrutide-20-mg', 'simple', false, '123.49', true, 0, 0, array() ),
);

/* ------------------------------------------------------------ WooCommerce  */

class OPL_Test_Child {
	public $id;
	public $max;

	public function __construct( $id, $max ) {
		$this->id  = $id;
		$this->max = $max;
	}

	public function get_product() {
		return wc_get_product( $this->id );
	}

	public function get_quantity( $which = 'min' ) {
		return 'max' === $which ? $this->max : 1;
	}
}

class OPL_Test_Product {
	private $id;
	private $d;

	public function __construct( $id, $d ) {
		$this->id = $id;
		$this->d  = $d;
	}

	public function get_id() {
		return $this->id; }

	public function get_name() {
		return $this->d[0]; }

	public function get_permalink() {
		return 'https://www.oligopolypeptides.com/products/' . $this->d[1] . '/'; }

	public function is_type( $t ) {
		return $t === $this->d[2]; }

	public function is_in_stock() {
		return (bool) $this->d[3]; }

	public function get_price() {
		return $this->d[4]; }

	public function is_not_sold_individually() {
		return (bool) $this->d[5]; }

	public function get_min_container_size() {
		return $this->d[6]; }

	public function get_max_container_size() {
		return $this->d[7]; }

	/** Mirrors WooCommerce: a not-sold-separately product is not purchasable. */
	public function is_purchasable() {
		if ( $this->d[5] ) {
			return false;
		}

		return '' !== (string) $this->d[4];
	}

	public function get_child_items() {
		if ( 'mix-and-match' !== $this->d[2] ) {
			return array();
		}

		$out = array();

		foreach ( $this->d[8] as $cid => $max ) {
			$out[] = new OPL_Test_Child( $cid, $max );
		}

		return $out;
	}
}

function wc_get_product( $id ) {
	global $PRODUCTS;

	return isset( $PRODUCTS[ $id ] ) ? new OPL_Test_Product( $id, $PRODUCTS[ $id ] ) : null;
}

function url_to_postid( $url ) {
	global $PRODUCTS;

	if ( ! preg_match( '#/products/([^/]+)/#', $url, $m ) ) {
		return 0;
	}

	foreach ( $PRODUCTS as $id => $d ) {
		if ( $d[1] === $m[1] ) {
			return $id;
		}
	}

	return 0;
}

function get_post_status( $id ) {
	return 'publish'; }

function get_post_meta( $id, $key, $single = false ) {
	return ''; }

function esc_html( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }

function esc_attr( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }

function esc_url( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }

function is_admin() {
	return false; }

function is_feed() {
	return false; }

function is_robots() {
	return false; }

function add_action( $a, $b, $c = 10, $d = 1 ) {}

function wc_price( $p ) {
	return '<span class="amount">$' . number_format( (float) $p, 2 ) . '</span>'; }

function wp_strip_all_tags( $s ) {
	return trim( strip_tags( (string) $s ) ); }

define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['opl_transients'] = array();

function get_transient( $k ) {
	return isset( $GLOBALS['opl_transients'][ $k ] ) ? $GLOBALS['opl_transients'][ $k ] : false;
}

function set_transient( $k, $v, $t = 0 ) {
	$GLOBALS['opl_transients'][ $k ] = $v;
}

/** Returns every mix-and-match container in the fixture set. */
function get_posts( $args ) {
	global $PRODUCTS;
	$out = array();

	foreach ( $PRODUCTS as $id => $d ) {
		if ( 'mix-and-match' === $d[2] ) {
			$out[] = $id;
		}
	}

	return $out;
}

require __DIR__ . '/product-cart-buttons.php';

/* ------------------------------------------------------------------ tests  */

echo "Unit: control selection\n";

$cases = array(
	array( 39, 'add', 'compound sells its dedicated kit' ),
	array( 447, 'add', 'compound sells its dedicated kit' ),
	array( 3454, 'add', 'single-child kit, one valid selection' ),
	array( 3463, 'add', 'single-child kit, one valid selection' ),
	array( 3474, 'configure', 'curated stack, several selections' ),
	array( 3447, 'configure', 'build-your-own 3, several selections' ),
	array( 3452, 'configure', 'build-your-own 9, several selections' ),
	array( 3470, 'configure', 'kit whose child is out of stock' ),
);

foreach ( $cases as $c ) {
	list( $id, $want, $why ) = $c;
	$control = opl_pcb_control( wc_get_product( $id ) );
	ok( "product $id -> $want ($why)", $control && $control['kind'] === $want, $control ? $control['kind'] : 'null' );
}

// The single-child kit must post the exact quantity the container requires.
$form = opl_pcb_control( wc_get_product( 3454 ) )['html'];
ok( 'kit form posts add-to-cart=3454', false !== strpos( $form, 'name="add-to-cart" value="3454"' ) );
ok( 'kit form posts mnm_quantity[39]=6', false !== strpos( $form, 'name="mnm_quantity[39]" value="6"' ) );
ok( 'kit form targets the product permalink', false !== strpos( $form, 'action="https://www.oligopolypeptides.com/products/tirzepatide-10-mg-6-vial-research-kit/"' ) );
ok( 'kit form is a real POST', false !== strpos( $form, 'method="post"' ) );
ok( 'kit form needs no JavaScript', false === stripos( $form, 'onclick' ) && false === stripos( $form, '<script' ) );

// With kit-featuring OFF, a compound card sells its dedicated kit and must SAY
// so in the button. (With it ON the card itself becomes the kit - covered in
// the integration block below.)
$bundled = opl_pcb_control( wc_get_product( 39 ) )['html'];
ok( 'compound card posts the KIT, not the compound',
	false !== strpos( $bundled, 'name="add-to-cart" value="3454"' ), $bundled );
ok( 'compound card never posts the unbuyable compound',
	false === strpos( $bundled, 'name="add-to-cart" value="39"' ) );
ok( 'button states the kit size', false !== strpos( $bundled, '6-Vial Kit' ), $bundled );
ok( 'button states the price the customer will pay',
	false !== strpos( $bundled, '413.94' ), $bundled );
ok( 'button does NOT show the compound price', false === strpos( $bundled, '74.99' ) );
ok( 'form targets the kit permalink',
	false !== strpos( $bundled, 'action="https://www.oligopolypeptides.com/products/tirzepatide-10-mg-6-vial-research-kit/"' ) );
ok( 'form carries the kit child quantity',
	false !== strpos( $bundled, 'name="mnm_quantity[39]" value="6"' ) );

// Selank's kit is 3463. Both compounds also appear inside stacks, so this also
// proves a one-child container is preferred over a multi-child one.
$selank = opl_pcb_control( wc_get_product( 447 ) )['html'];
ok( 'one-child kit preferred over a stack containing the same compound',
	false !== strpos( $selank, 'value="3463"' ), $selank );
ok( 'selank button states its own kit price', false !== strpos( $selank, '441.54' ) );

// A compound with NO dedicated kit still falls back to the explanatory link.
$GLOBALS['opl_transients'] = array();
$orphan_ctrl = opl_pcb_control( wc_get_product( 3396 ) );
ok( 'compound whose kit is unusable does not render a cart form',
	'add' !== $orphan_ctrl['kind'] || false === strpos( $orphan_ctrl['html'], 'value="3470"' ),
	$orphan_ctrl['kind'] );
$GLOBALS['opl_transients'] = array();

// A compound with no kit at all must still produce a working link.
$GLOBALS['opl_transients'] = array();
$orphan = opl_pcb_control( wc_get_product( 3396 ) )['html'];
ok( 'compound whose only kit is unavailable falls back to the catalogue',
	false !== strpos( $orphan, 'research-catalog' ) || false !== strpos( $orphan, '/products/' ), $orphan );
$GLOBALS['opl_transients'] = array();

// Nothing anywhere may claim a disabled cart button.
foreach ( array( 39, 447, 3474, 3447, 3470 ) as $id ) {
	$h = opl_pcb_control( wc_get_product( $id ) )['html'];
	ok( "product $id has no disabled control", false === strpos( $h, 'disabled' ), $h );
}

echo "\nIntegration: rewrite real captured pages\n";

$home    = isset( $argv[1] ) && is_readable( $argv[1] ) ? file_get_contents( $argv[1] ) : '';
$catalog = isset( $argv[2] ) && is_readable( $argv[2] ) ? file_get_contents( $argv[2] ) : '';

if ( $catalog ) {
	// Count the markup only. 'oprc-card-actions' also appears in stylesheets,
	// including the one this file injects, so a bare substring count moves for
	// reasons that have nothing to do with the cards.
	$before = substr_count( $catalog, '<div class="oprc-card-actions">' );
	$out    = opl_pcb_rewrite( $catalog );

	ok( 'catalog: css injected once', 1 === substr_count( $out, 'id="opl-pcb-css"' ) );
	ok( 'catalog: no action row created or destroyed',
		substr_count( $out, '<div class="oprc-card-actions">' ) === $before && $before > 0,
		'before ' . $before . ' after ' . substr_count( $out, '<div class="oprc-card-actions">' ) );

	$controls = substr_count( $out, 'class="opl-pcb-btn' ) + substr_count( $out, 'opl-pcb-btn opl-pcb-' );
	ok( 'catalog: controls added', $controls > 0, "added $controls" );
	ok( 'catalog: View Product links preserved',
		substr_count( $out, 'oprc-card-cta' ) === substr_count( $catalog, 'oprc-card-cta' ) );
	ok( 'catalog: idempotent', opl_pcb_rewrite( $out ) === $out );
	ok( 'catalog: marker emitted', false !== strpos( $out, '<!-- opl-pcb controls=' ) );

	preg_match( '/<!-- opl-pcb controls=(\d+) -->/', $out, $m );
	echo "  catalog controls: {$m[1]}\n";

	// A control must sit inside the card whose product it refers to.
	preg_match_all( '#<div class="oprc-card-actions">(.*?)</div>#s', $out, $rows );
	$mismatch = 0;

	foreach ( $rows[1] as $row ) {
		if ( preg_match( '#add-to-cart" value="(\d+)"#', $row, $a )
			&& preg_match( '#oprc-card-cta" href="([^"]+)"#', $row, $b ) ) {
			if ( url_to_postid( html_entity_decode( $b[1] ) ) !== (int) $a[1] ) {
				$mismatch++;
			}
		}
	}

	ok( 'catalog: no control attached to the wrong product', 0 === $mismatch, "$mismatch mismatched" );
}

if ( $home ) {
	$out = opl_pcb_rewrite( $home );

	ok( 'home: css injected once', 1 === substr_count( $out, 'id="opl-pcb-css"' ) );
	ok( 'home: View Product links preserved',
		substr_count( $out, 'op9-product-link' ) === substr_count( $home, 'op9-product-link' ) );
	ok( 'home: idempotent', opl_pcb_rewrite( $out ) === $out );

	// The homepage now sells kits from compound cards.
	$forms = substr_count( $out, 'class="opl-pcb-form"' );
	ok( 'home: compound cards now carry a cart form', $forms > 0, "forms: $forms" );

	ok( 'home: every home form posts a KIT id, never a bare compound',
		( function () use ( $out ) {
			preg_match_all( '#name="add-to-cart" value="(\d+)"#', $out, $m );
			$compounds = array( 39, 447, 3395, 3396, 3397, 63, 436, 441 );

			foreach ( $m[1] as $id ) {
				if ( in_array( (int) $id, $compounds, true ) ) {
					return false;
				}
			}

			return count( $m[1] ) > 0;
		} )(), 'a compound id was posted' );


	// With kit-featuring on the card IS the kit, so no per-vial suffix is
	// needed - the price shown is the price charged.
	// Look for the rendered element, not the class name - the stylesheet also
	// contains `.opl-pcb-unit`, which a bare substring search matches.
	ok( 'home: no per-vial suffix when the card is already the kit',
		false === strpos( $out, '<span class="opl-pcb-unit">' ) );

	// The grid must now advertise kits, not unbuyable compounds.
	ok( 'home: headings name kits', false !== strpos( $out, '6 Vial Research Kit' )
		|| false !== strpos( $out, '6-Vial Research Kit' ), 'no kit heading' );

	ok( 'home: no card still links to a bare compound',
		( function () use ( $out ) {
			preg_match_all( '#<a class="op9-product-media" href="([^"]+)"#', $out, $m );

			foreach ( $m[1] as $u ) {
				$id = url_to_postid( html_entity_decode( $u ) );

				if ( $id && in_array( $id, array( 39, 447, 3395, 3396, 3397, 63, 436, 441 ), true ) ) {
					return false;
				}
			}

			return true;
		} )(), 'a compound card survived' );

	ok( 'home: card price is the kit price, not the compound price',
		false !== strpos( $out, '413.94' ) && false === strpos( $out, '74.99' ),
		'compound price still shown' );

	ok( 'home: every link inside a swapped card agrees',
		( function () use ( $out ) {
			preg_match_all( '#<article class="op9-product-card">.*?</article>#s', $out, $cards );

			foreach ( $cards[0] as $c ) {
				preg_match_all( '#href="(https://[^"]*/products/[^"]+)"#', $c, $h );
				if ( count( array_unique( $h[1] ) ) > 1 ) {
					return false;
				}
			}

			return true;
		} )(), 'a card mixed two products' );

	ok( 'home: swapped cards carry a plain Add to Cart, not a kit-swap label',
		false === strpos( $out, '6-Vial Kit &middot;' ) && false === strpos( $out, '6-Vial Kit ·' ) );

	ok( 'home: the fact bullets survive the swap',
		substr_count( $out, 'opl-facts' ) === substr_count( $home, 'opl-facts' ) );

	ok( 'home: card count unchanged',
		substr_count( $out, '<article class="op9-product-card">' )
		=== substr_count( $home, '<article class="op9-product-card">' ) );

	ok( 'home: the curated stack still gets a configure link',
		false !== strpos( $out, 'opl-pcb-configure' ) );

	// The malformed product link must become a real permalink.
	ok( 'home: raw ?post_type=product link rewritten',
		false === strpos( $out, 'post_type=product' ),
		'raw link survived' );

	// The malformed link resolved to Selank, whose card is then retargeted to
	// the Selank kit - so the kit URL is what should survive.
	ok( 'home: repaired link ends up on the right kit',
		false !== strpos( $out, '/products/selank-5-mg-6-vial-research-kit/' ),
		'kit permalink missing' );

	// A raw link to something that is not a published product stays untouched.
	$fake = '<a href="https://x.test/?post_type=product&#038;p=99999">x</a>';
	ok( 'unknown product id left alone',
		opl_pcb_fix_raw_product_links( $fake ) === $fake );

	preg_match( '/<!-- opl-pcb controls=(\d+) -->/', $out, $m );
	echo '  home controls: ' . ( $m[1] ?? 0 ) . "\n";
}

echo "\n$PASS/" . ( $PASS + count( $FAIL ) ) . " passed\n";

if ( $FAIL ) {
	echo "\nFAILED:\n";

	foreach ( $FAIL as $f ) {
		echo "  - $f\n";
	}

	exit( 1 );
}
