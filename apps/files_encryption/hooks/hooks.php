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
	 * @brief Startup encryption backend upon user login
	 * @note This method should never be called for users using client side encryption
	 */
	public static function login($params) {

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}


		$l = new \OC_L10N('files_encryption');

		$view = new \OC_FilesystemView('/');

		// ensure filesystem is loaded
		if(!\OC\Files\Filesystem::$loaded) {
			\OC_Util::setupFS($params['uid']);
		}

		$privateKey = \OCA\Encryption\Keymanager::getPrivateKey($view, $params['uid']);

		// if no private key exists, check server configuration
		if(!$privateKey) {
			//check if all requirements are met
			if(!Helper::checkRequirements() || !Helper::checkConfiguration()) {
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

		// If migration not yet done
		if ($ready) {

			$userView = new \OC_FilesystemView('/' . $params['uid']);

			// Set legacy encryption key if it exists, to support
			// depreciated encryption system
			if (
				$userView->file_exists('encryption.key')
				&& $encLegacyKey = $userView->file_get_contents('encryption.key')
			) {
					$plainLegacyKey = Crypt::legacyDecrypt($encLegacyKey, $params['password']);

					$session->setLegacyKey($plainLegacyKey);
			}

			// Encrypt existing user files
			try {
				$result = $util->encryptAll('/' . $params['uid'] . '/' . 'files', $session->getLegacyKey(), $params['password']);
			} catch (\Exception $ex) {
				\OCP\Util::writeLog('Encryption library', 'Initial encryption failed! Error: ' . $ex->getMessage(), \OCP\Util::FATAL);
				$util->resetMigrationStatus();
				\OCP\User::logout();
				$result = false;
			}

			if ($result) {

				\OC_Log::write(
					'Encryption library', 'Encryption of existing files belonging to "' . $params['uid'] . '" completed'
					, \OC_Log::INFO
				);

				// Register successful migration in DB
				$util->finishMigration();

			}
		}

		return true;

	}

	/**
	 * @brief setup encryption backend upon user created
	 * @note This method should never be called for users using client side encryption
	 */
	public static function postCreateUser($params) {

		if (\OCP\App::isEnabled('files_encryption')) {
			$view = new \OC_FilesystemView('/');
			$util = new Util($view, $params['uid']);
			Helper::setupUser($util, $params['password']);
		}
	}

	/**
	 * @brief cleanup encryption backend upon user deleted
	 * @note This method should never be called for users using client side encryption
	 */
	public static function postDeleteUser($params) {

		if (\OCP\App::isEnabled('files_encryption')) {
			$view = new \OC_FilesystemView('/');

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
	 * @brief If the password can't be changed within ownCloud, than update the key password in advance.
	 */
	public static function preSetPassphrase($params) {
		if (\OCP\App::isEnabled('files_encryption')) {
			if ( ! \OC_User::canUserChangePassword($params['uid']) ) {
				self::setPassphrase($params);
			}
		}
	}

	/**
	 * @brief Change a user's encryption passphrase
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

			$view = new \OC_FilesystemView('/');
			$session = new \OCA\Encryption\Session($view);

			// Get existing decrypted private key
			$privateKey = $session->getPrivateKey();

			if ($params['uid'] === \OCP\User::getUser() && $privateKey) {

				// Encrypt private key with new user pwd as passphrase
				$encryptedPrivateKey = Crypt::symmetricEncryptFileContent($privateKey, $params['password']);

				// Save private key
				if ($encryptedPrivateKey) {
					Keymanager::setPrivateKey($encryptedPrivateKey);
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
	 * @brief check if files can be encrypted to every user.
	 */
	/**
	 * @param $params
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

		// NOTE: $params has keys:
		// [itemType] => file
		// itemSource -> int, filecache file ID
		// [parent] =>
		// [itemTarget] => /13
		// shareWith -> string, uid of user being shared to
		// fileTarget -> path of file being shared
		// uidOwner -> owner of the original file being shared
		// [shareType] => 0
		// [shareWith] => test1
		// [uidOwner] => admin
		// [permissions] => 17
		// [fileSource] => 13
		// [fileTarget] => /test8
		// [id] => 10
		// [token] =>
		// [run] => whether emitting script should continue to run
		// TODO: Should other kinds of item be encrypted too?

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {

			$view = new \OC_FilesystemView('/');
			$userId = \OCP\User::getUser();
			$util = new Util($view, $userId);
			$path = $util->fileIdToPath($params['itemSource']);

			$share = $util->getParentFromShare($params['id']);
			//if parent is set, then this is a re-share action
			if ($share['parent'] !== null) {

				// get the parent from current share
				$parent = $util->getShareParent($params['parent']);

				// if parent has the same type than the child it is a 1:1 share
				if ($parent['item_type'] === $params['itemType']) {

					// prefix path with Shared
					$path = '/Shared' . $parent['file_target'];
				} else {

					// NOTE: parent is folder but shared was a file!
					// we try to rebuild the missing path
					// some examples we face here
					// user1 share folder1 with user2 folder1 has
					// the following structure
					// /folder1/subfolder1/subsubfolder1/somefile.txt
					// user2 re-share subfolder2 with user3
					// user3 re-share somefile.txt user4
					// so our path should be
					// /Shared/subfolder1/subsubfolder1/somefile.txt
					// while user3 is sharing

					if ($params['itemType'] === 'file') {
						// get target path
						$targetPath = $util->fileIdToPath($params['fileSource']);
						$targetPathSplit = array_reverse(explode('/', $targetPath));

						// init values
						$path = '';
						$sharedPart = ltrim($parent['file_target'], '/');

						// rebuild path
						foreach ($targetPathSplit as $pathPart) {
							if ($pathPart !== $sharedPart) {
								$path = '/' . $pathPart . $path;
							} else {
								break;
							}
						}
						// prefix path with Shared
						$path = '/Shared' . $parent['file_target'] . $path;
					} else {
						// prefix path with Shared
						$path = '/Shared' . $parent['file_target'] . $params['fileTarget'];
					}
				}
			}

			// get the path including mount point only if not a shared folder
			if (strncmp($path, '/Shared', strlen('/Shared') !== 0)) {
				// get path including the the storage mount point
				$path = $util->getPathWithMountPoint($params['itemSource']);
			}

			self::updateKeyfiles($path, $params['itemType']);
		}
	}

	/**
	 * update keyfiles and share keys recursively
	 *
	 * @param string $path to the file/folder
	 * @param string $type 'file' or 'folder'
	 */
	private static function updateKeyfiles($path, $type) {

		$view = new \OC_FilesystemView('/');
		$session = new \OCA\Encryption\Session($view);
		$userId = \OCP\User::getUser();
		$util = new Util($view, $userId);

		$sharingEnabled = \OCP\Share::isEnabled();

		// if a folder was shared, get a list of all (sub-)folders
		if ($type === 'folder') {
			$allFiles = $util->getAllFiles($path);
		} else {
			$allFiles = array($path);
		}

		foreach ($allFiles as $path) {
			$usersSharing = $util->getSharingUsersArray($sharingEnabled, $path);
			$util->setSharedFileKeyfiles($session, $usersSharing, $path);
		}
	}

	/**
	 * @brief
	 */
	public static function postUnshare($params) {

		// NOTE: $params has keys:
		// [itemType] => file
		// [itemSource] => 13
		// [shareType] => 0
		// [shareWith] => test1
		// [itemParent] =>

		if (\OCP\App::isEnabled('files_encryption') === false) {
			return true;
		}

		if ($params['itemType'] === 'file' || $params['itemType'] === 'folder') {

			$view = new \OC_FilesystemView('/');
			$userId = \OCP\User::getUser();
			$util = new Util($view, $userId);
			$path = $util->fileIdToPath($params['itemSource']);

			// check if this is a re-share
			if ($params['itemParent']) {

				// get the parent from current share
				$parent = $util->getShareParent($params['itemParent']);

				// get target path
				$targetPath = $util->fileIdToPath($params['itemSource']);
				$targetPathSplit = array_reverse(explode('/', $targetPath));

				// init values
				$path = '';
				$sharedPart = ltrim($parent['file_target'], '/');

				// rebuild path
				foreach ($targetPathSplit as $pathPart) {
					if ($pathPart !== $sharedPart) {
						$path = '/' . $pathPart . $path;
					} else {
						break;
					}
				}

				// prefix path with Shared
				$path = '/Shared' . $parent['file_target'] . $path;
			}

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

			// get the path including mount point only if not a shared folder
			if (strncmp($path, '/Shared', strlen('/Shared') !== 0)) {
				// get path including the the storage mount point
				$path = $util->getPathWithMountPoint($params['itemSource']);
			}

			// if we unshare a folder we need a list of all (sub-)files
			if ($params['itemType'] === 'folder') {
				$allFiles = $util->getAllFiles($path);
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
	 * @brief mark file as renamed so that we know the original source after the file was renamed
	 * @param array $params with the old path and the new path
	 */
	public static function preRename($params) {
		$user = \OCP\User::getUser();
		$view = new \OC_FilesystemView('/');
		$util = new Util($view, $user);
		list($ownerOld, $pathOld) = $util->getUidAndFilename($params['oldpath']);

		// we only need to rename the keys if the rename happens on the same mountpoint
		// otherwise we perform a stream copy, so we get a new set of keys
		$mp1 = $view->getMountPoint('/' . $user . '/files/' . $params['oldpath']);
		$mp2 = $view->getMountPoint('/' . $user . '/files/' . $params['newpath']);
		$type = $view->is_dir('/' . $user . '/files/' . $params['oldpath']) ? 'folder' : 'file';
		if ($mp1 === $mp2) {
			self::$renamedFiles[$params['oldpath']] = array(
				'uid' => $ownerOld,
				'path' => $pathOld,
				'type' => $type);
		}
	}

	/**
	 * @brief after a file is renamed, rename its keyfile and share-keys also fix the file size and fix also the sharing
	 * @param array with oldpath and newpath
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

		$view = new \OC_FilesystemView('/');
		$userId = \OCP\User::getUser();
		$util = new Util($view, $userId);

		if (isset(self::$renamedFiles[$params['oldpath']]['uid']) &&
				isset(self::$renamedFiles[$params['oldpath']]['path'])) {
			$ownerOld = self::$renamedFiles[$params['oldpath']]['uid'];
			$pathOld = self::$renamedFiles[$params['oldpath']]['path'];
			$type = self::$renamedFiles[$params['oldpath']]['type'];
			unset(self::$renamedFiles[$params['oldpath']]);
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
		if ($type === 'file') {
			$oldKeyfilePath .= '.key';
			$newKeyfilePath .= '.key';

			// create destination folder if not exists
			$localKeyPath = $view->getLocalFile($oldShareKeyPath);
			$newLocalKeyPath = \OC\Files\Filesystem::normalizePath(str_replace($pathOld, $pathNew, $localKeyPath));
			if (!file_exists(dirname($newLocalKeyPath))) {
				mkdir(dirname($newLocalKeyPath), 0750, true);
			}


			// handle share-keys
			$matches = Helper::findShareKeys($oldShareKeyPath, $view);
			foreach ($matches as $src) {
				$dst = \OC\Files\Filesystem::normalizePath(str_replace($pathOld, $pathNew, $src));
				$view->rename($src, $dst);
			}

		} else {
			// handle share-keys folders

			// create destination folder if not exists
			if (!$view->file_exists(dirname($newShareKeyPath))) {
				mkdir($view->getLocalFile($newShareKeyPath), 0750, true);
			}

			$view->rename($oldShareKeyPath, $newShareKeyPath);
		}

		// Rename keyfile so it isn't orphaned
		if ($view->file_exists($oldKeyfilePath)) {

			// create destination folder if not exists
			if (!$view->file_exists(dirname($newKeyfilePath))) {
				mkdir(dirname($view->getLocalFile($newKeyfilePath)), 0750, true);
			}

			$view->rename($oldKeyfilePath, $newKeyfilePath);
		}

		// build the path to the file
		$newPath = '/' . $ownerNew . '/files' . $pathNew;

		$util->fixFileSize($newPath);

		// update sharing-keys
		self::updateKeyfiles($params['newpath'], $type);

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
	 * @brief if the file was really deleted we remove the encryption keys
	 * @param array $params
	 * @return boolean
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
	 * @brief remember the file which should be deleted and it's owner
	 * @param array $params
	 * @return boolean
	 */
	public static function preDelete($params) {
		$path = $params[\OC\Files\Filesystem::signal_param_path];

		// skip this method if the trash bin is enabled or if we delete a file
		// outside of /data/user/files
		if (\OCP\App::isEnabled('files_trashbin')) {
			return true;
		}

		$util = new Util(new \OC_FilesystemView('/'), \OCP\USER::getUser());
		list($owner, $ownerPath) = $util->getUidAndFilename($path);

		self::$deleteFiles[$params[\OC\Files\Filesystem::signal_param_path]] = array(
			'uid' => $owner,
			'path' => $ownerPath);
	}

}
