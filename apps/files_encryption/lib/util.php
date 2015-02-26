<?php
/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Sam Tuke <samtuke@owncloud.com>,
 * @author Frank Karlitschek <frank@owncloud.org>,
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
 * Class for utilities relating to encrypted file storage system
 * @param \OC\Files\View $view expected to have OC '/' as root path
 * @param string $userId ID of the logged in user
 * @param int $client indicating status of client side encryption. Currently
 * unused, likely to become obsolete shortly
 */

class Util {

	const MIGRATION_COMPLETED = 1;    // migration to new encryption completed
	const MIGRATION_IN_PROGRESS = -1; // migration is running
	const MIGRATION_OPEN = 0;         // user still needs to be migrated

	const FILE_TYPE_FILE = 0;
	const FILE_TYPE_VERSION = 1;
	const FILE_TYPE_CACHE = 2;

	/**
	 * @var \OC\Files\View
	 */
	private $view; // OC\Files\View object for filesystem operations

	/**
	 * @var string
	 */
	private $userId; // ID of the user we use to encrypt/decrypt files

	/**
	 * @var string
	 */
	private $keyId; // ID of the key we want to manipulate

	/**
	 * @var bool
	 */
	private $client; // Client side encryption mode flag

	/**
	 * @var string
	 */
	private $publicKeyDir; // Dir containing all public user keys

	/**
	 * @var string
	 */
	private $encryptionDir; // Dir containing user's files_encryption

	/**
	 * @var string
	 */
	private $keysPath; // Dir containing all file related encryption keys

	/**
	 * @var string
	 */
	private $publicKeyPath; // Path to user's public key

	/**
	 * @var string
	 */
	private $privateKeyPath; // Path to user's private key

	/**
	 * @var string
	 */
	private $userFilesDir;

	/**
	 * @var string
	 */
	private $publicShareKeyId;

	/**
	 * @var string
	 */
	private $recoveryKeyId;

	/**
	 * @var bool
	 */
	private $isPublic;

	/**
	 * @param \OC\Files\View $view
	 * @param string $userId
	 * @param bool $client
	 */
	public function __construct($view, $userId, $client = false) {

		$this->view = $view;
		$this->client = $client;
		$this->userId = $userId;

		$appConfig = \OC::$server->getAppConfig();

		$this->publicShareKeyId = $appConfig->getValue('files_encryption', 'publicShareKeyId');
		$this->recoveryKeyId = $appConfig->getValue('files_encryption', 'recoveryKeyId');

		$this->userDir = '/' . $this->userId;
		$this->fileFolderName = 'files';
		$this->userFilesDir =
				'/' . $userId . '/' . $this->fileFolderName; // TODO: Does this need to be user configurable?
		$this->publicKeyDir = Keymanager::getPublicKeyPath();
		$this->encryptionDir = '/' . $this->userId . '/' . 'files_encryption';
		$this->keysPath = $this->encryptionDir . '/' . 'keys';
		$this->publicKeyPath =
				$this->publicKeyDir . '/' . $this->userId . '.publicKey'; // e.g. data/public-keys/admin.publicKey
		$this->privateKeyPath =
				$this->encryptionDir . '/' . $this->userId . '.privateKey'; // e.g. data/admin/admin.privateKey
		// make sure that the owners home is mounted
		\OC\Files\Filesystem::initMountPoints($userId);

		if (Helper::isPublicAccess()) {
			$this->keyId = $this->publicShareKeyId;
			$this->isPublic = true;
		} else {
			$this->keyId = $this->userId;
			$this->isPublic = false;
		}
	}

	/**
	 * @return bool
	 */
	public function ready() {

		if (
			!$this->view->file_exists($this->encryptionDir)
			or !$this->view->file_exists($this->keysPath)
			or !$this->view->file_exists($this->publicKeyPath)
			or !$this->view->file_exists($this->privateKeyPath)
		) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * check if the users private & public key exists
	 * @return boolean
	 */
	public function userKeysExists() {
		if (
				$this->view->file_exists($this->privateKeyPath) &&
				$this->view->file_exists($this->publicKeyPath)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * create a new public/private key pair for the user
	 *
	 * @param string $password password for the private key
	 */
	public function replaceUserKeys($password) {
		$this->backupAllKeys('password_reset');
		$this->view->unlink($this->publicKeyPath);
		$this->view->unlink($this->privateKeyPath);
		$this->setupServerSide($password);
	}

	/**
	 * Sets up user folders and keys for serverside encryption
	 *
	 * @param string $passphrase to encrypt server-stored private key with
	 * @return bool
	 */
	public function setupServerSide($passphrase = null) {

		// Set directories to check / create
		$setUpDirs = array(
			$this->userDir,
			$this->publicKeyDir,
			$this->encryptionDir,
			$this->keysPath
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
				$encryptedPrivateKey = Crypt::symmetricEncryptFileContent($keypair['privateKey'], $passphrase, Helper::getCipher());

				// Save key-pair
				if ($encryptedPrivateKey) {
					$header = crypt::generateHeader();
					$this->view->file_put_contents($this->privateKeyPath, $header . $encryptedPrivateKey);
					$this->view->file_put_contents($this->publicKeyPath, $keypair['publicKey']);
				}

				\OC_FileProxy::$enabled = true;
			}

		} else {
			// check if public-key exists but private-key is missing
			if ($this->view->file_exists($this->publicKeyPath) && !$this->view->file_exists($this->privateKeyPath)) {
				\OCP\Util::writeLog('Encryption library',
					'public key exists but private key is missing for "' . $this->keyId . '"', \OCP\Util::FATAL);
				return false;
			} else {
				if (!$this->view->file_exists($this->publicKeyPath) && $this->view->file_exists($this->privateKeyPath)
				) {
					\OCP\Util::writeLog('Encryption library',
						'private key exists but public key is missing for "' . $this->keyId . '"', \OCP\Util::FATAL);
					return false;
				}
			}
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
	 * Check whether pwd recovery is enabled for a given user
	 * @return bool 1 = yes, 0 = no, false = no record
	 *
	 * @note If records are not being returned, check for a hidden space
	 *       at the start of the uid in db
	 */
	public function recoveryEnabledForUser() {

		$recoveryMode = \OC::$server->getConfig()->getUserValue($this->userId, 'files_encryption', 'recovery_enabled', '0');

		return ($recoveryMode === '1') ? true : false;

	}

	/**
	 * Enable / disable pwd recovery for a given user
	 * @param bool $enabled Whether to enable or disable recovery
	 * @return bool
	 */
	public function setRecoveryForUser($enabled) {

		$value = $enabled ? '1' : '0';
		try {
			\OC::$server->getConfig()->setUserValue($this->userId, 'files_encryption', 'recovery_enabled', $value);
			return true;
		} catch(\OCP\PreConditionNotMetException $e) {
			return false;
		}

	}

	/**
	 * Find all files and their encryption status within a directory
	 * @param string $directory The path of the parent directory to search
	 * @param bool $found the founded files if called again
	 * @return array keys: plain, encrypted, broken
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
				'broken' => array(),
			);
		}

		if ($this->view->is_dir($directory) && $handle = $this->view->opendir($directory)){
			if (is_resource($handle)) {
				while (false !== ($file = readdir($handle))) {

					if ($file !== "." && $file !== "..") {
						// skip stray part files
						if (Helper::isPartialFilePath($file)) {
							continue;
						}

						$filePath = $directory . '/' . $this->view->getRelativePath('/' . $file);
						$relPath = Helper::stripUserFilesPath($filePath);

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
							if ($isEncryptedPath) {

								$fileKey = Keymanager::getFileKey($this->view, $this, $relPath);
								$shareKey = Keymanager::getShareKey($this->view, $this->userId, $this, $relPath);
								// if file is encrypted but now file key is available, throw exception
								if ($fileKey === false || $shareKey === false) {
									\OCP\Util::writeLog('encryption library', 'No keys available to decrypt the file: ' . $filePath, \OCP\Util::ERROR);
									$found['broken'][] = array(
										'name' => $file,
										'path' => $filePath,
									);
								} else {
									$found['encrypted'][] = array(
										'name' => $file,
										'path' => $filePath,
									);
								}

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
		}

		\OC_FileProxy::$enabled = true;

		return $found;
	}

	/**
	 * Check if a given path identifies an encrypted file
	 * @param string $path
	 * @return boolean
	 */
	public function isEncryptedPath($path) {

		// Disable encryption proxy so data retrieved is in its
		// original form
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$data = '';

		// we only need 24 byte from the last chunk
		if ($this->view->file_exists($path)) {
			$handle = $this->view->fopen($path, 'r');
			if (is_resource($handle)) {
				// suppress fseek warining, we handle the case that fseek doesn't
				// work in the else branch
				if (@fseek($handle, -24, SEEK_END) === 0) {
					$data = fgets($handle);
				} else {
					// if fseek failed on the storage we create a local copy from the file
					// and read this one
					fclose($handle);
					$localFile = $this->view->getLocalFile($path);
					$handle = fopen($localFile, 'r');
					if (is_resource($handle) && fseek($handle, -24, SEEK_END) === 0) {
						$data = fgets($handle);
					}
				}
				fclose($handle);
			}
		}

		// re-enable proxy
		\OC_FileProxy::$enabled = $proxyStatus;

		return Crypt::isCatfileContent($data);
	}

	/**
	 * get the file size of the unencrypted file
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

			$cipher = 'AES-128-CFB';
			$realSize = 0;

			// get the size from filesystem
			$size = $this->view->filesize($path);

			// open stream
			$stream = $this->view->fopen($path, "r");

			if (is_resource($stream)) {

				// if the file contains a encryption header we
				// we set the cipher
				// and we update the size
				if ($this->containHeader($path)) {
					$data = fread($stream,Crypt::BLOCKSIZE);
					$header = Crypt::parseHeader($data);
					$cipher = Crypt::getCipher($header);
					$size -= Crypt::BLOCKSIZE;
				}

				// fast path, else the calculation for $lastChunkNr is bogus
				if ($size === 0) {
					\OC_FileProxy::$enabled = $proxyStatus;
					return 0;
				}

				// calculate last chunk nr
				// next highest is end of chunks, one subtracted is last one
				// we have to read the last chunk, we can't just calculate it (because of padding etc)
				$lastChunkNr = ceil($size/Crypt::BLOCKSIZE)-1;

				// calculate last chunk position
				$lastChunkPos = ($lastChunkNr * Crypt::BLOCKSIZE);

				// get the content of the last chunk
				if (@fseek($stream, $lastChunkPos, SEEK_CUR) === 0) {
					$realSize+=$lastChunkNr*6126;
				}
				$lastChunkContentEncrypted='';
				$count=Crypt::BLOCKSIZE;
				while ($count>0) {
					$data=fread($stream,Crypt::BLOCKSIZE);
					$count=strlen($data);
					$lastChunkContentEncrypted.=$data;
					if(strlen($lastChunkContentEncrypted)>Crypt::BLOCKSIZE) {
						$realSize+=6126;
						$lastChunkContentEncrypted=substr($lastChunkContentEncrypted,Crypt::BLOCKSIZE);
					}
				}
				fclose($stream);
				$relPath = Helper::stripUserFilesPath($path);
				$shareKey = Keymanager::getShareKey($this->view, $this->keyId, $this, $relPath);
				if($shareKey===false) {
					\OC_FileProxy::$enabled = $proxyStatus;
					return $result;
				}
				$session = new Session($this->view);
				$privateKey = $session->getPrivateKey();
				$plainKeyfile = $this->decryptKeyfile($relPath, $privateKey);
				$plainKey = Crypt::multiKeyDecrypt($plainKeyfile, $shareKey, $privateKey);
				$lastChunkContent=Crypt::symmetricDecryptFileContent($lastChunkContentEncrypted, $plainKey, $cipher);

				// calc the real file size with the size of the last chunk
				$realSize += strlen($lastChunkContent);

				// store file size
				$result = $realSize;
			}
		}

		\OC_FileProxy::$enabled = $proxyStatus;

		return $result;
	}

	/**
	 * check if encrypted file contain a encryption header
	 *
	 * @param string $path
	 * @return boolean
	 */
	private function containHeader($path) {
		// Disable encryption proxy to read the raw data
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$isHeader = false;
		$handle = $this->view->fopen($path, 'r');

		if (is_resource($handle)) {
			$firstBlock = fread($handle, Crypt::BLOCKSIZE);
			$isHeader =  Crypt::isHeader($firstBlock);
		}

		\OC_FileProxy::$enabled = $proxyStatus;

		return $isHeader;
	}

	/**
	 * fix the file size of the encrypted file
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
	 * encrypt versions from given file
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
	 * decrypt versions from given file
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
	 * Decrypt all files
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
					'unencrypted_size' => 0,
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

			// if there are broken encrypted files than the complete decryption
			// was not successful
			if (!empty($found['broken'])) {
				$successful = false;
			}

			if ($successful) {
				$this->backupAllKeys('decryptAll', false, false);
				$this->view->deleteAll($this->keysPath);
			}

			\OC_FileProxy::$enabled = true;
		}

		return $successful;
	}

	/**
	 * Encrypt all files in a directory
	 * @param string $dirPath the directory whose files will be encrypted
	 * @return bool
	 * @note Encryption is recursive
	 */
	public function encryptAll($dirPath) {

		$result = true;

		$found = $this->findEncFiles($dirPath);

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

			if (is_resource($encHandle) && is_resource($plainHandle)) {
				// Move plain file to a temporary location
				$size = stream_copy_to_stream($plainHandle, $encHandle);

				fclose($encHandle);
				fclose($plainHandle);

				$fakeRoot = $this->view->getRoot();
				$this->view->chroot('/' . $this->userId . '/files');

				$this->view->rename($relPath . '.part', $relPath);

				// set timestamp
				$this->view->touch($relPath, $timestamp);

				$encSize = $this->view->filesize($relPath);

				$this->view->chroot($fakeRoot);

				// Add the file to the cache
				\OC\Files\Filesystem::putFileInfo($relPath, array(
					'encrypted' => true,
					'size' => $encSize,
					'unencrypted_size' => $size,
					'etag' => $fileInfo['etag']
				));

				$encryptedFiles[] = $relPath;
			} else {
				\OCP\Util::writeLog('files_encryption', 'initial encryption: could not encrypt ' . $rawPath, \OCP\Util::FATAL);
				$result = false;
			}
		}

		\OC_FileProxy::$enabled = true;

		if ($versionStatus) {
			\OC_App::enable('files_versions');
		}

		$result = $result && $this->encryptVersions($encryptedFiles);

		return $result;

	}

	/**
	 * Return important encryption related paths
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

			case 'keysPath':

				return $this->keysPath;

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
	 * Returns whether the given user is ready for encryption.
	 * Also returns true if the given user is the public user
	 * or the recovery key user.
	 *
	 * @param string $user user to check
	 *
	 * @return boolean true if the user is ready, false otherwise
	 */
	private function isUserReady($user) {
		if ($user === $this->publicShareKeyId
			|| $user === $this->recoveryKeyId
		) {
			return true;
		}
		$util = new Util($this->view, $user);
		return $util->ready();
	}

	/**
	 * Filter an array of UIDs to return only ones ready for sharing
	 * @param array $unfilteredUsers users to be checked for sharing readiness
	 * @return array as multi-dimensional array. keys: ready, unready
	 */
	public function filterShareReadyUsers($unfilteredUsers) {

		// This array will collect the filtered IDs
		$readyIds = $unreadyIds = array();

		// Loop through users and create array of UIDs that need new keyfiles
		foreach ($unfilteredUsers as $user) {
			// Check that the user is encryption capable, or is the
			// public system user (for public shares)
			if ($this->isUserReady($user)) {

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
	 * Decrypt a keyfile
	 * @param string $filePath
	 * @param string $privateKey
	 * @return false|string
	 */
	private function decryptKeyfile($filePath, $privateKey) {

		// Get the encrypted keyfile
		$encKeyfile = Keymanager::getFileKey($this->view, $this, $filePath);

		// The file has a shareKey and must use it for decryption
		$shareKey = Keymanager::getShareKey($this->view, $this->keyId, $this, $filePath);

		$plainKeyfile = Crypt::multiKeyDecrypt($encKeyfile, $shareKey, $privateKey);

		return $plainKeyfile;
	}

	/**
	 * Encrypt keyfile to multiple users
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

		try {
			// Decrypt keyfile
			$plainKeyfile = $this->decryptKeyfile($filePath, $privateKey);
			// Re-enc keyfile to (additional) sharekeys
			$multiEncKey = Crypt::multiKeyEncrypt($plainKeyfile, $userPubKeys);
		} catch (Exception\EncryptionException $e) {
			$msg = 'set shareFileKeyFailed (code: ' . $e->getCode() . '): ' . $e->getMessage();
			\OCP\Util::writeLog('files_encryption', $msg, \OCP\Util::FATAL);
			return false;
		} catch (\Exception $e) {
			$msg = 'set shareFileKeyFailed (unknown error): ' . $e->getMessage();
			\OCP\Util::writeLog('files_encryption', $msg, \OCP\Util::FATAL);
			return false;
		}

		// Save the recrypted key to it's owner's keyfiles directory
		// Save new sharekeys to all necessary user directory
		if (
				!Keymanager::setFileKey($this->view, $this, $filePath, $multiEncKey['data'])
				|| !Keymanager::setShareKeys($this->view, $this, $filePath, $multiEncKey['keys'])
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
	 * Find, sanitise and format users sharing a file
	 * @note This wraps other methods into a portable bundle
	 * @param boolean $sharingEnabled
	 * @param string $filePath path relativ to current users files folder
	 */
	public function getSharingUsersArray($sharingEnabled, $filePath) {

		$appConfig = \OC::$server->getAppConfig();

		// Check if key recovery is enabled
		if (
			$appConfig->getValue('files_encryption', 'recoveryAdminEnabled')
			&& $this->recoveryEnabledForUser()
		) {
			$recoveryEnabled = true;
		} else {
			$recoveryEnabled = false;
		}

		// Make sure that a share key is generated for the owner too
		list($owner, $ownerPath) = $this->getUidAndFilename($filePath);

		$ownerPath = Helper::stripPartialFileExtension($ownerPath);

		// always add owner to the list of users with access to the file
		$userIds = array($owner);

		if ($sharingEnabled) {

			// Find out who, if anyone, is sharing the file
			$result = \OCP\Share::getUsersSharingFile($ownerPath, $owner);
			$userIds = \array_merge($userIds, $result['users']);
			if ($result['public'] || $result['remote']) {
				$userIds[] = $this->publicShareKeyId;
			}

		}

		// If recovery is enabled, add the
		// Admin UID to list of users to share to
		if ($recoveryEnabled) {
			// Find recoveryAdmin user ID
			$recoveryKeyId = $appConfig->getValue('files_encryption', 'recoveryKeyId');
			// Add recoveryAdmin to list of users sharing
			$userIds[] = $recoveryKeyId;
		}

		// check if it is a group mount
		if (\OCP\App::isEnabled("files_external")) {
			$mounts = \OC_Mount_Config::getSystemMountPoints();
			foreach ($mounts as $mount) {
				if ($mount['mountpoint'] == substr($ownerPath, 1, strlen($mount['mountpoint']))) {
					$userIds = array_merge($userIds, $this->getUserWithAccessToMountPoint($mount['applicable']['users'], $mount['applicable']['groups']));
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
	 * set migration status
	 * @param int $status
	 * @param int $preCondition only update migration status if the previous value equals $preCondition
	 * @return boolean
	 */
	private function setMigrationStatus($status, $preCondition = null) {

		// convert to string if preCondition is set
		$preCondition = ($preCondition === null) ? null : (string)$preCondition;

		try {
			\OC::$server->getConfig()->setUserValue($this->userId, 'files_encryption', 'migration_status', (string)$status, $preCondition);
			return true;
		} catch(\OCP\PreConditionNotMetException $e) {
			return false;
		}

	}

	/**
	 * start migration mode to initially encrypt users data
	 * @return boolean
	 */
	public function beginMigration() {

		$result = $this->setMigrationStatus(self::MIGRATION_IN_PROGRESS, self::MIGRATION_OPEN);

		if ($result) {
			\OCP\Util::writeLog('Encryption library', "Start migration to encryption mode for " . $this->userId, \OCP\Util::INFO);
		} else {
			\OCP\Util::writeLog('Encryption library', "Could not activate migration mode for " . $this->userId . ". Probably another process already started the initial encryption", \OCP\Util::WARN);
		}

		return $result;
	}

	public function resetMigrationStatus() {
		return $this->setMigrationStatus(self::MIGRATION_OPEN);

	}

	/**
	 * close migration mode after users data has been encrypted successfully
	 * @return boolean
	 */
	public function finishMigration() {
		$result = $this->setMigrationStatus(self::MIGRATION_COMPLETED);

		if ($result) {
			\OCP\Util::writeLog('Encryption library', "Finish migration successfully for " . $this->userId, \OCP\Util::INFO);
		} else {
			\OCP\Util::writeLog('Encryption library', "Could not deactivate migration mode for " . $this->userId, \OCP\Util::WARN);
		}

		return $result;
	}

	/**
	 * check if files are already migrated to the encryption system
	 * @return int|false migration status, false = in case of no record
	 * @note If records are not being returned, check for a hidden space
	 *       at the start of the uid in db
	 */
	public function getMigrationStatus() {

		$migrationStatus = false;
		if (\OCP\User::userExists($this->userId)) {
			$migrationStatus = \OC::$server->getConfig()->getUserValue($this->userId, 'files_encryption', 'migration_status', null);
			if ($migrationStatus === null) {
				\OC::$server->getConfig()->setUserValue($this->userId, 'files_encryption', 'migration_status', (string)self::MIGRATION_OPEN);
				$migrationStatus = self::MIGRATION_OPEN;
			}
		}

		return (int)$migrationStatus;

	}

	/**
	 * get uid of the owners of the file and the path to the file
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
			return array($this->userId, $path);
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
				\OC\Files\Filesystem::normalizePath($filename)
			);
		}
	}

	/**
	 * go recursively through a dir and collect all files and sub files.
	 * @param string $dir relative to the users files folder
	 * @return array with list of files relative to the users files folder
	 */
	public function getAllFiles($dir, $mountPoint = '') {
		$result = array();
		$dirList = array($dir);

		while ($dirList) {
			$dir = array_pop($dirList);
			$content = $this->view->getDirectoryContent(\OC\Files\Filesystem::normalizePath(
					$this->userFilesDir . '/' . $dir));

			foreach ($content as $c) {
				// getDirectoryContent() returns the paths relative to the mount points, so we need
				// to re-construct the complete path
				$path = ($mountPoint !== '') ? $mountPoint . '/' .  $c['path'] : $c['path'];
				$path = \OC\Files\Filesystem::normalizePath($path);
				if ($c['type'] === 'dir') {
					$dirList[] = substr($path, strlen('/' . \OCP\User::getUser() . "/files"));
				} else {
					$result[] = substr($path, strlen('/' . \OCP\User::getUser() . "/files"));
				}
			}

		}

		return $result;
	}

	/**
	 * get owner of the shared files.
	 * @param int $id ID of a share
	 * @return string owner
	 */
	public function getOwnerFromSharedFile($id) {

		$query = \OCP\DB::prepare('SELECT `parent`, `uid_owner` FROM `*PREFIX*share` WHERE `id` = ?', 1);

		$result = $query->execute(array($id));

		$source = null;
		if (\OCP\DB::isError($result)) {
			\OCP\Util::writeLog('Encryption library', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
		} else {
			$source = $result->fetchRow();
		}

		$fileOwner = false;

		if ($source && isset($source['parent'])) {

			$parent = $source['parent'];

			while (isset($parent)) {

				$query = \OCP\DB::prepare('SELECT `parent`, `uid_owner` FROM `*PREFIX*share` WHERE `id` = ?', 1);

				$result = $query->execute(array($parent));

				$item = null;
				if (\OCP\DB::isError($result)) {
					\OCP\Util::writeLog('Encryption library', \OC_DB::getErrorMessage($result), \OCP\Util::ERROR);
				} else {
					$item = $result->fetchRow();
				}

				if ($item && isset($item['parent'])) {

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
	public function getKeyId() {
		return $this->keyId;
	}

	/**
	 * @return string
	 */
	public function getUserFilesDir() {
		return $this->userFilesDir;
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function checkRecoveryPassword($password) {

		$result = false;

		$recoveryKey = Keymanager::getPrivateSystemKey($this->recoveryKeyId);
		$decryptedRecoveryKey = Crypt::decryptPrivateKey($recoveryKey, $password);

		if ($decryptedRecoveryKey) {
			$result = true;
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function getRecoveryKeyId() {
		return $this->recoveryKeyId;
	}

	/**
	 * add recovery key to all encrypted files
	 */
	public function addRecoveryKeys($path = '/') {
		$dirContent = $this->view->getDirectoryContent($this->keysPath . '/' . $path);
		foreach ($dirContent as $item) {
			// get relative path from files_encryption/keyfiles/
			$filePath = substr($item['path'], strlen('files_encryption/keys'));
			if ($this->view->is_dir($this->userFilesDir . '/' . $filePath)) {
				$this->addRecoveryKeys($filePath . '/');
			} else {
				$session = new Session(new \OC\Files\View('/'));
				$sharingEnabled = \OCP\Share::isEnabled();
				$usersSharing = $this->getSharingUsersArray($sharingEnabled, $filePath);
				$this->setSharedFileKeyfiles($session, $usersSharing, $filePath);
			}
		}
	}

	/**
	 * remove recovery key to all encrypted files
	 */
	public function removeRecoveryKeys($path = '/') {
		$dirContent = $this->view->getDirectoryContent($this->keysPath . '/' . $path);
		foreach ($dirContent as $item) {
			// get relative path from files_encryption/keyfiles
			$filePath = substr($item['path'], strlen('files_encryption/keys'));
			if ($this->view->is_dir($this->userFilesDir . '/' . $filePath)) {
				$this->removeRecoveryKeys($filePath . '/');
			} else {
				$this->view->unlink($this->keysPath . '/' . $filePath . '/' . $this->recoveryKeyId . '.shareKey');
			}
		}
	}

	/**
	 * decrypt given file with recovery key and encrypt it again to the owner and his new key
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

		//decrypt file key
		$encKeyfile = Keymanager::getFileKey($this->view, $this, $file);
		$shareKey = Keymanager::getShareKey($this->view, $this->recoveryKeyId, $this, $file);
		$plainKeyfile = Crypt::multiKeyDecrypt($encKeyfile, $shareKey, $privateKey);
		// encrypt file key again to all users, this time with the new public key for the recovered use
		$userPubKeys = Keymanager::getPublicKeys($this->view, $filteredUids['ready']);
		$multiEncKey = Crypt::multiKeyEncrypt($plainKeyfile, $userPubKeys);

		Keymanager::setFileKey($this->view, $this, $file, $multiEncKey['data']);
		Keymanager::setShareKeys($this->view, $this, $file, $multiEncKey['keys']);

	}

	/**
	 * collect all files and recover them one by one
	 * @param string $path to look for files keys
	 * @param string $privateKey private recovery key which is used to decrypt the files
	 */
	private function recoverAllFiles($path, $privateKey) {
		$dirContent = $this->view->getDirectoryContent($this->keysPath . '/' . $path);
		foreach ($dirContent as $item) {
			// get relative path from files_encryption/keyfiles
			$filePath = substr($item['path'], strlen('files_encryption/keys'));
			if ($this->view->is_dir($this->userFilesDir . '/' . $filePath)) {
				$this->recoverAllFiles($filePath . '/', $privateKey);
			} else {
				$this->recoverFile($filePath, $privateKey);
			}
		}
	}

	/**
	 * recover users files in case of password lost
	 * @param string $recoveryPassword
	 */
	public function recoverUsersFiles($recoveryPassword) {

		$encryptedKey = Keymanager::getPrivateSystemKey( $this->recoveryKeyId);
		$privateKey = Crypt::decryptPrivateKey($encryptedKey, $recoveryPassword);

		$this->recoverAllFiles('/', $privateKey);
	}

	/**
	 * create a backup of all keys from the user
	 *
	 * @param string $purpose define the purpose of the backup, will be part of the backup folder name
	 * @param boolean $timestamp (optional) should a timestamp be added, default true
	 * @param boolean $includeUserKeys (optional) include users private-/public-key, default true
	 */
	public function backupAllKeys($purpose, $timestamp = true, $includeUserKeys = true) {
		$this->userId;
		$backupDir = $this->encryptionDir . '/backup.' . $purpose;
		$backupDir .= ($timestamp) ? '.' . date("Y-m-d_H-i-s") . '/' : '/';
		$this->view->mkdir($backupDir);
		$this->view->copy($this->keysPath, $backupDir . 'keys/');
		if ($includeUserKeys) {
			$this->view->copy($this->privateKeyPath, $backupDir . $this->userId . '.privateKey');
			$this->view->copy($this->publicKeyPath, $backupDir . $this->userId . '.publicKey');
		}
	}

	/**
	 * restore backup
	 *
	 * @param string $backup complete name of the backup
	 * @return boolean
	 */
	public function restoreBackup($backup) {
		$backupDir = $this->encryptionDir . '/backup.' . $backup . '/';

		$fileKeysRestored = $this->view->rename($backupDir . 'keys', $this->encryptionDir . '/keys');

		$pubKeyRestored = $privKeyRestored = true;
		if (
			$this->view->file_exists($backupDir . $this->userId . '.privateKey') &&
			$this->view->file_exists($backupDir . $this->userId . '.privateKey')
		) {

			$pubKeyRestored = $this->view->rename($backupDir . $this->userId . '.publicKey', $this->publicKeyPath);
			$privKeyRestored = $this->view->rename($backupDir . $this->userId . '.privateKey', $this->privateKeyPath);
		}

		if ($fileKeysRestored && $pubKeyRestored && $privKeyRestored) {
			$this->view->deleteAll($backupDir);

			return true;
		}

		return false;
	}

	/**
	 * delete backup
	 *
	 * @param string $backup complete name of the backup
	 * @return boolean
	 */
	public function deleteBackup($backup) {
		$backupDir = $this->encryptionDir . '/backup.' . $backup . '/';
		return $this->view->deleteAll($backupDir);
	}

	/**
	 * check if the file is stored on a system wide mount point
	 * @param string $path relative to /data/user with leading '/'
	 * @return boolean
	 */
	public function isSystemWideMountPoint($path) {
		$normalizedPath = ltrim($path, '/');
		if (\OCP\App::isEnabled("files_external")) {
			$mounts = \OC_Mount_Config::getSystemMountPoints();
			foreach ($mounts as $mount) {
				if ($mount['mountpoint'] == substr($normalizedPath, 0, strlen($mount['mountpoint']))) {
					if ($this->isMountPointApplicableToUser($mount)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * check if mount point is applicable to user
	 *
	 * @param array $mount contains $mount['applicable']['users'], $mount['applicable']['groups']
	 * @return boolean
	 */
	protected function isMountPointApplicableToUser($mount) {
		$uid = \OCP\User::getUser();
		$acceptedUids = array('all', $uid);
		// check if mount point is applicable for the user
		$intersection = array_intersect($acceptedUids, $mount['applicable']['users']);
		if (!empty($intersection)) {
			return true;
		}
		// check if mount point is applicable for group where the user is a member
		foreach ($mount['applicable']['groups'] as $gid) {
			if (\OC_Group::inGroup($uid, $gid)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * decrypt private key and add it to the current session
	 * @param array $params with 'uid' and 'password'
	 * @return mixed session or false
	 */
	public function initEncryption($params) {

		$session = new Session($this->view);

		// we tried to initialize the encryption app for this session
		$session->setInitialized(Session::INIT_EXECUTED);

		$encryptedKey = Keymanager::getPrivateKey($this->view, $params['uid']);

		$privateKey = false;
		if ($encryptedKey) {
			$privateKey = Crypt::decryptPrivateKey($encryptedKey, $params['password']);
		}

		if ($privateKey === false) {
			\OCP\Util::writeLog('Encryption library', 'Private key for user "' . $params['uid']
					. '" is not valid! Maybe the user password was changed from outside if so please change it back to gain access', \OCP\Util::ERROR);
			return false;
		}

		$session->setPrivateKey($privateKey);
		$session->setInitialized(Session::INIT_SUCCESSFUL);

		return $session;
	}

	/*
	 * remove encryption related keys from the session
	 */
	public function closeEncryptionSession() {
		$session = new Session($this->view);
		$session->closeSession();
	}

}
