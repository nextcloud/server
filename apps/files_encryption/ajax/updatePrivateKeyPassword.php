<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
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

\OCP\JSON::checkLoggedIn();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$l = \OC::$server->getL10N('core');

$return = false;
$errorMessage = $l->t('Could not update the private key password.');

$oldPassword = (string)$_POST['oldPassword'];
$newPassword = (string)$_POST['newPassword'];

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
