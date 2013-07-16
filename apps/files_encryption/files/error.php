<?php
if (!isset($_)) { //also provide standalone error page
	require_once '../../../lib/base.php';

	$l = OC_L10N::get('files_encryption');

	$errorMsg = $l->t('Your private key is not valid! Likely your password was changed outside the ownCloud system (e.g. your corporate directory). You can update your private key password in your personal settings to recover access to your encrypted files.');

	if(isset($_GET['p']) && $_GET['p'] === '1') {
		header('HTTP/1.0 404 ' . $errorMsg);
	}

	// check if ajax request
	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		\OCP\JSON::error(array('data' => array('message' => $errorMsg)));
	} else {
		header('HTTP/1.0 404 ' . $errorMsg);
		$tmpl = new OC_Template('files_encryption', 'invalid_private_key', 'guest');
		$tmpl->printPage();
	}

	exit;
}
