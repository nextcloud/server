<?php

// only need filesystem apps
$RUNTIME_APPTYPES=array('filesystem','authentication');
OC_App::loadApps($RUNTIME_APPTYPES);

if($path_info == '/ampache' || $path_info == '/ampache/'){
	require_once(OC::$APPSROOT . '/apps/media/index.php');
}else{
	require_once(OC::$APPSROOT . '/apps/media/server/xml.server.php');
}
