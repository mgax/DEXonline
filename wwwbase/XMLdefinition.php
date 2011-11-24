<?php

require_once("../phplib/util.php");

if(!isset($_GET['cuvant']))
	exit(0);

$cuv = mysql_escape_string($_GET['cuvant']);

if ( $cuv == "" )
	exit(0);

$word = db_getSingleValue ( "select htmlRep from Definition where lexicon = \"$cuv\" and status=0 ;") ;

echo $word ;

?>

