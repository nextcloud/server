<?php

/**
 * ownCloud
 *
 * @author Florin Peter
 * @copyright 2013 Florin Peter <owncloud@florin-peter.de>
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
 * Class to manage registration of hooks an various helper methods
 * @package OCA\Encryption
 */
class Helper {

	private static $tmpFileMapping; // Map tmp files to files in data/user/files

	/**
	 * register share related hooks
	 *
	 */
	public static function registerShareHooks() {

		\OCP\Util::connectHook('OCP\Share', 'pre_shared', 'OCA\Encryption\Hooks', 'preShared');
		\OCP\Util::connectHook('OCP\Share', 'post_shared', 'OCA\Encryption\Hooks', 'postShared');
		\OCP\Util::connectHook('OCP\Share', 'post_unshare', 'OCA\Encryption\Hooks', 'postUnshare');
	}

	/**
	 * register user related hooks
	 *
	 */
	public static function registerUserHooks() {

		\OCP\Util::connectHook('OC_User', 'post_login', 'OCA\Encryption\Hooks', 'login');
		\OCP\Util::connectHook('OC_User', 'logout', 'OCA\Encryption\Hooks', 'logout');
		\OCP\Util::connectHook('OC_User', 'post_setPassword', 'OCA\Encryption\Hooks', 'setPassphrase');
		\OCP\Util::connectHook('OC_User', 'pre_setPassword', 'OCA\Encryption\Hooks', 'preSetPassphrase');
		\OCP\Util::connectHook('OC_User', 'post_createUser', 'OCA\Encryption\Hooks', 'postCreateUser');
		\OCP\Util::connectHook('OC_User', 'post_deleteUser', 'OCA\Encryption\Hooks', 'postDeleteUser');
	}

	/**
	 * register filesystem related hooks
	 *
	 */
	public static function registerFilesystemHooks() {

		\OCP\Util::connectHook('OC_Filesystem', 'rename', 'OCA\Encryption\Hooks', 'preRename');
		\OCP\Util::connectHook('OC_Filesystem', 'post_rename', 'OCA\Encryption\Hooks', 'postRenameOrCopy');
		\OCP\Util::connectHook('OC_Filesystem', 'copy', 'OCA\Encryption\Hooks', 'preCopy');
		\OCP\Util::connectHook('OC_Filesystem', 'post_copy', 'OCA\Encryption\Hooks', 'postRenameOrCopy');
		\OCP\Util::connectHook('OC_Filesystem', 'post_delete', 'OCA\Encryption\Hooks', 'postDelete');
		\OCP\Util::connectHook('OC_Filesystem', 'delete', 'OCA\Encryption\Hooks', 'preDelete');
		\OCP\Util::connectHook('OC_Filesystem', 'post_umount', 'OCA\Encryption\Hooks', 'postUmount');
		\OCP\Util::connectHook('OC_Filesystem', 'umount', 'OCA\Encryption\Hooks', 'preUmount');
		\OCP\Util::connectHook('\OC\Core\LostPassword\Controller\LostController', 'post_passwordReset', 'OCA\Encryption\Hooks', 'postPasswordReset');
	}

	/**
	 * register app management related hooks
	 *
	 */
	public static function registerAppHooks() {

		\OCP\Util::connectHook('OC_App', 'pre_disable', 'OCA\Encryption\Hooks', 'preDisable');
		\OCP\Util::connectHook('OC_App', 'post_disable', 'OCA\Encryption\Hooks', 'postEnable');
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
	 * enable recovery
	 *
	 * @param string $recoveryKeyId
	 * @param string $recoveryPassword
	 * @internal param \OCA\Encryption\Util $util
	 * @internal param string $password
	 * @return bool
	 */
	public static function adminEnableRecovery($recoveryKeyId, $recoveryPassword) {

		$view = new \OC\Files\View('/');
		$appConfig = \OC::$server->getAppConfig();

		if ($recoveryKeyId === null) {
			$recoveryKeyId = 'recovery_' . substr(md5(time()), 0, 8);
			$appConfig->setValue('files_encryption', 'recoveryKeyId', $recoveryKeyId);
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

			$cipher = \OCA\Encryption\Helper::getCipher();
			$encryptedKey = \OCA\Encryption\Crypt::symmetricEncryptFileContent($keypair['privateKey'], $recoveryPassword, $cipher);
			if ($encryptedKey) {
				Keymanager::setPrivateSystemKey($encryptedKey, $recoveryKeyId . '.private.key');
				// Set recoveryAdmin as enabled
				$appConfig->setValue('files_encryption', 'recoveryAdminEnabled', 1);
				$return = true;
			}

			\OC_FileProxy::$enabled = true;

		} else { // get recovery key and check the password
			$util = new \OCA\Encryption\Util(new \OC\Files\View('/'), \OCP\User::getUser());
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
		$trimmed = ltrim($path, '/');
		$split = explode('/', $trimmed);

		// it is not a file relative to data/user/files
		if (count($split) < 3 || $split[1] !== 'files') {
			return false;
		}

		$sliced = array_slice($split, 2);
		$relPath = implode('/', $sliced);

		return $relPath;
	}

	/**
	 * try to get the user from the path if no user is logged in
	 * @param string $path
	 * @return mixed user or false if we couldn't determine a user
	 */
	public static function getUser($path) {

		$user = \OCP\User::getUser();


		// if we are logged in, then we return the userid
		if ($user) {
			return $user;
		}

		// if no user is logged in we try to access a publicly shared files.
		// In this case we need to try to get the user from the path

		$trimmed = ltrim($path, '/');
		$split = explode('/', $trimmed);

		// it is not a file relative to data/user/files
		if (count($split) < 2 || ($split[1] !== 'files' && $split[1] !== 'cache')) {
			return false;
		}

		$user = $split[0];

		if (\OCP\User::userExists($user)) {
			return $user;
		}

		return false;
	}

	/**
	 * get path to the corresponding file in data/user/files if path points
	 *        to a version or to a file in cache
	 * @param string $path path to a version or a file in the trash
	 * @return string path to corresponding file relative to data/user/files
	 */
	public static function getPathToRealFile($path) {
		$trimmed = ltrim($path, '/');
		$split = explode('/', $trimmed);
		$result = false;

		if (count($split) >= 3 && ($split[1] === "files_versions" || $split[1] === 'cache')) {
			$sliced = array_slice($split, 2);
			$result = implode('/', $sliced);
			if ($split[1] === "files_versions") {
				// we skip user/files
				$sliced = array_slice($split, 2);
				$relPath = implode('/', $sliced);
				//remove the last .v
				$result = substr($relPath, 0, strrpos($relPath, '.v'));
			}
			if ($split[1] === "cache") {
				// we skip /user/cache/transactionId
				$sliced = array_slice($split, 3);
				$result = implode('/', $sliced);
				//prepare the folders
				self::mkdirr($path, new \OC\Files\View('/'));
			}
		}

		return $result;
	}

	/**
	 * create directory recursively
	 * @param string $path
	 * @param \OC\Files\View $view
	 */
	public static function mkdirr($path, \OC\Files\View $view) {
		$dirname = \OC\Files\Filesystem::normalizePath(dirname($path));
		$dirParts = explode('/', $dirname);
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
				case \OCA\Encryption\Session::INIT_EXECUTED:
					$errorCode = \OCA\Encryption\Crypt::ENCRYPTION_PRIVATE_KEY_NOT_VALID_ERROR;
					break;
				case \OCA\Encryption\Session::NOT_INITIALIZED:
					$errorCode = \OCA\Encryption\Crypt::ENCRYPTION_NOT_INITIALIZED_ERROR;
					break;
				default:
					$errorCode = \OCA\Encryption\Crypt::ENCRYPTION_UNKNOWN_ERROR;
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
		$result = true;

		//openssl extension needs to be loaded
		$result &= extension_loaded("openssl");
		// we need php >= 5.3.3
		$result &= version_compare(phpversion(), '5.3.3', '>=');

		return (bool) $result;
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
		$config = array_merge(\OCP\Config::getSystemValue('openssl', array()), $config);
		return $config;
	}

	/**
	 * find all share keys for a given file
	 *
	 * @param string $filePath path to the file name relative to the user's files dir
	 * for example "subdir/filename.txt"
	 * @param string $shareKeyPath share key prefix path relative to the user's data dir
	 * for example "user1/files_encryption/share-keys/subdir/filename.txt"
	 * @param \OC\Files\View $rootView root view, relative to data/
	 * @return array list of share key files, path relative to data/$user
	 */
	public static function findShareKeys($filePath, $shareKeyPath,  \OC\Files\View $rootView) {
		$result = array();

		$user = \OCP\User::getUser();
		$util = new Util($rootView, $user);
		// get current sharing state
		$sharingEnabled = \OCP\Share::isEnabled();

		// get users sharing this file
		$usersSharing = $util->getSharingUsersArray($sharingEnabled, $filePath);

		$pathinfo = pathinfo($shareKeyPath);

		$baseDir = $pathinfo['dirname'] . '/';
		$fileName = $pathinfo['basename'];
		foreach ($usersSharing as $user) {
			$keyName = $fileName . '.' . $user . '.shareKey';
			if ($rootView->file_exists($baseDir . $keyName)) {
				$result[] = $baseDir . $keyName;
			} else {
				\OC_Log::write(
					'Encryption library',
					'No share key found for user "' . $user . '" for file "' . $fileName . '"',
					\OC_Log::WARN
				);
			}
		}

		return $result;
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
	 * read the cipher used for encryption from the config.php
	 *
	 * @return string
	 */
	public static function getCipher() {

		$cipher = \OCP\Config::getSystemValue('cipher', Crypt::DEFAULT_CIPHER);

		if ($cipher !== 'AES-256-CFB' && $cipher !== 'AES-128-CFB') {
			\OCP\Util::writeLog('files_encryption',
					'wrong cipher defined in config.php, only AES-128-CFB and AES-256-CFB is supported. Fall back ' . Crypt::DEFAULT_CIPHER,
					\OCP\Util::WARN);

			$cipher = Crypt::DEFAULT_CIPHER;
		}

		return $cipher;
	}
}

