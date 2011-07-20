<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
require_once('../lib_share.php');

$source = $_GET['source'];
$uid_shared_with = array($_GET['uid_shared_with']);
$permissions = $_GET['permissions'];
new OC_SHARE($source, $uid_shared_with, $permissions);

?>