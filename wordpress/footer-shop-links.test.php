<?php
/**
 * Runs opl_fsl_rewrite() over the real footer markup captured from the live
 * site, plus the failure modes that matter.
 *
 *   php wordpress/footer-shop-links.test.php <captured-page.html>
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );

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

/* Which categories hold products. Flipped per scenario. */
$GLOBALS['opl_stock'] = array(
	'metabolic-research' => 6,
	'cellular-research'  => 4,
	'longevity-research' => 3,
	'cognitive-research' => 2,
	'research-stacks'    => 7,
);

$GLOBALS['opl_cache'] = array();

function get_transient( $k ) {
	return isset( $GLOBALS['opl_cache'][ $k ] ) ? $GLOBALS['opl_cache'][ $k ] : false; }

function set_transient( $k, $v, $t = 0 ) {
	$GLOBALS['opl_cache'][ $k ] = $v; }

function delete_transient( $k ) {
	unset( $GLOBALS['opl_cache'][ $k ] ); }

function get_term_by( $field, $slug, $tax ) {
	if ( ! array_key_exists( $slug, $GLOBALS['opl_stock'] ) ) {
		return false;
	}

	return (object) array(
		'term_id' => crc32( $slug ) % 1000,
		'slug'    => $slug,
	);
}

function get_posts( $args ) {
	foreach ( $GLOBALS['opl_stock'] as $slug => $n ) {
		if ( ( crc32( $slug ) % 1000 ) === $args['tax_query'][0]['terms'] ) {
			return $n > 0 ? array( 1 ) : array();
		}
	}

	return array();
}

function get_term_link( $term ) {
	return 'https://www.oligopolypeptides.com/product-category/' . $term->slug . '/'; }

function is_wp_error( $t ) {
	return false; }

function esc_url( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }

function esc_html( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }

function is_admin() {
	return false; }

function is_feed() {
	return false; }

function is_robots() {
	return false; }

function add_action( $a, $b, $c = 10, $d = 1 ) {}

require __DIR__ . '/footer-shop-links.php';

/* The exact Shop column as captured from the live footer. */
$FOOTER = '<nav aria-label="Shop footer links"><h3>Shop</h3>'
	. '<a href="/product-category/vitamins/">Vitamins</a>'
	. '<a href="/product-category/longevity/">Longevity</a>'
	. '<a href="/product-category/performance/">Performance</a>'
	. '<a href="/product-category/cognitive-support/">Cognitive Support</a>'
	. '<a href="/product-category/wellness/">Wellness</a></nav>';

$PAGE = '<html><body><footer class="op-footer opl-footer-v2"><div>'
	. '<img class="opl-footer-v2-logo" src="/logo.webp" alt="OligoPoly">'
	. $FOOTER
	. '<nav aria-label="Research footer links"><h3>Research</h3>'
	. '<a href="/research-catalog/">Research Catalog</a></nav>'
	. '<nav aria-label="Company footer links"><h3>Company</h3>'
	. '<a href="/about/">About</a></nav>'
	. '</div></footer></body></html>';

/* ------------------------------------------------------------ happy path  */

$out = opl_fsl_rewrite( $PAGE );

$dead = array( 'vitamins', 'longevity/', 'performance', 'cognitive-support', 'wellness' );

foreach ( $dead as $slug ) {
	ok( "410 link removed: $slug", false === strpos( $out, '/product-category/' . $slug ) );
}

foreach ( array( 'metabolic-research', 'cellular-research', 'longevity-research', 'cognitive-research', 'research-stacks' ) as $slug ) {
	ok( "live link added: $slug", false !== strpos( $out, '/product-category/' . $slug . '/' ) );
}

ok( 'Shop heading kept', false !== strpos( $out, '<h3>Shop</h3>' ) );
ok( 'nav aria-label kept', 1 === substr_count( $out, 'aria-label="Shop footer links"' ) );
ok( 'exactly five links', 5 === substr_count(
	substr( $out, strpos( $out, 'Shop footer links' ), 600 ), '<a href=' ) );

/* Everything else in the footer survives untouched. */
ok( 'Research column untouched', false !== strpos( $out, 'aria-label="Research footer links"' ) );
ok( 'Company column untouched', false !== strpos( $out, 'aria-label="Company footer links"' ) );
ok( 'footer logo untouched', false !== strpos( $out, 'opl-footer-v2-logo' ) );
ok( 'About link untouched', false !== strpos( $out, '/about/' ) );
ok( 'only the Shop nav changed', 3 === substr_count( $out, '<nav aria-label=' ) );

/* ---------------------------------------------------------- failure modes */

// Nothing qualifies -> leave the existing column completely alone.
$GLOBALS['opl_stock'] = array();
$GLOBALS['opl_cache'] = array();
$untouched            = opl_fsl_rewrite( $PAGE );
ok( 'no qualifying categories leaves the footer exactly as it was', $untouched === $PAGE );

// Only some qualify -> render only those, never a dead one.
$GLOBALS['opl_stock'] = array(
	'metabolic-research' => 6,
	'cellular-research'  => 0,
	'research-stacks'    => 7,
);
$GLOBALS['opl_cache'] = array();
$partial              = opl_fsl_rewrite( $PAGE );
ok( 'empty category is skipped', false === strpos( $partial, 'cellular-research' ) );
ok( 'populated categories still render',
	false !== strpos( $partial, 'metabolic-research' ) && false !== strpos( $partial, 'research-stacks' ) );
ok( 'partial run still drops every 410 link',
	false === strpos( $partial, '/product-category/vitamins/' ) );

// A page with no footer is returned unchanged.
ok( 'page without the Shop nav is untouched',
	opl_fsl_rewrite( '<html><body>no footer here</body></html>' ) === '<html><body>no footer here</body></html>' );

// Idempotent.
$GLOBALS['opl_stock'] = array( 'metabolic-research' => 6, 'research-stacks' => 7 );
$GLOBALS['opl_cache'] = array();
$once                 = opl_fsl_rewrite( $PAGE );
ok( 'idempotent', opl_fsl_rewrite( $once ) === $once );

// Cache is used rather than re-querying.
$GLOBALS['opl_stock'] = array( 'metabolic-research' => 6 );
$GLOBALS['opl_cache'] = array();
opl_fsl_links();
ok( 'links are cached', isset( $GLOBALS['opl_cache']['opl_fsl_links'] ) );
opl_fsl_flush();
ok( 'flush clears the cache', ! isset( $GLOBALS['opl_cache']['opl_fsl_links'] ) );

/* --------------------------------------------------- against a real page  */

if ( isset( $argv[1] ) && is_readable( $argv[1] ) ) {
	$GLOBALS['opl_stock'] = array(
		'metabolic-research' => 6,
		'cellular-research'  => 4,
		'longevity-research' => 3,
		'cognitive-research' => 2,
		'research-stacks'    => 7,
	);
	$GLOBALS['opl_cache'] = array();

	$live = file_get_contents( $argv[1] );
	$got  = opl_fsl_rewrite( $live );

	ok( 'real page: had dead links before',
		false !== strpos( $live, '/product-category/vitamins/' ) );
	ok( 'real page: dead links gone after',
		false === strpos( $got, '/product-category/vitamins/' )
		&& false === strpos( $got, '/product-category/wellness/' ) );
	ok( 'real page: footer still present',
		substr_count( $got, '<footer' ) === substr_count( $live, '<footer' ) );
	ok( 'real page: page length barely changed',
		abs( strlen( $got ) - strlen( $live ) ) < 400,
		'delta ' . ( strlen( $got ) - strlen( $live ) ) );

	echo 'real page delta: ' . ( strlen( $got ) - strlen( $live ) ) . " bytes\n";
}

echo "\n$PASS/" . ( $PASS + count( $FAIL ) ) . " passed\n";

if ( $FAIL ) {
	echo "\nFAILED:\n";

	foreach ( $FAIL as $f ) {
		echo "  - $f\n";
	}

	exit( 1 );
}
