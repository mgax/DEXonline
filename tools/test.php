<?php

require_once __DIR__ . '/../phplib/util.php';
assert_options(ASSERT_BAIL, 1);

function assertEquals($expected, $actual) {
  if ($expected != $actual) {
    print "Assertion failed.\n";
    print "  expected [$expected]\n";
    print "  actual   [$actual]\n";
    debug_print_backtrace();
    exit;
  }
}

function assertEqualArrays($expected, $actual) {
  assertEquals(count($expected), count($actual));
  for ($i = 0; $i < count($expected); $i++) {
    $elemE = each($expected);
    $elemA = each($actual);
    assertEquals($elemE[0], $elemA[0]);
    assertEquals($elemE[1], $elemA[1]);
  }
}

function assertAbbreviations($typed, $internal, $html, $sourceId) {
  assertEquals($internal, AdminStringUtil::markAbbreviations($typed, $sourceId));
  assertEquals($html, AdminStringUtil::htmlize($internal, $sourceId));
}

/********************* Tests for stringUtil.php ************************/

// Check that we've got the shorthand->Unicode mappings right
assertEquals(AdminStringUtil::shorthandToUnicode("~a"), 'ă');
assertEquals(AdminStringUtil::shorthandToUnicode("~a^a^i,s,t"), 'ăâîșț');
assertEquals(AdminStringUtil::shorthandToUnicode("'^a'^A^'a^'A"), 'ấẤấẤ');
assertEquals(AdminStringUtil::shorthandToUnicode("'~a'~A~'a~'A"), 'ắẮắẮ');
assertEquals(AdminStringUtil::shorthandToUnicode("~a~A^a^A'a'A"), 'ăĂâÂáÁ');
assertEquals(AdminStringUtil::shorthandToUnicode("`a`A:a:A"), 'àÀäÄ');
assertEquals(AdminStringUtil::shorthandToUnicode(",c,C'c'C~c~C"), 'çÇćĆčČ');
assertEquals(AdminStringUtil::shorthandToUnicode("'e'E`e`E^e^E"), 'éÉèÈêÊ');
assertEquals(AdminStringUtil::shorthandToUnicode(":e:E~e~E~g~G"), 'ëËĕĔğĞ');
assertEquals(AdminStringUtil::shorthandToUnicode("'^i'^I^'i^'I"), 'î́Î́î́Î́');
assertEquals(AdminStringUtil::shorthandToUnicode("'i'I`i`I^i^I"), 'íÍìÌîÎ');
assertEquals(AdminStringUtil::shorthandToUnicode(":i:I~i~I~n~N"), 'ïÏĭĬñÑ');
assertEquals(AdminStringUtil::shorthandToUnicode("'o'O`o`O^o^O"), 'óÓòÒôÔ');
assertEquals(AdminStringUtil::shorthandToUnicode(":o:O~o~O~r~R"), 'öÖõÕřŘ');
assertEquals(AdminStringUtil::shorthandToUnicode("~s~S,s,S,t,T"), 'šŠșȘțȚ');
assertEquals(AdminStringUtil::shorthandToUnicode("'u'U`u`U^u^U"), 'úÚùÙûÛ');
assertEquals(AdminStringUtil::shorthandToUnicode(":u:U~u~U"), 'üÜŭŬ');
assertEquals(AdminStringUtil::shorthandToUnicode("'y'Y:y:Y~z~Z"), 'ýÝÿŸžŽ');

assertEquals('acegyzACEGYZ', StringUtil::unicodeToLatin("ắčèğýžẮČÈĞÝŽ"));

assertEquals('mama', mb_strtolower('mama'));
assertEquals('mama', mb_strtolower('maMa'));
assertEquals('mama', mb_strtolower('MAmA'));
assertEquals('mamă', mb_strtolower('MAmă'));
assertEquals('mamă', mb_strtolower('MAmĂ'));
assertEquals('abcúùû', mb_strtolower('ABCÚÙÛ'));
assertEquals('ÿ', mb_strtolower('Ÿ'));

assertEquals('MAMA', mb_strtoupper('MAMA'));
assertEquals('MAMA', mb_strtoupper('MAmA'));
assertEquals('MAMA', mb_strtoupper('MAmA'));
assertEquals('MAMĂ', mb_strtoupper('MamĂ'));
assertEquals('MAMĂ', mb_strtoupper('maMă'));
assertEquals('ABCÚÙÛ', mb_strtoupper('abcúùû'));
assertEquals('Ÿ', mb_strtoupper('ÿ'));

// Check that we're using the right encoding
assertEquals(mb_strlen('íÍìÌîÎ'), 6);
assertEquals(mb_substr('íÍìÌîÎ', 3, 2), 'Ìî');

// Test string reversal
assertEquals('cba', StringUtil::reverse('abc'));
assertEquals('țșîâă', StringUtil::reverse('ăâîșț'));
assertEquals('ȚȘÎÂĂ', StringUtil::reverse('ĂÂÎȘȚ'));

// Check suffix removals
assertEquals(AdminStringUtil::removeKnownSuffixes(''), '');
assertEquals(AdminStringUtil::removeKnownSuffixes('mama'), 'mama');
assertEquals(AdminStringUtil::removeKnownSuffixes('farmaciei'), 'farmacie');
assertEquals(AdminStringUtil::removeKnownSuffixes('dealului'), 'deal');
assertEquals(AdminStringUtil::removeKnownSuffixes('dealul'), 'deal');
assertEquals(AdminStringUtil::removeKnownSuffixes('dealuri'), 'deal');
assertEquals(AdminStringUtil::removeKnownSuffixes('dealurilor'), 'deal');
assertEquals(AdminStringUtil::removeKnownSuffixes('copacilor'), 'copac');
assertEquals(AdminStringUtil::removeKnownSuffixes('bogată'), 'bogat');
assertEquals(AdminStringUtil::removeKnownSuffixes('bogate'), 'bogat');

assertEquals(AdminStringUtil::getLastWord(''), '');
assertEquals(AdminStringUtil::getLastWord('foo'), 'foo');
assertEquals(AdminStringUtil::getLastWord('foo bar'), 'bar');
assertEquals(AdminStringUtil::getLastWord('foo bar (@1@)'), 'bar');
assertEquals(AdminStringUtil::getLastWord('foo bar õÕ (@1@)'), 'õÕ');

assertEquals(AdminStringUtil::internalizeAllReferences('|foo|bar|'), '|foo|bar|');
assertEquals(AdminStringUtil::internalizeAllReferences('|foo moo|bar|'), '|foo moo|bar|');
assertEquals(AdminStringUtil::internalizeAllReferences('|foo moo (@1@)|bar|'),
	     '|foo moo (@1@)|bar|');
assertEquals(AdminStringUtil::internalizeAllReferences('|foo||'), '|foo|foo|');
assertEquals(AdminStringUtil::internalizeAllReferences('|foo moo||'), '|foo moo|moo|');
assertEquals(AdminStringUtil::internalizeAllReferences('|foo moo (@1@)||'),
	     '|foo moo (@1@)|moo|');
assertEquals(AdminStringUtil::internalizeAllReferences('|dealului|-|'), '|dealului|deal|');
assertEquals(AdminStringUtil::internalizeAllReferences('|vax albina|-|'),
	     '|vax albina|vax albina|');
assertEquals(AdminStringUtil::internalizeAllReferences('text 1 |foo|| text 2 |dealul|-| text 3'),
	     'text 1 |foo|foo| text 2 |dealul|deal| text 3');

assertEquals('<a class="ref" href="/definitie/y">x</a>', AdminStringUtil::convertReferencesToHtml('|x|y|'));
assertEquals('<a class="ref" href="/definitie/î">ă</a>', AdminStringUtil::convertReferencesToHtml('|ă|î|'));
assertEquals('<a class="ref" href="/definitie/ab cd ef">ab cd ef</a>', AdminStringUtil::convertReferencesToHtml('|ab cd ef|ab cd ef|'));
assertEquals('<a class="ref" href="/definitie/ab cd ef (@1@)">ab cd ef (@1@)</a>', AdminStringUtil::convertReferencesToHtml('|ab cd ef (@1@)|ab cd ef (@1@)|'));
assertEquals('<a class="ref" href="/definitie/ab cd õÕ (@1@)">ab cd õÕ (@1@)</a>', AdminStringUtil::convertReferencesToHtml('|ab cd õÕ (@1@)|ab cd õÕ (@1@)|'));
assertEquals('<a class="ref" href="/definitie/y">x</a> foobar <a class="ref" href="/definitie/t">z</a>', AdminStringUtil::convertReferencesToHtml('|x|y| foobar |z|t|'));

assertEquals(AdminStringUtil::insertSuperscripts("copil^{+123}. copil_{-123}----"),
	     "copil<sup>+123</sup>. copil<sub>-123</sub>----");
assertEquals(AdminStringUtil::insertSuperscripts("copil^i^2"), "copil^i<sup>2</sup>");

assertEquals('xxx &#x25ca; &#x2666; < &#x2013; > yyy',
             AdminStringUtil::minimalInternalToHtml('xxx * ** < - > yyy'));

assertEquals('„abc”„”',
	     AdminStringUtil::internalToHtml('"abc"""', FALSE));
assertEquals('<b><i>bold and italic</i> bold only</b> regular.',
	     AdminStringUtil::internalToHtml('@$bold and italic$ bold only@ regular.',
				  FALSE));
assertEquals('<@bold, but inside tag@>',
	     AdminStringUtil::internalToHtml('<@bold, but inside tag@>', FALSE));
assertEquals('foo &lt; <i>bar</i>',
	     AdminStringUtil::internalToHtml('foo &lt; $bar$', FALSE));
assertEquals('<span class="spaced">cățel</span>', AdminStringUtil::internalToHtml('%cățel%', FALSE));
assertEquals('foo <span class="spaced">bar &amp;</span> bib', AdminStringUtil::internalToHtml('foo %bar &amp;% bib', FALSE));
assertEquals('<span class="spaced">unu, doi</span>', AdminStringUtil::internalToHtml('%unu, doi%', FALSE));
assertEquals('<span class="spaced">ab <b>cd</b></span>', AdminStringUtil::internalToHtml('%ab @cd@%', FALSE));
assertEquals("okely\ndokely",
	     AdminStringUtil::internalToHtml("okely\ndokely", FALSE));
assertEquals("okely<br/>\ndokely",
	     AdminStringUtil::internalToHtml("okely\ndokely", TRUE));

assertEquals("@FILLER@ #adj. dem.# (antepus), art.", AdminStringUtil::markAbbreviations("@FILLER@ adj. dem. (antepus), art.", 1));
assertEquals("@FILLER@ #adj. dem.# (antepus), art.", AdminStringUtil::markAbbreviations("@FILLER@ adj. dem. (antepus), art.", 1));
assertEquals("@FILLER@ #loc. adv. și adj.# @MORE FILLER@", AdminStringUtil::markAbbreviations("@FILLER@ loc. adv. și adj. @MORE FILLER@", 1));
assertEquals("@FILLER@ #arg.# șarg. catarg. ăarg. țarg. @FILLER@", AdminStringUtil::markAbbreviations("@FILLER@ arg. șarg. catarg. ăarg. țarg. @FILLER@", 1));
assertEquals("@FILLER@ #et. nec.#", AdminStringUtil::markAbbreviations("@FILLER@ et. nec.", 1));
assertEquals("@FILLER@ #Înv.# @MORE FILLER@", AdminStringUtil::markAbbreviations("@FILLER@ Înv. @MORE FILLER@", 1)); // Unicode uppercase
assertEquals("@FILLER@ #art. hot.# @FILLER@", AdminStringUtil::markAbbreviations("@FILLER@ art.hot. @FILLER@", 1));
assertEquals("@FILLER@ #art. hot.# @FILLER@", AdminStringUtil::markAbbreviations("@FILLER@ #art. hot.# @FILLER@", 1));
assertEquals("FOO ornit. BAR", AdminStringUtil::markAbbreviations("FOO ornit. BAR", 99)); // Inexistent source
assertEquals("FOO BAR", AdminStringUtil::markAbbreviations("FOO BAR", 1)); // No abbreviations
assertEquals("FOO dat. BAR", AdminStringUtil::markAbbreviations("FOO dat. BAR", 1)); // Ambiguous abbreviations
// A more complex example which also reports ambiguous matches
$ambiguousMatches = array();
assertEquals("FOO dat. #arh.# #loc. adv.# BAR", AdminStringUtil::markAbbreviations("FOO dat. arh. loc. adv. BAR", 1, $ambiguousMatches));
assertEquals(1, count($ambiguousMatches));
assertEqualArrays(array('abbrev' => 'dat.', 'position' => 4, 'length' => 4), $ambiguousMatches[0]);

$ambiguousMatches = array();
assertEquals("FOO s-a dus BAR", AdminStringUtil::markAbbreviations("FOO s-a dus BAR", 32, $ambiguousMatches));
assertEquals(0, count($ambiguousMatches));

assertEquals("FOO <abbr class=\"abbrev\" title=\"farmacie; farmacologie\">farm.</abbr> BAR", AdminStringUtil::htmlize("FOO #farm.# BAR", 1)); /** Semicolon in abbreviation **/
assertEquals("FOO <abbr class=\"abbrev\" title=\"substantiv masculin\">s. m.</abbr> BAR", AdminStringUtil::htmlize("FOO #s. m.# BAR", 1));
$errors = array();
assertEquals("FOO <abbr class=\"abbrev\" title=\"abreviere necunoscută\">brrb. ghhg.</abbr> BAR", AdminStringUtil::htmlize("FOO #brrb. ghhg.# BAR", 1, $errors));
assertEqualArrays(array(0 => 'Abreviere necunoscută: «brrb. ghhg.». Verificați că după fiecare punct există un spațiu.'), $errors);

$internalRep = '@MÁRE^2,@ $mări,$ #s. f.# Nume generic dat vastelor întinderi de apă stătătoare, adânci și sărate, de pe suprafața |Pământului|Pământ|, care de obicei sunt unite cu |oceanul|ocean| printr-o |strâmtoare|strâmtoare|; parte a oceanului de lângă |țărm|țărm|; $#p. ext.#$ ocean. * #Expr.# $Marea cu sarea$ = mult, totul; imposibilul. $A vântura mări și țări$ = a călători mult. $A încerca marea cu degetul$ = a face o încercare, chiar dacă șansele de reușită sunt minime. $Peste (nouă) mări și (nouă) țări$ = foarte departe. ** #Fig.# Suprafață vastă; întindere mare; imensitate. ** #Fig.# Mulțime (nesfârșită), cantitate foarte mare. - Lat. @mare, -is.@';
assertEquals($internalRep,
             AdminStringUtil::internalizeDefinition('@M\'ARE^2@, $m~ari$, s. f. Nume generic dat vastelor ^intinderi de ap~a st~at~atoare, ad^anci ,si s~arate, de pe suprafa,ta |P~am^antului|-|, care de obicei sunt unite cu |oceanul|-| printr-o |str^amtoare||; parte a oceanului de l^ang~a |,t~arm||; $p.ext.$ ocean. * Expr. $Marea cu sarea$ = mult, totul; imposibilul. $A v^antura m~ari ,si ,t~ari$ = a c~al~atori mult. $A ^incerca marea cu degetul$ = a face o ^incercare, chiar dac~a ,sansele de reu,sit~a sunt minime. $Peste (nou~a) m~ari ,si (nou~a) ,t~ari$ = foarte departe. ** Fig. Suprafa,t~a vast~a; ^intindere mare; imensitate. ** Fig. Mul,time (nesf^ar,sit~a), cantitate foarte mare. - Lat. @mare, -is@.', 1));
assertEquals('<b>MÁRE<sup>2</sup>,</b> <i>mări,</i> <abbr class="abbrev" title="substantiv feminin">s. f.</abbr> Nume generic dat vastelor întinderi de apă stătătoare, adânci și sărate, de pe suprafața <a class="ref" href="/definitie/Pământ">Pământului</a>, care de obicei sunt unite cu <a class="ref" href="/definitie/ocean">oceanul</a> printr-o <a class="ref" href="/definitie/strâmtoare">strâmtoare</a>; parte a oceanului de lângă <a class="ref" href="/definitie/țărm">țărm</a>; <i><abbr class="abbrev" title="prin extensiune">p. ext.</abbr></i> ocean. &#x25ca; <abbr class="abbrev" title="expresie">Expr.</abbr> <i>Marea cu sarea</i> = mult, totul; imposibilul. <i>A vântura mări și țări</i> = a călători mult. <i>A încerca marea cu degetul</i> = a face o încercare, chiar dacă șansele de reușită sunt minime. <i>Peste (nouă) mări și (nouă) țări</i> = foarte departe. &#x2666; <abbr class="abbrev" title="figurat">Fig.</abbr> Suprafață vastă; întindere mare; imensitate. &#x2666; <abbr class="abbrev" title="figurat">Fig.</abbr> Mulțime (nesfârșită), cantitate foarte mare. &#x2013; Lat. <b>mare, -is.</b>',
             AdminStringUtil::htmlize($internalRep, 1));
assertEquals($internalRep, AdminStringUtil::internalizeDefinition($internalRep, 1));

// Test various capitalization combos with abbreviations
// - When internalizing the definition, preserve the capitalization if the defined abbreviation is capitalized;
//   otherwise, capitalize the first letter (if necessary) and convert the rest to lowercase
// - If the defined abbreviation contains capital letters, then only match text with identical capitalization
// - If the defined abbreviation does not contain capital letters, then match text regardless of capitalization
// - When htmlizing the definition, use the expansion from the abbreviation that best matches the case.
assertAbbreviations("FILLER adv. FILLER", "FILLER #adv.# FILLER", "FILLER <abbr class=\"abbrev\" title=\"adverb\">adv.</abbr> FILLER", 1);
assertAbbreviations("FILLER Adv. FILLER", "FILLER #Adv.# FILLER", "FILLER <abbr class=\"abbrev\" title=\"adverb\">Adv.</abbr> FILLER", 1);
assertAbbreviations("FILLER BWV FILLER", "FILLER #BWV# FILLER", "FILLER <abbr class=\"abbrev\" title=\"Bach-Werke-Verzeichnis\">BWV</abbr> FILLER", 32);
assertAbbreviations("FILLER bwv FILLER", "FILLER bwv FILLER", "FILLER bwv FILLER", 32);
assertAbbreviations("FILLER bWv FILLER", "FILLER bWv FILLER", "FILLER bWv FILLER", 32);
assertAbbreviations("FILLER ed. FILLER", "FILLER #ed.# FILLER", "FILLER <abbr class=\"abbrev\" title=\"ediție, editat\">ed.</abbr> FILLER", 32);
assertAbbreviations("FILLER Ed. FILLER", "FILLER #Ed.# FILLER", "FILLER <abbr class=\"abbrev\" title=\"Editura\">Ed.</abbr> FILLER", 32);
assertAbbreviations("FILLER ED. FILLER", "FILLER #Ed.# FILLER", "FILLER <abbr class=\"abbrev\" title=\"Editura\">Ed.</abbr> FILLER", 32);

// Abbreviation includes special characters
assertAbbreviations("FILLER RRHA, TMC FILLER", "FILLER #RRHA, TMC# FILLER",
		    "FILLER <abbr class=\"abbrev\" title=\"Revue Roumaine d'Histoire de l'Art, série Théâtre, Musique, Cinématographie\">RRHA, TMC</abbr> FILLER", 32);
assertAbbreviations("FILLER adj. interog.-rel. FILLER", "FILLER #adj. interog.-rel.# FILLER",
                    "FILLER <abbr class=\"abbrev\" title=\"adjectiv interogativ-relativ\">adj. interog.-rel.</abbr> FILLER", 1);

// Abbreviation is not delimited by spaces
assertAbbreviations("AGNUS DEI", "AGNUS DEI", "AGNUS DEI", 32);

assertEquals('@MÁRE^2,@ $mări,$ s.f.', AdminStringUtil::migrateFormatChars('@MÁRE^2@, $mări$, s.f.'));
assertEquals('@$%spaced% text$@', AdminStringUtil::migrateFormatChars('@$ % spaced % text $@'));
assertEquals('40\% dolomite', AdminStringUtil::migrateFormatChars('40\% dolomite'));
assertEquals('40 %dolomite%', AdminStringUtil::migrateFormatChars('40% dolomite%'));

assertEquals('cățel', AdminStringUtil::internalizeWordName("C~A,t'EL"));
assertEquals('ă', AdminStringUtil::internalizeWordName("~~A~~!@#$%^&*()123456790"));

assertEquals('casă', AdminStringUtil::removeAccents('cásă'));

assertEquals('mama', StringUtil::cleanupQuery("'mama'"));
assertEquals('mama', StringUtil::cleanupQuery('"mama"'));
assertEquals('aăbcdef', StringUtil::cleanupQuery("aăbc<mamă foo bar>def"));
assertEquals('AĂBCDEF', StringUtil::cleanupQuery("AĂBC<MAMĂ FOO BAR>DEF"));
assertEquals('a~abcdef', StringUtil::cleanupQuery("a~abc<mam~a foo bar>def"));
assertEquals('a~ABcdef', StringUtil::cleanupQuery("a~ABc<mam~a foo bar>def"));
assertEquals('1234', StringUtil::cleanupQuery('12&qweasd;34'));

assert(StringUtil::hasDiacritics('mamă'));
assert(!StringUtil::hasDiacritics('mama'));

$def = Model::factory('Definition')->create();
$def->sourceId = 1;
$def->internalRep = 'abcd';
assertEquals('abcd', AdminStringUtil::extractLexicon($def));
$def->internalRep = 'wxyz';
assertEquals('wxyz', AdminStringUtil::extractLexicon($def));
$def->internalRep = 'mamă';
assertEquals('mamă', AdminStringUtil::extractLexicon($def));

assert(StringUtil::hasRegexp('asd[0-9]'));
assert(!StringUtil::hasRegexp('ăâîșț'));
assert(StringUtil::hasRegexp('cop?l'));

assertEquals("like 'cop%l'", StringUtil::dexRegexpToMysqlRegexp('cop*l'));
assertEquals("like 'cop_l'", StringUtil::dexRegexpToMysqlRegexp('cop?l'));
assertEquals("rlike '^(cop[a-z]l)$'", StringUtil::dexRegexpToMysqlRegexp('cop[a-z]l'));
assertEquals("rlike '^(cop[^a-z]l)$'", StringUtil::dexRegexpToMysqlRegexp('cop[^a-z]l'));
assertEquals("rlike '^(cop[â-z]l)$'", StringUtil::dexRegexpToMysqlRegexp('cop[â-z]l'));
assertEquals("rlike '^(cop[â-z]l.*)$'", StringUtil::dexRegexpToMysqlRegexp('cop[â-z]l*'));

assertEqualArrays(array(0, 0, 0), StringUtil::analyzeQuery('mama'));
assertEqualArrays(array(1, 0, 0), StringUtil::analyzeQuery('mamă'));
assertEqualArrays(array(0, 1, 0), StringUtil::analyzeQuery('cop?l'));
assertEqualArrays(array(0, 1, 0), StringUtil::analyzeQuery('cop[c-g]l'));
assertEqualArrays(array(1, 1, 0), StringUtil::analyzeQuery('căț[c-g]l'));
assertEqualArrays(array(0, 0, 1), StringUtil::analyzeQuery('1234567'));

assertEquals('&#x25;&#x7e;&#x24;&#x40;&#x27;',
             AdminStringUtil::xmlizeRequired('\\%\\~\\$\\@\\\''));
assertEquals('&lt;&gt;&amp;',
             AdminStringUtil::xmlizeRequired('<>&'));

$t = FlexStringUtil::extractTransforms('arde', 'arzând', 0);
assertEquals(4, count($t));
assertEquals('d', $t[0]->transfFrom);
assertEquals('z', $t[0]->transfTo);
assertEquals('e', $t[1]->transfFrom);
assertEquals('', $t[1]->transfTo);
assertEquals('', $t[2]->transfFrom);
assertEquals('ând', $t[2]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[3]);

$t = FlexStringUtil::extractTransforms('frumos', 'frumoasă', 0);
assertEquals(3, count($t));
assertEquals('o', $t[0]->transfFrom);
assertEquals('oa', $t[0]->transfTo);
assertEquals('', $t[1]->transfFrom);
assertEquals('ă', $t[1]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[2]);

$t = FlexStringUtil::extractTransforms('fi', 'sunt', 0);
assertEquals(2, count($t));
assertEquals('fi', $t[0]->transfFrom);
assertEquals('sunt', $t[0]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[1]);

$t = FlexStringUtil::extractTransforms('abil', 'abilul', 0);
assertEquals(2, count($t));
assertEquals('', $t[0]->transfFrom);
assertEquals('ul', $t[0]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[1]);

$t = FlexStringUtil::extractTransforms('alamă', 'alămuri', 0);
assertEquals(4, count($t));
assertEquals('a', $t[0]->transfFrom);
assertEquals('ă', $t[0]->transfTo);
assertEquals('ă', $t[1]->transfFrom);
assertEquals('', $t[1]->transfTo);
assertEquals('', $t[2]->transfFrom);
assertEquals('uri', $t[2]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[3]);

$t = FlexStringUtil::extractTransforms('sămânță', 'semințe', 0);
assertEquals(4, count($t));
assertEquals('ă', $t[0]->transfFrom);
assertEquals('e', $t[0]->transfTo);
assertEquals('â', $t[1]->transfFrom);
assertEquals('i', $t[1]->transfTo);
assertEquals('ă', $t[2]->transfFrom);
assertEquals('e', $t[2]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[3]);

$t = FlexStringUtil::extractTransforms('deșert', 'deșartelor', 0);
assertEquals(3, count($t));
assertEquals('e', $t[0]->transfFrom);
assertEquals('a', $t[0]->transfTo);
assertEquals('', $t[1]->transfFrom);
assertEquals('elor', $t[1]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[2]);

$t = FlexStringUtil::extractTransforms('cumătră', 'cumetrelor', 0);
assertEquals(4, count($t));
assertEquals('ă', $t[0]->transfFrom);
assertEquals('e', $t[0]->transfTo);
assertEquals('ă', $t[1]->transfFrom);
assertEquals('e', $t[1]->transfTo);
assertEquals('', $t[2]->transfFrom);
assertEquals('lor', $t[2]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[3]);

$t = FlexStringUtil::extractTransforms('crăpa', 'crapă', 0);
assertEquals(3, count($t));
assertEquals('ă', $t[0]->transfFrom);
assertEquals('a', $t[0]->transfTo);
assertEquals('a', $t[1]->transfFrom);
assertEquals('ă', $t[1]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[2]);

$t = FlexStringUtil::extractTransforms('stradă', 'străzi', 0);
assertEquals(4, count($t));
assertEquals('a', $t[0]->transfFrom);
assertEquals('ă', $t[0]->transfTo);
assertEquals('d', $t[1]->transfFrom);
assertEquals('z', $t[1]->transfTo);
assertEquals('ă', $t[2]->transfFrom);
assertEquals('i', $t[2]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[3]);

$t = FlexStringUtil::extractTransforms('frumos', 'frumoasă', 0);
assertEquals(3, count($t));
assertEquals('o', $t[0]->transfFrom);
assertEquals('oa', $t[0]->transfTo);
assertEquals('', $t[1]->transfFrom);
assertEquals('ă', $t[1]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[2]);

$t = FlexStringUtil::extractTransforms('groapă', 'gropilor', 0);
assertEquals(4, count($t));
assertEquals('a', $t[0]->transfFrom);
assertEquals('', $t[0]->transfTo);
assertEquals('ă', $t[1]->transfFrom);
assertEquals('i', $t[1]->transfTo);
assertEquals('', $t[2]->transfFrom);
assertEquals('lor', $t[2]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[3]);

$t = FlexStringUtil::extractTransforms('căpăta', 'capăt', 0);
assertEquals(4, count($t));
assertEquals('ă', $t[0]->transfFrom);
assertEquals('a', $t[0]->transfTo);
assertEquals('ă', $t[1]->transfFrom);
assertEquals('ă', $t[1]->transfTo);
assertEquals('a', $t[2]->transfFrom);
assertEquals('', $t[2]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[3]);

$t = FlexStringUtil::extractTransforms('răscrăcăra', 'răscracăr', 0);
assertEquals(4, count($t));
assertEquals('ă', $t[0]->transfFrom);
assertEquals('a', $t[0]->transfTo);
assertEquals('ă', $t[1]->transfFrom);
assertEquals('ă', $t[1]->transfTo);
assertEquals('a', $t[2]->transfFrom);
assertEquals('', $t[2]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[3]);

$t = FlexStringUtil::extractTransforms('răscrăcăra', 'rascrăcăr', 0);
assertEquals(5, count($t));
assertEquals('ă', $t[0]->transfFrom);
assertEquals('a', $t[0]->transfTo);
assertEquals('ă', $t[1]->transfFrom);
assertEquals('ă', $t[1]->transfTo);
assertEquals('ă', $t[2]->transfFrom);
assertEquals('ă', $t[2]->transfTo);
assertEquals('a', $t[3]->transfFrom);
assertEquals('', $t[3]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[4]);

$t = FlexStringUtil::extractTransforms('foo', 'foo', 0);
assertEquals(2, count($t));
assertEquals('', $t[0]->transfFrom);
assertEquals('', $t[0]->transfTo);
assertEquals(UNKNOWN_ACCENT_SHIFT, $t[1]);

// Try some accents
$t = FlexStringUtil::extractTransforms("căpăt'a", "c'apăt", 0);
assertEquals(5, count($t));
assertEquals('ă', $t[0]->transfFrom);
assertEquals('a', $t[0]->transfTo);
assertEquals('ă', $t[1]->transfFrom);
assertEquals('ă', $t[1]->transfTo);
assertEquals('a', $t[2]->transfFrom);
assertEquals('', $t[2]->transfTo);
assertEquals('a', $t[3]);
assertEquals(2, $t[4]);

$t = FlexStringUtil::extractTransforms("c'ăpăta", "cap'ăt", 0);
assertEquals(5, count($t));
assertEquals('ă', $t[0]->transfFrom);
assertEquals('a', $t[0]->transfTo);
assertEquals('ă', $t[1]->transfFrom);
assertEquals('ă', $t[1]->transfTo);
assertEquals('a', $t[2]->transfFrom);
assertEquals('', $t[2]->transfTo);
assertEquals('ă', $t[3]);
assertEquals(1, $t[4]);

$t = FlexStringUtil::extractTransforms("n'ailon", "nailo'ane", 0);
assertEquals(4, count($t));
assertEquals('o', $t[0]->transfFrom);
assertEquals('oa', $t[0]->transfTo);
assertEquals('', $t[1]->transfFrom);
assertEquals('e', $t[1]->transfTo);
assertEquals('a', $t[2]);
assertEquals(2, $t[3]);

$t = FlexStringUtil::extractTransforms("n'ailon", "n'ailonului", 0);
assertEquals(2, count($t));
assertEquals('', $t[0]->transfFrom);
assertEquals('ului', $t[0]->transfTo);
assertEquals(NO_ACCENT_SHIFT, $t[1]);

assertEquals(1, FlexStringUtil::countVowels('abc'));
assertEquals(2, FlexStringUtil::countVowels('abcde'));
assertEquals(8, FlexStringUtil::countVowels('aeiouăâî'));

assertEquals('cásă', AdminStringUtil::internalize("c'as~a", false));
assertEquals("c'asă", AdminStringUtil::internalize("c'as~a", true));

assertEquals("cas'ă", FlexStringUtil::placeAccent("casă", 1, ''));
assertEquals("c'asă", FlexStringUtil::placeAccent("casă", 2, ''));
assertEquals("casă", FlexStringUtil::placeAccent("casă", 3, ''));
assertEquals("ap'ă", FlexStringUtil::placeAccent("apă", 1, ''));
assertEquals("'apă", FlexStringUtil::placeAccent("apă", 2, ''));
assertEquals("apă", FlexStringUtil::placeAccent("apă", 3, ''));
assertEquals("'a", FlexStringUtil::placeAccent("a", 1, ''));
assertEquals("a", FlexStringUtil::placeAccent("a", 2, ''));

assertEquals("șa'ibă", FlexStringUtil::placeAccent("șaibă", 2, ''));
assertEquals("ș'aibă", FlexStringUtil::placeAccent("șaibă", 3, ''));
assertEquals("ș'aibă", FlexStringUtil::placeAccent("șaibă", 2, 'a'));
assertEquals("ș'aibă", FlexStringUtil::placeAccent("șaibă", 3, 'a'));
assertEquals("șa'ibă", FlexStringUtil::placeAccent("șaibă", 2, 'i'));
assertEquals("șa'ibă", FlexStringUtil::placeAccent("șaibă", 3, 'i'));

assertEquals("unfuckingbelievable", FlexStringUtil::insert("unbelievable", "fucking", 2));
assertEquals("abcdef", FlexStringUtil::insert("cdef", "ab", 0));
assertEquals("abcdef", FlexStringUtil::insert("abcd", "ef", 4));

assertEquals('mamă      ', AdminStringUtil::padRight('mamă', 10));
assertEquals('mama      ', AdminStringUtil::padRight('mama', 10));
assertEquals('ăâîșț   ', AdminStringUtil::padRight('ăâîșț', 8));
assertEquals('ăâîșț', AdminStringUtil::padRight('ăâîșț', 5));
assertEquals('ăâîșț', AdminStringUtil::padRight('ăâîșț', 3));

assertEqualArrays(array('c', 'a', 'r'), AdminStringUtil::unicodeExplode('car'));
assertEqualArrays(array('ă', 'a', 'â', 'ș', 'ț'),
                  AdminStringUtil::unicodeExplode('ăaâșț'));

assertEqualArrays(array(1, 5, 10),
                  util_intersectArrays(array(1, 3, 5, 7, 9, 10),
                                       array(1, 2, 4, 5, 6, 8, 10)));
assertEqualArrays(array(),
                  util_intersectArrays(array(2, 4, 6, 8),
                                       array(1, 3, 5, 7)));

assert(!Lock::release('test'));
assert(!Lock::exists('test'));
assert(Lock::acquire('test'));
assert(Lock::exists('test'));
assert(!Lock::acquire('test'));
assert(Lock::release('test'));
assert(!Lock::exists('test'));
assert(!Lock::release('test'));

assertEquals(0, util_findSnippet(array(array(1, 2, 10))));
assertEquals(1, util_findSnippet(array(array(1, 2, 10),
                                       array(5, 6, 9))));
assertEquals(2, util_findSnippet(array(array(1, 2, 10),
                                       array(5, 6, 8))));
assertEquals(4, util_findSnippet(array(array(1, 2, 10),
                                       array(6, 20),
                                       array(8, 15))));

assertEquals('$abc$ @def@', AdminStringUtil::formatLexem('$abc$ @def@')); // This is intentional -- lexem formatting is very lenient.
assertEquals("m'amă m'are", AdminStringUtil::formatLexem("m'am~a máre  "));

?>
