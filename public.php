<?php
$RUNTIME_NOAPPS = true;
require_once 'lib/base.php';
if (!isset($_GET['service'])) {
	header('HTTP/1.0 404 Not Found');
	exit;
}
$file = OCP\CONFIG::getAppValue('core', 'public_' . strip_tags($_GET['service']));
if(is_null($file)) {
	header('HTTP/1.0 404 Not Found');
	exit;
}

$parts=explode('/', $file, 2);
$app=$parts[0];

OC_Util::checkAppEnabled($app);
OC_App::loadApp($app);

require_once OC_App::getAppPath($app) .'/'. $parts[1];
