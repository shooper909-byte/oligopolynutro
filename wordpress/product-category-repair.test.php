<?php
/**
 * Tests the category plan for every container, against the real contents read
 * from the live store on 2026-08-23.
 *
 *   php wordpress/product-category-repair.test.php
 *
 * This exercises the planning only. It never writes: `opl_pcat_run()` is bound
 * to `admin_init`, which is not fired here.
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

/* Real compound categories, as reported by the live store. */
$COMPOUND_CATS = array(
	39   => array( 'metabolic-research', 'research-catalog', 'research-compounds' ),
	47   => array( 'metabolic-research', 'research-catalog', 'research-compounds' ),
	12   => array( 'metabolic-research', 'research-catalog', 'research-compounds' ),
	436  => array( 'metabolic-research', 'research-compounds', 'research-catalog' ),
	3395 => array( 'metabolic-research', 'research-compounds', 'research-catalog', 'research-products' ),
	3396 => array( 'metabolic-research', 'research-compounds', 'research-catalog', 'research-products' ),
	3397 => array( 'metabolic-research', 'research-catalog', 'research-compounds', 'research-products' ),
	447  => array( 'cognitive-research', 'research-compounds', 'research-products' ),
	441  => array( 'cellular-research', 'longevity-research', 'research-compounds', 'research-products' ),
	63   => array( 'cellular-research', 'longevity-research', 'research-compounds', 'research-products' ),
);

/* Real container contents. */
$CONTAINERS = array(
	3454 => array( 'Tirzepatide 10 mg - 6 Vial Research Kit', array( 39 ) ),
	3457 => array( 'Semaglutide 5 mg - 6 Vial Research Kit', array( 3397 ) ),
	3459 => array( 'NAD+ 500 mg - 6 Vial Research Kit', array( 63 ) ),
	3463 => array( 'Selank 5 mg - 6 Vial Research Kit', array( 447 ) ),
	3465 => array( 'Retatrutide 5 mg - 6 Vial Research Kit', array( 3395 ) ),
	3468 => array( 'GHK-Cu 50 mg - 6 Vial Research Kit', array( 441 ) ),
	3470 => array( 'Retatrutide 20 mg - 6 Vial Research Kit', array( 3396 ) ),
	3472 => array( 'Cagrilintide 5 mg - 6 Vial Research Kit', array( 436 ) ),
	3474 => array( 'Metabolic Pathways Stack', array( 39, 63, 436, 441, 447, 3395 ) ),
	3477 => array( 'Cellular Energy Stack', array( 63, 436, 441, 447 ) ),
	3480 => array( 'Neurocognitive Pathways Stack', array( 63, 441, 447 ) ),
	3483 => array( 'Regenerative Biology Stack', array( 63, 441, 447 ) ),
	3447 => array( 'Build Your Research Bundle - 3 Vials', array( 39, 63, 436, 441, 447, 3395, 3396, 3397 ) ),
	3450 => array( 'Build Your Research Bundle - 6 Vials', array( 39, 63, 436, 441, 447, 3395, 3396, 3397 ) ),
	3452 => array( 'Build Your Research Bundle - 9 Vials', array( 39, 63, 436, 441, 447, 3395, 3396, 3397 ) ),
);

class OPL_PC_Child {
	public $id;

	public function __construct( $id ) {
		$this->id = $id; }

	public function get_product() {
		return wc_get_product( $this->id ); }
}

class OPL_PC_Product {
	public $id;
	public $name;
	public $kids;

	public function __construct( $id, $name, $kids ) {
		$this->id   = $id;
		$this->name = $name;
		$this->kids = $kids;
	}

	public function get_id() {
		return $this->id; }

	public function get_name() {
		return $this->name; }

	public function get_child_items() {
		return array_map(
			function ( $k ) {
				return new OPL_PC_Child( $k );
			},
			$this->kids
		);
	}
}

function wc_get_product( $id ) {
	global $CONTAINERS;

	if ( isset( $CONTAINERS[ $id ] ) ) {
		return new OPL_PC_Product( $id, $CONTAINERS[ $id ][0], $CONTAINERS[ $id ][1] );
	}

	return new OPL_PC_Product( $id, 'Compound ' . $id, array() );
}

function wp_get_object_terms( $id, $tax, $args = array() ) {
	global $COMPOUND_CATS;

	return isset( $COMPOUND_CATS[ $id ] ) ? $COMPOUND_CATS[ $id ] : array();
}

function is_wp_error( $t ) {
	return false; }

function add_action( $a, $b, $c = 10, $d = 1 ) {}

require __DIR__ . '/product-category-repair.php';

/* --------------------------------------------------------------- expected */

$EXPECTED = array(
	// Kits inherit from the single compound inside them.
	3454 => array( 'metabolic-research', 'research-compounds', 'research-products' ),
	3457 => array( 'metabolic-research', 'research-compounds', 'research-products' ),
	3459 => array( 'cellular-research', 'longevity-research', 'research-compounds', 'research-products' ),
	3463 => array( 'cognitive-research', 'research-compounds', 'research-products' ),
	3465 => array( 'metabolic-research', 'research-compounds', 'research-products' ),
	3468 => array( 'cellular-research', 'longevity-research', 'research-compounds', 'research-products' ),
	3470 => array( 'metabolic-research', 'research-compounds', 'research-products' ),
	3472 => array( 'metabolic-research', 'research-compounds', 'research-products' ),

	// Named stacks take their own stated area.
	3474 => array( 'metabolic-research', 'research-compounds', 'research-products' ),
	3477 => array( 'cellular-research', 'research-compounds', 'research-products' ),
	3480 => array( 'cognitive-research', 'research-compounds', 'research-products' ),
	3483 => array( 'cellular-research', 'longevity-research', 'research-compounds', 'research-products' ),

	// Build-your-own spans everything: umbrella only.
	3447 => array( 'research-compounds', 'research-products' ),
	3450 => array( 'research-compounds', 'research-products' ),
	3452 => array( 'research-compounds', 'research-products' ),
);

foreach ( $EXPECTED as $id => $want ) {
	$got = opl_pcat_plan_for( wc_get_product( $id ) );
	sort( $got );
	sort( $want );
	ok(
		$id . ' ' . $CONTAINERS[ $id ][0],
		$got === $want,
		'got ' . implode( ',', $got )
	);
}

/* The two same-contents stacks must NOT get the same categories. */
$neuro = opl_pcat_plan_for( wc_get_product( 3480 ) );
$regen = opl_pcat_plan_for( wc_get_product( 3483 ) );
ok( 'stacks with identical contents are told apart by name', $neuro !== $regen,
	'both got ' . implode( ',', $neuro ) );
ok( 'neurocognitive stack is not filed under metabolic',
	! in_array( 'metabolic-research', $neuro, true ) );

/* No plan may ever be empty - that would leave a product uncategorised. */
foreach ( array_keys( $CONTAINERS ) as $id ) {
	$plan = opl_pcat_plan_for( wc_get_product( $id ) );
	ok( "plan for $id is non-empty", ! empty( $plan ) );
	ok( "plan for $id carries both umbrella categories",
		in_array( 'research-compounds', $plan, true ) && in_array( 'research-products', $plan, true ) );
}

/* Every category page that currently shows nothing must gain products. */
$coverage = array();

foreach ( array_keys( $CONTAINERS ) as $id ) {
	foreach ( opl_pcat_plan_for( wc_get_product( $id ) ) as $slug ) {
		$coverage[ $slug ] = ( $coverage[ $slug ] ?? 0 ) + 1;
	}
}

foreach ( array( 'metabolic-research', 'cellular-research', 'longevity-research', 'cognitive-research' ) as $slug ) {
	ok( "category $slug gains sellable products", ! empty( $coverage[ $slug ] ), 'still empty' );
}

echo "\nResulting category sizes:\n";
ksort( $coverage );

foreach ( $coverage as $slug => $n ) {
	echo sprintf( "  %-22s %d\n", $slug, $n );
}

echo "\n$PASS/" . ( $PASS + count( $FAIL ) ) . " passed\n";

if ( $FAIL ) {
	echo "\nFAILED:\n";

	foreach ( $FAIL as $f ) {
		echo "  - $f\n";
	}

	exit( 1 );
}
