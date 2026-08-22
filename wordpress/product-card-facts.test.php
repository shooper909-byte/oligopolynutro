<?php
$GLOBALS['filters'] = array();
function add_filter( $h, $f, $p = 10, $a = 1 ) { $GLOBALS['filters'][$h][] = $f; }
function wp_strip_all_tags( $s ) { return trim( strip_tags( $s ) ); }
function is_admin() { return false; }
function is_feed()  { return false; }
require '/home/user/oligopolynutro/wordpress/product-card-facts.php';
function run( $h ) { foreach ( $GLOBALS['filters']['the_content'] as $f ) { $h = $f( $h ); } return $h; }

$pages = array(
  '/research/'         => array('p-research.html',         'oplhub-product'),
  'homepage'           => array('home4.html',              'op9-product-card'),
  '/research-catalog/' => array('p-research-catalog.html', 'oprc-card'),
);

$total_cards = 0; $total_done = 0;
foreach ( $pages as $label => $i ) {
    list( $file, $cls ) = $i;
    $out = run( file_get_contents( __DIR__ . '/' . $file ) );
    preg_match_all( '#<article\b[^>]*class="[^"]*' . $cls . '[^"]*"[^>]*>.*?</article>#s', $out, $cards );
    echo "=== $label (" . count( $cards[0] ) . " cards) ===\n";
    foreach ( $cards[0] as $c ) {
        preg_match( '#<h3\b[^>]*>(.*?)</h3>#s', $c, $h );
        $title = $h ? wp_strip_all_tags( $h[1] ) : '(no h3)';
        $has = preg_match( '#<ul class="opl-facts">(.*?)</ul>#s', $c, $u );
        $first = $has ? wp_strip_all_tags( preg_replace( '#</li>.*#s', '', $u[1] ) ) : '-- NONE --';
        printf( "  %-46s %s\n", substr( $title, 0, 44 ), $first );
        $total_cards++; if ( $has ) { $total_done++; }
    }
    echo "\n";
}
printf( "TOTAL: %d/%d cards have fact bullets\n", $total_done, $total_cards );
