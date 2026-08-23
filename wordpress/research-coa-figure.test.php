<?php
define('ABSPATH',__DIR__.'/');
function add_action($h,$f,$p=10,$n=1){}
function esc_url($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function is_admin(){return false;} function is_feed(){return false;} function is_robots(){return false;}
$MODE = getenv('MODE') ?: 'ok';
function wp_get_attachment_url($id){ return getenv('MODE')==='missing' ? '' : 'https://www.oligopolypeptides.com/wp-content/uploads/2026/08/5113.png'; }
function wp_get_attachment_metadata($id){return array('width'=>1536,'height'=>1024);}
function get_post_meta($id,$k,$s=true){return 'Example Certificate of Analysis shown beside a labelled Tirzepatide research vial, with callouts explaining how a batch number and QR code link a vial to its certificate. Illustrative of the documentation format.';}
require '/home/user/oligopolynutro/wordpress/research-coa-figure.php';

$page = file_get_contents(__DIR__.'/rsearch.html');
$out  = opl_coa_rewrite($page);
file_put_contents(__DIR__.'/site/research-coa.html', str_replace('https://www.oligopolypeptides.com/wp-content/','/wp-content/',$out));
preg_match('#<!-- opl-coa-fig[^>]*-->#',$out,$mk);
echo "MODE=".getenv('MODE')."\n";
echo "  marker      : ".($mk[0]??'none')."\n";
echo "  figure count: ".substr_count($out,'opl-coa-fig"')."\n";
echo "  css         : ".(strpos($out,'opl-coa-fig-css')!==false?'injected':'no')."\n";
echo "  before cards: ".(strpos($out,'opl-coa-fig') < strpos($out,'oplhub-doc-cards') ? 'yes':'NO')."\n";
echo "  slot intact : ".(strpos($out,'oplhub-coa-slot')!==false && strpos($out,'data-coa-preview-slot hidden')!==false ?'yes (still hidden)':'CHANGED')."\n";
$twice = opl_coa_rewrite($out);
echo "  re-run adds : ".(substr_count($twice,'opl-coa-fig"')===substr_count($out,'opl-coa-fig"')?'no (idempotent)':'YES - BUG')."\n";
