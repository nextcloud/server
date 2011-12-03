<?php
//$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
OC_JSON::checkAppEnabled('files_sharing');
require_once('../lib_share.php');

$source = "/".OC_User::getUser()."/files".$_GET['source'];
$uid_shared_with = $_GET['uid_shared_with'];
$permissions = $_GET['permissions'];
OC_Share::setPermissions($source, $uid_shared_with, $permissions);

?>
