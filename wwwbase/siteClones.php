<?php

require_once("../phplib/util.php");

# Select random definition to search.
$count = db_getArray(db_execute("select count(*) from Definition where status = 0 and length(internalRep) > 250;"));

$nr = rand(1, $count[0]);
$strnr = (string)$nr;

$type = util_getRequestParameter('t');

$word = db_getArray(db_execute("select htmlRep from Definition where status = 0 and length(internalRep) > 200 limit " . $strnr . ",1;"));

# Parse definition and create string to search
$v = explode(" ", strip_tags($word[0]));

$to_search = "\"";

# Set string to search start + end
$WORD_START = 5;
$WORD_NO = 16;
$WORD_END = $WORD_START + $WORD_NO;
for($i = $WORD_START; $i <= $WORD_END; $i++) {
	$to_search .= $v[$i] . " ";
}

$to_search .= "\"";
$to_search = str_replace(",", " ", $to_search);
$to_search = str_replace("(", " ", $to_search);
$to_search = str_replace(")", " ", $to_search);
$to_search = str_replace("[", " ", $to_search);
$to_search = str_replace("]", " ", $to_search);
$to_search = str_replace("-", " ", $to_search);
$to_search = str_replace(";", " ", $to_search);
$to_search = str_replace("◊", " ", $to_search);
$to_search = str_replace("♦", " ", $to_search);
$to_search = str_replace("<", " ", $to_search);
$to_search = str_replace(">", " ", $to_search);
$to_search = str_replace("?", " ", $to_search);
$to_search = str_replace("\\", " ", $to_search);
$to_search = str_replace("/", " ", $to_search);

$urlGoogle = "https://ajax.googleapis.com/ajax/services/search/web?v=1.0";
$apiKey = "ABQIAAAAJjSg4ig0a8tq8tf6rkzmRRRkgZ4KowWMOepZjsVjwMpJ1fhnABQTlVg9YVYaGVZuAI6SYGceehM79w";
$url = $urlGoogle . "&q=". urlencode($to_search) . "&key=" . $apiKey;

# sendRequest
# note how referer is set manually
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_REFERER, "http://www.dexonline.ro");
$body = curl_exec($ch);
curl_close($ch);

# now, process the JSON string
$json = json_decode($body);

$rezultate = $json->responseData->results;

$lista = "";
$content = "";
$messageAlert = "";
$blackList = "";

foreach($rezultate as $iter) {
	# Skip dexonline.ro from results
	if(stripos($iter->url, "dexonline.ro") == true)
		continue;
	
	$lista .= $iter ->url ." <br />";
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
smarty_assign('Results', $word[0]);

smarty_assign('JSON', "<p></p><br />" . $lista);
smarty_assign('ALERT', "<p></p><br />" . $messageAlert);
smarty_assign("CRAWL_THROUGH", "<p><b>Blacklist</b></p><br />" . $blackList);
smarty_displayCommonPageWithSkin("siteClones.ihtml");

?>