<?php
$RUNTIME_NOAPPS=true; //no need to load the apps
$RUNTIME_NOSETUPFS=true; //don't setup the fs yet

require_once '../../lib/base.php';

require_once 'lib_public.php';

$token=$_GET['token'];
OC_PublicLink::downloadFile($token);
?>