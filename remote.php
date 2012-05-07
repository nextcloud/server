<?php
$RUNTIME_NOSETUPFS = true;
//$RUNTIME_NOAPPS = TRUE;
require_once('lib/base.php');
$path_info = $_SERVER['PATH_INFO'];
if (!$pos = strpos($path_info, '/', 1)) {
	$pos = strlen($path_info);
}
$service=substr($path_info, 1, $pos-1);
$file = OCP\CONFIG::getAppValue('core', 'remote_' . $service);
if(is_null($file)){
	header('HTTP/1.0 404 Not Found');
	exit;
}
$baseuri = OC::$WEBROOT . '/remote.php/'.$service.'/';
require_once(OC::$APPSROOT . $file);