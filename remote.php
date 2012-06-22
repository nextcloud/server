<?php
$RUNTIME_NOSETUPFS = true;
$RUNTIME_NOAPPS = TRUE;
require_once('lib/base.php');
if (array_key_exists('PATH_INFO', $_SERVER)){
	$path_info = $_SERVER['PATH_INFO'];
}else{
	$path_info = substr($_SERVER['REQUEST_URI'], strlen($_SERVER['SCRIPT_NAME']));
}
if ($path_info === false) {
	OC_Response::setStatus(OC_Response::STATUS_NOT_FOUND);
	exit;
}
if (!$pos = strpos($path_info, '/', 1)) {
	$pos = strlen($path_info);
}
$service=substr($path_info, 1, $pos-1);
$file = OC_AppConfig::getValue('core', 'remote_' . $service);
if(is_null($file)){
	OC_Response::setStatus(OC_Response::STATUS_NOT_FOUND);
	exit;
}
if(count(explode('/',$file)) == 3) {
	$parts=explode('/',$file);
	$app=$parts[2];
	OC_App::loadApp($app);
	$baseuri = OC::$WEBROOT . '/remote.php/'.$service.'/';
	require_once( OC::$SERVERROOT.$file);
} else {
	$parts=explode('/', $file, 4);
	$app=$parts[2];
	OC_App::loadApp($app);
	$baseuri = OC::$WEBROOT . '/remote.php/'.$service.'/';
	require_once(OC_App::getAppPath($app) .'/'. $parts[3]);
}
