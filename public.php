<?php
$RUNTIME_NOSETUPFS = true;
$RUNTIME_NOAPPS = TRUE;
require_once('lib/base.php');
$file = OCP\CONFIG::getAppValue('core', 'public_' . strip_tags($_GET['service']));
if(is_null($file)){
	header('HTTP/1.0 404 Not Found');
	exit;
}

$parts=explode('/',$file);
$app=$parts[2];
OC_App::loadApp($app);

require_once(OC::$APPSROOT . $file);
