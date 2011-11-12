<?php

require_once("../phplib/util.php");

# Select random definition to search.
$count = db_getSingleValue("select count(*) from Definition where status = 0 and length(internalRep) > 250;");

$nr = rand(1, $count);

$definition = db_getSingleValue("select htmlRep from Definition where status = 0 and length(internalRep) > 200 limit $nr ,1;");

# Parse definition and create string to search
$v = explode(" ", strip_tags($definition));

$to_search = "\"";

# Set string to search start + end
$WORD_START = 5;
$WORD_NO = 16;

$to_search = implode( " ",array_slice($v, $WORD_START,$WORD_NO )) ;
 
$to_search .= "\"";

$to_search = str_replace ( array( ",", "(", ")", "[", "]", "-", ";", "◊", "♦", "<", ">", "?", "\\", "/") ,
				array_pad( array(), 14 ,'') ,$to_search) ; 

$urlGoogle = "https://ajax.googleapis.com/ajax/services/search/web?v=1.0";
$apiKey = "ABQIAAAAJjSg4ig0a8tq8tf6rkzmRRRkgZ4KowWMOepZjsVjwMpJ1fhnABQTlVg9YVYaGVZuAI6SYGceehM79w";
$url = $urlGoogle . "&q=". urlencode($to_search) . "&key=" . $apiKey;


$body = util_fetchUrl($url) ;

# now, process the JSON string
$json = json_decode($body);

$rezultate = $json->responseData->results;

$listAll = "";
$content = "";
$messageAlert = "";
$blackList = "";

foreach($rezultate as $iter) {
	# Skip dexonline.ro from results
	if(stripos($iter->url, "dexonline.ro") == true)
		continue;
	
	$listAll .= $iter ->url ." <br />";
	# Search for "licenta GPL" or "dexonline.ro" in resulted page
	$content = @file_get_contents($iter->url);
	
	$poslink = stripos($content, "dexonline.ro");
	$posGPLlicenta = stripos($content, "licenta GPL");
	$posGPL = stripos($content, "GPL");

	if($poslink == false && $posGPL == false && $posGPLlicenta == false) {
		$blackList .= $iter->url . "<br />";
		$messageAlert .= "Licenta GPL sau link catre dexonline.ro negasite in site-ul "  . $iter->url . "<br /><br />";
	} else {
		$messageAlert .= "A fost gasita o mentiune catre licenta GPL sau un link catre dexonline.ro in site-ul  " . $iter->url . "<br /><br />";
	}

}

# Print Blacklist items if any

smarty_assign('page_title', 'Site Clones');
smarty_assign('definition', $definition);

smarty_assign('listAll', "<p></p><br />" . $listAll);
smarty_assign('alert', "<p></p><br />" . $messageAlert);
smarty_assign("blackList", "<p><b>Blacklist</b></p><br />" . $blackList);
smarty_displayCommonPageWithSkin("siteClones.ihtml");

?>
