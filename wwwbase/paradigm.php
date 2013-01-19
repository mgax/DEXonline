<?php
require_once("../phplib/util.php");

define('TYPE_SHOW_ONLY_VERBS', 'conjugare');
define('TYPE_SHOW_NO_VERBS', 'declinare');

$cuv = util_getRequestParameter('cuv');
$lexemId = util_getRequestParameter('lexemId');
$ajax = util_getRequestParameter('ajax');
$type = util_getRequestParameter('type');

$searchType = SEARCH_INFLECTED;
$arr = StringUtil::analyzeQuery($cuv);
$hasDiacritics = session_user_prefers(Preferences::FORCE_DIACRITICS) || $arr[0];

// LexemId search
if ($lexemId) {
  $searchType = SEARCH_LEXEM_ID;
  SmartyWrap::assign('lexemId', $lexemId);
  if (!StringUtil::validateAlphabet($lexemId, '0123456789')) {
    $lexemId = '';
  }
  $lexem = Lexem::get_by_id($lexemId);
  if ($lexem) {
    $lexems = array($lexem);
	$cuv = $lexem->formNoAccent;
  } else {
    $lexems = array();
	$cuv = NULL;
  }
}

if ($lexemId) {
  SmartyWrap::assign('paradigmLink', util_getWwwRoot() . "lexem/$cuv/$lexemId/paradigma");
}
else {
  SmartyWrap::assign('paradigmLink', util_getWwwRoot() . "definitie/$cuv/paradigma");
}

if ($cuv) {
  $cuv = StringUtil::cleanupQuery($cuv);
}

// Normal search
if ($searchType == SEARCH_INFLECTED) {
  $lexems = Lexem::searchInflectedForms($cuv, $hasDiacritics);
  if (count($lexems) == 0) {
    $cuv_old = StringUtil::tryOldOrthography($cuv);
    $lexems = Lexem::searchInflectedForms($cuv_old, $hasDiacritics);
  }
}

// Maps lexems to arrays of inflected forms (some lexems may lack inflections)
// Also compute the text of the link to the paradigm div,
// which can be 'conjugări', 'declinări' or both
if (!empty($lexems)) {
  $ifMaps = array();
  $conjugations = false;
  $declensions = false;
  $filtered_lexems = array();
  foreach ($lexems as $l) {
    if (TYPE_SHOW_ONLY_VERBS == $type) {
      if ($l->modelType == 'V' || $l->modelType == 'VT') {
        $filtered_lexems[] = $l;
        $conjugations = true;
        $ifMaps[] = InflectedForm::loadByLexemIdMapByInflectionRank($l->id);
      }
    }
    elseif (TYPE_SHOW_NO_VERBS == $type) {
      if ($l->modelType != 'V' && $l->modelType != 'VT') {
        $filtered_lexems[] = $l;
        $declensions = true;
        $ifMaps[] = InflectedForm::loadByLexemIdMapByInflectionRank($l->id);
      }
    }
    else {
      $filtered_lexems[] = $l;
      $ifMaps[] = InflectedForm::loadByLexemIdMapByInflectionRank($l->id);
      if ($l->modelType == 'V' || $l->modelType == 'VT') {
        $conjugations = true;
      } else {
        $declensions = true;
      }
    }
  }

  if (empty($filtered_lexems)) {
    FlashMessage::add("Niciun rezultat pentru {$cuv}.");
    SmartyWrap::assign('page_title', "Eroare");
  }

  $declensionText = $conjugations ? ($declensions ? 'Conjugare / Declinare' : 'Conjugare') : ($declensions ? 'Declinare' : '');

  if ($cuv && !empty($filtered_lexems)) {
    SmartyWrap::assign('cuv', $cuv);
    SmartyWrap::assign('page_title', "{$declensionText}: {$cuv}");
    SmartyWrap::assign('declensionText', "{$declensionText}: {$cuv}");
  }

  $sourceNamesArr = array();
  foreach($lexems as $l) {
    $sourceNamesArr[] = LexemSources::getNamesOfSources($l->source);
  }

  // This paragraph replicates code from paradigm.php
  $hasUnrecommendedForms = false;
  foreach ($ifMaps as $ifMap) {
    foreach ($ifMap as $rank => $ifs) {
      foreach ($ifs as $if) {
        $hasUnrecommendedForms |= !$if->recommended;
      }
    }
  }
  SmartyWrap::assign('hasUnrecommendedForms', $hasUnrecommendedForms);

  SmartyWrap::assign('sourceNamesArr', $sourceNamesArr);
  SmartyWrap::assign('lexems', $filtered_lexems);
  SmartyWrap::assign('ifMaps', $ifMaps);
  SmartyWrap::assign('showParadigm', true);
  SmartyWrap::assign('onlyParadigm', !$ajax);
}
else {
  FlashMessage::add("Niciun rezultat pentru {$cuv}.");
  SmartyWrap::assign('page_title', "Eroare");
}

if ($ajax) {
  SmartyWrap::displayWithoutSkin('common/bits/multiParadigm.ihtml');
}
else {
  SmartyWrap::displayCommonPageWithSkin('search.ihtml');
}
?>
