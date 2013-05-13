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

if (
	isset($_POST['adminEnableRecovery'])
	&& 1 == $_POST['adminEnableRecovery']
) {

	$view = new \OC\Files\View('/');

	$recoveryKeyId = OC_Appconfig::getValue('files_encryption', 'recoveryKeyId');

	if ($recoveryKeyId === null) {
		$recoveryKeyId = 'recovery_' . substr(md5(time()), 0, 8);
		\OC_Appconfig::setValue('files_encryption', 'recoveryKeyId', $recoveryKeyId);
	}

	if (!$view->is_dir('/owncloud_private_key')) {
		$view->mkdir('/owncloud_private_key');
	}

	if (
		(!$view->file_exists("/public-keys/" . $recoveryKeyId . ".public.key")
		|| !$view->file_exists("/owncloud_private_key/" . $recoveryKeyId . ".private.key"))
		&& isset($_POST['recoveryPassword'])
		&& !empty($_POST['recoveryPassword'])
	) {

		$keypair = \OCA\Encryption\Crypt::createKeypair();

		\OC_FileProxy::$enabled = false;

		// Save public key

		if (!$view->is_dir('/public-keys')) {
			$view->mkdir('/public-keys');
		}

		$view->file_put_contents('/public-keys/' . $recoveryKeyId . '.public.key', $keypair['publicKey']);

		// Encrypt private key empthy passphrase
		$encryptedPrivateKey = \OCA\Encryption\Crypt::symmetricEncryptFileContent($keypair['privateKey'], $_POST['recoveryPassword']);

		// Save private key
		$view->file_put_contents('/owncloud_private_key/' . $recoveryKeyId . '.private.key', $encryptedPrivateKey);

		\OC_FileProxy::$enabled = true;

	}

	// Set recoveryAdmin as enabled
	OC_Appconfig::setValue('files_encryption', 'recoveryAdminEnabled', 1);

	$return = true;

// Disable recoveryAdmin
} elseif (
	isset($_POST['adminEnableRecovery'])
	&& 0 == $_POST['adminEnableRecovery']
) {

	// Set recoveryAdmin as enabled
	OC_Appconfig::setValue('files_encryption', 'recoveryAdminEnabled', 0);

	$return = true;
}

// Return success or failure
( $return ) ? \OCP\JSON::success() : \OCP\JSON::error();