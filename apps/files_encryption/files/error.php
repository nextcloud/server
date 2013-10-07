<?php

if (!isset($_)) { //also provide standalone error page
	require_once __DIR__ . '/../../../lib/base.php';

	$l = OC_L10N::get('files_encryption');

	if (isset($_GET['i']) && $_GET['i'] === '0') {
		$errorMsg = $l->t('Encryption app not initialized! Maybe the encryption app was re-enabled during your session. Please try to log out and log back in to initialize the encryption app.');
		$init = '0';
	} else {
		$errorMsg = $l->t('Your private key is not valid! Likely your password was changed outside the ownCloud system (e.g. your corporate directory). You can update your private key password in your personal settings to recover access to your encrypted files.');
		$init = '1';
	}

	if (isset($_GET['p']) && $_GET['p'] === '1') {
		header('HTTP/1.0 404 ' . $errorMsg);
	}

// check if ajax request
	if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		\OCP\JSON::error(array('data' => array('message' => $errorMsg)));
	} else {
		header('HTTP/1.0 404 ' . $errorMsg);
		$tmpl = new OC_Template('files_encryption', 'invalid_private_key', 'guest');
		$tmpl->assign('message', $errorMsg);
		$tmpl->assign('init', $init);
		$tmpl->printPage();
	}

	exit;
}

