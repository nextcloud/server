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

	$view = new \OC\Files\View('/');

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

		// create control file which let us check later on if the entered password was correct.
		$encryptedControlData =  \OCA\Encryption\Crypt::keyEncrypt("ownCloud", $keypair['publicKey']);
		if (!$view->is_dir('/control-file')) {
			$view->mkdir('/control-file');
		}
		$view->file_put_contents('/control-file/controlfile.enc', $encryptedControlData);

		\OC_FileProxy::$enabled = true;

		// Set recoveryAdmin as enabled
		OC_Appconfig::setValue('files_encryption', 'recoveryAdminEnabled', 1);

		$return = true;

	} else { // get recovery key and check the password
		$util = new \OCA\Encryption\Util(new \OC_FilesystemView('/'), \OCP\User::getUser());
		$return = $util->checkRecoveryPassword($_POST['recoveryPassword']);
		if ($return) {
			OC_Appconfig::setValue('files_encryption', 'recoveryAdminEnabled', 1);
		} 
	}

// Disable recoveryAdmin
} elseif (
	isset($_POST['adminEnableRecovery'])
	&& 0 == $_POST['adminEnableRecovery']
) {
	$util = new \OCA\Encryption\Util(new \OC_FilesystemView('/'), \OCP\User::getUser());
	$return = $util->checkRecoveryPassword($_POST['recoveryPassword']);

	if ($return) {
	// Set recoveryAdmin as disabled
	OC_Appconfig::setValue('files_encryption', 'recoveryAdminEnabled', 0);
	}
}

// Return success or failure
( $return ) ? \OCP\JSON::success() : \OCP\JSON::error();