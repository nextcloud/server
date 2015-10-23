<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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


use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Encryption\Exceptions\EncryptionFailedException;
use OCA\Encryption\Exceptions\MultiKeyDecryptException;
use OCA\Encryption\Exceptions\MultiKeyEncryptException;
use OCA\Encryption\Vendor\PBKDF2Fallback;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;

class Crypt {

	const DEFAULT_CIPHER = 'AES-256-CFB';
	// default cipher from old ownCloud versions
	const LEGACY_CIPHER = 'AES-128-CFB';

	// default key format, old ownCloud version encrypted the private key directly
	// with the user password
	const LEGACY_KEY_FORMAT = 'password';

	const HEADER_START = 'HBEGIN';
	const HEADER_END = 'HEND';
	/**
	 * @var ILogger
	 */
	private $logger;
	/**
	 * @var string
	 */
	private $user;
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var array
	 */
	private $supportedKeyFormats;

	/**
	 * @param ILogger $logger
	 * @param IUserSession $userSession
	 * @param IConfig $config
	 */
	public function __construct(ILogger $logger, IUserSession $userSession, IConfig $config) {
		$this->logger = $logger;
		$this->user = $userSession && $userSession->isLoggedIn() ? $userSession->getUser()->getUID() : '"no user given"';
		$this->config = $config;
		$this->supportedKeyFormats = ['hash', 'password'];
	}

	/**
	 * create new private/public key-pair for user
	 *
	 * @return array|bool
	 */
	public function createKeyPair() {

		$log = $this->logger;
		$res = $this->getOpenSSLPKey();

		if (!$res) {
			$log->error("Encryption Library couldn't generate users key-pair for {$this->user}",
				['app' => 'encryption']);

			if (openssl_error_string()) {
				$log->error('Encryption library openssl_pkey_new() fails: ' . openssl_error_string(),
					['app' => 'encryption']);
			}
		} elseif (openssl_pkey_export($res,
			$privateKey,
			null,
			$this->getOpenSSLConfig())) {
			$keyDetails = openssl_pkey_get_details($res);
			$publicKey = $keyDetails['key'];

			return [
				'publicKey' => $publicKey,
				'privateKey' => $privateKey
			];
		}
		$log->error('Encryption library couldn\'t export users private key, please check your servers OpenSSL configuration.' . $this->user,
			['app' => 'encryption']);
		if (openssl_error_string()) {
			$log->error('Encryption Library:' . openssl_error_string(),
				['app' => 'encryption']);
		}

		return false;
	}

	/**
	 * Generates a new private key
	 *
	 * @return resource
	 */
	public function getOpenSSLPKey() {
		$config = $this->getOpenSSLConfig();
		return openssl_pkey_new($config);
	}

	/**
	 * get openSSL Config
	 *
	 * @return array
	 */
	private function getOpenSSLConfig() {
		$config = ['private_key_bits' => 4096];
		$config = array_merge(
			$config,
			$this->config->getSystemValue('openssl', [])
		);
		return $config;
	}

	/**
	 * @param string $plainContent
	 * @param string $passPhrase
	 * @return bool|string
	 * @throws GenericEncryptionException
	 */
	public function symmetricEncryptFileContent($plainContent, $passPhrase) {

		if (!$plainContent) {
			$this->logger->error('Encryption Library, symmetrical encryption failed no content given',
				['app' => 'encryption']);
			return false;
		}

		$iv = $this->generateIv();

		$encryptedContent = $this->encrypt($plainContent,
			$iv,
			$passPhrase,
			$this->getCipher());
		// combine content to encrypt the IV identifier and actual IV
		$catFile = $this->concatIV($encryptedContent, $iv);
		$padded = $this->addPadding($catFile);

		return $padded;
	}

	/**
	 * generate header for encrypted file
	 *
	 * @param string $keyFormat (can be 'hash' or 'password')
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function generateHeader($keyFormat = 'hash') {

		if (in_array($keyFormat, $this->supportedKeyFormats, true) === false) {
			throw new \InvalidArgumentException('key format "' . $keyFormat . '" is not supported');
		}

		$cipher = $this->getCipher();

		$header = self::HEADER_START
			. ':cipher:' . $cipher
			. ':keyFormat:' . $keyFormat
			. ':' . self::HEADER_END;

		return $header;
	}

	/**
	 * @param string $plainContent
	 * @param string $iv
	 * @param string $passPhrase
	 * @param string $cipher
	 * @return string
	 * @throws EncryptionFailedException
	 */
	private function encrypt($plainContent, $iv, $passPhrase = '', $cipher = self::DEFAULT_CIPHER) {
		$encryptedContent = openssl_encrypt($plainContent,
			$cipher,
			$passPhrase,
			false,
			$iv);

		if (!$encryptedContent) {
			$error = 'Encryption (symmetric) of content failed';
			$this->logger->error($error . openssl_error_string(),
				['app' => 'encryption']);
			throw new EncryptionFailedException($error);
		}

		return $encryptedContent;
	}

	/**
	 * return Cipher either from config.php or the default cipher defined in
	 * this class
	 *
	 * @return string
	 */
	public function getCipher() {
		$cipher = $this->config->getSystemValue('cipher', self::DEFAULT_CIPHER);
		if ($cipher !== 'AES-256-CFB' && $cipher !== 'AES-128-CFB') {
			$this->logger->warning('Wrong cipher defined in config.php only AES-128-CFB and AES-256-CFB are supported. Fall back' . self::DEFAULT_CIPHER,
				['app' => 'encryption']);
			$cipher = self::DEFAULT_CIPHER;
		}

		return $cipher;
	}

	/**
	 * get key size depending on the cipher
	 *
	 * @param string $cipher supported ('AES-256-CFB' and 'AES-128-CFB')
	 * @return int
	 * @throws \InvalidArgumentException
	 */
	protected function getKeySize($cipher) {
		if ($cipher === 'AES-256-CFB') {
			return 32;
		} else if ($cipher === 'AES-128-CFB') {
			return 16;
		}

		throw new \InvalidArgumentException(
			'Wrong cipher defined only AES-128-CFB and AES-256-CFB are supported.'
		);
	}

	/**
	 * get legacy cipher
	 *
	 * @return string
	 */
	public function getLegacyCipher() {
		return self::LEGACY_CIPHER;
	}

	/**
	 * @param string $encryptedContent
	 * @param string $iv
	 * @return string
	 */
	private function concatIV($encryptedContent, $iv) {
		return $encryptedContent . '00iv00' . $iv;
	}

	/**
	 * @param $data
	 * @return string
	 */
	private function addPadding($data) {
		return $data . 'xx';
	}

	/**
	 * generate password hash used to encrypt the users private key
	 *
	 * @param string $password
	 * @param string $cipher
	 * @param string $uid only used for user keys
	 * @return string
	 */
	protected function generatePasswordHash($password, $cipher, $uid = '') {
		$instanceId = $this->config->getSystemValue('instanceid');
		$instanceSecret = $this->config->getSystemValue('secret');
		$salt = hash('sha256', $uid . $instanceId . $instanceSecret, true);
		$keySize = $this->getKeySize($cipher);

		if (function_exists('hash_pbkdf2')) {
			$hash = hash_pbkdf2(
				'sha256',
				$password,
				$salt,
				100000,
				$keySize,
				true
			);
		} else {
			// fallback to 3rdparty lib for PHP <= 5.4.
			// FIXME: Can be removed as soon as support for PHP 5.4 was dropped
			$fallback = new PBKDF2Fallback();
			$hash = $fallback->pbkdf2(
				'sha256',
				$password,
				$salt,
				100000,
				$keySize,
				true
			);
		}

		return $hash;
	}

	/**
	 * encrypt private key
	 *
	 * @param string $privateKey
	 * @param string $password
	 * @param string $uid for regular users, empty for system keys
	 * @return bool|string
	 */
	public function encryptPrivateKey($privateKey, $password, $uid = '') {
		$cipher = $this->getCipher();
		$hash = $this->generatePasswordHash($password, $cipher, $uid);
		$encryptedKey = $this->symmetricEncryptFileContent(
			$privateKey,
			$hash
		);

		return $encryptedKey;
	}

	/**
	 * @param string $privateKey
	 * @param string $password
	 * @param string $uid for regular users, empty for system keys
	 * @return bool|string
	 */
	public function decryptPrivateKey($privateKey, $password = '', $uid = '') {

		$header = $this->parseHeader($privateKey);

		if (isset($header['cipher'])) {
			$cipher = $header['cipher'];
		} else {
			$cipher = self::LEGACY_CIPHER;
		}

		if (isset($header['keyFormat'])) {
			$keyFormat = $header['keyFormat'];
		} else {
			$keyFormat = self::LEGACY_KEY_FORMAT;
		}

		if ($keyFormat === 'hash') {
			$password = $this->generatePasswordHash($password, $cipher, $uid);
		}

		// If we found a header we need to remove it from the key we want to decrypt
		if (!empty($header)) {
			$privateKey = substr($privateKey,
				strpos($privateKey,
					self::HEADER_END) + strlen(self::HEADER_END));
		}

		$plainKey = $this->symmetricDecryptFileContent($privateKey,
			$password,
			$cipher);

		if ($this->isValidPrivateKey($plainKey) === false) {
			return false;
		}

		return $plainKey;
	}

	/**
	 * check if it is a valid private key
	 *
	 * @param $plainKey
	 * @return bool
	 */
	protected function isValidPrivateKey($plainKey) {
		$res = openssl_get_privatekey($plainKey);
		if (is_resource($res)) {
			$sslInfo = openssl_pkey_get_details($res);
			if (isset($sslInfo['key'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $keyFileContents
	 * @param string $passPhrase
	 * @param string $cipher
	 * @return string
	 * @throws DecryptionFailedException
	 */
	public function symmetricDecryptFileContent($keyFileContents, $passPhrase, $cipher = self::DEFAULT_CIPHER) {
		// Remove Padding
		$noPadding = $this->removePadding($keyFileContents);

		$catFile = $this->splitIv($noPadding);

		return $this->decrypt($catFile['encrypted'],
			$catFile['iv'],
			$passPhrase,
			$cipher);
	}

	/**
	 * remove padding
	 *
	 * @param $padded
	 * @return bool|string
	 */
	private function removePadding($padded) {
		if (substr($padded, -2) === 'xx') {
			return substr($padded, 0, -2);
		}
		return false;
	}

	/**
	 * split iv from encrypted content
	 *
	 * @param $catFile
	 * @return array
	 */
	private function splitIv($catFile) {
		// Fetch encryption metadata from end of file
		$meta = substr($catFile, -22);

		// Fetch IV from end of file
		$iv = substr($meta, -16);

		// Remove IV and IV Identifier text to expose encrypted content

		$encrypted = substr($catFile, 0, -22);

		return [
			'encrypted' => $encrypted,
			'iv' => $iv
		];
	}

	/**
	 * @param $encryptedContent
	 * @param $iv
	 * @param string $passPhrase
	 * @param string $cipher
	 * @return string
	 * @throws DecryptionFailedException
	 */
	private function decrypt($encryptedContent, $iv, $passPhrase = '', $cipher = self::DEFAULT_CIPHER) {
		$plainContent = openssl_decrypt($encryptedContent,
			$cipher,
			$passPhrase,
			false,
			$iv);

		if ($plainContent) {
			return $plainContent;
		} else {
			throw new DecryptionFailedException('Encryption library: Decryption (symmetric) of content failed: ' . openssl_error_string());
		}
	}

	/**
	 * @param $data
	 * @return array
	 */
	protected function parseHeader($data) {
		$result = [];

		if (substr($data, 0, strlen(self::HEADER_START)) === self::HEADER_START) {
			$endAt = strpos($data, self::HEADER_END);
			$header = substr($data, 0, $endAt + strlen(self::HEADER_END));

			// +1 not to start with an ':' which would result in empty element at the beginning
			$exploded = explode(':',
				substr($header, strlen(self::HEADER_START) + 1));

			$element = array_shift($exploded);

			while ($element != self::HEADER_END) {
				$result[$element] = array_shift($exploded);
				$element = array_shift($exploded);
			}
		}

		return $result;
	}

	/**
	 * generate initialization vector
	 *
	 * @return string
	 * @throws GenericEncryptionException
	 */
	private function generateIv() {
		$random = openssl_random_pseudo_bytes(12, $strong);
		if ($random) {
			if (!$strong) {
				// If OpenSSL indicates randomness is insecure log error
				$this->logger->error('Encryption Library: Insecure symmetric key was generated using openssl_random_psudo_bytes()',
					['app' => 'encryption']);
			}

			/*
			 * We encode the iv purely for string manipulation
			 * purposes -it gets decoded before use
			 */
			return base64_encode($random);
		}
		// If we ever get here we've failed anyway no need for an else
		throw new GenericEncryptionException('Generating IV Failed');
	}

	/**
	 * Generate a cryptographically secure pseudo-random base64 encoded 256-bit
	 * ASCII key, used as file key
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function generateFileKey() {
		// Generate key
		$key = base64_encode(openssl_random_pseudo_bytes(32, $strong));
		if (!$key || !$strong) {
				// If OpenSSL indicates randomness is insecure, log error
				throw new \Exception('Encryption library, Insecure symmetric key was generated using openssl_random_pseudo_bytes()');
		}

		return $key;
	}

	/**
	 * @param $encKeyFile
	 * @param $shareKey
	 * @param $privateKey
	 * @return mixed
	 * @throws MultiKeyDecryptException
	 */
	public function multiKeyDecrypt($encKeyFile, $shareKey, $privateKey) {
		if (!$encKeyFile) {
			throw new MultiKeyDecryptException('Cannot multikey decrypt empty plain content');
		}

		if (openssl_open($encKeyFile, $plainContent, $shareKey, $privateKey)) {
			return $plainContent;
		} else {
			throw new MultiKeyDecryptException('multikeydecrypt with share key failed:' . openssl_error_string());
		}
	}

	/**
	 * @param string $plainContent
	 * @param array $keyFiles
	 * @return array
	 * @throws MultiKeyEncryptException
	 */
	public function multiKeyEncrypt($plainContent, array $keyFiles) {
		// openssl_seal returns false without errors if plaincontent is empty
		// so trigger our own error
		if (empty($plainContent)) {
			throw new MultiKeyEncryptException('Cannot multikeyencrypt empty plain content');
		}

		// Set empty vars to be set by openssl by reference
		$sealed = '';
		$shareKeys = [];
		$mappedShareKeys = [];

		if (openssl_seal($plainContent, $sealed, $shareKeys, $keyFiles)) {
			$i = 0;

			// Ensure each shareKey is labelled with its corresponding key id
			foreach ($keyFiles as $userId => $publicKey) {
				$mappedShareKeys[$userId] = $shareKeys[$i];
				$i++;
			}

			return [
				'keys' => $mappedShareKeys,
				'data' => $sealed
			];
		} else {
			throw new MultiKeyEncryptException('multikeyencryption failed ' . openssl_error_string());
		}
	}
}

