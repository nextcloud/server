<?php

/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 *
 * @brief Script to handle admin settings for encrypted key recovery
 */
use OCA\Encryption;

\OCP\JSON::checkAdminUser();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$return = false;

// Enable recoveryAdmin

$recoveryKeyId = OC_Appconfig::getValue('files_encryption', 'recoveryKeyId');

if (isset($_POST['adminEnableRecovery']) && $_POST['adminEnableRecovery'] == 1){

	$return = \OCA\Encryption\Helper::adminEnableRecovery($recoveryKeyId, $_POST['recoveryPassword']);

// Disable recoveryAdmin
} elseif (
	isset($_POST['adminEnableRecovery'])
	&& 0 == $_POST['adminEnableRecovery']
) {
	$return = \OCA\Encryption\Helper::adminDisableRecovery($_POST['recoveryPassword']);
}

// Return success or failure
( $return ) ? \OCP\JSON::success() : \OCP\JSON::error();