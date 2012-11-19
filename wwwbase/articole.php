<?php
require_once("../phplib/util.php");

$type = util_getRequestParameter('t');

if ($type == 'rss') {
  $articles = WikiArticle::getRss();
  $results = array();
  foreach ($articles as $a) {
    $results[] = array('title' => $a->title,
                       'description' => $a->htmlContents,
                       'pubDate' => date('D, d M Y H:i:s', $a->modDate) . ' EEST',
                       'link' => sprintf("http://%s/articol/%s", $_SERVER['HTTP_HOST'], $a->getUrlTitle()));
  }

  header("Content-type: text/xml");
  SmartyWrap::assign('rss_title', 'Articole lingvistice - DEX online');
  SmartyWrap::assign('rss_link', 'http://' . $_SERVER['HTTP_HOST'] . '/rss/articole/');
  SmartyWrap::assign('rss_description', 'Articole pe teme lingvistice de la DEX online');
  SmartyWrap::assign('rss_pubDate', date('D, d M Y H:i:s') . ' EEST');
  SmartyWrap::assign('results', $results);
  SmartyWrap::displayWithoutSkin('common/rss.ixml');
  exit;
}

SmartyWrap::assign('page_title', 'Articole lingvistice');
SmartyWrap::assign('wikiTitles', WikiArticle::loadAllTitles());
SmartyWrap::displayCommonPageWithSkin('articole.ihtml');
