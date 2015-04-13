<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\Crypto;


use OCA\Encryption\Exceptions\PublicKeyMissingException;
use OCA\Encryption\Util;
use OCP\Encryption\IEncryptionModule;
use OCA\Encryption\KeyManager;
use OCP\ILogger;

class Encryption implements IEncryptionModule {

	const ID = 'OC_DEFAULT_MODULE';
	const DISPLAY_NAME = 'ownCloud Default Encryption';

	/**
	 * @var Crypt
	 */
	private $crypt;

	/** @var string */
	private $cipher;

	/** @var string */
	private $path;

	/** @var string */
	private $user;

	/** @var string */
	private $fileKey;

	/** @var string */
	private $writeCache;

	/** @var KeyManager */
	private $keyManager;

	/** @var array */
	private $accessList;

	/** @var boolean */
	private $isWriteOperation;

	/** @var Util */
	private $util;


	/** @var  ILogger */
	private $logger;

	/**
	 *
	 * @param Crypt $crypt
	 * @param KeyManager $keyManager
	 * @param Util $util
	 * @param ILogger $logger
	 */
	public function __construct(Crypt $crypt,
								KeyManager $keyManager,
								Util $util,
								ILogger $logger) {
		$this->crypt = $crypt;
		$this->keyManager = $keyManager;
		$this->util = $util;
		$this->logger = $logger;
	}

	/**
	 * @return string defining the technical unique id
	 */
	public function getId() {
		return self::ID;
	}

	/**
	 * In comparison to getKey() this function returns a human readable (maybe translated) name
	 *
	 * @return string
	 */
	public function getDisplayName() {
		return self::DISPLAY_NAME;
	}

	/**
	 * start receiving chunks from a file. This is the place where you can
	 * perform some initial step before starting encrypting/decrypting the
	 * chunks
	 *
	 * @param string $path to the file
	 * @param string $user who read/write the file
	 * @param string $mode php stream open mode
	 * @param array $header contains the header data read from the file
	 * @param array $accessList who has access to the file contains the key 'users' and 'public'
	 *
	 * @return array $header contain data as key-value pairs which should be
	 *                       written to the header, in case of a write operation
	 *                       or if no additional data is needed return a empty array
	 */
	public function begin($path, $user, $mode, array $header, array $accessList) {

		$this->path = $this->getPathToRealFile($path);
		$this->accessList = $accessList;
		$this->user = $user;
		$this->isWriteOperation = false;
		$this->writeCache = '';

		$this->fileKey = $this->keyManager->getFileKey($this->path, $this->user);

		if (
			$mode === 'w'
			|| $mode === 'w+'
			|| $mode === 'wb'
			|| $mode === 'wb+'
		) {
			$this->isWriteOperation = true;
			if (empty($this->fileKey)) {
				$this->fileKey = $this->crypt->generateFileKey();
			}
		}

		if (isset($header['cipher'])) {
			$this->cipher = $header['cipher'];
		} elseif ($this->isWriteOperation) {
			$this->cipher = $this->crypt->getCipher();
		} else {
			// if we read a file without a header we fall-back to the legacy cipher
			// which was used in <=oC6
			$this->cipher = $this->crypt->getLegacyCipher();
		}

		return array('cipher' => $this->cipher);
	}

	/**
	 * last chunk received. This is the place where you can perform some final
	 * operation and return some remaining data if something is left in your
	 * buffer.
	 *
	 * @param string $path to the file
	 * @return string remained data which should be written to the file in case
	 *                of a write operation
	 * @throws PublicKeyMissingException
	 * @throws \Exception
	 * @throws \OCA\Encryption\Exceptions\MultiKeyEncryptException
	 */
	public function end($path) {
		$result = '';
		if ($this->isWriteOperation) {
			if (!empty($this->writeCache)) {
				$result = $this->crypt->symmetricEncryptFileContent($this->writeCache, $this->fileKey);
				$this->writeCache = '';
			}
			$publicKeys = array();
			foreach ($this->accessList['users'] as $uid) {
				try {
					$publicKeys[$uid] = $this->keyManager->getPublicKey($uid);
				} catch (PublicKeyMissingException $e) {
					$this->logger->warning('no public key found for user \'' . $uid .
						'\', user will not be able to read the file', array('app' => 'encryption'));
					// if the public key of the owner is missing we should fail
					if ($uid === $this->user) {
						throw $e;
					}
				}
			}

			$publicKeys = $this->keyManager->addSystemKeys($this->accessList, $publicKeys);

			$encryptedKeyfiles = $this->crypt->multiKeyEncrypt($this->fileKey, $publicKeys);
			$this->keyManager->setAllFileKeys($this->path, $encryptedKeyfiles);
		}
		return $result;
	}

	/**
	 * encrypt data
	 *
	 * @param string $data you want to encrypt
	 * @return mixed encrypted data
	 */
	public function encrypt($data) {

		// If extra data is left over from the last round, make sure it
		// is integrated into the next 6126 / 8192 block
		if ($this->writeCache) {

			// Concat writeCache to start of $data
			$data = $this->writeCache . $data;

			// Clear the write cache, ready for reuse - it has been
			// flushed and its old contents processed
			$this->writeCache = '';

		}

		$encrypted = '';
		// While there still remains some data to be processed & written
		while (strlen($data) > 0) {

			// Remaining length for this iteration, not of the
			// entire file (may be greater than 8192 bytes)
			$remainingLength = strlen($data);

			// If data remaining to be written is less than the
			// size of 1 6126 byte block
			if ($remainingLength < 6126) {

				// Set writeCache to contents of $data
				// The writeCache will be carried over to the
				// next write round, and added to the start of
				// $data to ensure that written blocks are
				// always the correct length. If there is still
				// data in writeCache after the writing round
				// has finished, then the data will be written
				// to disk by $this->flush().
				$this->writeCache = $data;

				// Clear $data ready for next round
				$data = '';

			} else {

				// Read the chunk from the start of $data
				$chunk = substr($data, 0, 6126);

				$encrypted .= $this->crypt->symmetricEncryptFileContent($chunk, $this->fileKey);

				// Remove the chunk we just processed from
				// $data, leaving only unprocessed data in $data
				// var, for handling on the next round
				$data = substr($data, 6126);

			}

		}

		return $encrypted;
	}

	/**
	 * decrypt data
	 *
	 * @param string $data you want to decrypt
	 * @return mixed decrypted data
	 */
	public function decrypt($data) {
		$result = '';
		if (!empty($data)) {
			$result = $this->crypt->symmetricDecryptFileContent($data, $this->fileKey, $this->cipher);
		}
		return $result;
	}

	/**
	 * update encrypted file, e.g. give additional users access to the file
	 *
	 * @param string $path path to the file which should be updated
	 * @param string $uid of the user who performs the operation
	 * @param array $accessList who has access to the file contains the key 'users' and 'public'
	 * @return boolean
	 */
	public function update($path, $uid, array $accessList) {
		$fileKey = $this->keyManager->getFileKey($path, $uid);

		if (!empty($fileKey)) {

			$publicKeys = array();
			foreach ($accessList['users'] as $user) {
				$publicKeys[$user] = $this->keyManager->getPublicKey($user);
			}

			$publicKeys = $this->keyManager->addSystemKeys($accessList, $publicKeys);

			$encryptedFileKey = $this->crypt->multiKeyEncrypt($fileKey, $publicKeys);

			$this->keyManager->deleteAllFileKeys($path);

			$this->keyManager->setAllFileKeys($path, $encryptedFileKey);

		} else {
			$this->logger->debug('no file key found, we assume that the file "{file}" is not encrypted',
				array('file' => $path, 'app' => 'encryption'));

			return false;
		}

		return true;
	}

	/**
	 * add system keys such as the public share key and the recovery key
	 *
	 * @param array $accessList
	 * @param array $publicKeys
	 * @return array
	 */
	public function addSystemKeys(array $accessList, array $publicKeys) {
		if (!empty($accessList['public'])) {
			$publicKeys[$this->keyManager->getPublicShareKeyId()] = $this->keyManager->getPublicShareKey();
		}

		if ($this->keyManager->recoveryKeyExists() &&
			$this->util->isRecoveryEnabledForUser()) {

			$publicKeys[$this->keyManager->getRecoveryKeyId()] = $this->keyManager->getRecoveryKey();
		}

		return $publicKeys;
	}


	/**
	 * should the file be encrypted or not
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function shouldEncrypt($path) {
		$parts = explode('/', $path);
		if (count($parts) < 3) {
			return false;
		}

		if ($parts[2] == 'files') {
			return true;
		}
		if ($parts[2] == 'files_versions') {
			return true;
		}

		return false;
	}

	/**
	 * get size of the unencrypted payload per block.
	 * ownCloud read/write files with a block size of 8192 byte
	 *
	 * @return integer
	 */
	public function getUnencryptedBlockSize() {
		return 6126;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	protected function getPathToRealFile($path) {
		$realPath = $path;
		$parts = explode('/', $path);
		if ($parts[2] === 'files_versions') {
			$realPath = '/' . $parts[1] . '/files/' . implode('/', array_slice($parts, 3));
			$length = strrpos($realPath, '.');
			$realPath = substr($realPath, 0, $length);
		}

		return $realPath;
	}
}
