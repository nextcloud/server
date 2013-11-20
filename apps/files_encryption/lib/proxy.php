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
class Proxy extends \OC_FileProxy {

	private static $blackList = null; //mimetypes blacklisted from encryption

	/**
	 * Check if a file requires encryption
	 * @param string $path
	 * @return bool
	 *
	 * Tests if server side encryption is enabled, and file is allowed by blacklists
	 */
	private static function shouldEncrypt($path) {

		if (\OCP\App::isEnabled('files_encryption') === false || Crypt::mode() !== 'server' ||
				strpos($path, '/' . \OCP\User::getUser() . '/files') !== 0) {
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
	public function preFile_put_contents($path, &$data) {

		if (self::shouldEncrypt($path)) {

			if (!is_resource($data)) {

				// get root view
				$view = new \OC_FilesystemView('/');

				// get relative path
				$relativePath = \OCA\Encryption\Helper::stripUserFilesPath($path);

				if (!isset($relativePath)) {
					return true;
				}

				// create random cache folder
				$cacheFolder = rand();
				$path_slices = explode('/', \OC_Filesystem::normalizePath($path));
				$path_slices[2] = "cache/".$cacheFolder;
				$tmpPath = implode('/', $path_slices);

				$handle = fopen('crypt://' . $tmpPath, 'w');
				if (is_resource($handle)) {

					// write data to stream
					fwrite($handle, $data);

					// close stream
					fclose($handle);

					// disable encryption proxy to prevent recursive calls
					$proxyStatus = \OC_FileProxy::$enabled;
					\OC_FileProxy::$enabled = false;

					// get encrypted content
					$data = $view->file_get_contents($tmpPath);

					// remove our temp file
					$view->deleteAll('/' . \OCP\User::getUser() . '/cache/' . $cacheFolder);

					// re-enable proxy - our work is done
					\OC_FileProxy::$enabled = $proxyStatus;
				}
			}
		}

		return true;

	}

	/**
	 * @param string $path Path of file from which has been read
	 * @param string $data Data that has been read from file
	 */
	public function postFile_get_contents($path, $data) {

		$plainData = null;
		$view = new \OC_FilesystemView('/');

		// init session
		$session = new \OCA\Encryption\Session($view);

		// If data is a catfile
		if (
			Crypt::mode() === 'server'
			&& Crypt::isCatfileContent($data)
		) {

			$handle = fopen('crypt://' . $path, 'r');

			if (is_resource($handle)) {
				while (($plainDataChunk = fgets($handle, 8192)) !== false) {
					$plainData .= $plainDataChunk;
				}
			}

		} elseif (
			Crypt::mode() == 'server'
			&& \OC::$session->exists('legacyenckey')
			&& Crypt::isEncryptedMeta($path)
		) {
			// Disable encryption proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			$plainData = Crypt::legacyBlockDecrypt($data, $session->getLegacyKey());

			\OC_FileProxy::$enabled = $proxyStatus;
		}

		if (!isset($plainData)) {

			$plainData = $data;

		}

		return $plainData;

	}

	/**
	 * @brief When a file is deleted, remove its keyfile also
	 */
	public function preUnlink($path) {

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

		// get relative path
		$relativePath = \OCA\Encryption\Helper::stripUserFilesPath($path);

		list($owner, $ownerPath) = $util->getUidAndFilename($relativePath);

		// Delete keyfile & shareKey so it isn't orphaned
		if (!Keymanager::deleteFileKey($view, $owner, $ownerPath)) {
			\OCP\Util::writeLog('Encryption library',
				'Keyfile or shareKey could not be deleted for file "' . $ownerPath . '"', \OCP\Util::ERROR);
		}

		Keymanager::delAllShareKeys($view, $owner, $ownerPath);

		\OC_FileProxy::$enabled = $proxyStatus;

		// If we don't return true then file delete will fail; better
		// to leave orphaned keyfiles than to disallow file deletion
		return true;

	}

	/**
	 * @param $path
	 * @return bool
	 */
	public function postTouch($path) {
		$this->handleFile($path);

		return true;
	}

	/**
	 * @param $path
	 * @param $result
	 * @return resource
	 */
	public function postFopen($path, &$result) {

		$path = \OC\Files\Filesystem::normalizePath($path);

		if (!$result) {

			return $result;

		}

		// split the path parts
		$pathParts = explode('/', $path);

		// get relative path
		$relativePath = \OCA\Encryption\Helper::stripUserFilesPath($path);

		// FIXME: handling for /userId/cache used by webdav for chunking. The cache chunks are NOT encrypted
		if (isset($pathParts[2]) && $pathParts[2] === 'cache') {
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
			Crypt::mode() === 'server'
			&& $util->isEncryptedPath($path)
		) {

			// Close the original encrypted file
			fclose($result);

			// Open the file using the crypto stream wrapper
			// protocol and let it do the decryption work instead
			$result = fopen('crypt://' . $path, $meta['mode']);

		} elseif (
			self::shouldEncrypt($path)
			and $meta ['mode'] !== 'r'
				and $meta['mode'] !== 'rb'
		) {
			$result = fopen('crypt://' . $path, $meta['mode']);
		}

		// Re-enable the proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;

	}

	/**
	 * @param $path
	 * @param $data
	 * @return array
	 */
	public function postGetFileInfo($path, $data) {

		// if path is a folder do nothing
		if (\OCP\App::isEnabled('files_encryption') && is_array($data) && array_key_exists('size', $data)) {

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
	 * @param $size
	 * @return bool
	 */
	public function postFileSize($path, $size) {

		$view = new \OC_FilesystemView('/');

		$userId = \OCP\User::getUser();
		$util = new Util($view, $userId);

		// if encryption is no longer enabled or if the files aren't migrated yet
		// we return the default file size
		if(!\OCP\App::isEnabled('files_encryption') ||
				$util->getMigrationStatus() !== Util::MIGRATION_COMPLETED) {
			return $size;
		}

		// if path is a folder do nothing
		if ($view->is_dir($path)) {
			return $size;
		}

		// get relative path
		$relativePath = \OCA\Encryption\Helper::stripUserFilesPath($path);

		// if path is empty we cannot resolve anything
		if (empty($relativePath)) {
			return $size;
		}

		$fileInfo = false;
		// get file info from database/cache if not .part file
		if (!Helper::isPartialFilePath($path)) {
			$proxyState = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;
			$fileInfo = $view->getFileInfo($path);
			\OC_FileProxy::$enabled = $proxyState;
		}

		// if file is encrypted return real file size
		if (is_array($fileInfo) && $fileInfo['encrypted'] === true) {
			// try to fix unencrypted file size if it doesn't look plausible
			if ((int)$fileInfo['size'] > 0 && (int)$fileInfo['unencrypted_size'] === 0 ) {
				$fixSize = $util->getFileSize($path);
				$fileInfo['unencrypted_size'] = $fixSize;
				// put file info if not .part file
				if (!Helper::isPartialFilePath($relativePath)) {
					$view->putFileInfo($path, $fileInfo);
				}
			}
			$size = $fileInfo['unencrypted_size'];
		} else {
			// self healing if file was removed from file cache
			if (!is_array($fileInfo)) {
				$fileInfo = array();
			}

			$fixSize = $util->getFileSize($path);
			if ($fixSize > 0) {
				$size = $fixSize;

				$fileInfo['encrypted'] = true;
				$fileInfo['unencrypted_size'] = $size;

				// put file info if not .part file
				if (!Helper::isPartialFilePath($relativePath)) {
					$view->putFileInfo($path, $fileInfo);
				}
			}

		}
		return $size;
	}

	/**
	 * @param $path
	 */
	public function handleFile($path) {

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$view = new \OC_FilesystemView('/');
		$session = new \OCA\Encryption\Session($view);
		$userId = \OCP\User::getUser();
		$util = new Util($view, $userId);

		// split the path parts
		$pathParts = explode('/', $path);

		// get relative path
		$relativePath = \OCA\Encryption\Helper::stripUserFilesPath($path);

		// only if file is on 'files' folder fix file size and sharing
		if (isset($pathParts[2]) && $pathParts[2] === 'files' && $util->fixFileSize($path)) {

			// get sharing app state
			$sharingEnabled = \OCP\Share::isEnabled();

			// get users
			$usersSharing = $util->getSharingUsersArray($sharingEnabled, $relativePath);

			// update sharing-keys
			$util->setSharedFileKeyfiles($session, $usersSharing, $relativePath);
		}

		\OC_FileProxy::$enabled = $proxyStatus;
	}
}
