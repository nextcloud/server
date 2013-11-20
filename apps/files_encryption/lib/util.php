<?php
/**
 * ownCloud
 *
 * @author Sam Tuke, Frank Karlitschek
 * @copyright 2012 Sam Tuke <samtuke@owncloud.com>,
 * Frank Karlitschek <frank@owncloud.org>
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
 * @brief Class for utilities relating to encrypted file storage system
 * @param \OC_FilesystemView $view expected to have OC '/' as root path
 * @param string $userId ID of the logged in user
 * @param int $client indicating status of client side encryption. Currently
 * unused, likely to become obsolete shortly
 */

class Util {

	const MIGRATION_COMPLETED = 1;    // migration to new encryption completed
	const MIGRATION_IN_PROGRESS = -1; // migration is running
	const MIGRATION_OPEN = 0;         // user still needs to be migrated

	private $view; // OC_FilesystemView object for filesystem operations
	private $userId; // ID of the currently logged-in user
	private $client; // Client side encryption mode flag
	private $publicKeyDir; // Dir containing all public user keys
	private $encryptionDir; // Dir containing user's files_encryption
	private $keyfilesPath; // Dir containing user's keyfiles
	private $shareKeysPath; // Dir containing env keys for shared files
	private $publicKeyPath; // Path to user's public key
	private $privateKeyPath; // Path to user's private key
	private $publicShareKeyId;
	private $recoveryKeyId;
	private $isPublic;

	/**
	 * @param \OC_FilesystemView $view
	 * @param $userId
	 * @param bool $client
	 */
	public function __construct(\OC_FilesystemView $view, $userId, $client = false) {

		$this->view = $view;
		$this->userId = $userId;
		$this->client = $client;
		$this->isPublic = false;

		$this->publicShareKeyId = \OC_Appconfig::getValue('files_encryption', 'publicShareKeyId');
		$this->recoveryKeyId = \OC_Appconfig::getValue('files_encryption', 'recoveryKeyId');

		// if we are anonymous/public
		if (\OCA\Encryption\Helper::isPublicAccess()) {
			$this->userId = $this->publicShareKeyId;

			// only handle for files_sharing app
			if (isset($GLOBALS['app']) && $GLOBALS['app'] === 'files_sharing') {
				$this->userDir = '/' . $GLOBALS['fileOwner'];
				$this->fileFolderName = 'files';
				$this->userFilesDir = '/' . $GLOBALS['fileOwner'] . '/'
									  . $this->fileFolderName; // TODO: Does this need to be user configurable?
				$this->publicKeyDir = '/' . 'public-keys';
				$this->encryptionDir = '/' . $GLOBALS['fileOwner'] . '/' . 'files_encryption';
				$this->keyfilesPath = $this->encryptionDir . '/' . 'keyfiles';
				$this->shareKeysPath = $this->encryptionDir . '/' . 'share-keys';
				$this->publicKeyPath =
					$this->publicKeyDir . '/' . $this->userId . '.public.key'; // e.g. data/public-keys/admin.public.key
				$this->privateKeyPath =
					'/owncloud_private_key/' . $this->userId . '.private.key'; // e.g. data/admin/admin.private.key
				$this->isPublic = true;
				// make sure that the owners home is mounted
				\OC\Files\Filesystem::initMountPoints($GLOBALS['fileOwner']);
			}

		} else {
			$this->userDir = '/' . $this->userId;
			$this->fileFolderName = 'files';
			$this->userFilesDir =
				'/' . $this->userId . '/' . $this->fileFolderName; // TODO: Does this need to be user configurable?
			$this->publicKeyDir = '/' . 'public-keys';
			$this->encryptionDir = '/' . $this->userId . '/' . 'files_encryption';
			$this->keyfilesPath = $this->encryptionDir . '/' . 'keyfiles';
			$this->shareKeysPath = $this->encryptionDir . '/' . 'share-keys';
			$this->publicKeyPath =
				$this->publicKeyDir . '/' . $this->userId . '.public.key'; // e.g. data/public-keys/admin.public.key
			$this->privateKeyPath =
				$this->encryptionDir . '/' . $this->userId . '.private.key'; // e.g. data/admin/admin.private.key
			// make sure that the owners home is mounted
			\OC\Files\Filesystem::initMountPoints($this->userId);
		}
	}

	/**
	 * @return bool
	 */
	public function ready() {

		if (
			!$this->view->file_exists($this->encryptionDir)
			or !$this->view->file_exists($this->keyfilesPath)
			or !$this->view->file_exists($this->shareKeysPath)
			or !$this->view->file_exists($this->publicKeyPath)
			or !$this->view->file_exists($this->privateKeyPath)
		) {

			return false;

		} else {

			return true;

		}

	}

	/**
	 * @brief Sets up user folders and keys for serverside encryption
	 *
	 * @param string $passphrase to encrypt server-stored private key with
	 * @return bool
	 */
	public function setupServerSide($passphrase = null) {

		// Set directories to check / create
		$setUpDirs = array(
			$this->userDir,
			$this->userFilesDir,
			$this->publicKeyDir,
			$this->encryptionDir,
			$this->keyfilesPath,
			$this->shareKeysPath
		);

		// Check / create all necessary dirs
		foreach ($setUpDirs as $dirPath) {

			if (!$this->view->file_exists($dirPath)) {

				$this->view->mkdir($dirPath);

			}

		}

		// Create user keypair
		// we should never override a keyfile
		if (
			!$this->view->file_exists($this->publicKeyPath)
			&& !$this->view->file_exists($this->privateKeyPath)
		) {

			// Generate keypair
			$keypair = Crypt::createKeypair();

			if ($keypair) {

				\OC_FileProxy::$enabled = false;

				// Encrypt private key with user pwd as passphrase
				$encryptedPrivateKey = Crypt::symmetricEncryptFileContent($keypair['privateKey'], $passphrase);

				// Save key-pair
				if ($encryptedPrivateKey) {
					$this->view->file_put_contents($this->privateKeyPath, $encryptedPrivateKey);
					$this->view->file_put_contents($this->publicKeyPath, $keypair['publicKey']);
				}

				\OC_FileProxy::$enabled = true;
			}

		} else {
			// check if public-key exists but private-key is missing
			if ($this->view->file_exists($this->publicKeyPath) && !$this->view->file_exists($this->privateKeyPath)) {
				\OCP\Util::writeLog('Encryption library',
					'public key exists but private key is missing for "' . $this->userId . '"', \OCP\Util::FATAL);
				return false;
			} else {
				if (!$this->view->file_exists($this->publicKeyPath) && $this->view->file_exists($this->privateKeyPath)
				) {
					\OCP\Util::writeLog('Encryption library',
						'private key exists but public key is missing for "' . $this->userId . '"', \OCP\Util::FATAL);
					return false;
				}
			}
		}

		// If there's no record for this user's encryption preferences
		if (false === $this->recoveryEnabledForUser()) {

			// create database configuration
			$sql = 'INSERT INTO `*PREFIX*encryption` (`uid`,`mode`,`recovery_enabled`,`migration_status`) VALUES (?,?,?,?)';
			$args = array(
				$this->userId,
				'server-side',
				0,
				self::MIGRATION_OPEN
			);
			$query = \OCP\DB::prepare($sql);
			$query->execute($args);

		}

		return true;

	}

	/**
	 * @return string
	 */
	public function getPublicShareKeyId() {
		return $this->publicShareKeyId;
	}

	/**
	 * @brief Check whether pwd recovery is enabled for a given user
	 * @return bool 1 = yes, 0 = no, false = no record
	 *
	 * @note If records are not being returned, check for a hidden space
	 *       at the start of the uid in db
	 */
	public function recoveryEnabledForUser() {

		$sql = 'SELECT `recovery_enabled` FROM `*PREFIX*encryption` WHERE `uid` = ?';

		$args = array($this->userId);

		$query = \OCP\DB::prepare($sql);

		$result = $query->execute($args);

		$recoveryEnabled = array();

		if (\OCP\DB::isError($result)) {
			\OCP\Util::writeLog('Encryption library', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		} else {
			if ($result->numRows() > 0) {
				$row = $result->fetchRow();
				if (isset($row['recovery_enabled'])) {
					$recoveryEnabled[] = $row['recovery_enabled'];
				}
			}
		}

		// If no record is found
		if (empty($recoveryEnabled)) {

			return false;

			// If a record is found
		} else {

			return $recoveryEnabled[0];

		}

	}

	/**
	 * @brief Enable / disable pwd recovery for a given user
	 * @param bool $enabled Whether to enable or disable recovery
	 * @return bool
	 */
	public function setRecoveryForUser($enabled) {

		$recoveryStatus = $this->recoveryEnabledForUser();

		// If a record for this user already exists, update it
		if (false === $recoveryStatus) {

			$sql = 'INSERT INTO `*PREFIX*encryption` (`uid`,`mode`,`recovery_enabled`) VALUES (?,?,?)';

			$args = array(
				$this->userId,
				'server-side',
				$enabled
			);

			// Create a new record instead
		} else {

			$sql = 'UPDATE `*PREFIX*encryption` SET `recovery_enabled` = ? WHERE `uid` = ?';

			$args = array(
				$enabled,
				$this->userId
			);

		}

		return is_numeric(\OC_DB::executeAudited($sql, $args));

	}

	/**
	 * @brief Find all files and their encryption status within a directory
	 * @param string $directory The path of the parent directory to search
	 * @param bool $found the founded files if called again
	 * @return mixed false if 0 found, array on success. Keys: name, path
	 * @note $directory needs to be a path relative to OC data dir. e.g.
	 *       /admin/files NOT /backup OR /home/www/oc/data/admin/files
	 */
	public function findEncFiles($directory, &$found = false) {

		// Disable proxy - we don't want files to be decrypted before
		// we handle them
		\OC_FileProxy::$enabled = false;

		if ($found === false) {
			$found = array(
				'plain' => array(),
				'encrypted' => array(),
				'legacy' => array()
			);
		}

		if (
			$this->view->is_dir($directory)
			&& $handle = $this->view->opendir($directory)
		) {
			if(is_resource($handle)) {
				while (false !== ($file = readdir($handle))) {

					if (
						$file !== "."
						&& $file !== ".."
					) {

						$filePath = $directory . '/' . $this->view->getRelativePath('/' . $file);
						$relPath = \OCA\Encryption\Helper::stripUserFilesPath($filePath);

						// If the path is a directory, search
						// its contents
						if ($this->view->is_dir($filePath)) {

							$this->findEncFiles($filePath, $found);

							// If the path is a file, determine
							// its encryption status
						} elseif ($this->view->is_file($filePath)) {

							// Disable proxies again, some-
							// where they got re-enabled :/
							\OC_FileProxy::$enabled = false;

							$isEncryptedPath = $this->isEncryptedPath($filePath);
							// If the file is encrypted
							// NOTE: If the userId is
							// empty or not set, file will
							// detected as plain
							// NOTE: This is inefficient;
							// scanning every file like this
							// will eat server resources :(
							if (
								Keymanager::getFileKey($this->view, $relPath)
								&& $isEncryptedPath
							) {

								$found['encrypted'][] = array(
									'name' => $file,
									'path' => $filePath
								);

								// If the file uses old
								// encryption system
							} elseif (Crypt::isLegacyEncryptedContent($isEncryptedPath, $relPath)) {

								$found['legacy'][] = array(
									'name' => $file,
									'path' => $filePath
								);

								// If the file is not encrypted
							} else {

								$found['plain'][] = array(
									'name' => $file,
									'path' => $relPath
								);

							}

						}

					}

				}
			}

			\OC_FileProxy::$enabled = true;

			if (empty($found)) {

				return false;

			} else {

				return $found;

			}

		}

		\OC_FileProxy::$enabled = true;

		return false;

	}

	/**
	 * @brief Fetch the last lines of a file efficiently
	 * @note Safe to use on large files; does not read entire file to memory
	 * @note Derivative of http://tekkie.flashbit.net/php/tail-functionality-in-php
	 */
	public function tail($filename, $numLines) {

		\OC_FileProxy::$enabled = false;

		$text = '';
		$pos = -1;
		$handle = $this->view->fopen($filename, 'r');

		while ($numLines > 0) {

			--$pos;

			if (fseek($handle, $pos, SEEK_END) !== 0) {

				rewind($handle);
				$numLines = 0;

			} elseif (fgetc($handle) === "\n") {

				--$numLines;

			}

			$block_size = (-$pos) % 8192;
			if ($block_size === 0 || $numLines === 0) {

				$text = fread($handle, ($block_size === 0 ? 8192 : $block_size)) . $text;

			}
		}

		fclose($handle);

		\OC_FileProxy::$enabled = true;

		return $text;
	}

	/**
	 * @brief Check if a given path identifies an encrypted file
	 * @param string $path
	 * @return boolean
	 */
	public function isEncryptedPath($path) {

		$relPath = Helper::getPathToRealFile($path);

		if ($relPath === false) {
			$relPath = Helper::stripUserFilesPath($path);
		}

		$fileKey = Keymanager::getFileKey($this->view, $relPath);

		if ($fileKey === false) {
			return false;
		}

		return true;

	}

	/**
	 * @brief get the file size of the unencrypted file
	 * @param string $path absolute path
	 * @return bool
	 */
	public function getFileSize($path) {

		$result = 0;

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// split the path parts
		$pathParts = explode('/', $path);

		if (isset($pathParts[2]) && $pathParts[2] === 'files' && $this->view->file_exists($path)
			&& $this->isEncryptedPath($path)
		) {

			// get the size from filesystem
			$size = $this->view->filesize($path);

			// fast path, else the calculation for $lastChunkNr is bogus
			if ($size === 0) {
				\OC_FileProxy::$enabled = $proxyStatus;
				return 0;
			}

			// calculate last chunk nr
			// next highest is end of chunks, one subtracted is last one
			// we have to read the last chunk, we can't just calculate it (because of padding etc)
			$lastChunkNr = ceil($size/ 8192) - 1;
			$lastChunkSize = $size - ($lastChunkNr * 8192);

			// open stream
			$stream = fopen('crypt://' . $path, "r");

			if (is_resource($stream)) {
				// calculate last chunk position
				$lastChunckPos = ($lastChunkNr * 8192);

				// seek to end
				fseek($stream, $lastChunckPos);

				// get the content of the last chunk
				$lastChunkContent = fread($stream, $lastChunkSize);

				// calc the real file size with the size of the last chunk
				$realSize = (($lastChunkNr * 6126) + strlen($lastChunkContent));

				// store file size
				$result = $realSize;
			}
		}

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;
	}

	/**
	 * @brief fix the file size of the encrypted file
	 * @param string $path absolute path
	 * @return boolean true / false if file is encrypted
	 */
	public function fixFileSize($path) {

		$result = false;

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$realSize = $this->getFileSize($path);

		if ($realSize > 0) {

			$cached = $this->view->getFileInfo($path);
			$cached['encrypted'] = true;

			// set the size
			$cached['unencrypted_size'] = $realSize;

			// put file info
			$this->view->putFileInfo($path, $cached);

			$result = true;

		}

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;
	}


	/**
	 * @param $path
	 * @return bool
	 */
	public function isSharedPath($path) {

		$trimmed = ltrim($path, '/');
		$split = explode('/', $trimmed);

		if (isset($split[2]) && $split[2] === 'Shared') {

			return true;

		} else {

			return false;

		}

	}

	/**
	 * @brief encrypt versions from given file
	 * @param array $filelist list of encrypted files, relative to data/user/files
	 * @return boolean
	 */
	private function encryptVersions($filelist) {

		$successful = true;

		if (\OCP\App::isEnabled('files_versions')) {

			foreach ($filelist as $filename) {

				$versions = \OCA\Files_Versions\Storage::getVersions($this->userId, $filename);
				foreach ($versions as $version) {

					$path = '/' . $this->userId . '/files_versions/' . $version['path'] . '.v' . $version['version'];

					$encHandle = fopen('crypt://' . $path . '.part', 'wb');

					if ($encHandle === false) {
						\OCP\Util::writeLog('Encryption library', 'couldn\'t open "' . $path . '", decryption failed!', \OCP\Util::FATAL);
						$successful = false;
						continue;
					}

					$plainHandle = $this->view->fopen($path, 'rb');
					if ($plainHandle === false) {
						\OCP\Util::writeLog('Encryption library', 'couldn\'t open "' . $path . '.part", decryption failed!', \OCP\Util::FATAL);
						$successful = false;
						continue;
					}

					stream_copy_to_stream($plainHandle, $encHandle);

					fclose($encHandle);
					fclose($plainHandle);

					$this->view->rename($path . '.part', $path);
				}
			}
		}

		return $successful;
	}

	/**
	 * @brief decrypt versions from given file
	 * @param string $filelist list of decrypted files, relative to data/user/files
	 * @return boolean
	 */
	private function decryptVersions($filelist) {

		$successful = true;

		if (\OCP\App::isEnabled('files_versions')) {

			foreach ($filelist as $filename) {

				$versions = \OCA\Files_Versions\Storage::getVersions($this->userId, $filename);
				foreach ($versions as $version) {

					$path = '/' . $this->userId . '/files_versions/' . $version['path'] . '.v' . $version['version'];

					$encHandle = fopen('crypt://' . $path, 'rb');

					if ($encHandle === false) {
						\OCP\Util::writeLog('Encryption library', 'couldn\'t open "' . $path . '", decryption failed!', \OCP\Util::FATAL);
						$successful = false;
						continue;
					}

					$plainHandle = $this->view->fopen($path . '.part', 'wb');
					if ($plainHandle === false) {
						\OCP\Util::writeLog('Encryption library', 'couldn\'t open "' . $path . '.part", decryption failed!', \OCP\Util::FATAL);
						$successful = false;
						continue;
					}

					stream_copy_to_stream($encHandle, $plainHandle);

					fclose($encHandle);
					fclose($plainHandle);

					$this->view->rename($path . '.part', $path);
				}
			}
		}

		return $successful;
	}

	/**
	 * @brief Decrypt all files
	 * @return bool
	 */
	public function decryptAll() {

		$found = $this->findEncFiles($this->userId . '/files');

		$successful = true;

		if ($found) {

			$versionStatus = \OCP\App::isEnabled('files_versions');
			\OC_App::disable('files_versions');

			$decryptedFiles = array();

			// Encrypt unencrypted files
			foreach ($found['encrypted'] as $encryptedFile) {

				//relative to data/<user>/file
				$relPath = Helper::stripUserFilesPath($encryptedFile['path']);

				//get file info
				$fileInfo = \OC\Files\Filesystem::getFileInfo($relPath);

				//relative to /data
				$rawPath = $encryptedFile['path'];

				//get timestamp
				$timestamp = $fileInfo['mtime'];

				//enable proxy to use OC\Files\View to access the original file
				\OC_FileProxy::$enabled = true;

				// Open enc file handle for binary reading
				$encHandle = $this->view->fopen($rawPath, 'rb');

				// Disable proxy to prevent file being encrypted again
				\OC_FileProxy::$enabled = false;

				if ($encHandle === false) {
					\OCP\Util::writeLog('Encryption library', 'couldn\'t open "' . $rawPath . '", decryption failed!', \OCP\Util::FATAL);
					$successful = false;
					continue;
				}

				// Open plain file handle for binary writing, with same filename as original plain file
				$plainHandle = $this->view->fopen($rawPath . '.part', 'wb');
				if ($plainHandle === false) {
					\OCP\Util::writeLog('Encryption library', 'couldn\'t open "' . $rawPath . '.part", decryption failed!', \OCP\Util::FATAL);
					$successful = false;
					continue;
				}

				// Move plain file to a temporary location
				$size = stream_copy_to_stream($encHandle, $plainHandle);
				if ($size === 0) {
					\OCP\Util::writeLog('Encryption library', 'Zero bytes copied of "' . $rawPath . '", decryption failed!', \OCP\Util::FATAL);
					$successful = false;
					continue;
				}

				fclose($encHandle);
				fclose($plainHandle);

				$fakeRoot = $this->view->getRoot();
				$this->view->chroot('/' . $this->userId . '/files');

				$this->view->rename($relPath . '.part', $relPath);

				//set timestamp
				$this->view->touch($relPath, $timestamp);

				$this->view->chroot($fakeRoot);

				// Add the file to the cache
				\OC\Files\Filesystem::putFileInfo($relPath, array(
					'encrypted' => false,
					'size' => $size,
					'unencrypted_size' => $size,
					'etag' => $fileInfo['etag']
				));

				$decryptedFiles[] = $relPath;

			}

			if ($versionStatus) {
				\OC_App::enable('files_versions');
			}

			if (!$this->decryptVersions($decryptedFiles)) {
				$successful = false;
			}

			if ($successful) {
				$this->view->deleteAll($this->keyfilesPath);
				$this->view->deleteAll($this->shareKeysPath);
			}

			\OC_FileProxy::$enabled = true;
		}

		return $successful;
	}

	/**
	 * @brief Encrypt all files in a directory
	 * @param string $dirPath the directory whose files will be encrypted
	 * @param null $legacyPassphrase
	 * @param null $newPassphrase
	 * @return bool
	 * @note Encryption is recursive
	 */
	public function encryptAll($dirPath, $legacyPassphrase = null, $newPassphrase = null) {

		$found = $this->findEncFiles($dirPath);

		if ($found) {

			// Disable proxy to prevent file being encrypted twice
			\OC_FileProxy::$enabled = false;

			$versionStatus = \OCP\App::isEnabled('files_versions');
			\OC_App::disable('files_versions');

			$encryptedFiles = array();

			// Encrypt unencrypted files
			foreach ($found['plain'] as $plainFile) {

				//get file info
				$fileInfo = \OC\Files\Filesystem::getFileInfo($plainFile['path']);

				//relative to data/<user>/file
				$relPath = $plainFile['path'];

				//relative to /data
				$rawPath = '/' . $this->userId . '/files/' . $plainFile['path'];

				// keep timestamp
				$timestamp = $fileInfo['mtime'];

				// Open plain file handle for binary reading
				$plainHandle = $this->view->fopen($rawPath, 'rb');

				// Open enc file handle for binary writing, with same filename as original plain file
				$encHandle = fopen('crypt://' . $rawPath . '.part', 'wb');

				// Move plain file to a temporary location
				$size = stream_copy_to_stream($plainHandle, $encHandle);

				fclose($encHandle);
				fclose($plainHandle);

				$fakeRoot = $this->view->getRoot();
				$this->view->chroot('/' . $this->userId . '/files');

				$this->view->rename($relPath . '.part', $relPath);

				// set timestamp
				$this->view->touch($relPath, $timestamp);

				$this->view->chroot($fakeRoot);

				// Add the file to the cache
				\OC\Files\Filesystem::putFileInfo($relPath, array(
					'encrypted' => true,
					'size' => $size,
					'unencrypted_size' => $size,
					'etag' => $fileInfo['etag']
				));

				$encryptedFiles[] = $relPath;

			}

			// Encrypt legacy encrypted files
			if (
				!empty($legacyPassphrase)
				&& !empty($newPassphrase)
			) {

				foreach ($found['legacy'] as $legacyFile) {

					// Fetch data from file
					$legacyData = $this->view->file_get_contents($legacyFile['path']);

					// decrypt data, generate catfile
					$decrypted = Crypt::legacyBlockDecrypt($legacyData, $legacyPassphrase);

					$rawPath = $legacyFile['path'];

					// enable proxy the ensure encryption is handled
					\OC_FileProxy::$enabled = true;

					// Open enc file handle for binary writing, with same filename as original plain file
					$encHandle = $this->view->fopen( $rawPath, 'wb' );

					if (is_resource($encHandle)) {

						// write data to stream
						fwrite($encHandle, $decrypted);

						// close stream
						fclose($encHandle);
					}

					// disable proxy to prevent file being encrypted twice
					\OC_FileProxy::$enabled = false;
				}
			}

			\OC_FileProxy::$enabled = true;

			if ($versionStatus) {
				\OC_App::enable('files_versions');
			}

			$this->encryptVersions($encryptedFiles);

			// If files were found, return true
			return true;
		} else {

			// If no files were found, return false
			return false;
		}
	}

	/**
	 * @brief Return important encryption related paths
	 * @param string $pathName Name of the directory to return the path of
	 * @return string path
	 */
	public function getPath($pathName) {

		switch ($pathName) {

			case 'publicKeyDir':

				return $this->publicKeyDir;

				break;

			case 'encryptionDir':

				return $this->encryptionDir;

				break;

			case 'keyfilesPath':

				return $this->keyfilesPath;

				break;

			case 'publicKeyPath':

				return $this->publicKeyPath;

				break;

			case 'privateKeyPath':

				return $this->privateKeyPath;

				break;
		}

		return false;

	}

	/**
	 * @brief get path of a file.
	 * @param int $fileId id of the file
	 * @return string path of the file
	 */
	public static function fileIdToPath($fileId) {

		$sql = 'SELECT `path` FROM `*PREFIX*filecache` WHERE `fileid` = ?';

		$query = \OCP\DB::prepare($sql);

		$result = $query->execute(array($fileId));

		$path = false;
		if (\OCP\DB::isError($result)) {
			\OCP\Util::writeLog('Encryption library', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		} else {
			if ($result->numRows() > 0) {
				$row = $result->fetchRow();
				$path = substr($row['path'], strlen('files'));
			}
		}

		return $path;

	}

	/**
	 * @brief Filter an array of UIDs to return only ones ready for sharing
	 * @param array $unfilteredUsers users to be checked for sharing readiness
	 * @return array as multi-dimensional array. keys: ready, unready
	 */
	public function filterShareReadyUsers($unfilteredUsers) {

		// This array will collect the filtered IDs
		$readyIds = $unreadyIds = array();

		// Loop through users and create array of UIDs that need new keyfiles
		foreach ($unfilteredUsers as $user) {

			$util = new Util($this->view, $user);

			// Check that the user is encryption capable, or is the
			// public system user 'ownCloud' (for public shares)
			if (
				$user === $this->publicShareKeyId
				or $user === $this->recoveryKeyId
				or $util->ready()
			) {

				// Construct array of ready UIDs for Keymanager{}
				$readyIds[] = $user;

			} else {

				// Construct array of unready UIDs for Keymanager{}
				$unreadyIds[] = $user;

				// Log warning; we can't do necessary setup here
				// because we don't have the user passphrase
				\OCP\Util::writeLog('Encryption library',
					'"' . $user . '" is not setup for encryption', \OCP\Util::WARN);

			}

		}

		return array(
			'ready' => $readyIds,
			'unready' => $unreadyIds
		);

	}

	/**
	 * @brief Decrypt a keyfile
	 * @param string $filePath
	 * @param string $privateKey
	 * @return bool|string
	 */
	private function decryptKeyfile($filePath, $privateKey) {

		// Get the encrypted keyfile
		$encKeyfile = Keymanager::getFileKey($this->view, $filePath);

		// The file has a shareKey and must use it for decryption
		$shareKey = Keymanager::getShareKey($this->view, $this->userId, $filePath);

		$plainKeyfile = Crypt::multiKeyDecrypt($encKeyfile, $shareKey, $privateKey);

		return $plainKeyfile;
	}

	/**
	 * @brief Encrypt keyfile to multiple users
	 * @param Session $session
	 * @param array $users list of users which should be able to access the file
	 * @param string $filePath path of the file to be shared
	 * @return bool
	 */
	public function setSharedFileKeyfiles(Session $session, array $users, $filePath) {

		// Make sure users are capable of sharing
		$filteredUids = $this->filterShareReadyUsers($users);

		// If we're attempting to share to unready users
		if (!empty($filteredUids['unready'])) {

			\OCP\Util::writeLog('Encryption library',
				'Sharing to these user(s) failed as they are unready for encryption:"'
				. print_r($filteredUids['unready'], 1), \OCP\Util::WARN);

			return false;

		}

		// Get public keys for each user, ready for generating sharekeys
		$userPubKeys = Keymanager::getPublicKeys($this->view, $filteredUids['ready']);

		// Note proxy status then disable it
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		// Get the current users's private key for decrypting existing keyfile
		$privateKey = $session->getPrivateKey();

		$fileOwner = \OC\Files\Filesystem::getOwner($filePath);

		// Decrypt keyfile
		$plainKeyfile = $this->decryptKeyfile($filePath, $privateKey);

		// Re-enc keyfile to (additional) sharekeys
		$multiEncKey = Crypt::multiKeyEncrypt($plainKeyfile, $userPubKeys);

		// Save the recrypted key to it's owner's keyfiles directory
		// Save new sharekeys to all necessary user directory
		if (
			!Keymanager::setFileKey($this->view, $filePath, $fileOwner, $multiEncKey['data'])
			|| !Keymanager::setShareKeys($this->view, $filePath, $multiEncKey['keys'])
		) {

			\OCP\Util::writeLog('Encryption library',
				'Keyfiles could not be saved for users sharing ' . $filePath, \OCP\Util::ERROR);

			return false;

		}

		// Return proxy to original status
		\OC_FileProxy::$enabled = $proxyStatus;

		return true;
	}

	/**
	 * @brief Find, sanitise and format users sharing a file
	 * @note This wraps other methods into a portable bundle
	 */
	public function getSharingUsersArray($sharingEnabled, $filePath, $currentUserId = false) {

		// Check if key recovery is enabled
		if (
			\OC_Appconfig::getValue('files_encryption', 'recoveryAdminEnabled')
			&& $this->recoveryEnabledForUser()
		) {
			$recoveryEnabled = true;
		} else {
			$recoveryEnabled = false;
		}

		// Make sure that a share key is generated for the owner too
		list($owner, $ownerPath) = $this->getUidAndFilename($filePath);

		$ownerPath = \OCA\Encryption\Helper::stripPartialFileExtension($ownerPath);

		$userIds = array();
		if ($sharingEnabled) {

			// Find out who, if anyone, is sharing the file
			$result = \OCP\Share::getUsersSharingFile($ownerPath, $owner, true);
			$userIds = $result['users'];
			if ($result['public']) {
				$userIds[] = $this->publicShareKeyId;
			}

		}

		// If recovery is enabled, add the
		// Admin UID to list of users to share to
		if ($recoveryEnabled) {
			// Find recoveryAdmin user ID
			$recoveryKeyId = \OC_Appconfig::getValue('files_encryption', 'recoveryKeyId');
			// Add recoveryAdmin to list of users sharing
			$userIds[] = $recoveryKeyId;
		}

		// add current user if given
		if ($currentUserId !== false) {
			$userIds[] = $currentUserId;
		}

		// check if it is a group mount
		if (\OCP\App::isEnabled("files_external")) {
			$mount = \OC_Mount_Config::getSystemMountPoints();
			foreach ($mount as $mountPoint => $data) {
				if ($mountPoint == substr($ownerPath, 1, strlen($mountPoint))) {
					$userIds = array_merge($userIds, $this->getUserWithAccessToMountPoint($data['applicable']['users'], $data['applicable']['groups']));
				}
			}
		}

		// Remove duplicate UIDs
		$uniqueUserIds = array_unique($userIds);

		return $uniqueUserIds;

	}

	private function getUserWithAccessToMountPoint($users, $groups) {
		$result = array();
		if (in_array('all', $users)) {
			$result = \OCP\User::getUsers();
		} else {
			$result = array_merge($result, $users);
			foreach ($groups as $group) {
				$result = array_merge($result, \OC_Group::usersInGroup($group));
			}
		}

		return $result;
	}

	/**
	 * @brief start migration mode to initially encrypt users data
	 * @return boolean
	 */
	public function beginMigration() {

		$return = false;

		$sql = 'UPDATE `*PREFIX*encryption` SET `migration_status` = ? WHERE `uid` = ? and `migration_status` = ?';
		$args = array(self::MIGRATION_IN_PROGRESS, $this->userId, self::MIGRATION_OPEN);
		$query = \OCP\DB::prepare($sql);
		$manipulatedRows = $query->execute($args);

		if ($manipulatedRows === 1) {
			$return = true;
			\OCP\Util::writeLog('Encryption library', "Start migration to encryption mode for " . $this->userId, \OCP\Util::INFO);
		} else {
			\OCP\Util::writeLog('Encryption library', "Could not activate migration mode for " . $this->userId . ". Probably another process already started the initial encryption", \OCP\Util::WARN);
		}

		return $return;
	}

	/**
	 * @brief close migration mode after users data has been encrypted successfully
	 * @return boolean
	 */
	public function finishMigration() {

		$return = false;

		$sql = 'UPDATE `*PREFIX*encryption` SET `migration_status` = ? WHERE `uid` = ? and `migration_status` = ?';
		$args = array(self::MIGRATION_COMPLETED, $this->userId, self::MIGRATION_IN_PROGRESS);
		$query = \OCP\DB::prepare($sql);
		$manipulatedRows = $query->execute($args);

		if ($manipulatedRows === 1) {
			$return = true;
			\OCP\Util::writeLog('Encryption library', "Finish migration successfully for " . $this->userId, \OCP\Util::INFO);
		} else {
			\OCP\Util::writeLog('Encryption library', "Could not deactivate migration mode for " . $this->userId, \OCP\Util::WARN);
		}

		return $return;
	}

	/**
	 * @brief check if files are already migrated to the encryption system
	 * @return migration status, false = in case of no record
	 * @note If records are not being returned, check for a hidden space
	 *       at the start of the uid in db
	 */
	public function getMigrationStatus() {

		$sql = 'SELECT `migration_status` FROM `*PREFIX*encryption` WHERE `uid` = ?';

		$args = array($this->userId);

		$query = \OCP\DB::prepare($sql);

		$result = $query->execute($args);

		$migrationStatus = array();

		if (\OCP\DB::isError($result)) {
			\OCP\Util::writeLog('Encryption library', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		} else {
			if ($result->numRows() > 0) {
				$row = $result->fetchRow();
				if (isset($row['migration_status'])) {
					$migrationStatus[] = $row['migration_status'];
				}
			}
		}

		// If no record is found
		if (empty($migrationStatus)) {
			\OCP\Util::writeLog('Encryption library', "Could not get migration status for " . $this->userId . ", no record found", \OCP\Util::ERROR);
			// insert missing entry in DB with status open
			$sql = 'INSERT INTO `*PREFIX*encryption` (`uid`,`mode`,`recovery_enabled`,`migration_status`) VALUES (?,?,?,?)';
			$args = array(
				$this->userId,
				'server-side',
				0,
				self::MIGRATION_OPEN
			);
			$query = \OCP\DB::prepare($sql);
			$query->execute($args);

			return self::MIGRATION_OPEN;
			// If a record is found
		} else {
			return (int)$migrationStatus[0];
		}

	}

	/**
	 * @brief get uid of the owners of the file and the path to the file
	 * @param string $path Path of the file to check
	 * @throws \Exception
	 * @note $shareFilePath must be relative to data/UID/files. Files
	 *       relative to /Shared are also acceptable
	 * @return array
	 */
	public function getUidAndFilename($path) {

		$pathinfo = pathinfo($path);
		$partfile = false;
		$parentFolder = false;
		if (array_key_exists('extension', $pathinfo) && $pathinfo['extension'] === 'part') {
			// if the real file exists we check this file
			$filePath = $this->userFilesDir . '/' .$pathinfo['dirname'] . '/' . $pathinfo['filename'];
			if ($this->view->file_exists($filePath)) {
				$pathToCheck = $pathinfo['dirname'] . '/' . $pathinfo['filename'];
			} else { // otherwise we look for the parent
				$pathToCheck = $pathinfo['dirname'];
				$parentFolder = true;
			}
			$partfile = true;
		} else {
			$pathToCheck = $path;
		}

		$view = new \OC\Files\View($this->userFilesDir);
		$fileOwnerUid = $view->getOwner($pathToCheck);

		// handle public access
		if ($this->isPublic) {
			$filename = $path;
			$fileOwnerUid = $GLOBALS['fileOwner'];

			return array(
				$fileOwnerUid,
				$filename
			);
		} else {

			// Check that UID is valid
			if (!\OCP\User::userExists($fileOwnerUid)) {
				throw new \Exception(
					'Could not find owner (UID = "' . var_export($fileOwnerUid, 1) . '") of file "' . $path . '"');
			}

			// NOTE: Bah, this dependency should be elsewhere
			\OC\Files\Filesystem::initMountPoints($fileOwnerUid);

			// If the file owner is the currently logged in user
			if ($fileOwnerUid === $this->userId) {

				// Assume the path supplied is correct
				$filename = $path;

			} else {
				$info = $view->getFileInfo($pathToCheck);
				$ownerView = new \OC\Files\View('/' . $fileOwnerUid . '/files');

				// Fetch real file path from DB
				$filename = $ownerView->getPath($info['fileid']);
				if ($parentFolder) {
					$filename = $filename . '/'. $pathinfo['filename'];
				}

				if ($partfile) {
					$filename = $filename . '.' . $pathinfo['extension'];
				}

			}

			return array(
				$fileOwnerUid,
				\OC_Filesystem::normalizePath($filename)
			);
		}
	}


	/**
	 * @brief go recursively through a dir and collect all files and sub files.
	 * @param string $dir relative to the users files folder
	 * @return array with list of files relative to the users files folder
	 */
	public function getAllFiles($dir) {

		$result = array();

		$content = $this->view->getDirectoryContent(\OC\Files\Filesystem::normalizePath(
			$this->userFilesDir . '/' . $dir));

		// handling for re shared folders
		$pathSplit = explode('/', $dir);

		foreach ($content as $c) {

			$sharedPart = $pathSplit[sizeof($pathSplit) - 1];
			$targetPathSplit = array_reverse(explode('/', $c['path']));

			$path = '';

			// rebuild path
			foreach ($targetPathSplit as $pathPart) {

				if ($pathPart !== $sharedPart) {

					$path = '/' . $pathPart . $path;

				} else {

					break;

				}

			}

			$path = $dir . $path;

			if ($c['type'] === 'dir') {

				$result = array_merge($result, $this->getAllFiles($path));

			} else {

				$result[] = $path;

			}
		}

		return $result;

	}

	/**
	 * @brief get shares parent.
	 * @param int $id of the current share
	 * @return array of the parent
	 */
	public static function getShareParent($id) {

		$sql = 'SELECT `file_target`, `item_type` FROM `*PREFIX*share` WHERE `id` = ?';

		$query = \OCP\DB::prepare($sql);

		$result = $query->execute(array($id));

		$row = array();
		if (\OCP\DB::isError($result)) {
			\OCP\Util::writeLog('Encryption library', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		} else {
			if ($result->numRows() > 0) {
				$row = $result->fetchRow();
			}
		}

		return $row;

	}

	/**
	 * @brief get shares parent.
	 * @param int $id of the current share
	 * @return array of the parent
	 */
	public static function getParentFromShare($id) {

		$sql = 'SELECT `parent` FROM `*PREFIX*share` WHERE `id` = ?';

		$query = \OCP\DB::prepare($sql);

		$result = $query->execute(array($id));

		$row = array();
		if (\OCP\DB::isError($result)) {
			\OCP\Util::writeLog('Encryption library', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		} else {
			if ($result->numRows() > 0) {
				$row = $result->fetchRow();
			}
		}

		return $row;

	}

	/**
	 * @brief get owner of the shared files.
	 * @param $id
	 * @internal param int $Id of a share
	 * @return string owner
	 */
	public function getOwnerFromSharedFile($id) {

		$query = \OCP\DB::prepare('SELECT `parent`, `uid_owner` FROM `*PREFIX*share` WHERE `id` = ?', 1);

		$result = $query->execute(array($id));

		$source = array();
		if (\OCP\DB::isError($result)) {
			\OCP\Util::writeLog('Encryption library', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		} else {
			if ($result->numRows() > 0) {
				$source = $result->fetchRow();
			}
		}

		$fileOwner = false;

		if (isset($source['parent'])) {

			$parent = $source['parent'];

			while (isset($parent)) {

				$query = \OCP\DB::prepare('SELECT `parent`, `uid_owner` FROM `*PREFIX*share` WHERE `id` = ?', 1);

				$result = $query->execute(array($parent));

				$item = array();
				if (\OCP\DB::isError($result)) {
					\OCP\Util::writeLog('Encryption library', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
				} else {
					if ($result->numRows() > 0) {
						$item = $result->fetchRow();
					}
				}

				if (isset($item['parent'])) {

					$parent = $item['parent'];

				} else {

					$fileOwner = $item['uid_owner'];

					break;

				}
			}

		} else {

			$fileOwner = $source['uid_owner'];

		}

		return $fileOwner;

	}

	/**
	 * @return string
	 */
	public function getUserId() {
		return $this->userId;
	}

	/**
	 * @return string
	 */
	public function getUserFilesDir() {
		return $this->userFilesDir;
	}

	/**
	 * @param $password
	 * @return bool
	 */
	public function checkRecoveryPassword($password) {

		$result = false;
		$pathKey = '/owncloud_private_key/' . $this->recoveryKeyId . ".private.key";

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$recoveryKey = $this->view->file_get_contents($pathKey);

		$decryptedRecoveryKey = Crypt::decryptPrivateKey($recoveryKey, $password);

		if ($decryptedRecoveryKey) {
			$result = true;
		}

		\OC_FileProxy::$enabled = $proxyStatus;


		return $result;
	}

	/**
	 * @return string
	 */
	public function getRecoveryKeyId() {
		return $this->recoveryKeyId;
	}

	/**
	 * @brief add recovery key to all encrypted files
	 */
	public function addRecoveryKeys($path = '/') {
		$dirContent = $this->view->getDirectoryContent($this->keyfilesPath . $path);
		foreach ($dirContent as $item) {
			// get relative path from files_encryption/keyfiles/
			$filePath = substr($item['path'], strlen('files_encryption/keyfiles'));
			if ($item['type'] === 'dir') {
				$this->addRecoveryKeys($filePath . '/');
			} else {
				$session = new \OCA\Encryption\Session(new \OC_FilesystemView('/'));
				$sharingEnabled = \OCP\Share::isEnabled();
				// remove '.key' extension from path e.g. 'file.txt.key' to 'file.txt'
				$file = substr($filePath, 0, -4);
				$usersSharing = $this->getSharingUsersArray($sharingEnabled, $file);
				$this->setSharedFileKeyfiles($session, $usersSharing, $file);
			}
		}
	}

	/**
	 * @brief remove recovery key to all encrypted files
	 */
	public function removeRecoveryKeys($path = '/') {
		$dirContent = $this->view->getDirectoryContent($this->keyfilesPath . $path);
		foreach ($dirContent as $item) {
			// get relative path from files_encryption/keyfiles
			$filePath = substr($item['path'], strlen('files_encryption/keyfiles'));
			if ($item['type'] === 'dir') {
				$this->removeRecoveryKeys($filePath . '/');
			} else {
				// remove '.key' extension from path e.g. 'file.txt.key' to 'file.txt'
				$file = substr($filePath, 0, -4);
				$this->view->unlink($this->shareKeysPath . '/' . $file . '.' . $this->recoveryKeyId . '.shareKey');
			}
		}
	}

	/**
	 * @brief decrypt given file with recovery key and encrypt it again to the owner and his new key
	 * @param string $file
	 * @param string $privateKey recovery key to decrypt the file
	 */
	private function recoverFile($file, $privateKey) {

		$sharingEnabled = \OCP\Share::isEnabled();

		// Find out who, if anyone, is sharing the file
		if ($sharingEnabled) {
			$result = \OCP\Share::getUsersSharingFile($file, $this->userId, true);
			$userIds = $result['users'];
			$userIds[] = $this->recoveryKeyId;
			if ($result['public']) {
				$userIds[] = $this->publicShareKeyId;
			}
		} else {
			$userIds = array(
				$this->userId,
				$this->recoveryKeyId
			);
		}
		$filteredUids = $this->filterShareReadyUsers($userIds);

		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		//decrypt file key
		$encKeyfile = $this->view->file_get_contents($this->keyfilesPath . $file . ".key");
		$shareKey = $this->view->file_get_contents(
			$this->shareKeysPath . $file . "." . $this->recoveryKeyId . ".shareKey");
		$plainKeyfile = Crypt::multiKeyDecrypt($encKeyfile, $shareKey, $privateKey);
		// encrypt file key again to all users, this time with the new public key for the recovered use
		$userPubKeys = Keymanager::getPublicKeys($this->view, $filteredUids['ready']);
		$multiEncKey = Crypt::multiKeyEncrypt($plainKeyfile, $userPubKeys);

		// write new keys to filesystem TDOO!
		$this->view->file_put_contents($this->keyfilesPath . $file . '.key', $multiEncKey['data']);
		foreach ($multiEncKey['keys'] as $userId => $shareKey) {
			$shareKeyPath = $this->shareKeysPath . $file . '.' . $userId . '.shareKey';
			$this->view->file_put_contents($shareKeyPath, $shareKey);
		}

		// Return proxy to original status
		\OC_FileProxy::$enabled = $proxyStatus;
	}

	/**
	 * @brief collect all files and recover them one by one
	 * @param string $path to look for files keys
	 * @param string $privateKey private recovery key which is used to decrypt the files
	 */
	private function recoverAllFiles($path, $privateKey) {
		$dirContent = $this->view->getDirectoryContent($this->keyfilesPath . $path);
		foreach ($dirContent as $item) {
			// get relative path from files_encryption/keyfiles
			$filePath = substr($item['path'], strlen('files_encryption/keyfiles'));
			if ($item['type'] === 'dir') {
				$this->recoverAllFiles($filePath . '/', $privateKey);
			} else {
				// remove '.key' extension from path e.g. 'file.txt.key' to 'file.txt'
				$file = substr($filePath, 0, -4);
				$this->recoverFile($file, $privateKey);
			}
		}
	}

	/**
	 * @brief recover users files in case of password lost
	 * @param string $recoveryPassword
	 */
	public function recoverUsersFiles($recoveryPassword) {

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$encryptedKey = $this->view->file_get_contents(
			'/owncloud_private_key/' . $this->recoveryKeyId . '.private.key');
		$privateKey = Crypt::decryptPrivateKey($encryptedKey, $recoveryPassword);

		\OC_FileProxy::$enabled = $proxyStatus;

		$this->recoverAllFiles('/', $privateKey);
	}

	/**
	 * Get the path including the storage mount point
	 * @param int $id
	 * @return string the path including the mount point like AmazonS3/folder/file.txt
	 */
	public function getPathWithMountPoint($id) {
		list($storage, $internalPath) = \OC\Files\Cache\Cache::getById($id);
		$mount = \OC\Files\Filesystem::getMountByStorageId($storage);
		$mountPoint = $mount[0]->getMountPoint();
		$path = \OC\Files\Filesystem::normalizePath($mountPoint . '/' . $internalPath);

		// reformat the path to be relative e.g. /user/files/folder becomes /folder/
		$relativePath = \OCA\Encryption\Helper::stripUserFilesPath($path);

		return $relativePath;
	}

	/**
	 * @brief check if the file is stored on a system wide mount point
	 * @param $path relative to /data/user with leading '/'
	 * @return boolean
	 */
	public function isSystemWideMountPoint($path) {
		if (\OCP\App::isEnabled("files_external")) {
			$mount = \OC_Mount_Config::getSystemMountPoints();
			foreach ($mount as $mountPoint => $data) {
				if ($mountPoint == substr($path, 1, strlen($mountPoint))) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @brief decrypt private key and add it to the current session
	 * @param array $params with 'uid' and 'password'
	 * @return mixed session or false
	 */
	public function initEncryption($params) {

		$session = new \OCA\Encryption\Session($this->view);

		// we tried to initialize the encryption app for this session
		$session->setInitialized(\OCA\Encryption\Session::INIT_EXECUTED);

		$encryptedKey = Keymanager::getPrivateKey($this->view, $params['uid']);

		$privateKey = Crypt::decryptPrivateKey($encryptedKey, $params['password']);

		if ($privateKey === false) {
			\OCP\Util::writeLog('Encryption library', 'Private key for user "' . $params['uid']
					. '" is not valid! Maybe the user password was changed from outside if so please change it back to gain access', \OCP\Util::ERROR);
			return false;
		}

		$session->setPrivateKey($privateKey);
		$session->setInitialized(\OCA\Encryption\Session::INIT_SUCCESSFUL);

		return $session;
	}

}
