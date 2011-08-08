<?php
$RUNTIME_NOAPPS=true; //no need to load the apps

require_once '../../../lib/base.php';

require_once '../lib_public.php';

$path=$_GET['path'];
$expire=0;

$link=new OC_PublicLink($path,$expire);
echo $link->getToken();
?>
