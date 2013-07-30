<?php

//encryption app needs to be loaded
OC_App::loadApp('files_encryption');

// init encryption app
$params = array('uid' => \OCP\User::getUser(),
				'password' => $_POST['password']);

$view = new OC_FilesystemView('/');
$util = new \OCA\Encryption\Util($view, \OCP\User::getUser());

$result = $util->initEncryption($params);

if ($result !== false) {
	$successful = $util->decryptAll();
	if ($successful === true) {
		\OCP\JSON::success(array('data' => array('message' => 'Files decrypted successfully')));
	} else {
		\OCP\JSON::error(array('data' => array('message' => 'Couldn\'t decrypt your files, please check your owncloud.log or ask your administrator')));
	}
} else {
	\OCP\JSON::error(array('data' => array('message' => 'Couldn\'t decrypt your files, check your password and try again')));
}

