<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
require_once('../lib_share.php');

$source = "/".OC_User::getUser()."/files".$_GET['source'];
echo json_encode(OC_Share::getMySharedItem($source));
?>