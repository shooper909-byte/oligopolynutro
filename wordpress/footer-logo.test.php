<?php
define('ABSPATH',__DIR__.'/');
$GLOBALS['acts']=array();
function add_action($h,$f,$p=10,$n=1){$GLOBALS['acts'][$h][]=$f;}
function esc_url($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function is_admin(){return false;} function is_feed(){return false;} function is_robots(){return false;}
$GLOBALS['HAVE']=true;
function get_posts($a){ return $GLOBALS['HAVE'] ? array(9001) : array(); }
function wp_get_attachment_url($id){return 'https://www.oligopolypeptides.com/wp-content/uploads/2026/08/oligopoly-footer-logo.webp';}
function wp_get_attachment_metadata($id){return array('width'=>279,'height'=>192);}
require '/home/user/oligopolynutro/wordpress/footer-logo.php';

$page = file_get_contents(__DIR__.'/post-rs.html');
echo "--- attachment present ---\n";
$out = opl_flogo_rewrite($page);
preg_match('#<img[^>]*opl-footer-v2-logo[^>]*>#i',$out,$m);
echo "  img : ".($m[0]??'NOT FOUND')."\n";
echo "  css injected in <head>: ".(strpos(substr($out,0,strpos($out,'</head>')),'opl-footer-logo-css')!==false?'yes':'no')."\n";
echo "  old logo.webp still referenced: ".(strpos($out,'uploads/oligopoly/logo.webp')!==false?'YES (bad)':'no')."\n";
echo "  other <img> tags untouched: ".(substr_count($page,'<img')===substr_count($out,'<img')?'yes':'NO')."\n";
echo "  header logo untouched: ".(substr_count($page,'custom-logo')===substr_count($out,'custom-logo')?'yes':'NO')."\n";

echo "--- attachment MISSING (must be a no-op) ---\n";
$GLOBALS['HAVE']=false;
$fn = new ReflectionFunction('opl_flogo_asset'); // reset the static cache
$r = (function(){ static $x; return $x; });
// simulate a fresh request by re-running in a subprocess instead
