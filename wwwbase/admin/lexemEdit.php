<?php
require_once("../../phplib/util.php"); 

util_assertModerator(PRIV_EDIT);
util_assertNotMirror();
setlocale(LC_ALL, "ro_RO.utf8");

$lexemId = util_getRequestParameter('lexemId');
$dissociateDefinitionId = util_getRequestParameter('dissociateDefinitionId');
$associateDefinitionId = util_getRequestParameter('associateDefinitionId');
$lexemForm = util_getRequestParameter('lexemForm');
$lexemDescription = util_getRequestParameter('lexemDescription');
$lexemSources = util_getRequestParameter('lexemSources');
$lexemTags = util_getRequestParameter('lexemTags');
$lexemComment = util_getRequestParameter('lexemComment');
$lexemIsLoc = util_getRequestParameter('lexemIsLoc');
$lexemNoAccent = util_getRequestParameter('lexemNoAccent');
$modelType = util_getRequestParameter('modelType');
$modelNumber = util_getRequestParameter('modelNumber');
$similarModel = util_getRequestParameter('similarModel');
$similarLexemId = util_getRequestParameter('similarLexemId');
$restriction = util_getRequestCheckboxArray('restr', '');
$miniDefTarget = util_getRequestParameter('miniDefTarget');

$refreshLexem = util_getRequestParameter('refreshLexem');
$updateLexem = util_getRequestParameter('updateLexem');
$cloneLexem = util_getRequestParameter('cloneLexem');
$deleteLexem = util_getRequestParameter('deleteLexem');
$createDefinition = util_getRequestParameter('createDefinition');

$lexem = Lexem::get_by_id($lexemId);
$oldModelType = $lexem->modelType;
$oldModelNumber = $lexem->modelNumber;

if ($associateDefinitionId) {
  LexemDefinitionMap::associate($lexem->id, $associateDefinitionId);
  util_redirect("lexemEdit.php?lexemId={$lexem->id}");
}

if ($dissociateDefinitionId) {
  LexemDefinitionMap::dissociate($lexem->id, $dissociateDefinitionId);
  util_redirect("lexemEdit.php?lexemId={$lexem->id}");
}

if ($createDefinition) {
  $def = Model::factory('Definition')->create();
  $def->displayed = 0;
  $def->userId = session_getUserId();
  $def->sourceId = Source::get_by_shortName('Neoficial')->id;
  $def->lexicon = $lexem->formNoAccent;
  $def->internalRep =
    '@' . mb_strtoupper(AdminStringUtil::internalize($lexem->form, false)) .
    '@ v. @' . $miniDefTarget . '.@';
  $def->htmlRep = AdminStringUtil::htmlize($def->internalRep, $def->sourceId);
  $def->status = ST_ACTIVE;
  $def->save();

  LexemDefinitionMap::associate($lexem->id, $def->id);

  util_redirect("lexemEdit.php?lexemId={$lexem->id}");
  exit;
}

if ($deleteLexem) {
  $homonyms = Model::factory('Lexem')->where('formNoAccent', $lexem->formNoAccent)->where_not_equal('id', $lexem->id)->find_many();
  $lexem->delete();
  SmartyWrap::assign('lexem', $lexem);
  SmartyWrap::assign('homonyms', $homonyms);
  SmartyWrap::assign('sectionTitle', 'Confirmare ștergere lexem');
  SmartyWrap::displayAdminPage('admin/lexemDeleted.ihtml');
  return;
}

if ($cloneLexem) {
  $newLexem = _cloneLexem($lexem);
  log_userLog("Cloned lexem {$lexem->id} ({$lexem->form}), new id is {$newLexem->id}");
  util_redirect("lexemEdit.php?lexemId={$newLexem->id}");
}

if (!$similarModel && !$similarLexemId && !$refreshLexem && !$updateLexem) {
  RecentLink::createOrUpdate("Lexem: {$lexem}");
}

if ($lexemForm !== null) {
  $oldUnaccented = $lexem->formNoAccent;
  $lexem->form = AdminStringUtil::formatLexem($lexemForm);
  $lexem->formNoAccent = str_replace("'", '', $lexem->form);
  $lexem->reverse = StringUtil::reverse($lexem->formNoAccent);
  if ($lexem->formNoAccent != $oldUnaccented) {
    $lexem->modelType = 'T';
    $lexem->modelNumber = 1;
  }
}

if ($lexemDescription !== null) {
  $lexem->description = AdminStringUtil::internalize($lexemDescription, false);
}

if ($lexemTags !== null) {
  $lexem->tags = AdminStringUtil::internalize($lexemTags, false);
}

if ($lexemSources !== null) {
  $lexem->source = implode(',', $lexemSources);
}	

if ($lexemComment !== null) {
  $newComment = trim(AdminStringUtil::internalize($lexemComment, false));
  $oldComment = trim($lexem->comment);
  if (StringUtil::startsWith($newComment, $oldComment) &&
      $newComment != $oldComment &&
      !StringUtil::endsWith($newComment, ']]')) {
    $newComment .= " [[" . session_getUser() . ", " .
      strftime("%d %b %Y %H:%M") . "]]\n";
  } else if ($newComment) {
    $newComment .= "\n";
  }
  $lexem->comment = $newComment;
}

if ($lexemIsLoc !== null) {
  $lexem->isLoc = ($lexemIsLoc != '');
}

if ($lexemNoAccent !== null) {
  $lexem->noAccent = ($lexemNoAccent != '');
}

// The new model type, number and restrictions can come from three sources:
// $similarModel, $similarLexemId or ($modelType, $modelNumber,
// $restriction) directly
$errorMessage = '';
if ($similarModel !== null) {
  $parts = FlexModel::splitName($similarModel);
  $lexem->modelType = $parts[0];
  $lexem->modelNumber = $parts[1];
  $lexem->restriction = $parts[2];
} else if ($similarLexemId) {
  $similarLexem = Lexem::get_by_id($similarLexemId);
  $lexem->modelType = $similarLexem->modelType;
  $lexem->modelNumber = $similarLexem->modelNumber;
  $lexem->restriction = $similarLexem->restriction;
} else if ($modelType !== null) {
  $lexem->modelType = $modelType;
  $lexem->modelNumber = $modelNumber;
  $lexem->restriction = $restriction;
}

if (!$errorMessage) {
  $errorMessage = validate($lexem);
}

if (!$errorMessage) {
  $errorMessage = validateRestriction($lexem->modelType, $lexem->restriction);
}

if ($updateLexem && !$errorMessage) {
  if ($oldModelType == 'VT' && $lexem->modelType != 'VT') {
    $lexem->deleteParticiple($oldModelNumber);
  }
  if (($oldModelType == 'VT' || $oldModelType == 'V') &&
      ($lexem->modelType != 'VT' && $lexem->modelType != 'V')) {
    $lexem->deleteLongInfinitive();
  }
  $lexem->save();
  // There are two reasons to regenerate the paradigm: the model has changed
  // or the form has changed. It's easier to do it every time.
  $lexem->regenerateParadigm();

  log_userLog("Edited lexem {$lexem->id} ({$lexem->form})");
  util_redirect("lexemEdit.php?lexemId={$lexem->id}");
}

$definitions = Definition::loadByLexemId($lexem->id);
$searchResults = SearchResult::mapDefinitionArray($definitions);
$definitionLexem = mb_strtoupper(AdminStringUtil::internalize($lexem->form, false));

// Generate new inflected forms, but do not overwrite the old ones.
$ifs = $lexem->generateParadigm();
if (!is_array($ifs)) {
  $infl = Inflection::get_by_id($ifs);
  if (!$errorMessage) {
    $errorMessage = "Nu pot genera flexiunea '".htmlentities($infl->description)."' " .
      "conform modelului {$lexem->modelType}{$lexem->modelNumber}.";
  }
} else {
  $ifMap = InflectedForm::mapByInflectionRank($ifs);
  SmartyWrap::assign('ifMap', $ifMap);
  SmartyWrap::assign('searchResults', $searchResults);
}

$models = FlexModel::loadByType($lexem->modelType);

$sources = LexemSources::getSourceArrayChecked($lexem->source);
$sourceNames = LexemSources::getNamesOfSources($lexem->source);
$canEditForm = !$lexem->isLoc || util_isModerator(PRIV_LOC);

SmartyWrap::assign('lexem', $lexem);
SmartyWrap::assign('sources', $sources);
SmartyWrap::assign('sourceNames', $sourceNames);
SmartyWrap::assign('searchResults', $searchResults);
SmartyWrap::assign('definitionLexem', $definitionLexem);
SmartyWrap::assign('homonyms', Model::factory('Lexem')->where('formNoAccent', $lexem->formNoAccent)->where_not_equal('id', $lexem->id)->find_many());
SmartyWrap::assign('suggestedLexems', loadSuggestions($lexem, 5));
SmartyWrap::assign('restrS', FlexStringUtil::contains($lexem->restriction, 'S'));
SmartyWrap::assign('restrP', FlexStringUtil::contains($lexem->restriction, 'P'));
SmartyWrap::assign('restrU', FlexStringUtil::contains($lexem->restriction, 'U'));
SmartyWrap::assign('restrI', FlexStringUtil::contains($lexem->restriction, 'I'));
SmartyWrap::assign('restrT', FlexStringUtil::contains($lexem->restriction, 'T'));
SmartyWrap::assign('modelTypes', Model::factory('ModelType')->order_by_asc('code')->find_many());
SmartyWrap::assign('models', $models);
SmartyWrap::assign('canEditForm', $canEditForm);
SmartyWrap::assign('allStatuses', util_getAllStatuses());
SmartyWrap::assign('errorMessage', $errorMessage);
SmartyWrap::assign('recentLinks', RecentLink::loadForUser());
SmartyWrap::addCss('jqueryui', 'paradigm', 'select2');
SmartyWrap::addJs('jquery', 'jqueryui', 'struct', 'select2');
SmartyWrap::assign('sectionTitle', "Editare lexem: {$lexem->form} {$lexem->modelType}{$lexem->modelNumber}{$lexem->restriction}");
SmartyWrap::displayAdminPage('admin/lexemEdit.ihtml');

function validate($lexem) {
  if (!$lexem->form) {
    return 'Forma nu poate fi vidă.';
  }
  $numAccents = mb_substr_count($lexem->form, "'");
  // Note: we allow multiple accents for lexems like hárcea-párcea
  if ($numAccents && $lexem->noAccent) {
    return 'Ați indicat că lexemul nu necesită accent, dar forma conține un accent.';
  } else if (!$numAccents && !$lexem->noAccent) {
    return 'Adăugați un accent sau bifați câmpul "Nu necesită accent".';
  }
  return null;
}

function validateRestriction($modelType, $restriction) {
  $hasS = false;
  $hasP = false;
  for ($i = 0; $i < mb_strlen($restriction); $i++) {
    $char = StringUtil::getCharAt($restriction, $i);
    if ($char == 'T' || $char == 'U' || $char == 'I') {
      if ($modelType != 'V' && $modelType != 'VT') {
        return "Restricția <b>$char</b> se aplică numai verbelor";
      }
    } else if ($char == 'S') {
      if ($modelType == 'I' || $modelType == 'T') {
        return "Restricția S nu se aplică modelului $modelType";
      }
      $hasS = true;
    } else if ($char == 'P') {
      if ($modelType == 'I' || $modelType == 'T') {
        return "Restricția P nu se aplică modelului $modelType";
      }
      $hasP = true;
    } else {
      return "Restricția <b>$char</b> este incorectă.";
    }
  }
  
  if ($hasS && $hasP) {
    return "Restricțiile <b>S</b> și <b>P</b> nu pot coexista.";
  }
  return null;
}

function loadSuggestions($lexem, $limit) {
  $query = $lexem->reverse;
  $lo = 0;
  $hi = mb_strlen($query);
  $result = array();

  while ($lo <= $hi) {
    $mid = (int)(($lo + $hi) / 2);
    $partial = mb_substr($query, 0, $mid);
    $lexems = Model::factory('Lexem')->where_like('reverse', "{$partial}%")->where_not_equal('modelType', 'T')->where_not_equal('id', $lexem->id)
      ->group_by('modelType')->group_by('modelNumber')->limit($limit)->find_many();
    
    if (count($lexems)) {
      $result = $lexems;
      $lo = $mid + 1;
    } else {
      $hi = $mid - 1;
    }
  }
  return $result;
}

function _cloneLexem($lexem) {
  $clone = Lexem::create($lexem->form, 'T', 1, '');
  $clone->comment = $lexem->comment;
  $clone->description = ($lexem->description) ? "CLONĂ {$lexem->description}" : "CLONĂ";
  $clone->noAccent = $lexem->noAccent;
  $clone->save();
    
  // Clone the definition list
  $ldms = LexemDefinitionMap::get_all_by_lexemId($lexem->id);
  foreach ($ldms as $ldm) {
    LexemDefinitionMap::associate($clone->id, $ldm->definitionId);
  }

  $clone->regenerateParadigm();
  return $clone;
}

?>
