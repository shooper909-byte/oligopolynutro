<?php
define('ABSPATH', __DIR__ . '/'); define('OBJECT','OBJECT');
$GLOBALS['sc']=array(); $GLOBALS['acts']=array();
function add_shortcode($t,$f){$GLOBALS['sc'][$t]=$f;}
function add_action($h,$f,$p=10,$n=1){$GLOBALS['acts'][$h][]=$f;}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function esc_url($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function wp_kses_post($s){return $s;}
function wp_json_encode($d){return json_encode($d);}
function wc_price($p){return '<span class="woocommerce-Price-amount amount">$'.number_format((float)$p,2).'</span>';}

// ---- real catalog data, taken from the live MNM containers ----
$P = array(
 3447=>array('name'=>'Build Your Research Bundle - 3 Vials','slug'=>'build-your-research-bundle-3-vials','min'=>3,'max'=>3),
 3450=>array('name'=>'Build Your Research Bundle - 6 Vials','slug'=>'build-your-research-bundle-6-vials','min'=>6,'max'=>6),
 3452=>array('name'=>'Build Your Research Bundle - 9 Vials','slug'=>'build-your-research-bundle-9-vials','min'=>9,'max'=>9),
 39  =>array('name'=>'Tirzepatide 10mg Research Peptide | OligoPoly Laboratories','slug'=>'tirzepatide-10mg-research-peptide','price'=>'74.99','img'=>'OP-MET-TIRZ-10MG'),
 436 =>array('name'=>'Cagrilintide 5mg Research Peptide','slug'=>'cagrilintide-5mg-research-peptide','price'=>'109.99','img'=>'OP-MET-CAGRI-5MG'),
 63  =>array('name'=>'NAD+ 500mg Research Peptide','slug'=>'nad-500mg-research-compound','price'=>'109.99','img'=>'OP-AUX-NAD-500MG'),
 441 =>array('name'=>'GHK-Cu 50mg Research Peptide','slug'=>'ghk-cu-50mg-research-peptide','price'=>'89.99','img'=>'OP-LON-GHKCU-50MG'),
 3397=>array('name'=>'Semaglutide 5 mg','slug'=>'semaglutide-5mg-research-peptide','price'=>'79.99','img'=>'OP-MET-SEMA-5MG'),
 447 =>array('name'=>'Selank 5 mg','slug'=>'selank-5mg','price'=>'79.99','img'=>'OP-COG-SELANK-5MG'),
 3395=>array('name'=>'Retatrutide 5 mg','slug'=>'retatrutide-5mg-research-peptide','price'=>'99.99','img'=>'OP-MET-RETA-5MG'),
 3396=>array('name'=>'Retatrutide 20 mg','slug'=>'retatrutide-20mg-research-peptide','price'=>'129.99','img'=>'OP-MET-RETA-20MG'),
 3500=>array('name'=>'Metabolic Pathways Stack','slug'=>'metabolic-pathways-stack','img'=>'OP-STACK-METABOLIC','kids'=>array(3395,39,436,63,447,441)),
 3501=>array('name'=>'Cellular Energy Stack','slug'=>'cellular-energy-stack','img'=>'OP-STACK-CELLULAR','kids'=>array(63,441,447,436)),
 3502=>array('name'=>'Neurocognitive Pathways Stack','slug'=>'neurocognitive-pathways-stack','img'=>'OP-STACK-NEURO','kids'=>array(447,63,441)),
 3503=>array('name'=>'Regenerative Biology Stack','slug'=>'regenerative-biology-stack','img'=>'OP-STACK-REGEN','kids'=>array(441,63,447)),
);
$GLOBALS['P']=$P;
$CHILDREN = array(39,436,63,441,3397,447,3395,3396);
$GLOBALS['CHILDREN']=$CHILDREN;

class StubItem{ public $pid; function __construct($p){$this->pid=$p;}
  function get_product_id(){return $this->pid;} function get_product(){return wc_get_product($this->pid);} }

class StubProduct{
  public $id; public $d;
  function __construct($id){$this->id=$id;$this->d=$GLOBALS['P'][$id];}
  function get_id(){return $this->id;}
  function get_name(){return $this->d['name'];}
  function get_price(){return isset($this->d['price'])?$this->d['price']:'';}
  function get_stock_quantity(){return 9;}
  function is_in_stock(){return true;}
  function is_purchasable(){return true;}
  function get_image_id(){return $this->id;}
  function get_min_container_size(){return isset($this->d['min'])?$this->d['min']:0;}
  function get_max_container_size(){return isset($this->d['max'])?$this->d['max']:0;}
  function get_child_items(){
    if(isset($this->d['min'])){$o=array();foreach($GLOBALS['CHILDREN'] as $c)$o[]=new StubItem($c);return $o;}
    if(isset($this->d['kids'])){$o=array();foreach($this->d['kids'] as $c)$o[]=new StubItem($c);return $o;}
    return array();}
}
function wc_get_product($id){return isset($GLOBALS['P'][$id])?new StubProduct($id):null;}
function get_page_by_path($slug,$o=null,$t=null){
  foreach($GLOBALS['P'] as $id=>$d) if($d['slug']===$slug) return (object)array('ID'=>$id);
  return null;}
function get_post_status($id){return 'publish';}
function get_permalink($id){$d=$GLOBALS['P'][$id];
  $base=isset($d['min'])||isset($d['price'])||isset($d['kids'])?'products/':'';
  return 'https://www.oligopolypeptides.com/'.$base.$d['slug'].'/';}
function get_post_meta($id,$k,$s=true){
  if($k==='_wp_attachment_image_alt'){$d=$GLOBALS['P'][$id];return $d['name'].' research vial';}
  return '';}
function wp_get_attachment_image_url($id,$size){
  $d=$GLOBALS['P'][$id]; if(empty($d['img']))return '';
  $sz = $size==='large' ? '' : '-300x300';
  return '/wp-content/uploads/2026/08/'.$d['img'].$sz.'.png';}
function wp_get_attachment_image_srcset($id,$size){return '';}

require '/home/user/oligopolynutro/wordpress/research-stacks-page.php';
