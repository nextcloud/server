<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Florin Peter <github@florin-peter.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

\OCP\JSON::checkAdminUser();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$l = \OC::$server->getL10N('core');

$return = false;

$oldPassword = (string)$_POST['oldPassword'];
$newPassword = (string)$_POST['newPassword'];
$confirmPassword = (string)$_POST['confirmPassword'];

//check if both passwords are the same
if (empty($_POST['oldPassword'])) {
	$errorMessage = $l->t('Please provide the old recovery password');
	\OCP\JSON::error(array('data' => array('message' => $errorMessage)));
	exit();
}

if (empty($_POST['newPassword'])) {
	$errorMessage = $l->t('Please provide a new recovery password');
	\OCP\JSON::error(array('data' => array('message' => $errorMessage)));
	exit();
}

if (empty($_POST['confirmPassword'])) {
	$errorMessage = $l->t('Please repeat the new recovery password');
	\OCP\JSON::error(array('data' => array('message' => $errorMessage)));
	exit();
}

if ($_POST['newPassword'] !== $_POST['confirmPassword']) {
	$errorMessage = $l->t('Repeated recovery key password does not match the provided recovery key password');
	\OCP\JSON::error(array('data' => array('message' => $errorMessage)));
	exit();
}

$view = new \OC\Files\View('/');
$util = new \OCA\Files_Encryption\Util(new \OC\Files\View('/'), \OCP\User::getUser());

$proxyStatus = \OC_FileProxy::$enabled;
\OC_FileProxy::$enabled = false;

$keyId = $util->getRecoveryKeyId();

$encryptedRecoveryKey = \OCA\Files_Encryption\Keymanager::getPrivateSystemKey($keyId);
$decryptedRecoveryKey = $encryptedRecoveryKey ? \OCA\Files_Encryption\Crypt::decryptPrivateKey($encryptedRecoveryKey, $oldPassword) : false;

if ($decryptedRecoveryKey) {
	$cipher = \OCA\Files_Encryption\Helper::getCipher();
	$encryptedKey = \OCA\Files_Encryption\Crypt::symmetricEncryptFileContent($decryptedRecoveryKey, $newPassword, $cipher);
	if ($encryptedKey) {
		\OCA\Files_Encryption\Keymanager::setPrivateSystemKey($encryptedKey, $keyId);
		$return = true;
	}
}

\OC_FileProxy::$enabled = $proxyStatus;

// success or failure
if ($return) {
	\OCP\JSON::success(array('data' => array('message' => $l->t('Password successfully changed.'))));
} else {
	\OCP\JSON::error(array('data' => array('message' => $l->t('Could not change the password. Maybe the old password was not correct.'))));
}
