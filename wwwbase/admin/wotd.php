<?php
require_once("../../phplib/util.php");
util_assertModerator(PRIV_WOTD);
util_assertNotMirror();
RecentLink::createOrUpdate('Word of the Day');

smarty_assign('sectionTitle', 'Word of the Day');
smarty_assign('allStatuses', util_getAllStatuses());
//smarty_assign('recentLinks', RecentLink::loadForUser());
smarty_addCss('jqgrid', 'autocomplete');
smarty_addJs('jquery', 'jqgrid', 'autocomplete');
smarty_displayAdminPage('admin/wotd.ihtml');
?>
