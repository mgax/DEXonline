<?php
require_once("../../phplib/util.php"); 
ini_set('max_execution_time', '3600');
util_assertModerator(PRIV_LOC);
util_assertNotMirror();
DebugInfo::disable();

$modelType = util_getRequestParameter('modelType');
$modelNumber = util_getRequestParameter('modelNumber');
$newModelNumber = util_getRequestParameter('newModelNumber');
$chooseLexems = util_getRequestParameter('chooseLexems');
$lexemIds = util_getRequestParameter('lexemId');
$cloneButton = util_getRequestParameter('cloneButton');

$errorMessages = array();

if ($cloneButton) {
  // Disallow duplicate model numbers
  $m = FlexModel::loadCanonicalByTypeNumber($modelType, $newModelNumber);
  if ($m) {
    $errorMessages[] = "Modelul $modelType$newModelNumber există deja.";
  }
  if (!$newModelNumber) {
    $errorMessages[] = "Numărul modelului nu poate fi vid.";
  }

  if (!count($errorMessages)) {
    // Clone the model
    $model = Model::factory('FlexModel')->where('modelType', $modelType)->where('number', $modelNumber)->find_one();
    $cloneModel = FlexModel::create($modelType, $newModelNumber, "Clonat după $modelType$modelNumber", $model->exponent);
    $cloneModel->save();

    // Clone the model descriptions
    $mds = Model::factory('ModelDescription')->where('modelId', $model->id)->order_by_asc('inflectionId')
      ->order_by_asc('variant')->order_by_asc('applOrder')->find_many();
    foreach ($mds as $md) {
      $newMd = Model::factory('ModelDescription')->create();
      $newMd->copyFrom($md);
      $newMd->modelId = $cloneModel->id;
      $newMd->save();
    }

    // Clone the participle model
    if ($modelType == 'V') {
      $pm = ParticipleModel::loadByVerbModel($modelNumber);
      $clonePm = Model::factory('ParticipleModel')->create();
      $clonePm->verbModel = $newModelNumber;
      $clonePm->adjectiveModel = $pm->adjectiveModel;
      $clonePm->save();
    }

    // Migrate the selected lexems.
    if ($chooseLexems && $lexemIds) {
      foreach ($lexemIds as $lexemId) {
        $l = Lexem::get_by_id($lexemId);
        $l->modelNumber = $newModelNumber;
        $l->save();
        // It is not necessary to regenerate the paradigm for now, since
        // the inflected forms are identical.
      }
    }
    util_redirect('../admin/index.php');
    exit;
  }
} else {
  $newModelNumber = $modelNumber . ".1";
}

$lexems = Lexem::loadByCanonicalModel($modelType, $modelNumber);

SmartyWrap::assign('modelType', $modelType);
SmartyWrap::assign('modelNumber', $modelNumber);
SmartyWrap::assign('newModelNumber', $newModelNumber);
SmartyWrap::assign('lexems', $lexems);
SmartyWrap::assign('errorMessage', $errorMessages);
SmartyWrap::assign('recentLinks', RecentLink::loadForUser());
SmartyWrap::assign('sectionTitle', "Clonare model: {$modelType}{$modelNumber}");
SmartyWrap::displayAdminPage('flex/cloneModel.ihtml');

?>
