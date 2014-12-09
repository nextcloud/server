<?php

/**
 * Copyright (c) 2013, Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 *
 * Script to change recovery key password
 *
 */

\OCP\JSON::checkLoggedIn();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$l = \OC::$server->getL10N('core');

$return = false;
$errorMessage = $l->t('Could not update the private key password.');

$oldPassword = $_POST['oldPassword'];
$newPassword = $_POST['newPassword'];

$view = new \OC\Files\View('/');
$session = new \OCA\Files_Encryption\Session($view);
$user = \OCP\User::getUser();
$loginName = \OC::$server->getUserSession()->getLoginName();

// check new password
$passwordCorrect = \OCP\User::checkPassword($loginName, $newPassword);

if ($passwordCorrect !== false) {

$proxyStatus = \OC_FileProxy::$enabled;
\OC_FileProxy::$enabled = false;

$encryptedKey = \OCA\Files_Encryption\Keymanager::getPrivateKey($view, $user);
$decryptedKey = $encryptedKey ? \OCA\Files_Encryption\Crypt::decryptPrivateKey($encryptedKey, $oldPassword) : false;

if ($decryptedKey) {
	$cipher = \OCA\Files_Encryption\Helper::getCipher();
	$encryptedKey = \OCA\Files_Encryption\Crypt::symmetricEncryptFileContent($decryptedKey, $newPassword, $cipher);
	if ($encryptedKey) {
		\OCA\Files_Encryption\Keymanager::setPrivateKey($encryptedKey, $user);
		$session->setPrivateKey($decryptedKey);
		$return = true;
	}
} else {
	$result = false;
	$errorMessage = $l->t('The old password was not correct, please try again.');
}

\OC_FileProxy::$enabled = $proxyStatus;

} else {
	$result = false;
	$errorMessage = $l->t('The current log-in password was not correct, please try again.');
}

// success or failure
if ($return) {
	$session->setInitialized(\OCA\Files_Encryption\Session::INIT_SUCCESSFUL);
	\OCP\JSON::success(array('data' => array('message' => $l->t('Private key password successfully updated.'))));
} else {
	\OCP\JSON::error(array('data' => array('message' => $errorMessage)));
}
