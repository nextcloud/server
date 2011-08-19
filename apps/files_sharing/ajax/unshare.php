<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
require_once('../lib_share.php');

$source = "/".OC_User::getUser()."/files".$_GET['source'];
$uid_shared_with = $_GET['uid_shared_with'];
OC_Share::unshare($source, $uid_shared_with);

?>