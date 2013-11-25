<?php

/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2012 Bjoern Schiessle <schiessle@owncloud.com>
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

/**
 * @brief Class to manage storage and retrieval of encryption keys
 * @note Where a method requires a view object, it's root must be '/'
 */
class Keymanager {

	/**
	 * @brief retrieve the ENCRYPTED private key from a user
	 *
	 * @param \OC_FilesystemView $view
	 * @param string $user
	 * @return string private key or false (hopefully)
	 * @note the key returned by this method must be decrypted before use
	 */
	public static function getPrivateKey(\OC_FilesystemView $view, $user) {

		$path = '/' . $user . '/' . 'files_encryption' . '/' . $user . '.private.key';
		$key = false;

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		if ($view->file_exists($path)) {
			$key = $view->file_get_contents($path);
		}

		\OC_FileProxy::$enabled = $proxyStatus;

		return $key;
	}

	/**
	 * @brief retrieve public key for a specified user
	 * @param \OC_FilesystemView $view
	 * @param $userId
	 * @return string public key or false
	 */
	public static function getPublicKey(\OC_FilesystemView $view, $userId) {

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$result = $view->file_get_contents('/public-keys/' . $userId . '.public.key');

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;

	}

	/**
	 * @brief Retrieve a user's public and private key
	 * @param \OC_FilesystemView $view
	 * @param $userId
	 * @return array keys: privateKey, publicKey
	 */
	public static function getUserKeys(\OC_FilesystemView $view, $userId) {

		return array(
			'publicKey' => self::getPublicKey($view, $userId),
			'privateKey' => self::getPrivateKey($view, $userId)
		);

	}

	/**
	 * @brief Retrieve public keys for given users
	 * @param \OC_FilesystemView $view
	 * @param array $userIds
	 * @return array of public keys for the specified users
	 */
	public static function getPublicKeys(\OC_FilesystemView $view, array $userIds) {

		$keys = array();

		foreach ($userIds as $userId) {

			$keys[$userId] = self::getPublicKey($view, $userId);

		}

		return $keys;

	}

	/**
	 * @brief store file encryption key
	 *
	 * @param \OC_FilesystemView $view
	 * @param string $path relative path of the file, including filename
	 * @param $userId
	 * @param $catfile
	 * @internal param string $key
	 * @return bool true/false
	 * @note The keyfile is not encrypted here. Client code must
	 * asymmetrically encrypt the keyfile before passing it to this method
	 */
	public static function setFileKey(\OC_FilesystemView $view, $path, $userId, $catfile) {

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		//here we need the currently logged in user, while userId can be a different user
		$util = new Util($view, \OCP\User::getUser());
		list($owner, $filename) = $util->getUidAndFilename($path);

		// in case of system wide mount points the keys are stored directly in the data directory
		if ($util->isSystemWideMountPoint($filename)) {
			$basePath = '/files_encryption/keyfiles';
		} else {
			$basePath = '/' . $owner . '/files_encryption/keyfiles';
		}

		$targetPath = self::keySetPreparation($view, $filename, $basePath, $owner);

		if (!$view->is_dir($basePath . '/' . $targetPath)) {

			// create all parent folders
			$info = pathinfo($basePath . '/' . $targetPath);
			$keyfileFolderName = $view->getLocalFolder($info['dirname']);

			if (!file_exists($keyfileFolderName)) {

				mkdir($keyfileFolderName, 0750, true);

			}
		}

		// try reusing key file if part file
		if (Helper::isPartialFilePath($targetPath)) {

			$result = $view->file_put_contents(
				$basePath . '/' . Helper::stripPartialFileExtension($targetPath) . '.key', $catfile);

		} else {

			$result = $view->file_put_contents($basePath . '/' . $targetPath . '.key', $catfile);

		}

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;

	}

	/**
	 * @brief retrieve keyfile for an encrypted file
	 * @param \OC_FilesystemView $view
	 * @param $filePath
	 * @internal param \OCA\Encryption\file $string name
	 * @return string file key or false
	 * @note The keyfile returned is asymmetrically encrypted. Decryption
	 * of the keyfile must be performed by client code
	 */
	public static function getFileKey(\OC_FilesystemView $view, $filePath) {

		$util = new Util($view, \OCP\User::getUser());

		list($owner, $filename) = $util->getUidAndFilename($filePath);
		$filename = Helper::stripPartialFileExtension($filename);
		$filePath_f = ltrim($filename, '/');

		// in case of system wide mount points the keys are stored directly in the data directory
		if ($util->isSystemWideMountPoint($filename)) {
			$keyfilePath = '/files_encryption/keyfiles/' . $filePath_f . '.key';
		} else {
			$keyfilePath = '/' . $owner . '/files_encryption/keyfiles/' . $filePath_f . '.key';
		}

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		if ($view->file_exists($keyfilePath)) {

			$result = $view->file_get_contents($keyfilePath);

		} else {

			$result = false;

		}

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;

	}

	/**
	 * @brief Delete a keyfile
	 *
	 * @param \OC_FilesystemView $view
	 * @param string $userId username
	 * @param string $path path of the file the key belongs to
	 * @return bool Outcome of unlink operation
	 * @note $path must be relative to data/user/files. e.g. mydoc.txt NOT
	 *       /data/admin/files/mydoc.txt
	 */
	public static function deleteFileKey(\OC_FilesystemView $view, $userId, $path) {

		$trimmed = ltrim($path, '/');

		$util = new Util($view, \OCP\User::getUser());

		if($util->isSystemWideMountPoint($path)) {
			$keyPath = '/files_encryption/keyfiles/' . $trimmed;
		} else {
			$keyPath = '/' . $userId . '/files_encryption/keyfiles/' . $trimmed;
		}

		$result = false;

		if ($view->is_dir($keyPath)) {

			$result = $view->unlink($keyPath);

		} else {
			if ($view->file_exists($keyPath . '.key')) {

				$result = $view->unlink($keyPath . '.key');

			}
		}

		if (!$result) {

			\OCP\Util::writeLog('Encryption library',
				'Could not delete keyfile; does not exist: "' . $keyPath, \OCP\Util::ERROR);

		}

		return $result;

	}

	/**
	 * @brief store private key from the user
	 * @param string $key
	 * @return bool
	 * @note Encryption of the private key must be performed by client code
	 * as no encryption takes place here
	 */
	public static function setPrivateKey($key) {

		$user = \OCP\User::getUser();

		$view = new \OC_FilesystemView('/' . $user . '/files_encryption');

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		if (!$view->file_exists(''))
			$view->mkdir('');

		$result = $view->file_put_contents($user . '.private.key', $key);

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;

	}

	/**
	 * @brief store share key
	 *
	 * @param \OC_FilesystemView $view
	 * @param string $path where the share key is stored
	 * @param $shareKey
	 * @return bool true/false
	 * @note The keyfile is not encrypted here. Client code must
	 * asymmetrically encrypt the keyfile before passing it to this method
	 */
	private static function setShareKey(\OC_FilesystemView $view, $path, $shareKey) {

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$result = $view->file_put_contents($path, $shareKey);

		\OC_FileProxy::$enabled = $proxyStatus;

		if (is_int($result) && $result > 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @brief store multiple share keys for a single file
	 * @param \OC_FilesystemView $view
	 * @param $path
	 * @param array $shareKeys
	 * @return bool
	 */
	public static function setShareKeys(\OC_FilesystemView $view, $path, array $shareKeys) {

		// $shareKeys must be  an array with the following format:
		// [userId] => [encrypted key]
		// Here we need the currently logged in user, while userId can be a different user
		$util = new Util($view, \OCP\User::getUser());

		list($owner, $filename) = $util->getUidAndFilename($path);

		// in case of system wide mount points the keys are stored directly in the data directory
		if ($util->isSystemWideMountPoint($filename)) {
			$basePath = '/files_encryption/share-keys';
		} else {
			$basePath = '/' . $owner . '/files_encryption/share-keys';
		}

		$shareKeyPath = self::keySetPreparation($view, $filename, $basePath, $owner);

		$result = true;

		foreach ($shareKeys as $userId => $shareKey) {

			// try reusing key file if part file
			if (Helper::isPartialFilePath($shareKeyPath)) {
				$writePath = $basePath . '/' . Helper::stripPartialFileExtension($shareKeyPath) . '.' . $userId . '.shareKey';
			} else {
				$writePath = $basePath . '/' . $shareKeyPath . '.' . $userId . '.shareKey';
			}

			if (!self::setShareKey($view, $writePath, $shareKey)) {

				// If any of the keys are not set, flag false
				$result = false;
			}
		}

		// Returns false if any of the keys weren't set
		return $result;
	}

	/**
	 * @brief retrieve shareKey for an encrypted file
	 * @param \OC_FilesystemView $view
	 * @param string $userId
	 * @param string $filePath
	 * @internal param \OCA\Encryption\file $string name
	 * @return string file key or false
	 * @note The sharekey returned is encrypted. Decryption
	 * of the keyfile must be performed by client code
	 */
	public static function getShareKey(\OC_FilesystemView $view, $userId, $filePath) {

		// try reusing key file if part file
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		//here we need the currently logged in user, while userId can be a different user
		$util = new Util($view, \OCP\User::getUser());

		list($owner, $filename) = $util->getUidAndFilename($filePath);
		$filename = Helper::stripPartialFileExtension($filename);
		// in case of system wide mount points the keys are stored directly in the data directory
		if ($util->isSystemWideMountPoint($filename)) {
			$shareKeyPath = '/files_encryption/share-keys/' . $filename . '.' . $userId . '.shareKey';
		} else {
			$shareKeyPath = '/' . $owner . '/files_encryption/share-keys/' . $filename . '.' . $userId . '.shareKey';
		}

		if ($view->file_exists($shareKeyPath)) {

			$result = $view->file_get_contents($shareKeyPath);

		} else {

			$result = false;

		}

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;

	}

	/**
	 * @brief delete all share keys of a given file
	 * @param \OC_FilesystemView $view
	 * @param string $userId owner of the file
	 * @param string $filePath path to the file, relative to the owners file dir
	 */
	public static function delAllShareKeys(\OC_FilesystemView $view, $userId, $filePath) {

		$util = new util($view, $userId);

		if ($util->isSystemWideMountPoint($filePath)) {
			$baseDir = '/files_encryption/share-keys/';
		} else {
			$baseDir = $userId . '/files_encryption/share-keys/';
		}


		if ($view->is_dir($userId . '/files/' . $filePath)) {
			$view->unlink($baseDir . $filePath);
		} else {
			$localKeyPath = $view->getLocalFile($baseDir . $filePath);
			$escapedPath = Helper::escapeGlobPattern($localKeyPath);
			$matches = glob($escapedPath . '*.shareKey');
			foreach ($matches as $ma) {
				$result = unlink($ma);
				if (!$result) {
					\OCP\Util::writeLog('Encryption library',
						'Keyfile or shareKey could not be deleted for file "' . $filePath . '"', \OCP\Util::ERROR);
				}
			}
		}
	}

	/**
	 * @brief Delete a single user's shareKey for a single file
	 */
	public static function delShareKey(\OC_FilesystemView $view, $userIds, $filePath) {

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		//here we need the currently logged in user, while userId can be a different user
		$util = new Util($view, \OCP\User::getUser());

		list($owner, $filename) = $util->getUidAndFilename($filePath);

		if ($util->isSystemWideMountPoint($filename)) {
			$shareKeyPath = \OC\Files\Filesystem::normalizePath('/files_encryption/share-keys/' . $filename);
		} else {
			$shareKeyPath = \OC\Files\Filesystem::normalizePath('/' . $owner . '/files_encryption/share-keys/' . $filename);
		}

		if ($view->is_dir($shareKeyPath)) {

			$localPath = \OC\Files\Filesystem::normalizePath($view->getLocalFolder($shareKeyPath));
			self::recursiveDelShareKeys($localPath, $userIds);

		} else {

			foreach ($userIds as $userId) {

				if (!$view->unlink($shareKeyPath . '.' . $userId . '.shareKey')) {
					\OCP\Util::writeLog('Encryption library',
						'Could not delete shareKey; does not exist: "' . $shareKeyPath . '.' . $userId
						. '.shareKey"', \OCP\Util::ERROR);
				}

			}
		}

		\OC_FileProxy::$enabled = $proxyStatus;
	}

	/**
	 * @brief recursively delete share keys from given users
	 *
	 * @param string $dir directory
	 * @param array $userIds user ids for which the share keys should be deleted
	 */
	private static function recursiveDelShareKeys($dir, $userIds) {
		foreach ($userIds as $userId) {
			$extension = '.' . $userId . '.shareKey';
			$escapedDir = Helper::escapeGlobPattern($dir);
			$escapedExtension = Helper::escapeGlobPattern($extension);
			$matches = glob($escapedDir . '/*' . $escapedExtension);
		}
		/** @var $matches array */
		foreach ($matches as $ma) {
			if (!unlink($ma)) {
				\OCP\Util::writeLog('Encryption library',
					'Could not delete shareKey; does not exist: "' . $ma . '"', \OCP\Util::ERROR);
			}
		}
		$subdirs = $directories = glob($escapedDir . '/*', GLOB_ONLYDIR);
		foreach ($subdirs as $subdir) {
			self::recursiveDelShareKeys($subdir, $userIds);
		}
	}

	/**
	 * @brief Make preparations to vars and filesystem for saving a keyfile
	 */
	public static function keySetPreparation(\OC_FilesystemView $view, $path, $basePath, $userId) {

		$targetPath = ltrim($path, '/');

		$path_parts = pathinfo($targetPath);

		// If the file resides within a subdirectory, create it
		if (
			isset($path_parts['dirname'])
			&& !$view->file_exists($basePath . '/' . $path_parts['dirname'])
		) {
			$sub_dirs = explode(DIRECTORY_SEPARATOR, $basePath . '/' . $path_parts['dirname']);
			$dir = '';
			foreach ($sub_dirs as $sub_dir) {
				$dir .= '/' . $sub_dir;
				if (!$view->is_dir($dir)) {
					$view->mkdir($dir);
				}
			}
		}

		return $targetPath;

	}
}
