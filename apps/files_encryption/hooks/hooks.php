<?php

/**
 * ownCloud
 *
 * @author Sam Tuke
 * @copyright 2012 Sam Tuke samtuke@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Encryption;

use OC\Files\Filesystem;

/**
 * Class for hook specific logic
 */
class Hooks {

	// file for which we want to rename the keys after the rename operation was successful
	private static $renamedFiles = array();
	// file for which we want to delete the keys after the delete operation was successful
	private static $deleteFiles = array();

	/**
	 * Startup encryption backend upon user login
	 * @note This method should never be called for users using client side encryption
	 */
	public static function login($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}


		$l = new \OC_L10N('files_encryption');

		$view = new \OC\Files\View('/');

		// ensure filesystem is loaded
		if (!\OC\Files\Filesystem::$loaded) {
			\OC_Util::setupFS($params['uid']);
		}

		$privateKey = \OCA\Encryption\Keymanager::getPrivateKey($view, $params['uid']);

		// if no private key exists, check server configuration
		if (!$privateKey) {
			//check if all requirements are met
			if (!Helper::checkRequirements() || !Helper::checkConfiguration()) {
				$error_msg = $l->t("Missing requirements.");
				$hint = $l->t('Please make sure that PHP 5.3.3 or newer is installed and that OpenSSL together with the PHP extension is enabled and configured properly. For now, the encryption app has been disabled.');
				\OC_App::disable('files_encryption');
				\OCP\Util::writeLog('Encryption library', $error_msg . ' ' . $hint, \OCP\Util::ERROR);
				\OCP\Template::printErrorPage($error_msg, $hint);
			}
		}

		$util = new Util($view, $params['uid']);

		// setup user, if user not ready force relogin
		if (Helper::setupUser($util, $params['password']) === false) {
			return false;
		}

		$session = $util->initEncryption($params);

		// Check if first-run file migration has already been performed
		$ready = false;
		$migrationStatus = $util->getMigrationStatus();
		if ($migrationStatus === Util::MIGRATION_OPEN && $session !== false) {
			$ready = $util->beginMigration();
		} elseif ($migrationStatus === Util::MIGRATION_IN_PROGRESS) {
			// refuse login as long as the initial encryption is running
			sleep(5);
			\OCP\User::logout();
			return false;
		}

		$result = true;

		// If migration not yet done
		if ($ready) {

			$userView = new \OC\Files\View('/' . $params['uid']);

			// Set legacy encryption key if it exists, to support
			// depreciated encryption system
			if ($userView->file_exists('encryption.key')) {
				$encLegacyKey = $userView->file_get_contents('encryption.key');
				if ($encLegacyKey) {

					$plainLegacyKey = Crypt::legacyDecrypt($encLegacyKey, $params['password']);

					$session->setLegacyKey($plainLegacyKey);
				}
			}

			// Encrypt existing user files
			try {
				$result = $util->encryptAll('/' . $params['uid'] . '/' . 'files', $session->getLegacyKey(), $params['password']);
			} catch (\Exception $ex) {
				\OCP\Util::writeLog('Encryption library', 'Initial encryption failed! Error: ' . $ex->getMessage(), \OCP\Util::FATAL);
				$result = false;
			}

			if ($result) {
				\OC_Log::write(
						'Encryption library', 'Encryption of existing files belonging to "' . $params['uid'] . '" completed'
						, \OC_Log::INFO
					);
				// Register successful migration in DB
				$util->finishMigration();
			} else  {
				\OCP\Util::writeLog('Encryption library', 'Initial encryption failed!', \OCP\Util::FATAL);
				$util->resetMigrationStatus();
				\OCP\User::logout();
			}
		}

		return $result;
	}

	/**
	 * setup encryption backend upon user created
	 * @note This method should never be called for users using client side encryption
	 */
	public static function postCreateUser($params) {

		if (\OCP\App::isEnabled('files_encryption')) {
			$view = new \OC\Files\View('/');
			$util = new Util($view, $params['uid']);
			Helper::setupUser($util, $params['password']);
		}
	}

	/**
	 * cleanup encryption backend upon user deleted
	 * @note This method should never be called for users using client side encryption
	 */
	public static function postDeleteUser($params) {

		if (\OCP\App::isEnabled('files_encryption')) {
			$view = new \OC\Files\View('/');

			// cleanup public key
			$publicKey = '/public-keys/' . $params['uid'] . '.public.key';

			// Disable encryption proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			$view->unlink($publicKey);

			\OC_FileProxy::$enabled = $proxyStatus;
		}
	}

	/**
	 * If the password can't be changed within ownCloud, than update the key password in advance.
	 */
	public static function preSetPassphrase($params) {
		if (\OCP\App::isEnabled('files_encryption')) {
			if ( ! \OC_User::canUserChangePassword($params['uid']) ) {
				self::setPassphrase($params);
			}
		}
	}

	/**
	 * Change a user's encryption passphrase
	 * @param array $params keys: uid, password
	 */
	public static function setPassphrase($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		// Only attempt to change passphrase if server-side encryption
		// is in use (client-side encryption does not have access to
		// the necessary keys)
		if (Crypt::mode() === 'server') {

			$view = new \OC\Files\View('/');

			if ($params['uid'] === \OCP\User::getUser()) {

				$session = new \OCA\Encryption\Session($view);

				// Get existing decrypted private key
				$privateKey = $session->getPrivateKey();

				// Encrypt private key with new user pwd as passphrase
				$encryptedPrivateKey = Crypt::symmetricEncryptFileContent($privateKey, $params['password']);

				// Save private key
				Keymanager::setPrivateKey($encryptedPrivateKey);

				// NOTE: Session does not need to be updated as the
				// private key has not changed, only the passphrase
				// used to decrypt it has changed


			} else { // admin changed the password for a different user, create new keys and reencrypt file keys

				$user = $params['uid'];
				$util = new Util($view, $user);
				$recoveryPassword = isset($params['recoveryPassword']) ? $params['recoveryPassword'] : null;

				// we generate new keys if...
				// ...we have a recovery password and the user enabled the recovery key
				// ...encryption was activated for the first time (no keys exists)
				// ...the user doesn't have any files
				if (($util->recoveryEnabledForUser() && $recoveryPassword)
						|| !$util->userKeysExists()
						|| !$view->file_exists($user . '/files')) {

					$newUserPassword = $params['password'];

					// make sure that the users home is mounted
					\OC\Files\Filesystem::initMountPoints($user);

					$keypair = Crypt::createKeypair();

					// Disable encryption proxy to prevent recursive calls
					$proxyStatus = \OC_FileProxy::$enabled;
					\OC_FileProxy::$enabled = false;

					// Save public key
					$view->file_put_contents('/public-keys/' . $user . '.public.key', $keypair['publicKey']);

					// Encrypt private key empty passphrase
					$encryptedPrivateKey = Crypt::symmetricEncryptFileContent($keypair['privateKey'], $newUserPassword);

					// Save private key
					$view->file_put_contents(
							'/' . $user . '/files_encryption/' . $user . '.private.key', $encryptedPrivateKey);

					if ($recoveryPassword) { // if recovery key is set we can re-encrypt the key files
						$util = new Util($view, $user);
						$util->recoverUsersFiles($recoveryPassword);
					}

					\OC_FileProxy::$enabled = $proxyStatus;
				}
			}
		}
	}

	/*
	 * check if files can be encrypted to every user.
	 */
	/**
	 * @param array $params
	 */
	public static function preShared($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		$l = new \OC_L10N('files_encryption');
		$users = array();
		$view = new \OC\Files\View('/public-keys/');

		switch ($params['shareType']) {
			case \OCP\Share::SHARE_TYPE_USER:
				$users[] = $params['shareWith'];
				break;
			case \OCP\Share::SHARE_TYPE_GROUP:
				$users = \OC_Group::usersInGroup($params['shareWith']);
				break;
		}

		$notConfigured = array();
		foreach ($users as $user) {
			if (!$view->file_exists($user . '.public.key')) {
				$notConfigured[] = $user;
			}
		}

		if (count($notConfigured) > 0) {
			$params['run'] = false;
			$params['error'] = $l->t('Following users are not set up for encryption:') . ' ' . join(', ' , $notConfigured);
		}

	}

	/**
	 * @brief
	 */
	public static function postShared($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {

			$view = new \OC\Files\View('/');
			$session = new \OCA\Encryption\Session($view);
			$userId = \OCP\User::getUser();
			$util = new Util($view, $userId);
			$path = \OC\Files\Filesystem::getPath($params['fileSource']);

			$sharingEnabled = \OCP\Share::isEnabled();

			$mountManager = \OC\Files\Filesystem::getMountManager();
			$mount = $mountManager->find('/' . $userId . '/files' . $path);
			$mountPoint = $mount->getMountPoint();

			// if a folder was shared, get a list of all (sub-)folders
			if ($params['itemType'] === 'folder') {
				$allFiles = $util->getAllFiles($path, $mountPoint);
			} else {
				$allFiles = array($path);
			}

			foreach ($allFiles as $path) {
				$usersSharing = $util->getSharingUsersArray($sharingEnabled, $path);
				$util->setSharedFileKeyfiles($session, $usersSharing, $path);
			}
		}
	}

	/**
	 * @brief
	 */
	public static function postUnshare($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {

			$view = new \OC\Files\View('/');
			$userId = \OCP\User::getUser();
			$util = new Util($view, $userId);
			$path = \OC\Files\Filesystem::getPath($params['fileSource']);

			// for group shares get a list of the group members
			if ($params['shareType'] === \OCP\Share::SHARE_TYPE_GROUP) {
				$userIds = \OC_Group::usersInGroup($params['shareWith']);
			} else {
				if ($params['shareType'] === \OCP\Share::SHARE_TYPE_LINK) {
					$userIds = array($util->getPublicShareKeyId());
				} else {
					$userIds = array($params['shareWith']);
				}
			}

			$mountManager = \OC\Files\Filesystem::getMountManager();
			$mount = $mountManager->find('/' . $userId . '/files' . $path);
			$mountPoint = $mount->getMountPoint();

			// if we unshare a folder we need a list of all (sub-)files
			if ($params['itemType'] === 'folder') {
				$allFiles = $util->getAllFiles($path, $mountPoint);
			} else {
				$allFiles = array($path);
			}

			foreach ($allFiles as $path) {

				// check if the user still has access to the file, otherwise delete share key
				$sharingUsers = $util->getSharingUsersArray(true, $path);

				// Unshare every user who no longer has access to the file
				$delUsers = array_diff($userIds, $sharingUsers);

				// delete share key
				Keymanager::delShareKey($view, $delUsers, $path);
			}

		}
	}

	/**
	 * mark file as renamed so that we know the original source after the file was renamed
	 * @param array $params with the old path and the new path
	 */
	public static function preRename($params) {
		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/');
		$util = new Util($view, $user);
		list($ownerOld, $pathOld) = $util->getUidAndFilename($params['oldpath']);

		// we only need to rename the keys if the rename happens on the same mountpoint
		// otherwise we perform a stream copy, so we get a new set of keys
		$mp1 = $view->getMountPoint('/' . $user . '/files/' . $params['oldpath']);
		$mp2 = $view->getMountPoint('/' . $user . '/files/' . $params['newpath']);
		list($storage1, ) = Filesystem::resolvePath($params['oldpath']);

		if ($mp1 === $mp2) {
			self::$renamedFiles[$params['oldpath']] = array(
				'uid' => $ownerOld,
				'path' => $pathOld);
		}
	}

	/**
	 * after a file is renamed, rename its keyfile and share-keys also fix the file size and fix also the sharing
	 * @param array $params array with oldpath and newpath
	 *
	 * This function is connected to the rename signal of OC_Filesystem and adjust the name and location
	 * of the stored versions along the actual file
	 */
	public static function postRename($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$view = new \OC\Files\View('/');
		$session = new \OCA\Encryption\Session($view);
		$userId = \OCP\User::getUser();
		$util = new Util($view, $userId);

		if (isset(self::$renamedFiles[$params['oldpath']]['uid']) &&
				isset(self::$renamedFiles[$params['oldpath']]['path'])) {
			$ownerOld = self::$renamedFiles[$params['oldpath']]['uid'];
			$pathOld = self::$renamedFiles[$params['oldpath']]['path'];
		} else {
			\OCP\Util::writeLog('Encryption library', "can't get path and owner from the file before it was renamed", \OCP\Util::ERROR);
			return false;
		}

		list($ownerNew, $pathNew) = $util->getUidAndFilename($params['newpath']);

		// Format paths to be relative to user files dir
		if ($util->isSystemWideMountPoint($pathOld)) {
			$oldKeyfilePath = 'files_encryption/keyfiles/' . $pathOld;
			$oldShareKeyPath = 'files_encryption/share-keys/' . $pathOld;
		} else {
			$oldKeyfilePath = $ownerOld . '/' . 'files_encryption/keyfiles/' . $pathOld;
			$oldShareKeyPath = $ownerOld . '/' . 'files_encryption/share-keys/' . $pathOld;
		}

		if ($util->isSystemWideMountPoint($pathNew)) {
			$newKeyfilePath =  'files_encryption/keyfiles/' . $pathNew;
			$newShareKeyPath =  'files_encryption/share-keys/' . $pathNew;
		} else {
			$newKeyfilePath = $ownerNew . '/files_encryption/keyfiles/' . $pathNew;
			$newShareKeyPath = $ownerNew . '/files_encryption/share-keys/' . $pathNew;
		}

		// add key ext if this is not an folder
		if (!$view->is_dir($oldKeyfilePath)) {
			$oldKeyfilePath .= '.key';
			$newKeyfilePath .= '.key';

			// handle share-keys
			$localKeyPath = $view->getLocalFile($oldShareKeyPath);
			$escapedPath = Helper::escapeGlobPattern($localKeyPath);
			$matches = glob($escapedPath . '*.shareKey');
			foreach ($matches as $src) {
				$dst = \OC\Files\Filesystem::normalizePath(str_replace($pathOld, $pathNew, $src));

				// create destination folder if not exists
				if (!file_exists(dirname($dst))) {
					mkdir(dirname($dst), 0750, true);
				}

				rename($src, $dst);
			}

		} else {
			// handle share-keys folders

			// create destination folder if not exists
			if (!$view->file_exists(dirname($newShareKeyPath))) {
				$view->mkdir(dirname($newShareKeyPath), 0750, true);
			}

			$view->rename($oldShareKeyPath, $newShareKeyPath);
		}

		// Rename keyfile so it isn't orphaned
		if ($view->file_exists($oldKeyfilePath)) {

			// create destination folder if not exists
			if (!$view->file_exists(dirname($newKeyfilePath))) {
				$view->mkdir(dirname($newKeyfilePath), 0750, true);
			}

			$view->rename($oldKeyfilePath, $newKeyfilePath);
		}

		// build the path to the file
		$newPath = '/' . $ownerNew . '/files' . $pathNew;

		if ($util->fixFileSize($newPath)) {
			// get sharing app state
			$sharingEnabled = \OCP\Share::isEnabled();

			// get users
			$usersSharing = $util->getSharingUsersArray($sharingEnabled, $pathNew);

			// update sharing-keys
			$util->setSharedFileKeyfiles($session, $usersSharing, $pathNew);
		}

		\OC_FileProxy::$enabled = $proxyStatus;
	}

	/**
	 * set migration status and the init status back to '0' so that all new files get encrypted
	 * if the app gets enabled again
	 * @param array $params contains the app ID
	 */
	public static function preDisable($params) {
		if ($params['app'] === 'files_encryption') {

			$setMigrationStatus = \OC_DB::prepare('UPDATE `*PREFIX*encryption` SET `migration_status`=0');
			$setMigrationStatus->execute();

			$session = new \OCA\Encryption\Session(new \OC\Files\View('/'));
			$session->setInitialized(\OCA\Encryption\Session::NOT_INITIALIZED);
		}
	}

	/**
	 * set the init status to 'NOT_INITIALIZED' (0) if the app gets enabled
	 * @param array $params contains the app ID
	 */
	public static function postEnable($params) {
		if ($params['app'] === 'files_encryption') {
			$session = new \OCA\Encryption\Session(new \OC\Files\View('/'));
			$session->setInitialized(\OCA\Encryption\Session::NOT_INITIALIZED);
		}
	}

	/**
	 * if the file was really deleted we remove the encryption keys
	 * @param array $params
	 * @return boolean|null
	 */
	public static function postDelete($params) {

		if (!isset(self::$deleteFiles[$params[\OC\Files\Filesystem::signal_param_path]])) {
			return true;
		}

		$deletedFile = self::$deleteFiles[$params[\OC\Files\Filesystem::signal_param_path]];
		$path = $deletedFile['path'];
		$user = $deletedFile['uid'];

		// we don't need to remember the file any longer
		unset(self::$deleteFiles[$params[\OC\Files\Filesystem::signal_param_path]]);

		$view = new \OC\Files\View('/');

		// return if the file still exists and wasn't deleted correctly
		if ($view->file_exists('/' . $user . '/files/' . $path)) {
			return true;
		}

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// Delete keyfile & shareKey so it isn't orphaned
		if (!Keymanager::deleteFileKey($view, $path, $user)) {
			\OCP\Util::writeLog('Encryption library',
				'Keyfile or shareKey could not be deleted for file "' . $user.'/files/'.$path . '"', \OCP\Util::ERROR);
		}

		Keymanager::delAllShareKeys($view, $user, $path);

		\OC_FileProxy::$enabled = $proxyStatus;
	}

	/**
	 * remember the file which should be deleted and it's owner
	 * @param array $params
	 * @return boolean|null
	 */
	public static function preDelete($params) {
		$path = $params[\OC\Files\Filesystem::signal_param_path];

		// skip this method if the trash bin is enabled or if we delete a file
		// outside of /data/user/files
		if (\OCP\App::isEnabled('files_trashbin')) {
			return true;
		}

		$util = new Util(new \OC\Files\View('/'), \OCP\USER::getUser());
		list($owner, $ownerPath) = $util->getUidAndFilename($path);

		self::$deleteFiles[$params[\OC\Files\Filesystem::signal_param_path]] = array(
			'uid' => $owner,
			'path' => $ownerPath);
	}

}
