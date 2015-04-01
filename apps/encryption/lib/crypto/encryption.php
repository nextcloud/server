<?php
/**
 * @author Clark Tomlinson  <fallen013@gmail.com>
 * @since 3/6/15, 2:28 PM
 * @link http:/www.clarkt.com
 * @copyright Clark Tomlinson © 2015
 *
 */

namespace OCA\Encryption\Crypto;


use OCA\Encryption\Util;
use OCP\Encryption\IEncryptionModule;
use OCA\Encryption\KeyManager;

class Encryption implements IEncryptionModule {

	const ID = 'OC_DEFAULT_MODULE';

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

	/**
	 *
	 * @param \OCA\Encryption\Crypto\Crypt $crypt
	 * @param KeyManager $keyManager
	 * @param Util $util
	 */
	public function __construct(Crypt $crypt, KeyManager $keyManager, Util $util) {
		$this->crypt = $crypt;
		$this->keyManager = $keyManager;
		$this->util = $util;
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
		return 'ownCloud Default Encryption';
	}

	/**
	 * start receiving chunks from a file. This is the place where you can
	 * perform some initial step before starting encrypting/decrypting the
	 * chunks
	 *
	 * @param string $path to the file
	 * @param string $user who read/write the file
	 * @param array $header contains the header data read from the file
	 * @param array $accessList who has access to the file contains the key 'users' and 'public'
	 *
	 * @return array $header contain data as key-value pairs which should be
	 *                       written to the header, in case of a write operation
	 *                       or if no additional data is needed return a empty array
	 */
	public function begin($path, $user, $header, $accessList) {

		if (isset($header['cipher'])) {
			$this->cipher = $header['cipher'];
		} else {
			$this->cipher = $this->crypt->getCipher();
		}

		$this->path = $path;
		$this->accessList = $accessList;
		$this->user = $user;
		$this->writeCache = '';
		$this->isWriteOperation = false;

		$this->fileKey = $this->keyManager->getFileKey($path, $this->user);

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
				$publicKeys[$uid] = $this->keyManager->getPublicKey($uid);
			}

			$publicKeys = $this->keyManager->addSystemKeys($this->accessList, $publicKeys);

			$encryptedKeyfiles = $this->crypt->multiKeyEncrypt($this->fileKey, $publicKeys);
			$this->keyManager->setAllFileKeys($path, $encryptedKeyfiles);
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
		$this->isWriteOperation = true;
		if (empty($this->fileKey)) {
			$this->fileKey = $this->crypt->generateFileKey();
		}

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
			$result = $this->crypt->symmetricDecryptFileContent($data, $this->fileKey);
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
	public function update($path, $uid, $accessList) {
		$fileKey = $this->keyManager->getFileKey($path, $uid);
		$publicKeys = array();
		foreach ($accessList['users'] as $user) {
			$publicKeys[$user] = $this->keyManager->getPublicKey($user);
		}

		$publicKeys = $this->keyManager->addSystemKeys($accessList, $publicKeys);

		$encryptedFileKey = $this->crypt->multiKeyEncrypt($fileKey, $publicKeys);

		$this->keyManager->deleteAllFileKeys($path);

		$this->keyManager->setAllFileKeys($path, $encryptedFileKey);

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
			$this->util->recoveryEnabled($this->user)) {

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
	 * calculate unencrypted size
	 *
	 * @param string $path to file
	 * @return integer unencrypted size
	 */
	public function calculateUnencryptedSize($path) {
		// TODO: Implement calculateUnencryptedSize() method.
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
}
