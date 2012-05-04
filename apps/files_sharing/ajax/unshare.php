<?php
//$RUNTIME_NOAPPS = true;

 
OCP\JSON::checkAppEnabled('files_sharing');
require_once(OC::$APPSROOT . '/apps/files_sharing/lib_share.php');

$source = "/".OCP\USER::getUser()."/files".$_GET['source'];
$uid_shared_with = $_GET['uid_shared_with'];
OC_Share::unshare($source, $uid_shared_with);

?>
