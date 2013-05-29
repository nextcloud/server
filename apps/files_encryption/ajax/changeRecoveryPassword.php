<?php

/**
 * Copyright (c) 2013, Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 *
 * @brief Script to change recovery key password
 *
 */

use OCA\Encryption;

\OCP\JSON::checkAdminUser();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$l = OC_L10N::get('core');

$return = false;

$oldPassword = $_POST['oldPassword'];
$newPassword = $_POST['newPassword'];

$util = new \OCA\Encryption\Util(new \OC_FilesystemView('/'), \OCP\User::getUser());

$result = $util->checkRecoveryPassword($oldPassword);

if ($result) {
	$keyId = $util->getRecoveryKeyId();
	$keyPath = '/owncloud_private_key/' . $keyId . '.private.key';
	$view = new \OC\Files\View('/');

	$proxyStatus = \OC_FileProxy::$enabled;
	\OC_FileProxy::$enabled = false;

	$encryptedRecoveryKey = $view->file_get_contents($keyPath);
	$decryptedRecoveryKey = \OCA\Encryption\Crypt::symmetricDecryptFileContent($encryptedRecoveryKey, $oldPassword);
	$encryptedRecoveryKey = \OCA\Encryption\Crypt::symmetricEncryptFileContent($decryptedRecoveryKey, $newPassword);
	$view->file_put_contents($keyPath, $encryptedRecoveryKey);

	\OC_FileProxy::$enabled = $proxyStatus;

	$return = true;
}

// success or failure
if ($return) {
	\OCP\JSON::success(array('data' => array('message' => $l->t('Password successfully changed.'))));
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Could not change the password. Maybe the old password was not correct.'))));
}