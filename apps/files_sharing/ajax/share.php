<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
require_once('../lib_share.php');

$sources = explode(";", $_POST['sources']);
$uid_shared_with = $_POST['uid_shared_with'];
$permissions = $_POST['permissions'];
foreach ($sources as $source) {
	new OC_Share($source, $uid_shared_with, $permissions);
}

?>