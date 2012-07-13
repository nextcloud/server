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

$file=ltrim($file,'/');

$parts=explode('/', $file, 2);
$app=$parts[0];
switch ($app) {
	case 'core':
		$file =  OC::$SERVERROOT .'/'. $file;
		break;
	default:
		OC_Util::checkAppEnabled($app);
		OC_App::loadApp($app);
		$file = OC_App::getAppPath($app) .'/'. $parts[1];
		break;
}
$baseuri = OC::$WEBROOT . '/remote.php/'.$service.'/';
require_once($file);
