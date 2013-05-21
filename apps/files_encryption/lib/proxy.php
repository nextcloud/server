<?php

/**
 * ownCloud
 *
 * @author Sam Tuke, Robin Appelman
 * @copyright 2012 Sam Tuke samtuke@owncloud.com, Robin Appelman
 * icewind1991@gmail.com
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

/**
 * @brief Encryption proxy which handles filesystem operations before and after
 *        execution and encrypts, and handles keyfiles accordingly. Used for
 *        webui.
 */

namespace OCA\Encryption;

/**
 * Class Proxy
 * @package OCA\Encryption
 */
class Proxy extends \OC_FileProxy
{

	private static $blackList = null; //mimetypes blacklisted from encryption

	private static $enableEncryption = null;

	/**
	 * Check if a file requires encryption
	 * @param string $path
	 * @return bool
	 *
	 * Tests if server side encryption is enabled, and file is allowed by blacklists
	 */
	private static function shouldEncrypt($path)
	{

		if (is_null(self::$enableEncryption)) {

			if (
				\OCP\Config::getAppValue('files_encryption', 'enable_encryption', 'true') == 'true'
				&& Crypt::mode() == 'server'
			) {

				self::$enableEncryption = true;

			} else {

				self::$enableEncryption = false;

			}

		}

		if (!self::$enableEncryption) {

			return false;

		}

		if (is_null(self::$blackList)) {

			self::$blackList = explode(',', \OCP\Config::getAppValue('files_encryption', 'type_blacklist', ''));

		}

		if (Crypt::isCatfileContent($path)) {

			return true;

		}

		$extension = substr($path, strrpos($path, '.') + 1);

		if (array_search($extension, self::$blackList) === false) {

			return true;

		}

		return false;
	}

	/**
	 * @param $path
	 * @param $data
	 * @return bool
	 */
	public function preFile_put_contents($path, &$data)
	{

		if (self::shouldEncrypt($path)) {

			// Stream put contents should have been converted to fopen
			if (!is_resource($data)) {

				$userId = \OCP\USER::getUser();
				$view = new \OC_FilesystemView('/');
				$util = new Util($view, $userId);
				$session = new Session($view);
				$privateKey = $session->getPrivateKey();
				$filePath = $util->stripUserFilesPath($path);
				// Set the filesize for userland, before encrypting
				$size = strlen($data);

				// Disable encryption proxy to prevent recursive calls
				$proxyStatus = \OC_FileProxy::$enabled;
				\OC_FileProxy::$enabled = false;

				// Check if there is an existing key we can reuse
				if ($encKeyfile = Keymanager::getFileKey($view, $userId, $filePath)) {

					// Fetch shareKey
					$shareKey = Keymanager::getShareKey($view, $userId, $filePath);

					// Decrypt the keyfile
					$plainKey = Crypt::multiKeyDecrypt($encKeyfile, $shareKey, $privateKey);

				} else {

					// Make a new key
					$plainKey = Crypt::generateKey();

				}

				// Encrypt data
				$encData = Crypt::symmetricEncryptFileContent($data, $plainKey);

				$sharingEnabled = \OCP\Share::isEnabled();

				// if file exists try to get sharing users
				if ($view->file_exists($path)) {
					$uniqueUserIds = $util->getSharingUsersArray($sharingEnabled, $filePath, $userId);
				} else {
					$uniqueUserIds[] = $userId;
				}

				// Fetch public keys for all users who will share the file
				$publicKeys = Keymanager::getPublicKeys($view, $uniqueUserIds);

				// Encrypt plain keyfile to multiple sharefiles
				$multiEncrypted = Crypt::multiKeyEncrypt($plainKey, $publicKeys);

				// Save sharekeys to user folders
				Keymanager::setShareKeys($view, $filePath, $multiEncrypted['keys']);

				// Set encrypted keyfile as common varname
				$encKey = $multiEncrypted['data'];

				// Save keyfile for newly encrypted file in parallel directory tree
				Keymanager::setFileKey($view, $filePath, $userId, $encKey);

				// Replace plain content with encrypted content by reference
				$data = $encData;

				// Update the file cache with file info
				\OC\Files\Filesystem::putFileInfo($filePath, array('encrypted' => true, 'size' => strlen($size), 'unencrypted_size' => $size), '');

				// Re-enable proxy - our work is done
				\OC_FileProxy::$enabled = $proxyStatus;

			}
		}

		return true;

	}

	/**
	 * @param string $path Path of file from which has been read
	 * @param string $data Data that has been read from file
	 */
	public function postFile_get_contents($path, $data)
	{

		// FIXME: $path for shared files is just /uid/files/Shared/filepath

		$userId = \OCP\USER::getUser();
		$view = new \OC_FilesystemView('/');
		$util = new Util($view, $userId);

		$relPath = $util->stripUserFilesPath($path);


		// TODO check for existing key file and reuse it if possible to avoid problems with versioning etc.
		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// If data is a catfile
		if (
			Crypt::mode() == 'server'
			&& Crypt::isCatfileContent($data) // TODO: Do we really need this check? Can't we assume it is properly encrypted?
		) {

			// TODO: use get owner to find correct location of key files for shared files
			$session = new Session($view);
			$privateKey = $session->getPrivateKey($userId);

			// Get the encrypted keyfile
			$encKeyfile = Keymanager::getFileKey($view, $userId, $relPath);

			// Attempt to fetch the user's shareKey
			$shareKey = Keymanager::getShareKey($view, $userId, $relPath);

			// Decrypt keyfile with shareKey
			$plainKeyfile = Crypt::multiKeyDecrypt($encKeyfile, $shareKey, $privateKey);

			$plainData = Crypt::symmetricDecryptFileContent($data, $plainKeyfile);

		} elseif (
			Crypt::mode() == 'server'
			&& isset($_SESSION['legacyenckey'])
			&& Crypt::isEncryptedMeta($path)
		) {

			$plainData = Crypt::legacyDecrypt($data, $session->getLegacyKey());

		}

		\OC_FileProxy::$enabled = $proxyStatus;

		if (!isset($plainData)) {

			$plainData = $data;

		}

		return $plainData;

	}

	/**
	 * @brief When a file is deleted, remove its keyfile also
	 */
	public function preUnlink($path)
	{

		// let the trashbin handle this  
		if (\OCP\App::isEnabled('files_trashbin')) {
			return true;
		}

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$view = new \OC_FilesystemView('/');

		$userId = \OCP\USER::getUser();

		$util = new Util($view, $userId);

		// Format path to be relative to user files dir
		$relPath = $util->stripUserFilesPath($path);

		list($owner, $ownerPath) = $util->getUidAndFilename($relPath);

		// Delete keyfile & shareKey so it isn't orphaned
		if (
			!(
				Keymanager::deleteFileKey($view, $owner, $ownerPath)
					&& Keymanager::delAllShareKeys($view, $owner, $ownerPath)
			)
		) {

			\OC_Log::write('Encryption library', 'Keyfile or shareKey could not be deleted for file "' . $ownerPath . '"', \OC_Log::ERROR);

		}

		\OC_FileProxy::$enabled = $proxyStatus;

		// If we don't return true then file delete will fail; better
		// to leave orphaned keyfiles than to disallow file deletion
		return true;

	}

	/**
	 * @brief When a file is renamed, rename its keyfile also
	 * @param $path
	 * @return bool Result of rename()
	 * @note This is pre rather than post because using post didn't work
	 */
	public function postWrite($path)
	{
		$this->handleFile($path);

		return true;
	}

	/**
	 * @param $path
	 * @return bool
	 */
	public function postTouch($path)
	{
		$this->handleFile($path);

		return true;
	}

	/**
	 * @param $path
	 * @param $result
	 * @return resource
	 */
	public function postFopen($path, &$result)
	{

		if (!$result) {

			return $result;

		}

		// Reformat path for use with OC_FSV
		$path_split = explode('/', $path);
		$path_f = implode('/', array_slice($path_split, 3));

		// FIXME: handling for /userId/cache used by webdav for chunking. The cache chunks are NOT encrypted
		if ($path_split[2] == 'cache') {
			return $result;
		}

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$meta = stream_get_meta_data($result);

		$view = new \OC_FilesystemView('');

		$util = new Util($view, \OCP\USER::getUser());

		// If file is already encrypted, decrypt using crypto protocol
		if (
			Crypt::mode() == 'server'
			&& $util->isEncryptedPath($path)
		) {

			// Close the original encrypted file
			fclose($result);

			// Open the file using the crypto stream wrapper 
			// protocol and let it do the decryption work instead
			$result = fopen('crypt://' . $path_f, $meta['mode']);


		} elseif (
			self::shouldEncrypt($path)
			and $meta ['mode'] != 'r'
			and $meta['mode'] != 'rb'
		) {
			$result = fopen('crypt://' . $path_f, $meta['mode']);
		}

		// Re-enable the proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;

	}

	/**
	 * @param $path
	 * @param $mime
	 * @return string
	 */
	public function postGetMimeType($path, $mime)
	{

		if (Crypt::isCatfileContent($path)) {

			$mime = \OCP\Files::getMimeType('crypt://' . $path, 'w');

		}

		return $mime;

	}

	/**
	 * @param $path
	 * @param $data
	 * @return array
	 */
	public function postGetFileInfo($path, $data)
	{

		// if path is a folder do nothing
		if (is_array($data) && array_key_exists('size', $data)) {

			// Disable encryption proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			// get file size
			$data['size'] = self::postFileSize($path, $data['size']);

			// Re-enable the proxy
			\OC_FileProxy::$enabled = $proxyStatus;
		}

		return $data;
	}

	/**
	 * @param $path
	 * @param $data
	 * @return mixed
	 */
	public function postStat($path, $data)
	{
		// check if file is encrypted
		if (Crypt::isCatfileContent($path)) {

			// get file info from cache
			$cached = \OC\Files\Filesystem::getFileInfo($path, '');

			// set the real file size
			$data['size'] = $cached['unencrypted_size'];
		}

		return $data;
	}

	/**
	 * @param $path
	 * @param $size
	 * @return bool
	 */
	public function postFileSize($path, $size)
	{

		$view = new \OC_FilesystemView('/');

		// if path is a folder do nothing
		if ($view->is_dir($path)) {
			return $size;
		}

		// Reformat path for use with OC_FSV
		$path_split = explode('/', $path);
		$path_f = implode('/', array_slice($path_split, 3));

		// if path is empty we cannot resolve anything
		if (empty($path_f)) {
			return $size;
		}

		// get file info from database/cache
		$fileInfo = \OC\Files\Filesystem::getFileInfo($path_f);

		// if file is encrypted return real file size
		if (is_array($fileInfo) && $fileInfo['encrypted'] === true) {
			$size = $fileInfo['unencrypted_size'];
		} else {
			// self healing if file was removed from file cache
			if (is_array($fileInfo)) {
				$userId = \OCP\User::getUser();
				$util = new Util($view, $userId);
				$fixSize = $util->getFileSize($path);
				if ($fixSize > 0) {
					$size = $fixSize;

					$fileInfo['encrypted'] = true;
					$fileInfo['unencrypted_size'] = $size;

					// put file info
					$view->putFileInfo($path, $fileInfo);
				}
			}
		}
		return $size;
	}

	/**
	 * @param $path
	 */
	public function handleFile($path)
	{

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$view = new \OC_FilesystemView('/');
		$session = new Session($view);
		$userId = \OCP\User::getUser();
		$util = new Util($view, $userId);

		// Reformat path for use with OC_FSV
		$path_split = explode('/', $path);
		$path_f = implode('/', array_slice($path_split, 3));

		// only if file is on 'files' folder fix file size and sharing
		if ($path_split[2] == 'files' && $util->fixFileSize($path)) {

			// get sharing app state
			$sharingEnabled = \OCP\Share::isEnabled();

			// get users
			$usersSharing = $util->getSharingUsersArray($sharingEnabled, $path_f);

			// update sharing-keys
			$util->setSharedFileKeyfiles($session, $usersSharing, $path_f);
		}

		\OC_FileProxy::$enabled = $proxyStatus;
	}
}
