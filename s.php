<?php

try {

	require_once 'lib/base.php';
	OC::checkMaintenanceMode();
	OC::checkSingleUserMode();
	$file = OCP\CONFIG::getAppValue('core', 'public_files');
	if(is_null($file)) {
		header('HTTP/1.0 404 Not Found');
		exit;
	}

	// convert the token to hex, if it's base36
	if (strlen((string)$_GET['t']) != 16 && strlen((string)$_GET['t']) != 32) {
		$_GET['t'] = base_convert($_GET['t'], 36, 16);

		// the token should have leading zeroes and needs to be padded
		if (strlen((string)$_GET['t']) != 16) {
			$padding = '';
			for ($i = 0; $i < (16 - strlen((string)$_GET['t'])); $i++) {
				$padding .= '0';
			}
			$_GET['t'] = $padding . $_GET['t'];
		}
	}

	print($_GET['t']);

	OC_Util::checkAppEnabled('files_sharing');
	OC_App::loadApp('files_sharing');
	OC_User::setIncognitoMode(true);

	require_once OC_App::getAppPath('files_sharing') .'/public.php';

} catch (Exception $ex) {
	//show the user a detailed error page
	OC_Response::setStatus(OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	\OCP\Util::writeLog('remote', $ex->getMessage(), \OCP\Util::FATAL);
	OC_Template::printExceptionErrorPage($ex);
}
