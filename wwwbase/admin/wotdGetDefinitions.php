<?php

class wotdGetDefinitions{
  /**
   * The query to search
   * @var string $query
   * @access protected
   * */
  protected $query;

  /**
   * Constructs the object
   * @param string $query The query to search
   * @access public
   * @return void
   **/
  public function __construct($query){
    $this->query = $query;
  }

  /**
   * Searches the definition
   * @access protected
   * @return void
   * */
  protected function doSearch() {
    $q = str_replace("'", "\\'", $this->query);
    $where = "lexicon like '{$q}%'";
    if (strlen($q) >= 3){
      $where = " lexicon like '%{$q}%'";
    }
    $where .= " order by lexicon = '{$q}', lexicon, id limit 20";
    $definitions = Model::factory('Definition')->raw_query("select * from Definition where $where", null)->find_many();
    $result = '';
    foreach ($definitions as $definition){
      $source = Source::get_by_id($definition->sourceId);
      $result .= sprintf("[%s] %s (%s) [%d]\n", $definition->lexicon, mb_substr($definition->internalRep, 0, 80), $source->shortName, $definition->id);
    }
    return $result;
  }

    /**
   * Runs the application
   * @access public
   * @return void
   * */
  public function run() {
    header('Content-Type: text/plain; charset=UTF-8');
    echo $this->doSearch();
  }


}

require_once("../../phplib/util.php");
util_assertModerator(PRIV_WOTD);
util_assertNotMirror();

if (array_key_exists('q', $_GET)){
  $app = new wotdGetDefinitions($_GET['q']);
  $app->run();
}

?>
