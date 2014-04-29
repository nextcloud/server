<?php

/**
 * ownCloud
 *
 * @author Bjoern Schiessle, Sam Tuke, Robin Appelman
 * @copyright 2012 Sam Tuke <samtuke@owncloud.com>
 *            2012 Robin Appelman <icewind1991@gmail.com>
 *            2014 Bjoern Schiessle <schiessle@owncloud.com>
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

	private static $unencryptedSizes = array(); // remember unencrypted size
	private static $fopenMode = array(); // remember the fopen mode
	private static $enableEncryption = false; // Enable encryption for the given path

	/**
	 * Check if a file requires encryption
	 * @param string $path
	 * @param string $mode type of access
	 * @return bool
	 *
	 * Tests if server side encryption is enabled, and if we should call the
	 * crypt stream wrapper for the given file
	 */
	private static function shouldEncrypt($path, $mode = 'w') {

		$userId = Helper::getUser($path);
		$session = new Session(new \OC\Files\View());

		// don't call the crypt stream wrapper, if...
		if (
				$session->getInitialized() !== Session::INIT_SUCCESSFUL // encryption successful initialized
				|| Crypt::mode() !== 'server'   // we are not in server-side-encryption mode
				|| strpos($path, '/' . $userId . '/files') !== 0 // path is not in files/
				|| substr($path, 0, 8) === 'crypt://' // we are already in crypt mode
		) {
			return false;
		}

		$view = new \OC_FilesystemView('');
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

					// store new unenecrypted size so that it can be updated
					// in the post proxy
					$tmpFileInfo = $view->getFileInfo($tmpPath);
					if ( isset($tmpFileInfo['size']) ) {
						self::$unencryptedSizes[\OC_Filesystem::normalizePath($path)] = $tmpFileInfo['size'];
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
	 * @brief update file cache with the new unencrypted size after file was written
	 * @param string $path
	 * @param mixed $result
	 * @return mixed
	 */
	public function postFile_put_contents($path, $result) {
		$normalizedPath = \OC_Filesystem::normalizePath($path);
		if ( isset(self::$unencryptedSizes[$normalizedPath]) ) {
			$view = new \OC_FilesystemView('/');
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
	 * @brief remember initial fopen mode because sometimes it gets changed during the request
	 * @param string $path path
	 * @param string $mode type of access
	 */
	public function preFopen($path, $mode) {

		self::$fopenMode[$path] = $mode;
		self::$enableEncryption = self::shouldEncrypt($path, $mode);

	}


	/**
	 * @param $path
	 * @param $result
	 * @return resource
	 */
	public function postFopen($path, &$result) {

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
	 * @param $path
	 * @param $data
	 * @return array
	 */
	public function postGetFileInfo($path, $data) {

		// if path is a folder do nothing
		if (\OCP\App::isEnabled('files_encryption') && $data !== false && array_key_exists('size', $data)) {

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
		if ($fileInfo && $fileInfo['encrypted'] === true) {
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
			if (!$fileInfo) {
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

}
