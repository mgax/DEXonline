<?php
class wotdSave{
  /**
   * The wotd identifier
   * @var int $id
   * @access protected
   * */
  protected $id;

  /**
   * The date
   * @var string $displayDate
   * @access protected
   * */
  protected $displayDate;

  /**
   * The priority
   * @var int $priority
   * @access protected
   * */
  protected $priority;

  /**
   * The reference identifier
   * @var int $refId
   * @access protected
   * */
  protected $refId;

  /**
   * The reference type
   * @var string $refType
   * @access protected
   * */
  protected $refType;

  /**
   * The image file name
   * @var string $image
   * @access protected
   **/
  protected $image;

  /**
   * The descriptiom
   * @var string $description
   * @access protected
   **/
  protected $description;

  /**
   * Constructs the object
   * @param int $id The wotd identifier
   * @param string $displayDate The display date [OPTIONAL]
   * @param int $priority The priority [OPTIONAL]
   * @param int $refId The reference identifier [OPTIONAL]
   * @param string $refType The reference type [OPTIONAL]
   * @param string $image The image file [OPTIONAL]
   * @param string $description The description [OPTIONAL]
   * @access public
   * @return void
   **/
  public function __construct($id, $displayDate = null, $priority = null, $refId = null, $refType = null, $image = null, $description = null) {
    $this->id = $id;
    $this->displayDate = $displayDate;
    $this->priority = $priority;
    $this->refId = $refId;
    $this->refType = $refType;
    $this->image = $image;
    $this->description = $description;
  }

  /**
   * Saves the data
   * @access protected
   * @return error string (empty for success)
   * */
  protected function doSave() {
    if ($this->id != null){
      $wotd = WordOfTheDay::get_by_id($this->id);
    } else {
      $wotd = Model::factory('WordOfTheDay')->create();
      $wotd->userId = session_getUserId();
    }

    $today = date('Y-m-d', time());
    $isPast = $wotd->displayDate && $wotd->displayDate < $today;

    if ($isPast && $this->displayDate != $wotd->displayDate) {
      return 'Nu puteți modifica data pentru un cuvânt al zilei deja afișat';
    }
    if (!$this->refId) {
      return 'Trebuie să alegeți o definiție';
    }

    $wotd->displayDate = $this->displayDate ? $this->displayDate : null;
    $wotd->priority = $this->priority;
    $wotd->image = $this->image;
    $wotd->description = $this->description;
    $wotd->save();

    $wotdr = WordOfTheDayRel::get_by_wotdId($wotd->id);
    if (!$wotdr) {
      $wotdr = Model::factory('WordOfTheDayRel')->create();
    }
    $wotdr->refId = $this->refId;
    $wotdr->refType = $this->refType ? $this->refType : 'Definition';
    $wotdr->wotdId = $wotd->id;
    $wotdr->save();
    return '';
  }

  /**
   * Deletes a row from the wotd table
   * @access protected
   * @return error string (empty for success)
   * */
  protected function doDelete() {
    $wotd = WordOfTheDay::get_by_id($this->id);
    if ($wotd) {
      $wotd->delete();
      return '';
    } else {
      return "Înregistrarea de șters nu a fost găsită (id={$this->id})";
    }
  }

  /**
   * Runs the application
   * @param string $oper The operation to perform (edit|del) [OPTIONAL]
   * @access public
   * @return void
   * */
  public function run($oper) {
    header('Content-Type: text/plain; charset=UTF-8');
    if ($oper == 'edit' || $oper == 'add'){
      echo $this->doSave();
    }
    else if ($oper == 'del'){
      echo $this->doDelete();
    }
  }


}

require_once("../../phplib/util.php");
util_assertModerator(PRIV_WOTD);
util_assertNotMirror();

$oper = util_getRequestParameter('oper');
$id = util_getRequestParameter('id');
$displayDate = util_getRequestParameter('displayDate');
$priority = util_getRequestParameter('priority');
$definitionId = util_getRequestParameter('definitionId');
$refType = util_getRequestParameter('refType');
$image = util_getRequestParameter('image');
$description = util_getRequestParameter('description');

switch ($oper) {
case 'edit': $app = new wotdSave($id, $displayDate, $priority, $definitionId, $refType, $image, $description); break;
case 'del': $app = new wotdSave($id); break;
case 'add': $app = new wotdSave(null, $displayDate, $priority, $definitionId, $refType, null, $description); break;
}
$app->run($oper);

?>
