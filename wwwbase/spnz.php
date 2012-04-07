<?php

require_once("../phplib/util.php");
setlocale(LC_ALL, "ro_RO.utf8");

define('limit_freq',1);
<<<<<<< HEAD
define('easy', 0.85);
define('normal', 0.65);
define('hard',0.45);
define('imp',0.35);
=======
define('easy', 0.80);
define('normal', 0.60);
define('hard',0.40);
define('imp',0.20);
>>>>>>> 82e7c3a73dd6ae9ac6bc484930d6f86681c7ef1a

$min_freq = easy;
$max_freq = limit_freq;

$difficulty = (int) util_getRequestParameter('d');
if( is_int($difficulty)){
  switch ($difficulty) {
    case 1:
      $min_freq = normal;
      $max_freq = easy;
      break;
    case 2:
      $min_freq = hard;
      $max_freq = normal;
      break;
    default :
      if($difficulty >= 3) {
        $difficulty = 3;
        $min_freq = imp;
        $max_freq = hard;
	}
      break;
  }
}
else
  $difficulty = 0;
<<<<<<< HEAD
/* OLD WITH Error
$query = sprintf("SELECT id FROM Lexem WHERE frequency IS NOT NULL AND  frequency BETWEEN %d AND %d AND length(formUtf8General) > 5 ORDER BY rand() LIMIT 1;",$min_freq, $max_freq);
$randLexemId =  db_getSingleValue($query);
$query = sprintf("SELECT lexicon, htmlRep FROM  Definition WHERE  id IN (SELECT definitionId FROM LexemDefinitionMap WHERE lexemId  = %d ) LIMIT 1;", $randLexemId);
*/
/* NEW, OK, BUT SLOW*/
$query = sprintf("SELECT lexicon, htmlRep FROM Definition WHERE id IN (SELECT definitionId FROM LexemDefinitionMap WHERE lexemId IN (SELECT id FROM Lexem WHERE frequency BETWEEN %d AND %d )) AND length(lexicon) > 5 AND status = 0 ORDER BY rand() LIMIT 1;", $min_freq, $max_freq);
$arr = db_getArrayOfRows($query);
$cuv = $arr[0]["lexicon"];
$definitie = $arr[0]["htmlRep"];
=======

$count = Model::factory('Lexem')->where_gte('frequency', $min_freq)->where_lte('frequency', $max_freq)
  ->where_raw('length(formUtf8General) > 5')->count();

do {
  $lexem = Model::factory('Lexem')->where_gte('frequency', $min_freq)->where_lte('frequency', $max_freq)
    ->where_raw('length(formUtf8General) > 5')->limit(1)->offset(rand(0, $count - 1))->find_one();

  // Select an official definition for this lexem.
  $def = Model::factory('Definition')
    ->join('LexemDefinitionMap', 'Definition.id = definitionId')
    ->join('Source', 'Source.id = sourceId')
    ->where('lexemId', $lexem->id)
    ->where('status', 0)
    ->where('isOfficial', 2)
    ->order_by_asc('displayOrder')
    ->limit(1)
    ->find_one();
} while (!$def);

$cuv = $lexem->formNoAccent;
>>>>>>> 82e7c3a73dd6ae9ac6bc484930d6f86681c7ef1a

$nr_lit = mb_strlen($cuv);
$litere = array_filter(preg_split('//u',$cuv));
$iter = range(0,$nr_lit-1);
smarty_assign('iter', $iter);
smarty_assign('letters', preg_split('//u', 'aăâbcdefghiîjklmnopqrsștțuvwxyz-',NULL,PREG_SPLIT_NO_EMPTY));
smarty_assign('litere', $litere);
smarty_assign('page_title', 'Spânzurătoarea by CDL');
smarty_assign('cuvant', $cuv);
smarty_assign('nr_lit',$nr_lit);
<<<<<<< HEAD
smarty_assign('definitie', $definitie);
=======
smarty_assign('definitie', $def->htmlRep);
>>>>>>> 82e7c3a73dd6ae9ac6bc484930d6f86681c7ef1a
smarty_assign('difficulty', $difficulty);
smarty_displayCommonPageWithSkin("spnz.ihtml");
?>
