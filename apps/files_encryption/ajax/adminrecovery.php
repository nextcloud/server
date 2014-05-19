<?php

/**
 * Copyright (c) 2013, Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 *
 * Script to handle admin settings for encrypted key recovery
 */
use OCA\Encryption;

\OCP\JSON::checkAdminUser();
\OCP\JSON::checkAppEnabled('files_encryption');
\OCP\JSON::callCheck();

$l = OC_L10N::get('files_encryption');

$return = false;
// Enable recoveryAdmin

$recoveryKeyId = \OC::$server->getAppConfig()->getValue('files_encryption', 'recoveryKeyId');

if (isset($_POST['adminEnableRecovery']) && $_POST['adminEnableRecovery'] === '1') {

	$return = \OCA\Encryption\Helper::adminEnableRecovery($recoveryKeyId, $_POST['recoveryPassword']);

	// Return success or failure
	if ($return) {
		\OCP\JSON::success(array('data' => array('message' => $l->t('Recovery key successfully enabled'))));
	} else {
		\OCP\JSON::error(array(
							  'data' => array(
								  'message' => $l->t(
									  'Could not enable recovery key. Please check your recovery key password!')
							  )
						 ));
	}

// Disable recoveryAdmin
} elseif (
	isset($_POST['adminEnableRecovery'])
	&& '0' === $_POST['adminEnableRecovery']
) {
	$return = \OCA\Encryption\Helper::adminDisableRecovery($_POST['recoveryPassword']);

	// Return success or failure
	if ($return) {
		\OCP\JSON::success(array('data' => array('message' => $l->t('Recovery key successfully disabled'))));
	} else {
		\OCP\JSON::error(array(
							  'data' => array(
								  'message' => $l->t(
									  'Could not disable recovery key. Please check your recovery key password!')
							  )
						 ));
	}
}


