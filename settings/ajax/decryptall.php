<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

//encryption app needs to be loaded
OC_App::loadApp('files_encryption');

// init encryption app
$params = array('uid' => \OCP\User::getUser(),
				'password' => $_POST['password']);

$view = new OC\Files\View('/');
$util = new \OCA\Files_Encryption\Util($view, \OCP\User::getUser());
$l = \OC::$server->getL10N('settings');

$result = $util->initEncryption($params);

if ($result !== false) {

	try {
		$successful = $util->decryptAll();
	} catch (\Exception $ex) {
		\OCP\Util::writeLog('encryption library', "Decryption finished unexpected: " . $ex->getMessage(), \OCP\Util::ERROR);
		$successful = false;
	}

	$util->closeEncryptionSession();

	if ($successful === true) {
		\OCP\JSON::success(array('data' => array('message' => $l->t('Files decrypted successfully'))));
	} else {
		\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t decrypt your files, please check your owncloud.log or ask your administrator'))));
	}
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Couldn\'t decrypt your files, check your password and try again'))));
}

