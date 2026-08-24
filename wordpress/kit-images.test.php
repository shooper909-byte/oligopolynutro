<?php
/**
 * Tests the kit-image migration against the real product graph read from the
 * live store on 2026-08-23.
 *
 *   php wordpress/kit-images.test.php
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

/* id => [name, children[], own thumbnail id (0 = none)] — real store shape. */
$P = array(
	// Compounds, all with images.
	39   => array( 'Tirzepatide 10mg Research Peptide', array(), 9039 ),
	3397 => array( 'Semaglutide 5 mg', array(), 9397 ),
	63   => array( 'NAD+ 500mg Research Peptide', array(), 9063 ),
	447  => array( 'Selank 5 mg', array(), 9447 ),
	3395 => array( 'Retatrutide 5 mg', array(), 9395 ),
	441  => array( 'GHK-Cu 50mg Research Peptide', array(), 9441 ),
	3396 => array( 'Retatrutide 20 mg', array(), 9396 ),
	436  => array( 'Cagrilintide 5mg Research Peptide', array(), 9436 ),

	// The 8 single-compound kits, none with an image.
	3454 => array( 'Tirzepatide 10 mg - 6 Vial Research Kit', array( 39 ), 0 ),
	3457 => array( 'Semaglutide 5 mg - 6 Vial Research Kit', array( 3397 ), 0 ),
	3459 => array( 'NAD+ 500 mg - 6 Vial Research Kit', array( 63 ), 0 ),
	3463 => array( 'Selank 5 mg - 6 Vial Research Kit', array( 447 ), 0 ),
	3465 => array( 'Retatrutide 5 mg - 6 Vial Research Kit', array( 3395 ), 0 ),
	3468 => array( 'GHK-Cu 50 mg - 6 Vial Research Kit', array( 441 ), 0 ),
	3470 => array( 'Retatrutide 20 mg - 6 Vial Research Kit', array( 3396 ), 0 ),
	3472 => array( 'Cagrilintide 5 mg - 6 Vial Research Kit', array( 436 ), 0 ),

	// Build-your-own: 8 children, no single representative image.
	3447 => array( 'Build Your Research Bundle - 3 Vials', array( 39, 63, 436, 441, 447, 3395, 3396, 3397 ), 0 ),

	// Curated stacks already have their own images - must not be touched.
	3474 => array( 'Metabolic Pathways Stack', array( 39, 63, 436 ), 9474 ),

	// A kit whose compound has no image either.
	3490 => array( 'Orphan Kit', array( 8888 ), 0 ),
	8888 => array( 'Compound With No Image', array(), 0 ),
);

$GLOBALS['thumbs'] = array();

foreach ( $P as $id => $d ) {
	$GLOBALS['thumbs'][ $id ] = $d[2];
}

class OPL_KI_Child {
	public $id;

	public function __construct( $id ) {
		$this->id = $id; }

	public function get_product() {
		return wc_get_product( $this->id ); }
}

class OPL_KI_Product {
	public $id;

	public function __construct( $id ) {
		$this->id = $id; }

	public function get_id() {
		return $this->id; }

	public function get_name() {
		global $P;

		return $P[ $this->id ][0]; }

	public function get_child_items() {
		global $P;

		return array_map(
			function ( $c ) {
				return new OPL_KI_Child( $c );
			},
			$P[ $this->id ][1]
		);
	}
}

function wc_get_product( $id ) {
	global $P;

	return isset( $P[ $id ] ) ? new OPL_KI_Product( $id ) : null;
}

function get_post_thumbnail_id( $id ) {
	return isset( $GLOBALS['thumbs'][ $id ] ) ? $GLOBALS['thumbs'][ $id ] : 0; }

function set_post_thumbnail( $id, $thumb ) {
	$GLOBALS['thumbs'][ $id ] = $thumb; }

function get_posts( $args ) {
	global $P;
	$out = array();

	foreach ( $P as $id => $d ) {
		if ( ! empty( $d[1] ) ) {
			$out[] = $id;
		}
	}

	return $out;
}

$GLOBALS['opts'] = array();

function get_option( $k, $default = false ) {
	return isset( $GLOBALS['opts'][ $k ] ) ? $GLOBALS['opts'][ $k ] : $default; }

function update_option( $k, $v, $a = true ) {
	$GLOBALS['opts'][ $k ] = $v; }

function current_user_can( $c ) {
	return true; }

function add_action( $a, $b, $c = 10, $d = 1 ) {}

require __DIR__ . '/kit-images.php';

/* ------------------------------------------------------------------ run  */

opl_kimg_run();

$log     = $GLOBALS['opts']['opl_kimg_log'];
$skipped = $GLOBALS['opts']['opl_kimg_skipped'];

/* Every single-compound kit gets its compound's image. */
$expected = array(
	3454 => 9039,
	3457 => 9397,
	3459 => 9063,
	3463 => 9447,
	3465 => 9395,
	3468 => 9441,
	3470 => 9396,
	3472 => 9436,
);

foreach ( $expected as $kit => $thumb ) {
	ok( "kit $kit gets its compound's image",
		$GLOBALS['thumbs'][ $kit ] === $thumb,
		'got ' . $GLOBALS['thumbs'][ $kit ] );
	ok( "kit $kit is logged with its source", isset( $log[ $kit ]['from_product'] ) );
}

ok( 'all 8 kits fixed', 8 === count( $log ), count( $log ) . ' logged' );

/* Must never touch a product that already has an image. */
ok( 'curated stack image untouched', 9474 === $GLOBALS['thumbs'][3474] );
ok( 'stack not in the log', ! isset( $log[3474] ) );

/* Must never guess for a multi-compound bundle. */
ok( 'build-your-own bundle left alone', 0 === $GLOBALS['thumbs'][3447] );
ok( 'bundle recorded as skipped, with a reason',
	isset( $skipped[3447] ) && false !== strpos( $skipped[3447], 'single-compound' ) );

/* A kit whose compound has no image must be skipped, not given a 0 thumbnail. */
ok( 'orphan kit left alone', 0 === $GLOBALS['thumbs'][3490] );
ok( 'orphan kit recorded with a reason',
	isset( $skipped[3490] ) && false !== strpos( $skipped[3490], 'no image' ) );

/* Compounds themselves are never modified. */
foreach ( array( 39, 3397, 63, 447, 3395, 441, 3396, 436 ) as $c ) {
	ok( "compound $c untouched", $GLOBALS['thumbs'][ $c ] === $P[ $c ][2] );
}

/* Idempotent: a second run changes nothing. */
$before = $GLOBALS['thumbs'];
$GLOBALS['opts']['opl_kimg_done'] = '';
opl_kimg_run();
ok( 're-running changes nothing', $GLOBALS['thumbs'] === $before );

/* Guard actually stops it. */
$GLOBALS['opts']['opl_kimg_done'] = '2026-08-23';
$GLOBALS['thumbs'][3454] = 0;
opl_kimg_run();
ok( 'guard prevents a re-run', 0 === $GLOBALS['thumbs'][3454] );

echo "\n$PASS/" . ( $PASS + count( $FAIL ) ) . " passed\n";

if ( $FAIL ) {
	echo "\nFAILED:\n";

	foreach ( $FAIL as $f ) {
		echo "  - $f\n";
	}

	exit( 1 );
}
