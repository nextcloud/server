<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
require_once('../lib_share.php');

$sources = $_GET['sources'];
$uid_shared_with = $_GET['uid_shared_with'];
$permissions = $_GET['permissions'];
foreach ($sources as $source) {
	foreach ($uid_shared_with as $uid) {
		new OC_Share($source, $uid, $permissions);
	}
}

?>