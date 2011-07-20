<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
require_once('../lib_share.php');

$source = $_GET['source'];
$uid_shared_with = array($_GET['uid_shared_with']);
error_log("deleteitem called".$source.$uid_shared_with);
OC_SHARE::unshare($source, $uid_shared_with);

?>