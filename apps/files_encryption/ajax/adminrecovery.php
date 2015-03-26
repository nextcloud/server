<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Florin Peter <github@florin-peter.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Sam Tuke <mail@samtuke.com>
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

use OCA\Files_Encryption\Helper;

\OCP\JSON::checkAdminUser();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$l = \OC::$server->getL10N('files_encryption');

$return = false;
$errorMessage = $l->t("Unknown error");

//check if both passwords are the same
if (empty($_POST['recoveryPassword'])) {
	$errorMessage = $l->t('Missing recovery key password');
	\OCP\JSON::error(array('data' => array('message' => $errorMessage)));
	exit();
}

if (empty($_POST['confirmPassword'])) {
	$errorMessage = $l->t('Please repeat the recovery key password');
	\OCP\JSON::error(array('data' => array('message' => $errorMessage)));
	exit();
}

if ($_POST['recoveryPassword'] !== $_POST['confirmPassword']) {
	$errorMessage = $l->t('Repeated recovery key password does not match the provided recovery key password');
	\OCP\JSON::error(array('data' => array('message' => $errorMessage)));
	exit();
}

// Enable recoveryAdmin
$recoveryKeyId = \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryKeyId');

if (isset($_POST['adminEnableRecovery']) && $_POST['adminEnableRecovery'] === '1') {

	$return = Helper::adminEnableRecovery($recoveryKeyId, (string)$_POST['recoveryPassword']);

	// Return success or failure
	if ($return) {
		$successMessage = $l->t('Recovery key successfully enabled');
	} else {
		$errorMessage = $l->t('Could not disable recovery key. Please check your recovery key password!');
	}

// Disable recoveryAdmin
} elseif (
	isset($_POST['adminEnableRecovery'])
	&& '0' === $_POST['adminEnableRecovery']
) {
	$return = Helper::adminDisableRecovery((string)$_POST['recoveryPassword']);

	if ($return) {
		$successMessage = $l->t('Recovery key successfully disabled');
	} else {
		$errorMessage = $l->t('Could not disable recovery key. Please check your recovery key password!');
	}
}

// Return success or failure
if ($return) {
	\OCP\JSON::success(array('data' => array('message' => $successMessage)));
} else {
	\OCP\JSON::error(array('data' => array('message' => $errorMessage)));
}
