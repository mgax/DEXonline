<?php

require_once __DIR__ . '/../phplib/util.php';

log_scriptLog('Running purgeOldData.php');

$thirtyOneDaysAgo = time() - 31 * 24 * 3600;
$cookies = Model::factory('Cookie')->where_lt('createDate', $thirtyOneDaysAgo)->find_many();
foreach ($cookies as $cookie) {
  $cookie->delete();
}

$yesterday = time() - 24 * 3600;
$pts = Model::factory('PasswordToken')->where_lt('createDate', $yesterday)->find_many();
foreach($pts as $pt) {
  $pt->delete();
}

log_scriptLog('purgeOldData.php completed successfully (against all odds)');

?>
