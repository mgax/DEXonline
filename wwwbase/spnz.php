<?php

require_once("../phplib/util.php");
setlocale(LC_ALL, "ro_RO.utf8");

define('easy_max', 10);
define('normal_max', 15);
define('hard_max', 20);
define('imp_max', 99);

$min_length = 6;
$max_length = easy_max;

$difficulty = (int) util_getRequestParameter('d');
if( is_int($difficulty)){
  switch ($difficulty) {
    case 1:
      $min_length = easy_max;
      $max_length = normal_max;
      break;
    case 2:
      $min_length = normal_max;
      $max_length = hard_max;
      break;
    default :
      if($difficulty >= 3) {
        $difficulty = 3;
        $min_length = hard_max;
        $max_length = imp_max;
	}
      break;
  }
}
else
  $difficulty = 0;

$query = sprintf("SELECT lexicon, htmlRep FROM  Definition WHERE  id IN (SELECT id FROM Lexem) AND length(lexicon) BETWEEN %d AND %d  ORDER BY rand() LIMIT 1;",$min_length, $max_length);
$arr = db_getArrayOfRows($query);
$cuv = $arr[0]["lexicon"];
$definitie = $arr[0]["htmlRep"];

$nr_lit = mb_strlen($cuv);
$litere = array_filter(preg_split('//u',$cuv));
$iter = range(0,$nr_lit-1);
smarty_assign('iter', $iter);
smarty_assign('letters', preg_split('//u', 'aăâbcdefghiîjklmnopqrsștțuvwxyz-',NULL,PREG_SPLIT_NO_EMPTY));
smarty_assign('litere', $litere);
smarty_assign('page_title', 'Spânzurătoarea by CDL');
smarty_assign('cuvant', $cuv);
smarty_assign('nr_lit',$nr_lit);
smarty_assign('definitie', $definitie);
smarty_assign('difficulty', $difficulty);
smarty_displayCommonPageWithSkin("spnz.ihtml");
?>
