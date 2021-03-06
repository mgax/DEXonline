<?php

/**
 * This program counts lines of code written by each author. We use this information to award coder's achievements.
 * This program counts the number of added lines, i.e. lines that start with a '+' in 'svn diff -c <revision>'.
 * Therefore, it gives credit for lines of code that may no longer exists. This is intended behavior.
 * Usage:
 *   php svnAnnotate.php -- start at revision 2 (revision 1 is a huge import from CVS and should be ignored).
 *   php svnAnnotate.php -c <checkpoint> -- resume from checkpoint file.
 * This program generates an INI (config) file. You probably want to save this over docs/codeAuthors.conf.
 **/

require_once __DIR__ . '/../phplib/util.php';

chdir(util_getRootPath());

$opts = getopt('c:');

if (array_key_exists('c', $opts)) {
  list($lastRev, $authors) = loadCheckpointFile($opts['c']);
} else {
  $lastRev = 1; // Skip the initial SVN import
  $authors = array();
}

$currentRev = OS::executeAndReturnOutput('svnversion');
$currentRev = $currentRev[0];

for ($rev = $lastRev + 1; $rev <= $currentRev; $rev++) {
  $author = getRealAuthor(getRevisionAuthor($rev));
  if ($author) {
    $lines = parseRevision($rev);
    printf("Revision %4d, author %20s, lines %5d\n", $rev, $author, $lines);
    if (array_key_exists($author, $authors)) {
      $authors[$author] += $lines;
    } else {
      $authors[$author] = $lines;
    }
  }
  arsort($authors);
  writeCheckpointFile($rev, $authors);
}

/****************************************************************/

function loadCheckpointFile($fileName) {
  $ini = parse_ini_file($fileName, true);
  if (!$ini) {
    die("Cannot load checkpoint file $fileName.\n");
  }
  if (!$ini || !array_key_exists('revision', $ini) || !array_key_exists('revision', $ini['revision']) || !array_key_exists('authors', $ini)) {
    die("$fileName does not contain a valid INI file.\n");
  }
  return array($ini['revision']['revision'], $ini['authors']);
}

function writeCheckpointFile($rev, $authors) {
  print "------------------------------------------------\n";
  print "; Code authors up to and including revision $rev\n";
  print "\n";
  print "[revision]\n";
  print "revision = $rev\n";
  print "\n";
  print "[authors]\n";
  foreach ($authors as $author => $lines) {
    print "$author = $lines\n";
  }
  print "------------------------------------------------\n";
}

function getRevisionAuthor($rev) {
  $xmlLines = OS::executeAndReturnOutput("svn log -c $rev --xml");
  $xmlString = implode('', $xmlLines);
  $xml = simplexml_load_string($xmlString);
  return (string)$xml->logentry->author;
}

function getRealAuthor($author) {
  if ($author == 'svn') {
    return 'cata'; // For a while cata committed using the svn account.
  } else {
    return $author;
  }
}

function parseRevision($rev) {
  $f = popen("svn diff -c $rev --diff-cmd=diff -x -U0", 'r');
  if (!$f) {
    die ("svn diff failed.\n");
  }
  $curFile = null;
  $linesForFile = 0;
  $totalLines = 0;
  while (($line = fgets($f)) !== false) {
    $line = rtrim($line);
    if (StringUtil::startsWith($line, 'Index: ')) {
      if (!ignoreFile($curFile)) {
        $totalLines += $linesForFile;
      }
      $linesForFile = 0;
      $curFile = substr($line, 7);
    } else if (StringUtil::startsWith($line, '+') && !StringUtil::startsWith($line, '+++')) {
      if (!$curFile) {
        die("Cannot attribute line\n");
      }
      $linesForFile++;
    }
  }
  if (!ignoreFile($curFile)) {
    $totalLines += $linesForFile;
  }
  pclose($f);
  return $totalLines;
}

function ignoreFile($fileName) {
  return (!$fileName ||
          StringUtil::startsWith($fileName, 'app/crawler_log') ||
          StringUtil::startsWith($fileName, 'app/ParsedText') ||
          StringUtil::startsWith($fileName, 'app/RawPage') ||
          StringUtil::startsWith($fileName, 'log/') ||
          StringUtil::startsWith($fileName, 'phplib/Auth/') ||
          StringUtil::startsWith($fileName, 'phplib/idiorm') ||
          StringUtil::startsWith($fileName, 'phplib/smarty/') ||
          StringUtil::startsWith($fileName, 'tools/old-patches/') ||
          StringUtil::startsWith($fileName, 'wwwbase/Crawler/crawler_log') ||
          StringUtil::startsWith($fileName, 'wwwbase/Crawler/ParsedText') ||
          StringUtil::startsWith($fileName, 'wwwbase/Crawler/RawPage') ||
          StringUtil::startsWith($fileName, 'wwwbase/elfinder-connector/elFinder.class.php') ||
          StringUtil::startsWith($fileName, 'wwwbase/elfinder-connector/elFinderConnector.class.php') ||
          StringUtil::startsWith($fileName, 'wwwbase/elfinder-connector/elFinderLogger.class.php') ||
          StringUtil::startsWith($fileName, 'wwwbase/elfinder-connector/elFinderVolume') ||
          StringUtil::startsWith($fileName, 'wwwbase/elfinder-connector/mime') ||
          StringUtil::startsWith($fileName, 'wwwbase/elfinder-connector/MySQL') ||
          StringUtil::startsWith($fileName, 'wwwbase/img/') ||
          StringUtil::startsWith($fileName, 'wwwbase/js/jq') ||
          StringUtil::startsWith($fileName, 'wwwbase/js/easyui') ||
          StringUtil::startsWith($fileName, 'wwwbase/js/elfinder') ||
          StringUtil::startsWith($fileName, 'wwwbase/js/select2.min.js') ||
          StringUtil::startsWith($fileName, 'wwwbase/stat/') ||
          StringUtil::startsWith($fileName, 'wwwbase/styles/easyui') ||
          StringUtil::startsWith($fileName, 'wwwbase/styles/elfinder') ||
          StringUtil::startsWith($fileName, 'wwwbase/styles/jq') ||
          StringUtil::startsWith($fileName, 'wwwbase/styles/lightness') ||
          StringUtil::startsWith($fileName, 'wwwbase/styles/select2/') ||
          StringUtil::startsWith($fileName, 'wwwbase/styles/smoothness/') ||
          StringUtil::startsWith($fileName, 'wwwbase/styles/ui.jq') ||
          StringUtil::endsWith($fileName, 'simple_html_dom.php')
  );
}

?>
