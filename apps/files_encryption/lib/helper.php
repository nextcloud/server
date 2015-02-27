<?php

/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Florin Peter <owncloud@florin-peter.de>
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
 * Class to manage registration of hooks an various helper methods
 * @package OCA\Files_Encryption
 */
class Helper {

	private static $tmpFileMapping; // Map tmp files to files in data/user/files

	/**
	 * register share related hooks
	 *
	 */
	public static function registerShareHooks() {

		\OCP\Util::connectHook('OCP\Share', 'pre_shared', 'OCA\Files_Encryption\Hooks', 'preShared');
		\OCP\Util::connectHook('OCP\Share', 'post_shared', 'OCA\Files_Encryption\Hooks', 'postShared');
		\OCP\Util::connectHook('OCP\Share', 'post_unshare', 'OCA\Files_Encryption\Hooks', 'postUnshare');
	}

	/**
	 * register user related hooks
	 *
	 */
	public static function registerUserHooks() {

		\OCP\Util::connectHook('OC_User', 'post_login', 'OCA\Files_Encryption\Hooks', 'login');
		\OCP\Util::connectHook('OC_User', 'logout', 'OCA\Files_Encryption\Hooks', 'logout');
		\OCP\Util::connectHook('OC_User', 'post_setPassword', 'OCA\Files_Encryption\Hooks', 'setPassphrase');
		\OCP\Util::connectHook('OC_User', 'pre_setPassword', 'OCA\Files_Encryption\Hooks', 'preSetPassphrase');
		\OCP\Util::connectHook('OC_User', 'post_createUser', 'OCA\Files_Encryption\Hooks', 'postCreateUser');
		\OCP\Util::connectHook('OC_User', 'post_deleteUser', 'OCA\Files_Encryption\Hooks', 'postDeleteUser');
	}

	/**
	 * register filesystem related hooks
	 *
	 */
	public static function registerFilesystemHooks() {

		\OCP\Util::connectHook('OC_Filesystem', 'rename', 'OCA\Files_Encryption\Hooks', 'preRename');
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', 'OCA\Files_Encryption\Hooks', 'postRenameOrCopy');
		\OCP\Util::connectHook('OC_Filesystem', 'copy', 'OCA\Files_Encryption\Hooks', 'preCopy');
		\OCP\Util::connectHook('OC_Filesystem', 'post_copy', 'OCA\Files_Encryption\Hooks', 'postRenameOrCopy');
		\OCP\Util::connectHook('OC_Filesystem', 'post_delete', 'OCA\Files_Encryption\Hooks', 'postDelete');
		\OCP\Util::connectHook('OC_Filesystem', 'delete', 'OCA\Files_Encryption\Hooks', 'preDelete');
		\OCP\Util::connectHook('\OC\Core\LostPassword\Controller\LostController', 'post_passwordReset', 'OCA\Files_Encryption\Hooks', 'postPasswordReset');
		\OCP\Util::connectHook('OC_Filesystem', 'post_umount', 'OCA\Files_Encryption\Hooks', 'postUnmount');
		\OCP\Util::connectHook('OC_Filesystem', 'umount', 'OCA\Files_Encryption\Hooks', 'preUnmount');
	}

	/**
	 * register app management related hooks
	 *
	 */
	public static function registerAppHooks() {

		\OCP\Util::connectHook('OC_App', 'pre_disable', 'OCA\Files_Encryption\Hooks', 'preDisable');
		\OCP\Util::connectHook('OC_App', 'post_disable', 'OCA\Files_Encryption\Hooks', 'postEnable');
	}

	/**
	 * setup user for files_encryption
	 *
	 * @param Util $util
	 * @param string $password
	 * @return bool
	 */
	public static function setupUser(Util $util, $password) {
		// Check files_encryption infrastructure is ready for action
		if (!$util->ready()) {

			\OCP\Util::writeLog('Encryption library', 'User account "' . $util->getUserId()
													  . '" is not ready for encryption; configuration started', \OCP\Util::DEBUG);

			if (!$util->setupServerSide($password)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * get recovery key id
	 *
	 * @return string|bool recovery key ID or false
	 */
	public static function getRecoveryKeyId() {
		$appConfig = \OC::$server->getAppConfig();
		$key = $appConfig->getValue('files_encryption', 'recoveryKeyId');

		return ($key === null) ? false : $key;
	}

	public static function getPublicShareKeyId() {
		$appConfig = \OC::$server->getAppConfig();
		$key = $appConfig->getValue('files_encryption', 'publicShareKeyId');

		return ($key === null) ? false : $key;
	}

	/**
	 * enable recovery
	 *
	 * @param string $recoveryKeyId
	 * @param string $recoveryPassword
	 * @return bool
	 */
	public static function adminEnableRecovery($recoveryKeyId, $recoveryPassword) {

		$view = new \OC\Files\View('/');
		$appConfig = \OC::$server->getAppConfig();

		if ($recoveryKeyId === null) {
			$recoveryKeyId = 'recovery_' . substr(md5(time()), 0, 8);
			$appConfig->setValue('files_encryption', 'recoveryKeyId', $recoveryKeyId);
		}

		if (!Keymanager::recoveryKeyExists($view)) {

			$keypair = Crypt::createKeypair();

			// Save public key
			Keymanager::setPublicKey($keypair['publicKey'], $recoveryKeyId);

			$cipher = Helper::getCipher();
			$encryptedKey = Crypt::symmetricEncryptFileContent($keypair['privateKey'], $recoveryPassword, $cipher);
			if ($encryptedKey) {
				Keymanager::setPrivateSystemKey($encryptedKey, $recoveryKeyId);
				// Set recoveryAdmin as enabled
				$appConfig->setValue('files_encryption', 'recoveryAdminEnabled', 1);
				$return = true;
			}

		} else { // get recovery key and check the password
			$util = new Util(new \OC\Files\View('/'), \OCP\User::getUser());
			$return = $util->checkRecoveryPassword($recoveryPassword);
			if ($return) {
				$appConfig->setValue('files_encryption', 'recoveryAdminEnabled', 1);
			}
		}

		return $return;
	}

	/**
	 * Check if a path is a .part file
	 * @param string $path Path that may identify a .part file
	 * @return bool
	 */
	public static function isPartialFilePath($path) {

		$extension = pathinfo($path, PATHINFO_EXTENSION);
		if ( $extension === 'part') {
			return true;
		} else {
			return false;
		}

	}


	/**
	 * Remove .path extension from a file path
	 * @param string $path Path that may identify a .part file
	 * @return string File path without .part extension
	 * @note this is needed for reusing keys
	 */
	public static function stripPartialFileExtension($path) {
		$extension = pathinfo($path, PATHINFO_EXTENSION);

		if ( $extension === 'part') {

			$newLength = strlen($path) - 5; // 5 = strlen(".part") = strlen(".etmp")
			$fPath = substr($path, 0, $newLength);

			// if path also contains a transaction id, we remove it too
			$extension = pathinfo($fPath, PATHINFO_EXTENSION);
			if(substr($extension, 0, 12) === 'ocTransferId') { // 12 = strlen("ocTransferId")
				$newLength = strlen($fPath) - strlen($extension) -1;
				$fPath = substr($fPath, 0, $newLength);
			}
			return $fPath;

		} else {
			return $path;
		}
	}

	/**
	 * disable recovery
	 *
	 * @param string $recoveryPassword
	 * @return bool
	 */
	public static function adminDisableRecovery($recoveryPassword) {
		$util = new Util(new \OC\Files\View('/'), \OCP\User::getUser());
		$return = $util->checkRecoveryPassword($recoveryPassword);

		if ($return) {
			// Set recoveryAdmin as disabled
			\OC::$server->getAppConfig()->setValue('files_encryption', 'recoveryAdminEnabled', 0);
		}

		return $return;
	}

	/**
	 * checks if access is public/anonymous user
	 * @return bool
	 */
	public static function isPublicAccess() {
		if (\OCP\User::getUser() === false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Format a path to be relative to the /user/files/ directory
	 * @param string $path the absolute path
	 * @return string e.g. turns '/admin/files/test.txt' into 'test.txt'
	 */
	public static function stripUserFilesPath($path) {
		$split = self::splitPath($path);

		// it is not a file relative to data/user/files
		if (count($split) < 4 || $split[2] !== 'files') {
			return false;
		}

		$sliced = array_slice($split, 3);
		$relPath = implode('/', $sliced);

		return $relPath;
	}

	/**
	 * try to get the user from the path if no user is logged in
	 * @param string $path
	 * @return string user
	 */
	public static function getUser($path) {

		$user = \OCP\User::getUser();


		// if we are logged in, then we return the userid
		if ($user) {
			return $user;
		}

		// if no user is logged in we try to access a publicly shared files.
		// In this case we need to try to get the user from the path
		return self::getUserFromPath($path);
	}

	/**
	 * extract user from path
	 *
	 * @param string $path
	 * @return string user id
	 * @throws Exception\EncryptionException
	 */
	public static function getUserFromPath($path) {
		$split = self::splitPath($path);

		if (count($split) > 2 && (
			$split[2] === 'files' || $split[2] === 'files_versions' || $split[2] === 'cache' || $split[2] === 'files_trashbin')) {

			$user = $split[1];

			if (\OCP\User::userExists($user)) {
				return $user;
			}
		}

		throw new Exception\EncryptionException('Could not determine user', Exception\EncryptionException::GENERIC);
	}

	/**
	 * get path to the corresponding file in data/user/files if path points
	 * to a file in cache
	 *
	 * @param string $path path to a file in cache
	 * @return string path to corresponding file relative to data/user/files
	 * @throws Exception\EncryptionException
	 */
	public static function getPathFromCachedFile($path) {
		$split = self::splitPath($path);

		if (count($split) < 5) {
			throw new Exception\EncryptionException('no valid cache file path', Exception\EncryptionException::GENERIC);
		}

		// we skip /user/cache/transactionId
		$sliced = array_slice($split, 4);

		return implode('/', $sliced);
	}


	/**
	 * get path to the corresponding file in data/user/files for a version
	 *
	 * @param string $path path to a version
	 * @return string path to corresponding file relative to data/user/files
	 * @throws Exception\EncryptionException
	 */
	public static function getPathFromVersion($path) {
		$split = self::splitPath($path);

		if (count($split) < 4) {
			throw new Exception\EncryptionException('no valid path to a version', Exception\EncryptionException::GENERIC);
		}

		// we skip user/files_versions
		$sliced = array_slice($split, 3);
		$relPath = implode('/', $sliced);
		//remove the last .v
		$realPath = substr($relPath, 0, strrpos($relPath, '.v'));

		return $realPath;
	}

	/**
	 * create directory recursively
	 *
	 * @param string $path
	 * @param \OC\Files\View $view
	 */
	public static function mkdirr($path, \OC\Files\View $view) {
		$dirParts = self::splitPath(dirname($path));
		$dir = "";
		foreach ($dirParts as $part) {
			$dir = $dir . '/' . $part;
			if (!$view->file_exists($dir)) {
				$view->mkdir($dir);
			}
		}
	}

	/**
	 * redirect to a error page
	 * @param Session $session
	 * @param int|null $errorCode
	 * @throws \Exception
	 */
	public static function redirectToErrorPage(Session $session, $errorCode = null) {

		if ($errorCode === null) {
			$init = $session->getInitialized();
			switch ($init) {
				case Session::INIT_EXECUTED:
					$errorCode = Crypt::ENCRYPTION_PRIVATE_KEY_NOT_VALID_ERROR;
					break;
				case Session::NOT_INITIALIZED:
					$errorCode = Crypt::ENCRYPTION_NOT_INITIALIZED_ERROR;
					break;
				default:
					$errorCode = Crypt::ENCRYPTION_UNKNOWN_ERROR;
			}
		}

		$location = \OC_Helper::linkToAbsolute('apps/files_encryption/files', 'error.php');
		$post = 0;
		if(count($_POST) > 0) {
			$post = 1;
		}

		if(defined('PHPUNIT_RUN') and PHPUNIT_RUN) {
			throw new \Exception("Encryption error: $errorCode");
		}

		header('Location: ' . $location . '?p=' . $post . '&errorCode=' . $errorCode);
		exit();
	}

	/**
	 * check requirements for encryption app.
	 * @return bool true if requirements are met
	 */
	public static function checkRequirements() {

		//openssl extension needs to be loaded
		return extension_loaded("openssl");

	}

	/**
	 * check some common errors if the server isn't configured properly for encryption
	 * @return bool true if configuration seems to be OK
	 */
	public static function checkConfiguration() {
		if(self::getOpenSSLPkey()) {
			return true;
		} else {
			while ($msg = openssl_error_string()) {
				\OCP\Util::writeLog('Encryption library', 'openssl_pkey_new() fails:  ' . $msg, \OCP\Util::ERROR);
			}
			return false;
		}
	}

	/**
	 * Create an openssl pkey with config-supplied settings
	 * WARNING: This initializes a new private keypair, which is computationally expensive
	 * @return resource The pkey resource created
	 */
	public static function getOpenSSLPkey() {
		return openssl_pkey_new(self::getOpenSSLConfig());
	}

	/**
	 * Return an array of OpenSSL config options, default + config
	 * Used for multiple OpenSSL functions
	 * @return array The combined defaults and config settings
	 */
	public static function getOpenSSLConfig() {
		$config = array('private_key_bits' => 4096);
		$config = array_merge(\OC::$server->getConfig()->getSystemValue('openssl', array()), $config);
		return $config;
	}

	/**
	 * remember from which file the tmp file (getLocalFile() call) was created
	 * @param string $tmpFile path of tmp file
	 * @param string $originalFile path of the original file relative to data/
	 */
	public static function addTmpFileToMapper($tmpFile, $originalFile) {
		self::$tmpFileMapping[$tmpFile] = $originalFile;
	}

	/**
	 * get the path of the original file
	 * @param string $tmpFile path of the tmp file
	 * @return string|false path of the original file or false
	 */
	public static function getPathFromTmpFile($tmpFile) {
		if (isset(self::$tmpFileMapping[$tmpFile])) {
			return self::$tmpFileMapping[$tmpFile];
		}

		return false;
	}

	/**
	 * detect file type, encryption can read/write regular files, versions
	 * and cached files
	 *
	 * @param string $path
	 * @return int
	 * @throws Exception\EncryptionException
	 */
	public static function detectFileType($path) {
	    $parts = self::splitPath($path);

		if (count($parts) > 2) {
			switch ($parts[2]) {
				case 'files':
					return Util::FILE_TYPE_FILE;
				case 'files_versions':
					return Util::FILE_TYPE_VERSION;
				case 'cache':
					return Util::FILE_TYPE_CACHE;
			}
		}

		// thow exception if we couldn't detect a valid file type
		throw new Exception\EncryptionException('Could not detect file type', Exception\EncryptionException::GENERIC);
	}

	/**
	 * read the cipher used for encryption from the config.php
	 *
	 * @return string
	 */
	public static function getCipher() {

		$cipher = \OC::$server->getConfig()->getSystemValue('cipher', Crypt::DEFAULT_CIPHER);

		if ($cipher !== 'AES-256-CFB' && $cipher !== 'AES-128-CFB') {
			\OCP\Util::writeLog('files_encryption',
					'wrong cipher defined in config.php, only AES-128-CFB and AES-256-CFB is supported. Fall back ' . Crypt::DEFAULT_CIPHER,
					\OCP\Util::WARN);

			$cipher = Crypt::DEFAULT_CIPHER;
		}

		return $cipher;
	}

	public static function splitPath($path) {
		$normalized = \OC\Files\Filesystem::normalizePath($path);
		return explode('/', $normalized);
	}

}

