<?php

/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Sam Tuke <samtuke@owncloud.org>
 * @author Bjoern Schiessle <schiessle@owncloud.com>
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

namespace OCA\Files_Encryption;

/**
 * Class for hook specific logic
 */
class Hooks {

	// file for which we want to rename the keys after the rename operation was successful
	private static $renamedFiles = array();
	// file for which we want to delete the keys after the delete operation was successful
	private static $deleteFiles = array();
	// file for which we want to delete the keys after the delete operation was successful
	private static $unmountedFiles = array();

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

		$privateKey = Keymanager::getPrivateKey($view, $params['uid']);

		// if no private key exists, check server configuration
		if (!$privateKey) {
			//check if all requirements are met
			if (!Helper::checkRequirements() || !Helper::checkConfiguration()) {
				$error_msg = $l->t("Missing requirements.");
				$hint = $l->t('Please make sure that OpenSSL together with the PHP extension is enabled and configured properly. For now, the encryption app has been disabled.');
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

			// Encrypt existing user files
			try {
				$result = $util->encryptAll('/' . $params['uid'] . '/' . 'files');
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
	 * remove keys from session during logout
	 */
	public static function logout() {
		$session = new Session(new \OC\Files\View());
		$session->removeKeys();
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
			Keymanager::deletePublicKey(new \OC\Files\View(), $params['uid']);
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
			$session = new Session($view);

			// Get existing decrypted private key
			$privateKey = $session->getPrivateKey();

			if ($params['uid'] === \OCP\User::getUser() && $privateKey) {

				// Encrypt private key with new user pwd as passphrase
				$encryptedPrivateKey = Crypt::symmetricEncryptFileContent($privateKey, $params['password'], Helper::getCipher());

				// Save private key
				if ($encryptedPrivateKey) {
					Keymanager::setPrivateKey($encryptedPrivateKey, \OCP\User::getUser());
				} else {
					\OCP\Util::writeLog('files_encryption', 'Could not update users encryption password', \OCP\Util::ERROR);
				}

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

					// backup old keys
					$util->backupAllKeys('recovery');

					$newUserPassword = $params['password'];

					// make sure that the users home is mounted
					\OC\Files\Filesystem::initMountPoints($user);

					$keypair = Crypt::createKeypair();

					// Disable encryption proxy to prevent recursive calls
					$proxyStatus = \OC_FileProxy::$enabled;
					\OC_FileProxy::$enabled = false;

					// Save public key
					Keymanager::setPublicKey($keypair['publicKey'], $user);

					// Encrypt private key with new password
					$encryptedKey = Crypt::symmetricEncryptFileContent($keypair['privateKey'], $newUserPassword, Helper::getCipher());
					if ($encryptedKey) {
						Keymanager::setPrivateKey($encryptedKey, $user);

						if ($recoveryPassword) { // if recovery key is set we can re-encrypt the key files
							$util = new Util($view, $user);
							$util->recoverUsersFiles($recoveryPassword);
						}
					} else {
						\OCP\Util::writeLog('files_encryption', 'Could not update users encryption password', \OCP\Util::ERROR);
					}

					\OC_FileProxy::$enabled = $proxyStatus;
				}
			}
		}
	}

	/**
	 * after password reset we create a new key pair for the user
	 *
	 * @param array $params
	 */
	public static function postPasswordReset($params) {
		$uid = $params['uid'];
		$password = $params['password'];

		$util = new Util(new \OC\Files\View(), $uid);
		$util->replaceUserKeys($password);
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
		$view = new \OC\Files\View('/');

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
			if (!Keymanager::publicKeyExists($view, $user)) {
				$notConfigured[] = $user;
			}
		}

		if (count($notConfigured) > 0) {
			$params['run'] = false;
			$params['error'] = $l->t('Following users are not set up for encryption:') . ' ' . join(', ' , $notConfigured);
		}

	}

	/**
	 * update share keys if a file was shared
	 */
	public static function postShared($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {

			$path = \OC\Files\Filesystem::getPath($params['fileSource']);

			self::updateKeyfiles($path);
		}
	}

	/**
	 * update keyfiles and share keys recursively
	 *
	 * @param string $path to the file/folder
	 */
	private static function updateKeyfiles($path) {
		$view = new \OC\Files\View('/');
		$userId = \OCP\User::getUser();
		$session = new Session($view);
		$util = new Util($view, $userId);
		$sharingEnabled = \OCP\Share::isEnabled();

		$mountManager = \OC\Files\Filesystem::getMountManager();
		$mount = $mountManager->find('/' . $userId . '/files' . $path);
		$mountPoint = $mount->getMountPoint();

		// if a folder was shared, get a list of all (sub-)folders
		if ($view->is_dir('/' . $userId . '/files' . $path)) {
			$allFiles = $util->getAllFiles($path, $mountPoint);
		} else {
			$allFiles = array($path);
		}

		foreach ($allFiles as $path) {
			$usersSharing = $util->getSharingUsersArray($sharingEnabled, $path);
			$util->setSharedFileKeyfiles($session, $usersSharing, $path);
		}
	}

	/**
	 * unshare file/folder from a user with whom you shared the file before
	 */
	public static function postUnshare($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {

			$view = new \OC\Files\View('/');
			$userId = $params['uidOwner'];
			$userView = new \OC\Files\View('/' . $userId . '/files');
			$util = new Util($view, $userId);
			$path = $userView->getPath($params['fileSource']);

			// for group shares get a list of the group members
			if ($params['shareType'] === \OCP\Share::SHARE_TYPE_GROUP) {
				$userIds = \OC_Group::usersInGroup($params['shareWith']);
			} else {
				if ($params['shareType'] === \OCP\Share::SHARE_TYPE_LINK || $params['shareType'] === \OCP\Share::SHARE_TYPE_REMOTE) {
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
				$keyPath = Keymanager::getKeyPath($view, $util, $path);

				// delete share key
				Keymanager::delShareKey($view, $delUsers, $keyPath, $userId, $path);
			}

		}
	}

	/**
	 * mark file as renamed so that we know the original source after the file was renamed
	 * @param array $params with the old path and the new path
	 */
	public static function preRename($params) {
		self::preRenameOrCopy($params, 'rename');
	}

	/**
	 * mark file as copied so that we know the original source after the file was copied
	 * @param array $params with the old path and the new path
	 */
	public static function preCopy($params) {
		self::preRenameOrCopy($params, 'copy');
	}

	private static function preRenameOrCopy($params, $operation) {
		$user = \OCP\User::getUser();
		$view = new \OC\Files\View('/');
		$util = new Util($view, $user);

		// we only need to rename the keys if the rename happens on the same mountpoint
		// otherwise we perform a stream copy, so we get a new set of keys
		$oldPath = \OC\Files\Filesystem::normalizePath('/' . $user . '/files/' . $params['oldpath']);
		$newPath = \OC\Files\Filesystem::normalizePath('/' . $user . '/files/' . $params['newpath']);
		$mp1 = $view->getMountPoint($oldPath);
		$mp2 = $view->getMountPoint($newPath);

		$oldKeysPath = Keymanager::getKeyPath($view, $util, $params['oldpath']);

		if ($mp1 === $mp2) {
			self::$renamedFiles[$params['oldpath']] = array(
				'operation' => $operation,
				'oldKeysPath' => $oldKeysPath,
				);
		} elseif ($mp1 !== $oldPath . '/') {
			self::$renamedFiles[$params['oldpath']] = array(
				'operation' => 'cleanup',
				'oldKeysPath' => $oldKeysPath,
				);
		}
	}

	/**
	 * after a file is renamed/copied, rename/copy its keyfile and share-keys also fix the file size and fix also the sharing
	 *
	 * @param array $params array with oldpath and newpath
	 */
	public static function postRenameOrCopy($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		$view = new \OC\Files\View('/');
		$userId = \OCP\User::getUser();
		$util = new Util($view, $userId);

		if (isset(self::$renamedFiles[$params['oldpath']]['operation']) &&
				isset(self::$renamedFiles[$params['oldpath']]['oldKeysPath'])) {
			$operation = self::$renamedFiles[$params['oldpath']]['operation'];
			$oldKeysPath = self::$renamedFiles[$params['oldpath']]['oldKeysPath'];
			unset(self::$renamedFiles[$params['oldpath']]);
			if ($operation === 'cleanup') {
				return $view->unlink($oldKeysPath);
			}
		} else {
			\OCP\Util::writeLog('Encryption library', "can't get path and owner from the file before it was renamed", \OCP\Util::DEBUG);
			return false;
		}

		list($ownerNew, $pathNew) = $util->getUidAndFilename($params['newpath']);

		if ($util->isSystemWideMountPoint($pathNew)) {
			$newKeysPath =  'files_encryption/keys/' . $pathNew;
		} else {
			$newKeysPath = $ownerNew . '/files_encryption/keys/' . $pathNew;
		}

		// create  key folders if it doesn't exists
		if (!$view->file_exists(dirname($newKeysPath))) {
			$view->mkdir(dirname($newKeysPath));
		}

		$view->$operation($oldKeysPath, $newKeysPath);

		// update sharing-keys
		self::updateKeyfiles($params['newpath']);
	}

	/**
	 * set migration status and the init status back to '0' so that all new files get encrypted
	 * if the app gets enabled again
	 * @param array $params contains the app ID
	 */
	public static function preDisable($params) {
		if ($params['app'] === 'files_encryption') {

			\OC::$server->getConfig()->deleteAppFromAllUsers('files_encryption');

			$session = new Session(new \OC\Files\View('/'));
			$session->setInitialized(Session::NOT_INITIALIZED);
		}
	}

	/**
	 * set the init status to 'NOT_INITIALIZED' (0) if the app gets enabled
	 * @param array $params contains the app ID
	 */
	public static function postEnable($params) {
		if ($params['app'] === 'files_encryption') {
			$session = new Session(new \OC\Files\View('/'));
			$session->setInitialized(Session::NOT_INITIALIZED);
		}
	}

	/**
	 * if the file was really deleted we remove the encryption keys
	 * @param array $params
	 * @return boolean|null
	 */
	public static function postDelete($params) {

		$path = $params[\OC\Files\Filesystem::signal_param_path];

		if (!isset(self::$deleteFiles[$path])) {
			return true;
		}

		$deletedFile = self::$deleteFiles[$path];
		$keyPath = $deletedFile['keyPath'];

		// we don't need to remember the file any longer
		unset(self::$deleteFiles[$path]);

		$view = new \OC\Files\View('/');

		// return if the file still exists and wasn't deleted correctly
		if ($view->file_exists('/' . \OCP\User::getUser() . '/files/' . $path)) {
			return true;
		}

		// Delete keyfile & shareKey so it isn't orphaned
		$view->unlink($keyPath);

	}

	/**
	 * remember the file which should be deleted and it's owner
	 * @param array $params
	 * @return boolean|null
	 */
	public static function preDelete($params) {
		$view = new \OC\Files\View('/');
		$path = $params[\OC\Files\Filesystem::signal_param_path];

		// skip this method if the trash bin is enabled or if we delete a file
		// outside of /data/user/files
		if (\OCP\App::isEnabled('files_trashbin')) {
			return true;
		}

		$util = new Util($view, \OCP\USER::getUser());

		$keysPath = Keymanager::getKeyPath($view, $util, $path);

		self::$deleteFiles[$path] = array(
			'keyPath' => $keysPath);
	}

	/**
	 * unmount file from yourself
	 * remember files/folders which get unmounted
	 */
	public static function preUnmount($params) {
		$view = new \OC\Files\View('/');
		$user = \OCP\User::getUser();
		$path = $params[\OC\Files\Filesystem::signal_param_path];

		$util = new Util($view, $user);
		list($owner, $ownerPath) = $util->getUidAndFilename($path);

		$keysPath = Keymanager::getKeyPath($view, $util, $path);

		self::$unmountedFiles[$path] = array(
			'keyPath' => $keysPath,
			'owner' => $owner,
			'ownerPath' => $ownerPath
		);
	}

	/**
	 * unmount file from yourself
	 */
	public static function postUnmount($params) {

		$path = $params[\OC\Files\Filesystem::signal_param_path];
		$user = \OCP\User::getUser();

		if (!isset(self::$unmountedFiles[$path])) {
			return true;
		}

		$umountedFile = self::$unmountedFiles[$path];
		$keyPath = $umountedFile['keyPath'];
		$owner = $umountedFile['owner'];
		$ownerPath = $umountedFile['ownerPath'];

		$view = new \OC\Files\View();

		// we don't need to remember the file any longer
		unset(self::$unmountedFiles[$path]);

		// check if the user still has access to the file, otherwise delete share key
		$sharingUsers = \OCP\Share::getUsersSharingFile($path, $user);
		if (!in_array($user, $sharingUsers['users'])) {
			Keymanager::delShareKey($view, array($user), $keyPath, $owner, $ownerPath);
		}
	}

}
