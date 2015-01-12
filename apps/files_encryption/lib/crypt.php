<?php

/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 * @author Sam Tuke <samtuke@owncloud.com>
 * @author Frank Karlitschek <frank@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
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
 * Class for common cryptography functionality
 */

class Crypt {

	const ENCRYPTION_UNKNOWN_ERROR = -1;
	const ENCRYPTION_NOT_INITIALIZED_ERROR = 1;
	const ENCRYPTION_PRIVATE_KEY_NOT_VALID_ERROR = 2;
	const ENCRYPTION_NO_SHARE_KEY_FOUND = 3;

	const BLOCKSIZE = 8192; // block size will always be 8192 for a PHP stream https://bugs.php.net/bug.php?id=21641
	const DEFAULT_CIPHER = 'AES-256-CFB';

	const HEADERSTART = 'HBEGIN';
	const HEADEREND = 'HEND';

	/**
	 * return encryption mode client or server side encryption
	 * @param string $user name (use system wide setting if name=null)
	 * @return string 'client' or 'server'
	 * @note at the moment we only support server side encryption
	 */
	public static function mode($user = null) {

		return 'server';

	}

	/**
	 * Create a new encryption keypair
	 * @return array publicKey, privatekey
	 */
	public static function createKeypair() {

		$return = false;

		$res = Helper::getOpenSSLPkey();

		if ($res === false) {
			\OCP\Util::writeLog('Encryption library', 'couldn\'t generate users key-pair for ' . \OCP\User::getUser(), \OCP\Util::ERROR);
			while ($msg = openssl_error_string()) {
				\OCP\Util::writeLog('Encryption library', 'openssl_pkey_new() fails:  ' . $msg, \OCP\Util::ERROR);
			}
		} elseif (openssl_pkey_export($res, $privateKey, null, Helper::getOpenSSLConfig())) {
			// Get public key
			$keyDetails = openssl_pkey_get_details($res);
			$publicKey = $keyDetails['key'];

			$return = array(
				'publicKey' => $publicKey,
				'privateKey' => $privateKey
			);
		} else {
			\OCP\Util::writeLog('Encryption library', 'couldn\'t export users private key, please check your servers openSSL configuration.' . \OCP\User::getUser(), \OCP\Util::ERROR);
			while($errMsg = openssl_error_string()) {
				\OCP\Util::writeLog('Encryption library', $errMsg, \OCP\Util::ERROR);
			}
		}

		return $return;
	}

	/**
	 * Add arbitrary padding to encrypted data
	 * @param string $data data to be padded
	 * @return string padded data
	 * @note In order to end up with data exactly 8192 bytes long we must
	 * add two letters. It is impossible to achieve exactly 8192 length
	 * blocks with encryption alone, hence padding is added to achieve the
	 * required length.
	 */
	private static function addPadding($data) {

		$padded = $data . 'xx';

		return $padded;

	}

	/**
	 * Remove arbitrary padding to encrypted data
	 * @param string $padded padded data to remove padding from
	 * @return string unpadded data on success, false on error
	 */
	private static function removePadding($padded) {

		if (substr($padded, -2) === 'xx') {

			$data = substr($padded, 0, -2);

			return $data;

		} else {

			// TODO: log the fact that unpadded data was submitted for removal of padding
			return false;

		}

	}

	/**
	 * Check if a file's contents contains an IV and is symmetrically encrypted
	 * @param string $content
	 * @return boolean
	 * @note see also \OCA\Files_Encryption\Util->isEncryptedPath()
	 */
	public static function isCatfileContent($content) {

		if (!$content) {

			return false;

		}

		$noPadding = self::removePadding($content);

		// Fetch encryption metadata from end of file
		$meta = substr($noPadding, -22);

		// Fetch identifier from start of metadata
		$identifier = substr($meta, 0, 6);

		if ($identifier === '00iv00') {

			return true;

		} else {

			return false;

		}

	}

	/**
	 * Check if a file is encrypted according to database file cache
	 * @param string $path
	 * @return bool
	 */
	public static function isEncryptedMeta($path) {

		// TODO: Use DI to get \OC\Files\Filesystem out of here

		// Fetch all file metadata from DB
		$metadata = \OC\Files\Filesystem::getFileInfo($path);

		// Return encryption status
		return isset($metadata['encrypted']) && ( bool )$metadata['encrypted'];

	}

	/**
	 * Symmetrically encrypt a string
	 * @param string $plainContent
	 * @param string $iv
	 * @param string $passphrase
	 * @param string $cypher used for encryption, currently we support AES-128-CFB and AES-256-CFB
	 * @return string encrypted file content
	 * @throws \OCA\Files_Encryption\Exception\EncryptionException
	 */
	private static function encrypt($plainContent, $iv, $passphrase = '', $cipher = Crypt::DEFAULT_CIPHER) {

		$encryptedContent = openssl_encrypt($plainContent, $cipher, $passphrase, false, $iv);

		if (!$encryptedContent) {
			$error = "Encryption (symmetric) of content failed: " . openssl_error_string();
			\OCP\Util::writeLog('Encryption library', $error, \OCP\Util::ERROR);
			throw new Exception\EncryptionException($error, Exception\EncryptionException::ENCRYPTION_FAILED);
		}

		return $encryptedContent;

	}

	/**
	 * Symmetrically decrypt a string
	 * @param string $encryptedContent
	 * @param string $iv
	 * @param string $passphrase
	 * @param string $cipher cipher user for decryption, currently we support aes128 and aes256
	 * @throws \Exception
	 * @return string decrypted file content
	 */
	private static function decrypt($encryptedContent, $iv, $passphrase, $cipher = Crypt::DEFAULT_CIPHER) {

		$plainContent = openssl_decrypt($encryptedContent, $cipher, $passphrase, false, $iv);

		if ($plainContent) {
			return $plainContent;
		} else {
			throw new \Exception('Encryption library: Decryption (symmetric) of content failed');
		}

	}

	/**
	 * Concatenate encrypted data with its IV and padding
	 * @param string $content content to be concatenated
	 * @param string $iv IV to be concatenated
	 * @return string concatenated content
	 */
	private static function concatIv($content, $iv) {

		$combined = $content . '00iv00' . $iv;

		return $combined;

	}

	/**
	 * Split concatenated data and IV into respective parts
	 * @param string $catFile concatenated data to be split
	 * @return array keys: encrypted, iv
	 */
	private static function splitIv($catFile) {

		// Fetch encryption metadata from end of file
		$meta = substr($catFile, -22);

		// Fetch IV from end of file
		$iv = substr($meta, -16);

		// Remove IV and IV identifier text to expose encrypted content
		$encrypted = substr($catFile, 0, -22);

		$split = array(
			'encrypted' => $encrypted,
			'iv' => $iv
		);

		return $split;

	}

	/**
	 * Symmetrically encrypts a string and returns keyfile content
	 * @param string $plainContent content to be encrypted in keyfile
	 * @param string $passphrase
	 * @param string $cypher used for encryption, currently we support AES-128-CFB and AES-256-CFB
	 * @return false|string encrypted content combined with IV
	 * @note IV need not be specified, as it will be stored in the returned keyfile
	 * and remain accessible therein.
	 */
	public static function symmetricEncryptFileContent($plainContent, $passphrase = '', $cipher = Crypt::DEFAULT_CIPHER) {

		if (!$plainContent) {
			\OCP\Util::writeLog('Encryption library', 'symmetrically encryption failed, no content given.', \OCP\Util::ERROR);
			return false;
		}

		$iv = self::generateIv();

		try {
			$encryptedContent = self::encrypt($plainContent, $iv, $passphrase, $cipher);
			// Combine content to encrypt with IV identifier and actual IV
			$catfile = self::concatIv($encryptedContent, $iv);
			$padded = self::addPadding($catfile);

			return $padded;
		} catch (Exception\EncryptionException $e) {
			$message = 'Could not encrypt file content (code: ' . $e->getCode() . '): ';
			\OCP\Util::writeLog('files_encryption', $message . $e->getMessage(), \OCP\Util::ERROR);
			return false;
		}

	}


	/**
	 * Symmetrically decrypts keyfile content
	 * @param string $keyfileContent
	 * @param string $passphrase
	 * @param string $cipher cipher used for decryption, currently aes128 and aes256 is supported.
	 * @throws \Exception
	 * @return string|false
	 * @internal param string $source
	 * @internal param string $target
	 * @internal param string $key the decryption key
	 * @return string decrypted content
	 *
	 * This function decrypts a file
	 */
	public static function symmetricDecryptFileContent($keyfileContent, $passphrase = '', $cipher = Crypt::DEFAULT_CIPHER) {

		if (!$keyfileContent) {

			throw new \Exception('Encryption library: no data provided for decryption');

		}

		// Remove padding
		$noPadding = self::removePadding($keyfileContent);

		// Split into enc data and catfile
		$catfile = self::splitIv($noPadding);

		if ($plainContent = self::decrypt($catfile['encrypted'], $catfile['iv'], $passphrase, $cipher)) {

			return $plainContent;

		} else {
			return false;
		}

	}

	/**
	 * Decrypt private key and check if the result is a valid keyfile
	 *
	 * @param string $encryptedKey encrypted keyfile
	 * @param string $passphrase to decrypt keyfile
	 * @return string|false encrypted private key or false
	 *
	 * This function decrypts a file
	 */
	public static function decryptPrivateKey($encryptedKey, $passphrase) {

		$header = self::parseHeader($encryptedKey);
		$cipher = self::getCipher($header);

		// if we found a header we need to remove it from the key we want to decrypt
		if (!empty($header)) {
			$encryptedKey = substr($encryptedKey, strpos($encryptedKey, self::HEADEREND) + strlen(self::HEADEREND));
		}

		$plainKey = self::symmetricDecryptFileContent($encryptedKey, $passphrase, $cipher);

		// check if this a valid private key
		$res = openssl_pkey_get_private($plainKey);
		if (is_resource($res)) {
			$sslInfo = openssl_pkey_get_details($res);
			if (!isset($sslInfo['key'])) {
				$plainKey = false;
			}
		} else {
			$plainKey = false;
		}

		return $plainKey;

	}

	/**
	 * Create asymmetrically encrypted keyfile content using a generated key
	 * @param string $plainContent content to be encrypted
	 * @param array $publicKeys array keys must be the userId of corresponding user
	 * @return array keys: keys (array, key = userId), data
	 * @throws \OCA\Files_Encryption\Exception\MultiKeyEncryptException if encryption failed
	 * @note symmetricDecryptFileContent() can decrypt files created using this method
	 */
	public static function multiKeyEncrypt($plainContent, array $publicKeys) {

		// openssl_seal returns false without errors if $plainContent
		// is empty, so trigger our own error
		if (empty($plainContent)) {
			throw new Exception\MultiKeyEncryptException('Cannot multiKeyEncrypt empty plain content', Exception\MultiKeyEncryptException::EMPTY_DATA);
		}

		// Set empty vars to be set by openssl by reference
		$sealed = '';
		$shareKeys = array();
		$mappedShareKeys = array();

		if (openssl_seal($plainContent, $sealed, $shareKeys, $publicKeys)) {

			$i = 0;

			// Ensure each shareKey is labelled with its
			// corresponding userId
			foreach ($publicKeys as $userId => $publicKey) {

				$mappedShareKeys[$userId] = $shareKeys[$i];
				$i++;

			}

			return array(
				'keys' => $mappedShareKeys,
				'data' => $sealed
			);

		} else {
			throw new Exception\MultiKeyEncryptException('multi key encryption failed: ' . openssl_error_string(),
					Exception\MultiKeyEncryptException::OPENSSL_SEAL_FAILED);
		}

	}

	/**
	 * Asymmetrically encrypt a file using multiple public keys
	 * @param string $encryptedContent
	 * @param string $shareKey
	 * @param mixed $privateKey
	 * @throws \OCA\Files_Encryption\Exception\MultiKeyDecryptException if decryption failed
	 * @internal param string $plainContent contains decrypted content
	 * @return string $plainContent decrypted string
	 * @note symmetricDecryptFileContent() can be used to decrypt files created using this method
	 *
	 * This function decrypts a file
	 */
	public static function multiKeyDecrypt($encryptedContent, $shareKey, $privateKey) {

		if (!$encryptedContent) {
			throw new Exception\MultiKeyDecryptException('Cannot mutliKeyDecrypt empty plain content',
					Exception\MultiKeyDecryptException::EMPTY_DATA);
		}

		if (openssl_open($encryptedContent, $plainContent, $shareKey, $privateKey)) {

			return $plainContent;

		} else {
			throw new Exception\MultiKeyDecryptException('multiKeyDecrypt with share-key' . $shareKey . 'failed: ' . openssl_error_string(),
					Exception\MultiKeyDecryptException::OPENSSL_OPEN_FAILED);
		}

	}

	/**
	 * Generates a pseudo random initialisation vector
	 * @return String $iv generated IV
	 */
	private static function generateIv() {

		if ($random = openssl_random_pseudo_bytes(12, $strong)) {

			if (!$strong) {

				// If OpenSSL indicates randomness is insecure, log error
				\OCP\Util::writeLog('Encryption library', 'Insecure symmetric key was generated using openssl_random_pseudo_bytes()', \OCP\Util::WARN);

			}

			// We encode the iv purely for string manipulation
			// purposes - it gets decoded before use
			$iv = base64_encode($random);

			return $iv;

		} else {

			throw new \Exception('Generating IV failed');

		}

	}

	/**
	 * Generate a pseudo random 256-bit ASCII key, used as file key
	 * @return string|false Generated key
	 */
	public static function generateKey() {

		// Generate key
		if ($key = base64_encode(openssl_random_pseudo_bytes(32, $strong))) {

			if (!$strong) {

				// If OpenSSL indicates randomness is insecure, log error
				throw new \Exception('Encryption library, Insecure symmetric key was generated using openssl_random_pseudo_bytes()');

			}

			return $key;

		} else {

			return false;

		}

	}

	/**
	 * read header into array
	 *
	 * @param string $data
	 * @return array
	 */
	public static function parseHeader($data) {

		$result = array();

		if (substr($data, 0, strlen(self::HEADERSTART)) === self::HEADERSTART) {
			$endAt = strpos($data, self::HEADEREND);
			$header = substr($data, 0, $endAt + strlen(self::HEADEREND));

			// +1 to not start with an ':' which would result in empty element at the beginning
			$exploded = explode(':', substr($header, strlen(self::HEADERSTART)+1));

			$element = array_shift($exploded);
			while ($element !== self::HEADEREND) {

				$result[$element] = array_shift($exploded);

				$element = array_shift($exploded);

			}
		}

		return $result;
	}

	/**
	 * check if data block is the header
	 *
	 * @param string $data
	 * @return boolean
	 */
	public static function isHeader($data) {

		if (substr($data, 0, strlen(self::HEADERSTART)) === self::HEADERSTART) {
			return true;
		}

		return false;
	}

	/**
	 * get chiper from header
	 *
	 * @param array $header
	 * @throws \OCA\Files_Encryption\Exception\EncryptionException
	 */
	public static function getCipher($header) {
		$cipher = isset($header['cipher']) ? $header['cipher'] : 'AES-128-CFB';

		if ($cipher !== 'AES-256-CFB' && $cipher !== 'AES-128-CFB') {

			throw new Exception\EncryptionException('file header broken, no supported cipher defined',
					Exception\EncryptionException::UNKNOWN_CIPHER);
		}

		return $cipher;
	}

	/**
	 * generate header for encrypted file
	 */
	public static function generateHeader() {
		$cipher = Helper::getCipher();
		$header = self::HEADERSTART . ':cipher:' . $cipher . ':' . self::HEADEREND;

		return $header;
	}

}
