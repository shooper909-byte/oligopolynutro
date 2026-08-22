<?php
define('ABSPATH',__DIR__.'/');
function add_action($h,$f,$p=10,$n=1){}
function esc_url($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function is_admin(){return false;} function is_feed(){return false;} function is_robots(){return false;}
// Reproduce the REAL library: the mangled filename that actually landed.
$GLOBALS['ATT'] = array(3542 => '2026/08/oligopolyfooterlogo-1.webp', 3516 => '2026/08/Logo.png');
$GLOBALS['MODE'] = getenv('MODE') ?: 'real';
function get_posts($a){
  if ($GLOBALS['MODE']==='empty') return array();
  return array_keys($GLOBALS['ATT']);
}
function get_post_meta($id,$k,$s=true){return $GLOBALS['ATT'][$id] ?? '';}
function wp_get_attachment_url($id){return 'https://www.oligopolypeptides.com/wp-content/uploads/'.$GLOBALS['ATT'][$id];}
function wp_get_attachment_metadata($id){return array('width'=>279,'height'=>192);}
require '/home/user/oligopolynutro/wordpress/footer-logo.php';
$page = file_get_contents(__DIR__.'/fl2.html');
$out  = opl_flogo_rewrite($page);
preg_match('#<!-- opl-footer-logo v2[^>]*-->#',$out,$mk);
preg_match('#<img[^>]*opl-footer-v2-logo[^>]*>#',$out,$im);
echo "MODE={$GLOBALS['MODE']}\n  marker: ".($mk[0]??'none')."\n  img   : ".($im[0]??'none')."\n";
echo "  css   : ".(strpos($out,'opl-footer-logo-css')!==false?'injected':'no')."\n";
