<?php

/**
 * Copyright (c) 2013, Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 *
 * Script to change recovery key password
 *
 */

use OCA\Encryption;

\OCP\JSON::checkLoggedIn();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$l = OC_L10N::get('core');

$return = false;

$oldPassword = $_POST['oldPassword'];
$newPassword = $_POST['newPassword'];

$view = new \OC\Files\View('/');
$session = new \OCA\Encryption\Session($view);
$user = \OCP\User::getUser();

$proxyStatus = \OC_FileProxy::$enabled;
\OC_FileProxy::$enabled = false;

$keyPath = '/' . $user . '/files_encryption/' . $user . '.private.key';

$encryptedKey = $view->file_get_contents($keyPath);
$decryptedKey = \OCA\Encryption\Crypt::decryptPrivateKey($encryptedKey, $oldPassword);

if ($decryptedKey) {
	$cipher = \OCA\Encryption\Helper::getCipher();
	$encryptedKey = \OCA\Encryption\Crypt::symmetricEncryptFileContent($decryptedKey, $newPassword, $cipher);
	if ($encryptedKey) {
		\OCA\Encryption\Keymanager::setPrivateKey($encryptedKey, $user);
		$session->setPrivateKey($decryptedKey);
		$return = true;
	}
}

\OC_FileProxy::$enabled = $proxyStatus;

// success or failure
if ($return) {
	$session->setInitialized(\OCA\Encryption\Session::INIT_SUCCESSFUL);
	\OCP\JSON::success(array('data' => array('message' => $l->t('Private key password successfully updated.'))));
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Could not update the private key password. Maybe the old password was not correct.'))));
}
