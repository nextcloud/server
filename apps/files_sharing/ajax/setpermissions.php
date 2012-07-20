<?php
require_once(OC::$APPSROOT . '/apps/files_sharing/lib_share.php');

OCP\JSON::checkAppEnabled('files_sharing');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$source = '/'.OCP\USER::getUser().'/files'.$_POST['source'];
$uid_shared_with = $_POST['uid_shared_with'];
$permissions = $_POST['permissions'];
OC_Share::setPermissions($source, $uid_shared_with, $permissions);

OCP\JSON::success();

?>
