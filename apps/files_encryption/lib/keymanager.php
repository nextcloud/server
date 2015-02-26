<?php

/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
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
 * Class to manage storage and retrieval of encryption keys
 * @note Where a method requires a view object, it's root must be '/'
 */
class Keymanager {

	// base dir where all the file related keys are stored
	private static $keys_base_dir = '/files_encryption/keys/';
	private static $encryption_base_dir = '/files_encryption';
	private static $public_key_dir = '/files_encryption/public_keys';

	private static $key_cache = array(); // cache keys

	/**
	 * read key from hard disk
	 *
	 * @param string $path to key
	 * @param \OC\Files\View $view
	 * @return string|bool either the key or false
	 */
	private static function getKey($path, $view) {

		$key = false;

		if (isset(self::$key_cache[$path])) {
			$key =  self::$key_cache[$path];
		} else {

			/** @var \OCP\Files\Storage $storage */
			list($storage, $internalPath) = $view->resolvePath($path);

			if ($storage->file_exists($internalPath)) {
				$key = $storage->file_get_contents($internalPath);
				self::$key_cache[$path] = $key;
			}

		}

		return $key;
	}

	/**
	 * write key to disk
	 *
	 *
	 * @param string $path path to key directory
	 * @param string $name key name
	 * @param string $key key
	 * @param \OC\Files\View $view
	 * @return bool
	 */
	private static function setKey($path, $name, $key, $view) {
		self::keySetPreparation($view, $path);

		/** @var \OCP\Files\Storage $storage */
		$pathToKey = \OC\Files\Filesystem::normalizePath($path . '/' . $name);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($pathToKey);
		$result = $storage->file_put_contents($internalPath, $key);

		if (is_int($result) && $result > 0) {
			self::$key_cache[$pathToKey] = $key;
			return true;
		}

		return false;
	}

	/**
	 * retrieve the ENCRYPTED private key from a user
	 *
	 * @param \OC\Files\View $view
	 * @param string $user
	 * @return string private key or false (hopefully)
	 * @note the key returned by this method must be decrypted before use
	 */
	public static function getPrivateKey(\OC\Files\View $view, $user) {
		$path = '/' . $user . '/' . 'files_encryption' . '/' . $user . '.privateKey';
		return self::getKey($path, $view);
	}

	/**
	 * retrieve public key for a specified user
	 * @param \OC\Files\View $view
	 * @param string $userId
	 * @return string public key or false
	 */
	public static function getPublicKey(\OC\Files\View $view, $userId) {
		$path = self::$public_key_dir . '/' . $userId . '.publicKey';
		return self::getKey($path, $view);
	}

	public static function getPublicKeyPath() {
		return self::$public_key_dir;
	}

	/**
	 * Retrieve a user's public and private key
	 * @param \OC\Files\View $view
	 * @param string $userId
	 * @return array keys: privateKey, publicKey
	 */
	public static function getUserKeys(\OC\Files\View $view, $userId) {

		return array(
			'publicKey' => self::getPublicKey($view, $userId),
			'privateKey' => self::getPrivateKey($view, $userId)
		);

	}

	/**
	 * Retrieve public keys for given users
	 * @param \OC\Files\View $view
	 * @param array $userIds
	 * @return array of public keys for the specified users
	 */
	public static function getPublicKeys(\OC\Files\View $view, array $userIds) {

		$keys = array();
		foreach ($userIds as $userId) {
			$keys[$userId] = self::getPublicKey($view, $userId);
		}

		return $keys;

	}

	/**
	 * store file encryption key
	 *
	 * @param \OC\Files\View $view
	 * @param \OCA\Files_Encryption\Util $util
	 * @param string $path relative path of the file, including filename
	 * @param string $catfile keyfile content
	 * @return bool true/false
	 * @note The keyfile is not encrypted here. Client code must
	 * asymmetrically encrypt the keyfile before passing it to this method
	 */
	public static function setFileKey(\OC\Files\View $view, $util, $path, $catfile) {
		$path = self::getKeyPath($view, $util, $path);
		return self::setKey($path, 'fileKey', $catfile, $view);

	}

	/**
	 * get path to key folder for a given file
	 *
	 * @param \OC\Files\View $view relative to data directory
	 * @param \OCA\Files_Encryption\Util $util
	 * @param string $path path to the file, relative to the users file directory
	 * @return string
	 */
	public static function getKeyPath($view, $util, $path) {

		if ($view->is_dir('/' . \OCP\User::getUser() . '/' . $path)) {
			throw new Exception\EncryptionException('file was expected but directoy was given', Exception\EncryptionException::GENERIC);
		}

		list($owner, $filename) = $util->getUidAndFilename($path);
		$filename = Helper::stripPartialFileExtension($filename);
		$filePath_f = ltrim($filename, '/');

		// in case of system wide mount points the keys are stored directly in the data directory
		if ($util->isSystemWideMountPoint($filename)) {
			$keyPath = self::$keys_base_dir . $filePath_f . '/';
		} else {
			$keyPath = '/' . $owner . self::$keys_base_dir . $filePath_f . '/';
		}

		return $keyPath;
	}

	/**
	 * get path to file key for a given file
	 *
	 * @param \OC\Files\View $view relative to data directory
	 * @param \OCA\Files_Encryption\Util $util
	 * @param string $path path to the file, relative to the users file directory
	 * @return string
	 */
	public static function getFileKeyPath($view, $util, $path) {
		$keyDir = self::getKeyPath($view, $util, $path);
		return $keyDir . 'fileKey';
	}

	/**
	 * get path to share key for a given user
	 *
	 * @param \OC\Files\View $view relateive to data directory
	 * @param \OCA\Files_Encryption\Util $util
	 * @param string $path path to file relative to the users files directoy
	 * @param string $uid user for whom we want the share-key path
	 * @retrun string
	 */
	public static function getShareKeyPath($view, $util, $path, $uid) {
		$keyDir = self::getKeyPath($view, $util, $path);
		return $keyDir . $uid . '.shareKey';
	}

	/**
	 * delete key
	 *
	 * @param \OC\Files\View $view
	 * @param string $path
	 * @return boolean
	 */
	private static function deleteKey($view, $path) {
		$normalizedPath = \OC\Files\Filesystem::normalizePath($path);
		$result = $view->unlink($normalizedPath);

		if ($result) {
			unset(self::$key_cache[$normalizedPath]);
			return true;
		}

		return false;
	}

	/**
	 * delete public key from a given user
	 *
	 * @param \OC\Files\View $view
	 * @param string $uid user
	 * @return bool
	 */
	public static function deletePublicKey($view, $uid) {

		$result = false;

		if (!\OCP\User::userExists($uid)) {
			$publicKey = self::$public_key_dir . '/' . $uid . '.publicKey';
			self::deleteKey($view, $publicKey);
		}

		return $result;
	}

	/**
	 * check if public key for user exists
	 *
	 * @param \OC\Files\View $view
	 * @param string $uid
	 */
	public static function publicKeyExists($view, $uid) {
		return $view->file_exists(self::$public_key_dir . '/'. $uid . '.publicKey');
	}



	/**
	 * retrieve keyfile for an encrypted file
	 * @param \OC\Files\View $view
	 * @param \OCA\Files_Encryption\Util $util
	 * @param string|false $filePath
	 * @return string file key or false
	 * @note The keyfile returned is asymmetrically encrypted. Decryption
	 * of the keyfile must be performed by client code
	 */
	public static function getFileKey($view, $util, $filePath) {
		$path = self::getFileKeyPath($view, $util, $filePath);
		return self::getKey($path, $view);
	}

	/**
	 * store private key from the user
	 * @param string $key
	 * @return bool
	 * @note Encryption of the private key must be performed by client code
	 * as no encryption takes place here
	 */
	public static function setPrivateKey($key, $user = '') {

		$user = $user === '' ? \OCP\User::getUser() : $user;
		$path = '/' . $user . '/files_encryption';
		$header = Crypt::generateHeader();

		return self::setKey($path, $user . '.privateKey', $header . $key, new \OC\Files\View());

	}

	/**
	 * check if recovery key exists
	 *
	 * @param \OC\Files\View $view
	 * @return bool
	 */
	public static function recoveryKeyExists($view) {

		$result = false;

		$recoveryKeyId = Helper::getRecoveryKeyId();
		if ($recoveryKeyId) {
			$result = ($view->file_exists(self::$public_key_dir . '/' . $recoveryKeyId . ".publicKey")
					&& $view->file_exists(self::$encryption_base_dir . '/' . $recoveryKeyId . ".privateKey"));
		}

		return $result;
	}

	public static function publicShareKeyExists($view) {
		$result = false;

		$publicShareKeyId = Helper::getPublicShareKeyId();
		if ($publicShareKeyId) {
			$result = ($view->file_exists(self::$public_key_dir . '/' . $publicShareKeyId . ".publicKey")
					&& $view->file_exists(self::$encryption_base_dir . '/' . $publicShareKeyId . ".privateKey"));

		}

		return $result;
	}

	/**
	 * store public key from the user
	 * @param string $key
	 * @param string $user
	 *
	 * @return bool
	 */
	public static function setPublicKey($key, $user = '') {

		$user = $user === '' ? \OCP\User::getUser() : $user;

		return self::setKey(self::$public_key_dir, $user . '.publicKey', $key, new \OC\Files\View('/'));
	}

	/**
	 * write private system key (recovery and public share key) to disk
	 *
	 * @param string $key encrypted key
	 * @param string $keyName name of the key
	 * @return boolean
	 */
	public static function setPrivateSystemKey($key, $keyName) {

		$keyName = $keyName . '.privateKey';
		$header = Crypt::generateHeader();

		return self::setKey(self::$encryption_base_dir, $keyName,$header . $key, new \OC\Files\View());
	}

	/**
	 * read private system key (recovery and public share key) from disk
	 *
	 * @param string $keyName name of the key
	 * @return string|boolean private system key or false
	 */
	public static function getPrivateSystemKey($keyName) {
		$path = $keyName . '.privateKey';
		return self::getKey($path, new \OC\Files\View(self::$encryption_base_dir));
	}

	/**
	 * store multiple share keys for a single file
	 * @param \OC\Files\View $view
	 * @param \OCA\Files_Encryption\Util $util
	 * @param string $path
	 * @param array $shareKeys
	 * @return bool
	 */
	public static function setShareKeys($view, $util, $path, array $shareKeys) {

		// in case of system wide mount points the keys are stored directly in the data directory
		$basePath = Keymanager::getKeyPath($view, $util, $path);

		self::keySetPreparation($view, $basePath);

		$result = true;

		foreach ($shareKeys as $userId => $shareKey) {
			if (!self::setKey($basePath, $userId . '.shareKey', $shareKey, $view)) {
				// If any of the keys are not set, flag false
				$result = false;
			}
		}

		// Returns false if any of the keys weren't set
		return $result;
	}

	/**
	 * retrieve shareKey for an encrypted file
	 * @param \OC\Files\View $view
	 * @param string $userId
	 * @param \OCA\Files_Encryption\Util $util
	 * @param string $filePath
	 * @return string file key or false
	 * @note The sharekey returned is encrypted. Decryption
	 * of the keyfile must be performed by client code
	 */
	public static function getShareKey($view, $userId, $util, $filePath) {
		$path = self::getShareKeyPath($view, $util, $filePath, $userId);
		return self::getKey($path, $view);
	}

	/**
	 * Delete a single user's shareKey for a single file
	 *
	 * @param \OC\Files\View $view relative to data/
	 * @param array $userIds list of users we want to remove
	 * @param string $keyPath
	 * @param string $owner the owner of the file
	 * @param string $ownerPath the owners name of the file for which we want to remove the users relative to data/user/files
	 */
	public static function delShareKey($view, $userIds, $keysPath, $owner, $ownerPath) {

		$key = array_search($owner, $userIds, true);
		if ($key !== false && $view->file_exists('/' . $owner . '/files/' . $ownerPath)) {
			unset($userIds[$key]);
		}

		self::recursiveDelShareKeys($keysPath, $userIds, $view);

	}

	/**
	 * recursively delete share keys from given users
	 *
	 * @param string $dir directory
	 * @param array $userIds user ids for which the share keys should be deleted
	 * @param \OC\Files\View $view view relative to data/
	 */
	private static function recursiveDelShareKeys($dir, $userIds, $view) {

		$dirContent = $view->opendir($dir);

		if (is_resource($dirContent)) {
			while (($file = readdir($dirContent)) !== false) {
				if (!\OC\Files\Filesystem::isIgnoredDir($file)) {
					if ($view->is_dir($dir . '/' . $file)) {
						self::recursiveDelShareKeys($dir . '/' . $file, $userIds, $view);
					} else {
						foreach ($userIds as $userId) {
							if ($userId . '.shareKey' === $file) {
								\OCP\Util::writeLog('files_encryption', 'recursiveDelShareKey: delete share key: ' . $file, \OCP\Util::DEBUG);
								self::deleteKey($view, $dir . '/' . $file);
							}
						}
					}
				}
			}
			closedir($dirContent);
		}
	}

	/**
	 * Make preparations to vars and filesystem for saving a keyfile
	 *
	 * @param \OC\Files\View $view
	 * @param string $path relatvie to the views root
	 * @param string $basePath
	 */
	protected static function keySetPreparation($view, $path) {
		// If the file resides within a subdirectory, create it
		if (!$view->file_exists($path)) {
			$sub_dirs = explode('/', $path);
			$dir = '';
			foreach ($sub_dirs as $sub_dir) {
				$dir .= '/' . $sub_dir;
				if (!$view->is_dir($dir)) {
					$view->mkdir($dir);
				}
			}
		}
	}

}
