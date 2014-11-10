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
 * Class to manage storage and retrieval of encryption keys
 * @note Where a method requires a view object, it's root must be '/'
 */
class Keymanager {

	// base dir where all the file related keys are stored
	const KEYS_BASE_DIR = '/files_encryption/keys/';

	/**
	 * retrieve the ENCRYPTED private key from a user
	 *
	 * @param \OC\Files\View $view
	 * @param string $user
	 * @return string private key or false (hopefully)
	 * @note the key returned by this method must be decrypted before use
	 */
	public static function getPrivateKey(\OC\Files\View $view, $user) {

		$path = '/' . $user . '/' . 'files_encryption' . '/' . $user . '.private.key';
		$key = false;

		if ($view->file_exists($path)) {
			$key = $view->file_get_contents($path);
		}

		return $key;
	}

	/**
	 * retrieve public key for a specified user
	 * @param \OC\Files\View $view
	 * @param string $userId
	 * @return string public key or false
	 */
	public static function getPublicKey(\OC\Files\View $view, $userId) {

		$result = $view->file_get_contents('/public-keys/' . $userId . '.public.key');

		return $result;

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
	 * @param \OCA\Encryption\Util $util
	 * @param string $path relative path of the file, including filename
	 * @param string $catfile keyfile content
	 * @return bool true/false
	 * @note The keyfile is not encrypted here. Client code must
	 * asymmetrically encrypt the keyfile before passing it to this method
	 */
	public static function setFileKey(\OC\Files\View $view, $util, $path, $catfile) {

		$basePath = self::getKeyPath($view, $util, $path);

		self::keySetPreparation($view, $basePath);

		$result = $view->file_put_contents(
				$basePath . '/fileKey', $catfile);

		return $result;

	}

	/**
	 * get path to key folder for a given file
	 *
	 * @param \OC\Files\View $view relative to data directory
	 * @param \OCA\Encryption\Util $util
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
			$keyPath = self::KEYS_BASE_DIR . $filePath_f . '/';
		} else {
			$keyPath = '/' . $owner . self::KEYS_BASE_DIR . $filePath_f . '/';
		}

		return $keyPath;
	}

	/**
	 * get path to file key for a given file
	 *
	 * @param \OC\Files\View $view relative to data directory
	 * @param \OCA\Encryption\Util $util
	 * @param string $path path to the file, relative to the users file directory
	 * @return string
	 */
	public static function getFileKeyPath($view, $util, $path) {

		if ($view->is_dir('/' . \OCP\User::getUser() . '/' . $path)) {
			throw new Exception\EncryptionException('file was expected but directoy was given', Exception\EncryptionException::GENERIC);
		}

		list($owner, $filename) = $util->getUidAndFilename($path);
		$filename = Helper::stripPartialFileExtension($filename);
		$filePath_f = ltrim($filename, '/');

		// in case of system wide mount points the keys are stored directly in the data directory
		if ($util->isSystemWideMountPoint($filename)) {
			$keyfilePath = self::KEYS_BASE_DIR . $filePath_f . '/fileKey';
		} else {
			$keyfilePath = '/' . $owner . self::KEYS_BASE_DIR . $filePath_f . '/fileKey';
		}

		return $keyfilePath;
	}

	/**
	 * get path to share key for a given user
	 *
	 * @param \OC\Files\View $view relateive to data directory
	 * @param \OCA\Encryption\Util $util
	 * @param string $path path to file relative to the users files directoy
	 * @param string $uid user for whom we want the share-key path
	 * @retrun string
	 */
	public static function getShareKeyPath($view, $util, $path, $uid) {

		if ($view->is_dir('/' . \OCP\User::getUser() . '/' . $path)) {
			throw new Exception\EncryptionException('file was expected but directoy was given', Exception\EncryptionException::GENERIC);
		}

		list($owner, $filename) = $util->getUidAndFilename($path);
		$filename = Helper::stripPartialFileExtension($filename);

		// in case of system wide mount points the keys are stored directly in the data directory
		if ($util->isSystemWideMountPoint($filename)) {
			$shareKeyPath = self::KEYS_BASE_DIR . $filename . '/'. $uid . '.shareKey';
		} else {
			$shareKeyPath = '/' . $owner . self::KEYS_BASE_DIR . $filename . '/' . $uid . '.shareKey';
		}

		return $shareKeyPath;
	}



	/**
	 * retrieve keyfile for an encrypted file
	 * @param \OC\Files\View $view
	 * @param \OCA\Encryption\Util $util
	 * @param string|false $filePath
	 * @internal param \OCA\Encryption\file $string name
	 * @return string file key or false
	 * @note The keyfile returned is asymmetrically encrypted. Decryption
	 * of the keyfile must be performed by client code
	 */
	public static function getFileKey($view, $util, $filePath) {

		$keyfilePath = self::getFileKeyPath($view, $util, $filePath);

		if ($view->file_exists($keyfilePath)) {
			$result = $view->file_get_contents($keyfilePath);
		} else {
			$result = false;
		}

		return $result;

	}

	/**
	 * store private key from the user
	 * @param string $key
	 * @return bool
	 * @note Encryption of the private key must be performed by client code
	 * as no encryption takes place here
	 */
	public static function setPrivateKey($key, $user = '') {

		if ($user === '') {
			$user = \OCP\User::getUser();
		}

		$header = Crypt::generateHeader();

		$view = new \OC\Files\View('/' . $user . '/files_encryption');

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		if (!$view->file_exists('')) {
			$view->mkdir('');
		}

		$result = $view->file_put_contents($user . '.private.key', $header . $key);

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;

	}

	/**
	 * write private system key (recovery and public share key) to disk
	 *
	 * @param string $key encrypted key
	 * @param string $keyName name of the key file
	 * @return boolean
	 */
	public static function setPrivateSystemKey($key, $keyName) {

		$header = Crypt::generateHeader();

		$view = new \OC\Files\View('/owncloud_private_key');

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		if (!$view->file_exists('')) {
			$view->mkdir('');
		}

		$result = $view->file_put_contents($keyName, $header . $key);

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;
	}

	/**
	 * store share key
	 *
	 * @param \OC\Files\View $view
	 * @param string $path where the share key is stored
	 * @param string $shareKey
	 * @return bool true/false
	 * @note The keyfile is not encrypted here. Client code must
	 * asymmetrically encrypt the keyfile before passing it to this method
	 */
	private static function setShareKey(\OC\Files\View $view, $path, $shareKey) {

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
	 * store multiple share keys for a single file
	 * @param \OC\Files\View $view
	 * @param \OCA\Encryption\Util $util
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

			$writePath = $basePath . '/' . $userId . '.shareKey';

			if (!self::setShareKey($view, $writePath, $shareKey)) {

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
	 * @param \OCA\Encryption\Util $util
	 * @param string $filePath
	 * @return string file key or false
	 * @note The sharekey returned is encrypted. Decryption
	 * of the keyfile must be performed by client code
	 */
	public static function getShareKey($view, $userId, $util, $filePath) {

		$shareKeyPath = self::getShareKeyPath($view, $util, $filePath, $userId);

		if ($view->file_exists($shareKeyPath)) {
			$result = $view->file_get_contents($shareKeyPath);
		} else {
			$result = false;
		}

		return $result;
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
								$view->unlink($dir . '/' . $file);
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
