<?php
if($path_info == '/ampache' || $path_info == '/ampache/'){
	require_once(OC::$APPSROOT . '/apps/media/index.php');
}else{
	require_once(OC::$APPSROOT . '/apps/media/server/xml.server.php');
}
?>