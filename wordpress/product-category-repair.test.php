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

	// Named stacks take their own stated area, plus the Stacks shelf.
	3474 => array( 'metabolic-research', 'research-compounds', 'research-products', 'research-stacks' ),
	3477 => array( 'cellular-research', 'research-compounds', 'research-products', 'research-stacks' ),
	3480 => array( 'cognitive-research', 'research-compounds', 'research-products', 'research-stacks' ),
	3483 => array( 'cellular-research', 'longevity-research', 'research-compounds', 'research-products', 'research-stacks' ),

	// Build-your-own spans every area, so no area of its own - but it is a stack.
	3447 => array( 'research-compounds', 'research-products', 'research-stacks' ),
	3450 => array( 'research-compounds', 'research-products', 'research-stacks' ),
	3452 => array( 'research-compounds', 'research-products', 'research-stacks' ),
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

/* The sitewide "Research Stacks" nav link must stop being a dead end. */
ok( 'research-stacks gains the multi-vial products', ( $coverage['research-stacks'] ?? 0 ) === 7,
	'got ' . ( $coverage['research-stacks'] ?? 0 ) );

/* A single-compound kit is a kit, not a stack. */
foreach ( array( 3454, 3457, 3459, 3463, 3465, 3468, 3470, 3472 ) as $id ) {
	ok( "kit $id is not filed as a stack",
		! in_array( 'research-stacks', opl_pcat_plan_for( wc_get_product( $id ) ), true ) );
}

/* --------------------------------------------- deletion sweep protections */

$protected = opl_pcat_protected();

// Everything this migration fills must be un-deletable.
foreach ( array( 'metabolic-research', 'cellular-research', 'longevity-research',
	'cognitive-research', 'research-compounds', 'research-products', 'research-stacks' ) as $slug ) {
	ok( "sweep protects $slug (filled by this migration)", in_array( $slug, $protected, true ) );
}

// The five retired categories serve 410 deliberately; deleting the term would
// downgrade that to an accidental 404.
foreach ( array( 'vitamins', 'longevity', 'performance', 'cognitive-support', 'wellness' ) as $slug ) {
	ok( "sweep protects retired $slug (serves 410)", in_array( $slug, $protected, true ) );
}

// Four are still linked from catalogue cards; deleting them trades an empty
// page for a 404.
foreach ( array( 'growth-hormone-research', 'immune-research', 'recovery-research', 'research-blends-cat' ) as $slug ) {
	ok( "sweep protects linked $slug", in_array( $slug, $protected, true ) );
}

ok( 'sweep protects uncategorized', in_array( 'uncategorized', $protected, true ) );

// Sediment must NOT be protected, or the cleanup does nothing.
foreach ( array( 'gut-health', 'sleep-support', 'daily-foundation', 'starter-packs',
	'research-catalog', 'cellular-longevity-2', 'support-products-support-products' ) as $slug ) {
	ok( "sweep will remove unused $slug", ! in_array( $slug, $protected, true ) );
}

ok( 'protection list has no duplicates', count( $protected ) === count( array_unique( $protected ) ) );
ok( 'protection list covers every slug the plans use', ( function () use ( $protected ) {
	global $CONTAINERS;

	foreach ( array_keys( $CONTAINERS ) as $id ) {
		foreach ( opl_pcat_plan_for( wc_get_product( $id ) ) as $slug ) {
			if ( ! in_array( $slug, $protected, true ) ) {
				return false;
			}
		}
	}

	return true;
} )(), 'a category a product is filed into could be swept' );

echo "\nResulting category sizes:\n";
ksort( $coverage );

foreach ( $coverage as $slug => $n ) {
	echo sprintf( "  %-22s %d\n", $slug, $n );
}

/* ------------------------------------------- the destructive sweep itself */

/**
 * The sweep must never trust `$term->count`. `research-catalog` reports
 * count = 0 on this site yet holds 7 products; deleting it on the strength of
 * that stale zero would lose a category that is in use.
 */
$GLOBALS['opl_terms'] = array(
	// slug => array( term_id, cached count, REAL product count, child count )
	'gut-health'         => array( 801, 0, 0, 0 ),
	'sleep-support'      => array( 802, 0, 0, 0 ),
	'research-catalog'   => array( 594, 0, 7, 0 ),   // stale zero, really in use
	'metabolic-research' => array( 198, 0, 13, 0 ),  // protected AND in use
	'vitamins'           => array( 300, 0, 0, 0 ),   // protected, serves 410
	'parent-with-child'  => array( 900, 0, 0, 2 ),   // must not be orphaned
);

$GLOBALS['opl_deleted'] = array();

function get_terms( $args ) {
	$out = array();

	foreach ( $GLOBALS['opl_terms'] as $slug => $d ) {
		$out[] = (object) array(
			'term_id' => $d[0],
			'slug'    => $slug,
			'name'    => $slug,
			'parent'  => 0,
			'count'   => $d[1],
		);
	}

	return $out;
}

function get_option( $k, $default = false ) {
	return 'default_product_cat' === $k ? 15 : $default; }

/** Reports the REAL product count, which is what the sweep must rely on. */
function get_posts( $args ) {
	$want = (int) $args['tax_query'][0]['terms'];

	foreach ( $GLOBALS['opl_terms'] as $d ) {
		if ( $d[0] === $want ) {
			return $d[2] > 0 ? array( 1 ) : array();
		}
	}

	return array();
}

function get_term_children( $id, $tax ) {
	foreach ( $GLOBALS['opl_terms'] as $d ) {
		if ( $d[0] === $id ) {
			return array_fill( 0, $d[3], 1 );
		}
	}

	return array();
}

function wp_delete_term( $id, $tax ) {
	$GLOBALS['opl_deleted'][] = $id;

	return true;
}

$swept = opl_pcat_sweep_unused();
$ids   = $GLOBALS['opl_deleted'];

ok( 'sweep deletes genuinely unused terms',
	in_array( 801, $ids, true ) && in_array( 802, $ids, true ) );
ok( 'sweep SKIPS a term with a stale zero count but real products',
	! in_array( 594, $ids, true ), 'research-catalog would have been deleted' );
ok( 'sweep skips protected terms in use', ! in_array( 198, $ids, true ) );
ok( 'sweep skips protected terms serving 410', ! in_array( 300, $ids, true ) );
ok( 'sweep never orphans a child term', ! in_array( 900, $ids, true ) );
ok( 'sweep deleted exactly the two it should', 2 === count( $ids ), implode( ',', $ids ) );
ok( 'sweep records enough to recreate each term', ( function () use ( $swept ) {
	foreach ( $swept as $r ) {
		foreach ( array( 'id', 'name', 'slug', 'parent' ) as $k ) {
			if ( ! array_key_exists( $k, $r ) ) {
				return false;
			}
		}
	}

	return count( $swept ) > 0;
} )() );

echo "\n$PASS/" . ( $PASS + count( $FAIL ) ) . " passed\n";

if ( $FAIL ) {
	echo "\nFAILED:\n";

	foreach ( $FAIL as $f ) {
		echo "  - $f\n";
	}

	exit( 1 );
}
