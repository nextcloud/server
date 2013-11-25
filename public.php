<?php
$RUNTIME_NOAPPS = true;

try {

	require_once 'lib/base.php';
	OC::checkMaintenanceMode();
	OC::checkSingleUserMode();
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
	OC_User::setIncognitoMode(true);

	require_once OC_App::getAppPath($app) .'/'. $parts[1];

} catch (Exception $ex) {
	//show the user a detailed error page
	OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	\OCP\Util::writeLog('remote', $ex->getMessage(), \OCP\Util::FATAL);
	OC_Template::printExceptionErrorPage($ex);
}
