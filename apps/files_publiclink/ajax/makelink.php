<?php
$RUNTIME_NOAPPS=true; //no need to load the apps

require_once '../../../lib/base.php';

require_once '../lib_public.php';

$path=$_GET['path'];
$expire=(isset($_GET['expire']))?$_GET['expire']:0;
if($expire!==0){
	
	$expire=strtotime($expire);
}
// echo $expire;
// die();

$link=new OC_PublicLink($path,$expire);
echo $link->getToken();
?>