<?php
$RUNTIME_NOAPPS=true; //no need to load the apps

require_once '../../../lib/base.php';

require_once '../lib_public.php';

$token=$_GET['token'];

OC_PublicLink::delete($token);
?>