<?php

if (!isset($_)) { //also provide standalone error page
	require_once __DIR__ . '/../../../lib/base.php';
	require_once __DIR__ . '/../lib/crypt.php';

	OC_JSON::checkAppEnabled('files_encryption');
	OC_App::loadApp('files_encryption');

	$l = \OC::$server->getL10N('files_encryption');

	if (isset($_GET['errorCode'])) {
		$errorCode = $_GET['errorCode'];
		switch ($errorCode) {
			case \OCA\Files_Encryption\Crypt::ENCRYPTION_NOT_INITIALIZED_ERROR:
				$errorMsg = $l->t('Encryption app not initialized! Maybe the encryption app was re-enabled during your session. Please try to log out and log back in to initialize the encryption app.');
				break;
			case \OCA\Files_Encryption\Crypt::ENCRYPTION_PRIVATE_KEY_NOT_VALID_ERROR:
				$theme = new OC_Defaults();
				$errorMsg = $l->t('Your private key is not valid! Likely your password was changed outside of %s (e.g. your corporate directory). You can update your private key password in your personal settings to recover access to your encrypted files.', array($theme->getName()));
				break;
			case \OCA\Files_Encryption\Crypt::ENCRYPTION_NO_SHARE_KEY_FOUND:
				$errorMsg = $l->t('Can not decrypt this file, probably this is a shared file. Please ask the file owner to reshare the file with you.');
				break;
			default:
				$errorMsg = $l->t("Unknown error. Please check your system settings or contact your administrator");
				break;
		}
	} else {
		$errorCode = \OCA\Files_Encryption\Crypt::ENCRYPTION_UNKNOWN_ERROR;
		$errorMsg = $l->t("Unknown error. Please check your system settings or contact your administrator");
	}

	if (isset($_GET['p']) && $_GET['p'] === '1') {
		header('HTTP/1.0 403 ' . $errorMsg);
	}

// check if ajax request
	if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		\OCP\JSON::error(array('data' => array('message' => $errorMsg)));
	} else {
		header('HTTP/1.0 403 ' . $errorMsg);
		$tmpl = new OC_Template('files_encryption', 'invalid_private_key', 'guest');
		$tmpl->assign('message', $errorMsg);
		$tmpl->assign('errorCode', $errorCode);
		$tmpl->printPage();
	}

	exit;
}

