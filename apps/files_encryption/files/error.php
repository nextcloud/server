<?php
if (!isset($_)) { //also provide standalone error page
	require_once '../../../lib/base.php';

	$l = OC_L10N::get('files_encryption');

	// check if ajax request
	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		\OCP\JSON::error(array('data' => array('message' => $l->t('Your private key is not valid! Maybe the your password was changed from outside.'))));
	} else {
		header('HTTP/1.0 404 ' . $l->t('Your private key is not valid! Maybe the your password was changed from outside.'));
		$tmpl = new OC_Template('files_encryption', 'invalid_private_key', 'guest');
		$tmpl->printPage();
	}

	exit;
}
?>
