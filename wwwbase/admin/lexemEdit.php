<?
require_once("../../phplib/util.php"); 
util_assertModeratorStatus();
util_assertNotMirror();
setlocale(LC_ALL, "ro_RO");

$lexemId = util_getRequestParameter('lexemId');
$dissociateDefinitionId = util_getRequestParameter('dissociateDefinitionId');
$associateDefinitionId = util_getRequestParameter('associateDefinitionId');
$lexemForm = util_getRequestParameter('lexemForm');
$lexemDescription = util_getRequestParameter('lexemDescription');
$lexemComment = util_getRequestParameter('lexemComment');
$lexemIsLoc = util_getRequestParameter('lexemIsLoc');
$lexemNoAccent = util_getRequestParameter('lexemNoAccent');
$modelType = util_getRequestParameter('modelType');
$modelNumber = util_getRequestParameter('modelNumber');
$similarLexemId = util_getRequestParameter('similarLexemId');
$similarLexemName = util_getRequestParameter('similarLexemName');
$restrArray = util_getRequestParameter('restr');
$restriction = $restrArray ? implode($restrArray, '') : '';
$miniDefTarget = util_getRequestParameter('miniDefTarget');

$refreshLexem = util_getRequestParameter('refreshLexem');
$updateLexem = util_getRequestParameter('updateLexem');
$cloneLexem = util_getRequestParameter('cloneLexem');
$deleteLexem = util_getRequestParameter('deleteLexem');
$createDefinition = util_getRequestParameter('createDefinition');

$lexem = Lexem::load($lexemId);
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
  $def = new Definition();
  $def->userId = session_getUserId();
  $def->sourceId = Source::loadUnofficial()->id;
  $def->lexicon = $lexem->unaccented;
  $def->internalRep =
    '@' . text_unicodeToUpper(text_internalize($lexem->form, false)) .
    '@ v. @' . $miniDefTarget . '.@';
  $def->htmlRep = text_htmlize($def->internalRep);
  $def->status = ST_ACTIVE;
  $def->save();
  $def->id = db_getLastInsertedId();

  LexemDefinitionMap::associate($lexem->id, $def->id);

  util_redirect("lexemEdit.php?lexemId={$lexem->id}");
  exit;
}

if ($deleteLexem) {
  $homonyms = $lexem->loadHomonyms();
  if ($lexem->modelType == 'VT') {
    $lexem->deleteParticiple($lexem->modelNumber);
  }
  if ($lexem->modelType == 'VT' || $lexem->modelType == 'V') {
    $lexem->deleteLongInfinitive();
  }
  $lexem->delete();
  smarty_assign('lexem', $lexem);
  smarty_assign('homonyms', $homonyms);
  smarty_displayWithoutSkin('admin/lexemDeleted.ihtml');
  return;
}

if ($cloneLexem) {
  $newLexem = $lexem->cloneLexem();
  util_redirect("lexemEdit.php?lexemId={$newLexem->id}");
}

if (!$similarLexemId && !$similarLexemName && !$refreshLexem &&
    !$updateLexem) {
  RecentLink::createOrUpdate('Lexem: ' . $lexem->getExtendedName());
}

if ($lexemForm !== null) {
  $oldUnaccented = $lexem->unaccented;
  $lexem->form = text_internalize($lexemForm, true);
  $lexem->unaccented = str_replace("'", '', $lexem->form);
  $lexem->reverse = text_reverse($lexem->unaccented);
  if ($lexem->unaccented != $oldUnaccented) {
    $lexem->modelType = 'T';
    $lexem->modelNumber = 1;
  }
}

if ($lexemDescription !== null) {
  $lexem->description = text_internalize($lexemDescription, false);
}

if ($lexemComment !== null) {
  $newComment = trim(text_internalize($lexemComment, false));
  $oldComment = trim($lexem->comment);
  if (text_startsWith($newComment, $oldComment) &&
      $newComment != $oldComment &&
      !text_endsWith($newComment, ']]')) {
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
// $similarLexemId, $similarLexemName or ($modelType, $modelNumber,
// $restriction) directly
$errorMessage = '';
if ($similarLexemId !== null) {
  $similarLexem = Lexem::load($similarLexemId);
  $lexem->modelType = $similarLexem->modelType;
  $lexem->modelNumber = $similarLexem->modelNumber;
  $lexem->restriction = $similarLexem->restriction;
} else if ($similarLexemName) {
  $matches = Lexem::loadByExtendedName($similarLexemName);
  if (count($matches) == 1) {
    $similarLexem = $matches[0];
    $lexem->modelType = $similarLexem->modelType;
    $lexem->modelNumber = $similarLexem->modelNumber;
    $lexem->restriction = $similarLexem->restriction;
  } else {
    $errorMessage = (count($matches) == 0)
      ? "Lexemul <i>".htmlentities($similarLexemName)."</i> nu există."
      : "Lexemul <i>".htmlentities($similarLexemName)."</i> este ambiguu.";
  }
} else if ($modelType !== null) {
  $lexem->modelType = $modelType;
  $lexem->modelNumber = $modelNumber;
  $lexem->restriction = $restriction;
}

if (!$errorMessage) {
  $errorMessage = $lexem->validate();
}

if (!$errorMessage) {
  $errorMessage = Lexem::validateRestriction($lexem->modelType,
                                             $lexem->restriction);
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

  util_redirect("lexemEdit.php?lexemId={$lexem->id}");
}

$definitions = Definition::loadByLexemId($lexem->id);
$searchResults = SearchResult::mapDefinitionArray($definitions);
$definitionLexem = text_unicodeToUpper(text_internalize($lexem->form, false));

// Generate new wordlists, but do not overwrite the old ones.
$wordLists = $lexem->generateParadigm();
if (!is_array($wordLists)) {
  $infl = Inflection::load($wordLists);
  if (!$errorMessage) {
    $errorMessage = "Nu pot genera inflexiunea '".htmlentities($infl->description)."' " .
      "conform modelului {$lexem->modelType}{$lexem->modelNumber}.";
  }
} else {
  $wlMap = WordList::mapByInflectionId($wordLists);
  smarty_assign('wlMap', $wlMap);
  smarty_assign('searchResults', $searchResults);
}

$models = Model::loadByType($lexem->modelType);

smarty_assign('lexem', $lexem);
smarty_assign('searchResults', $searchResults);
smarty_assign('definitionLexem', $definitionLexem);
smarty_assign('homonyms', $lexem->loadHomonyms());
smarty_assign('suggestedLexems', $lexem->loadSuggestions(5));
smarty_assign('restrS', text_contains($lexem->restriction, 'S'));
smarty_assign('restrP', text_contains($lexem->restriction, 'P'));
smarty_assign('restrU', text_contains($lexem->restriction, 'U'));
smarty_assign('restrI', text_contains($lexem->restriction, 'I'));
smarty_assign('restrT', text_contains($lexem->restriction, 'T'));
smarty_assign('modelTypes', ModelType::loadAll());
smarty_assign('models', $models);
smarty_assign('allStatuses', util_getAllStatuses());
smarty_assign('errorMessage', $errorMessage);
smarty_assign('recentLinks', RecentLink::loadForUser());
smarty_displayWithoutSkin('admin/lexemEdit.ihtml');

?>
