<?php

/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 * @author Sam Tuke <samtuke@owncloud.com>
 * @author Robin Appelman <icewind1991@gmail.com>
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
 * Encryption proxy which handles filesystem operations before and after
 *        execution and encrypts, and handles keyfiles accordingly. Used for
 *        webui.
 */

namespace OCA\Files_Encryption;

/**
 * Class Proxy
 * @package OCA\Files_Encryption
 */
class Proxy extends \OC_FileProxy {

	private static $unencryptedSizes = array(); // remember unencrypted size
	private static $fopenMode = array(); // remember the fopen mode
	private static $enableEncryption = false; // Enable encryption for the given path


	/**
	 * check if path is excluded from encryption
	 *
	 * @param string $path relative to data/
	 * @return boolean
	 */
	protected function isExcludedPath($path) {

		$view = new \OC\Files\View();

		$normalizedPath = \OC\Files\Filesystem::normalizePath($path);

		$parts = explode('/', $normalizedPath);

		// we only encrypt/decrypt files in the files and files_versions folder
		if (sizeof($parts) < 3) {
			/**
			 * Less then 3 parts means, we can't match:
			 * - /{$uid}/files/* nor
			 * - /{$uid}/files_versions/*
			 * So this is not a path we are looking for.
			 */
			return true;
		}
		if(
			!($parts[2] === 'files' && \OCP\User::userExists($parts[1])) &&
			!($parts[2] === 'files_versions' && \OCP\User::userExists($parts[1]))) {

			return true;
		}

		if (!$view->file_exists($normalizedPath)) {
			$normalizedPath = dirname($normalizedPath);
		}

		// we don't encrypt server-to-server shares
		list($storage, ) = \OC\Files\Filesystem::resolvePath($normalizedPath);
		/**
		 * @var \OCP\Files\Storage $storage
		 */
		if ($storage->instanceOfStorage('OCA\Files_Sharing\External\Storage')) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a file requires encryption
	 * @param string $path
	 * @param string $mode type of access
	 * @return bool
	 *
	 * Tests if server side encryption is enabled, and if we should call the
	 * crypt stream wrapper for the given file
	 */
	private function shouldEncrypt($path, $mode = 'w') {

		// don't call the crypt stream wrapper, if...
		if (
				Crypt::mode() !== 'server'   // we are not in server-side-encryption mode
				|| $this->isExcludedPath($path) // if path is excluded from encryption
				|| substr($path, 0, 8) === 'crypt://' // we are already in crypt mode
		) {
			return false;
		}

		$userId = Helper::getUser($path);
		$view = new \OC\Files\View('');
		$util = new Util($view, $userId);

		// for write operation we always encrypt the files, for read operations
		// we check if the existing file is encrypted or not decide if it needs to
		// decrypt it.
		if (($mode !== 'r' && $mode !== 'rb') || $util->isEncryptedPath($path)) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function preFile_put_contents($path, &$data) {

		if ($this->shouldEncrypt($path)) {

			if (!is_resource($data)) {

				// get root view
				$view = new \OC\Files\View('/');

				// get relative path
				$relativePath = Helper::stripUserFilesPath($path);

				if (!isset($relativePath)) {
					return true;
				}

				// create random cache folder
				$cacheFolder = rand();
				$path_slices = explode('/', \OC\Files\Filesystem::normalizePath($path));
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

					// store new unenecrypted size so that it can be updated
					// in the post proxy
					$tmpFileInfo = $view->getFileInfo($tmpPath);
					if ( isset($tmpFileInfo['unencrypted_size']) ) {
						self::$unencryptedSizes[\OC\Files\Filesystem::normalizePath($path)] = $tmpFileInfo['unencrypted_size'];
					}

					// remove our temp file
					$view->deleteAll('/' . \OCP\User::getUser() . '/cache/' . $cacheFolder);

					// re-enable proxy - our work is done
					\OC_FileProxy::$enabled = $proxyStatus;
				} else {
					return false;
				}
			}
		}

		return true;

	}

	/**
	 * update file cache with the new unencrypted size after file was written
	 * @param string $path
	 * @param mixed $result
	 * @return mixed
	 */
	public function postFile_put_contents($path, $result) {
		$normalizedPath = \OC\Files\Filesystem::normalizePath($path);
		if ( isset(self::$unencryptedSizes[$normalizedPath]) ) {
			$view = new \OC\Files\View('/');
			$view->putFileInfo($normalizedPath,
					array('encrypted' => true, 'unencrypted_size' => self::$unencryptedSizes[$normalizedPath]));
			unset(self::$unencryptedSizes[$normalizedPath]);
		}

		return $result;
	}

	/**
	 * @param string $path Path of file from which has been read
	 * @param string $data Data that has been read from file
	 */
	public function postFile_get_contents($path, $data) {

		$plainData = null;

		// If data is a catfile
		if (
			Crypt::mode() === 'server'
			&& $this->shouldEncrypt($path)
			&& Crypt::isCatfileContent($data)
		) {

			$handle = fopen('crypt://' . $path, 'r');

			if (is_resource($handle)) {
				while (($plainDataChunk = fgets($handle, 8192)) !== false) {
					$plainData .= $plainDataChunk;
				}
			}

		}

		if (!isset($plainData)) {

			$plainData = $data;

		}

		return $plainData;

	}

	/**
	 * remember initial fopen mode because sometimes it gets changed during the request
	 * @param string $path path
	 * @param string $mode type of access
	 */
	public function preFopen($path, $mode) {

		self::$fopenMode[$path] = $mode;
		self::$enableEncryption = $this->shouldEncrypt($path, $mode);

	}


	/**
	 * @param string $path
	 * @param resource $result
	 * @return resource
	 */
	public function postFopen($path, $result) {

		$path = \OC\Files\Filesystem::normalizePath($path);

		if (!$result || self::$enableEncryption === false) {

			return $result;

		}

		// if we remember the mode from the pre proxy we re-use it
		// otherwise we fall back to stream_get_meta_data()
		if (isset(self::$fopenMode[$path])) {
			$mode = self::$fopenMode[$path];
			unset(self::$fopenMode[$path]);
		} else {
			$meta = stream_get_meta_data($result);
			$mode = $meta['mode'];
		}

		// Close the original encrypted file
		fclose($result);

		// Open the file using the crypto stream wrapper
		// protocol and let it do the decryption work instead
		$result = fopen('crypt://' . $path, $mode);

		return $result;

	}

	/**
	 * @param string $path
	 * @param array $data
	 * @return array
	 */
	public function postGetFileInfo($path, $data) {

		// if path is a folder do nothing
		if (\OCP\App::isEnabled('files_encryption') && $data !== false && array_key_exists('size', $data)) {

			// Disable encryption proxy to prevent recursive calls
			$proxyStatus = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;

			// get file size
			$data['size'] = self::postFileSize($path, $data['size'], $data);

			// Re-enable the proxy
			\OC_FileProxy::$enabled = $proxyStatus;
		}

		return $data;
	}

	/**
	 * @param string $path
	 * @param int $size
	 * @return int|bool
	 */
	public function postFileSize($path, $size, $fileInfo = null) {

		$view = new \OC\Files\View('/');

		$userId = Helper::getUser($path);
		$util = new Util($view, $userId);

		// if encryption is no longer enabled or if the files aren't migrated yet
		// we return the default file size
		if(!\OCP\App::isEnabled('files_encryption') ||
				$util->getMigrationStatus() !== Util::MIGRATION_COMPLETED) {
			return $size;
		}

		// if path is a folder do nothing
		if ($view->is_dir($path)) {
			$proxyState = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;
			$fileInfo = $view->getFileInfo($path);
			\OC_FileProxy::$enabled = $proxyState;
			if (isset($fileInfo['unencrypted_size']) && $fileInfo['unencrypted_size'] > 0) {
				return $fileInfo['unencrypted_size'];
			}
			return $size;
		}

		// get relative path
		$relativePath = Helper::stripUserFilesPath($path);

		// if path is empty we cannot resolve anything
		if (empty($relativePath)) {
			return $size;
		}

		// get file info from database/cache
		if (empty($fileInfo)) {
			$proxyState = \OC_FileProxy::$enabled;
			\OC_FileProxy::$enabled = false;
			$fileInfo = $view->getFileInfo($path);
			\OC_FileProxy::$enabled = $proxyState;
		}

		// if file is encrypted return real file size
		if (isset($fileInfo['encrypted']) && $fileInfo['encrypted'] === true) {
			// try to fix unencrypted file size if it doesn't look plausible
			if ((int)$fileInfo['size'] > 0 && (int)$fileInfo['unencrypted_size'] === 0 ) {
				$fixSize = $util->getFileSize($path);
				$fileInfo['unencrypted_size'] = $fixSize;
				// put file info if not .part file
				if (!Helper::isPartialFilePath($relativePath)) {
					$view->putFileInfo($path, array('unencrypted_size' => $fixSize));
				}
			}
			$size = $fileInfo['unencrypted_size'];
		} else {

			$fileInfoUpdates = array();

			$fixSize = $util->getFileSize($path);
			if ($fixSize > 0) {
				$size = $fixSize;

				$fileInfoUpdates['encrypted'] = true;
				$fileInfoUpdates['unencrypted_size'] = $size;

				// put file info if not .part file
				if (!Helper::isPartialFilePath($relativePath)) {
					$view->putFileInfo($path, $fileInfoUpdates);
				}
			}

		}
		return $size;
	}

}
