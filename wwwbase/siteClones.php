<?php

require_once("../phplib/util.php");

$count = db_getArray(db_execute("select count(*) from Definition where status = 0 and length(internalRep) > 250;"));

#echo $count[0];
$nr = rand(1, $count[0]);

#echo $nr;
$strnr = (string)$nr;

$type = util_getRequestParameter('t');

#$word = db_getArray(db_execute("select htmlRep from Definition where status = 0 and id = " . $strnr));

$word = db_getArray(db_execute("select htmlRep from Definition where status = 0 and length(internalRep) > 200 limit " . $strnr . ",1;"));

$v = explode(" ", strip_tags($word[0]));
#$v = preg_split("\b", strip_tags($word[0]));

$no = rand(1, 7);
$len = count($v);
$to_search = "\"";

smarty_assign('page_title', 'New Stuff');
smarty_assign('Results', $word[0]);
smarty_assign('Test', "<p></p><p></p>" . $v[$no]);

for($i = 5; $i <= 20; $i++) {
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

smarty_assign('Lungime', "<p></p>" . $len . "<br /> <br /> <br /> " . $to_search);

$url = "https://ajax.googleapis.com/ajax/services/search/web?v=1.0&"
    . "q=". urlencode($to_search) . "&key=ABQIAAAAJjSg4ig0a8tq8tf6rkzmRRRkgZ4KowWMOepZjsVjwMpJ1fhnABQTlVg9YVYaGVZuAI6SYGceehM79w";

// sendRequest
// note how referer is set manually
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_REFERER, "http://www.dexonline.ro");
$body = curl_exec($ch);
curl_close($ch);

// now, process the JSON string
$json = json_decode($body);

$rezultate = $json->responseData->results;

$lista = "";
$content = "";
$messageAlert = "";

foreach($rezultate as $iter) {
	$lista .= $iter ->url ." <br />";
	$content .= $iter -> content . "<br /><br />";
		
#	$poslink = strpos($content, "dexonline.ro");
	$posGPL = strpos($content, "licenta GPL");

	if($poslink == false && $posGPL == false) {
		$messageAlert .= "Licenta GPL sau link catre dexonline.ro negasite in site-ul "  . $iter->url . "<br /><br />";
	} else {
		$messageAlert .= "A fost gasita o mentiune catre licenta GPL sau un link catre dexonline.ro in site-ul  " . $iter->url . "<br /><br />";
	}

}

$toks = explode($content, " ");


smarty_assign('JSON', "<p></p><br />" . $lista);
smarty_assign('CRAWL_THROUGH', "<p></p><br />" . $content);
smarty_assign('ALERT', "<p></p><br />" . $messageAlert);
#smarty_assign('JSON', "<p></p><br />" . $json ->responseData -> results[0] -> url );
smarty_displayCommonPageWithSkin("new_stuff.ihtml");

?>
