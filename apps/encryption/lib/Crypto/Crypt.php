<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Kevin Niehage <kevin@niehage.name>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weiberg <sweiberg@suse.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Encryption\Crypto;

use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Encryption\Exceptions\EncryptionFailedException;
use OC\ServerNotAvailableException;
use OCA\Encryption\Exceptions\MultiKeyDecryptException;
use OCA\Encryption\Exceptions\MultiKeyEncryptException;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserSession;
use phpseclib\Crypt\RC4;
use Psr\Log\LoggerInterface;

/**
 * Class Crypt provides the encryption implementation of the default Nextcloud
 * encryption module. As default AES-256-CTR is used, it does however offer support
 * for the following modes:
 *
 * - AES-256-CTR
 * - AES-128-CTR
 * - AES-256-CFB
 * - AES-128-CFB
 *
 * For integrity protection Encrypt-Then-MAC using HMAC-SHA256 is used.
 *
 * @package OCA\Encryption\Crypto
 */
class Crypt {
	public const SUPPORTED_CIPHERS_AND_KEY_SIZE = [
		'AES-256-CTR' => 32,
		'AES-128-CTR' => 16,
		'AES-256-CFB' => 32,
		'AES-128-CFB' => 16,
	];
	// one out of SUPPORTED_CIPHERS_AND_KEY_SIZE
	public const DEFAULT_CIPHER = 'AES-256-CTR';
	// default cipher from old Nextcloud versions
	public const LEGACY_CIPHER = 'AES-128-CFB';

	public const SUPPORTED_KEY_FORMATS = ['hash2', 'hash', 'password'];
	// one out of SUPPORTED_KEY_FORMATS
	public const DEFAULT_KEY_FORMAT = 'hash2';
	// default key format, old Nextcloud version encrypted the private key directly
	// with the user password
	public const LEGACY_KEY_FORMAT = 'password';

	public const HEADER_START = 'HBEGIN';
	public const HEADER_END = 'HEND';

	// default encoding format, old Nextcloud versions used base64
	public const BINARY_ENCODING_FORMAT = 'binary';

	private string $user;

	private ?string $currentCipher = null;

	private bool $supportLegacy;

	/**
	 * Use the legacy base64 encoding instead of the more space-efficient binary encoding.
	 */
	private bool $useLegacyBase64Encoding;

	public function __construct(
		private LoggerInterface $logger,
		IUserSession $userSession,
		private IConfig $config,
		private IL10N $l,
	) {
		$this->user = $userSession->isLoggedIn() ? $userSession->getUser()->getUID() : '"no user given"';
		$this->supportLegacy = $this->config->getSystemValueBool('encryption.legacy_format_support', false);
		$this->useLegacyBase64Encoding = $this->config->getSystemValueBool('encryption.use_legacy_base64_encoding', false);
	}

	/**
	 * create new private/public key-pair for user
	 *
	 * @return array|bool
	 */
	public function createKeyPair() {
		$res = $this->getOpenSSLPKey();

		if (!$res) {
			$this->logger->error("Encryption Library couldn't generate users key-pair for {$this->user}",
				['app' => 'encryption']);

			if (openssl_error_string()) {
				$this->logger->error('Encryption library openssl_pkey_new() fails: ' . openssl_error_string(),
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
		$this->logger->error('Encryption library couldn\'t export users private key, please check your servers OpenSSL configuration.' . $this->user,
			['app' => 'encryption']);
		if (openssl_error_string()) {
			$this->logger->error('Encryption Library:' . openssl_error_string(),
				['app' => 'encryption']);
		}

		return false;
	}

	/**
	 * Generates a new private key
	 *
	 * @return \OpenSSLAsymmetricKey|false
	 */
	public function getOpenSSLPKey() {
		$config = $this->getOpenSSLConfig();
		return openssl_pkey_new($config);
	}

	private function getOpenSSLConfig(): array {
		$config = ['private_key_bits' => 4096];
		$config = array_merge(
			$config,
			$this->config->getSystemValue('openssl', [])
		);
		return $config;
	}

	/**
	 * @throws EncryptionFailedException
	 */
	public function symmetricEncryptFileContent(string $plainContent, string $passPhrase, int $version, string $position): string|false {
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

		// Create a signature based on the key as well as the current version
		$sig = $this->createSignature($encryptedContent, $passPhrase.'_'.$version.'_'.$position);

		// combine content to encrypt the IV identifier and actual IV
		$catFile = $this->concatIV($encryptedContent, $iv);
		$catFile = $this->concatSig($catFile, $sig);
		return $this->addPadding($catFile);
	}

	/**
	 * generate header for encrypted file
	 *
	 * @param string $keyFormat see SUPPORTED_KEY_FORMATS
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	public function generateHeader($keyFormat = self::DEFAULT_KEY_FORMAT) {
		if (in_array($keyFormat, self::SUPPORTED_KEY_FORMATS, true) === false) {
			throw new \InvalidArgumentException('key format "' . $keyFormat . '" is not supported');
		}

		$header = self::HEADER_START
			. ':cipher:' . $this->getCipher()
			. ':keyFormat:' . $keyFormat;

		if ($this->useLegacyBase64Encoding !== true) {
			$header .= ':encoding:' . self::BINARY_ENCODING_FORMAT;
		}

		$header .= ':' . self::HEADER_END;

		return $header;
	}

	/**
	 * @throws EncryptionFailedException
	 */
	private function encrypt(string $plainContent, string $iv, string $passPhrase = '', string $cipher = self::DEFAULT_CIPHER): string {
		$options = $this->useLegacyBase64Encoding ? 0 : OPENSSL_RAW_DATA;
		$encryptedContent = openssl_encrypt($plainContent,
			$cipher,
			$passPhrase,
			$options,
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
	 * return cipher either from config.php or the default cipher defined in
	 * this class
	 */
	private function getCachedCipher(): string {
		if (isset($this->currentCipher)) {
			return $this->currentCipher;
		}

		// Get cipher either from config.php or the default cipher defined in this class
		$cipher = $this->config->getSystemValueString('cipher', self::DEFAULT_CIPHER);
		if (!isset(self::SUPPORTED_CIPHERS_AND_KEY_SIZE[$cipher])) {
			$this->logger->warning(
				sprintf(
					'Unsupported cipher (%s) defined in config.php supported. Falling back to %s',
					$cipher,
					self::DEFAULT_CIPHER
				),
				['app' => 'encryption']
			);
			$cipher = self::DEFAULT_CIPHER;
		}

		// Remember current cipher to avoid frequent lookups
		$this->currentCipher = $cipher;
		return $this->currentCipher;
	}

	/**
	 * return current encryption cipher
	 *
	 * @return string
	 */
	public function getCipher() {
		return $this->getCachedCipher();
	}

	/**
	 * get key size depending on the cipher
	 *
	 * @param string $cipher
	 * @return int
	 * @throws \InvalidArgumentException
	 */
	protected function getKeySize($cipher) {
		if (isset(self::SUPPORTED_CIPHERS_AND_KEY_SIZE[$cipher])) {
			return self::SUPPORTED_CIPHERS_AND_KEY_SIZE[$cipher];
		}

		throw new \InvalidArgumentException(
			sprintf(
				'Unsupported cipher (%s) defined.',
				$cipher
			)
		);
	}

	/**
	 * get legacy cipher
	 *
	 * @return string
	 */
	public function getLegacyCipher() {
		if (!$this->supportLegacy) {
			throw new ServerNotAvailableException('Legacy cipher is no longer supported!');
		}

		return self::LEGACY_CIPHER;
	}

	private function concatIV(string $encryptedContent, string $iv): string {
		return $encryptedContent . '00iv00' . $iv;
	}

	private function concatSig(string $encryptedContent, string $signature): string {
		return $encryptedContent . '00sig00' . $signature;
	}

	/**
	 * Note: This is _NOT_ a padding used for encryption purposes. It is solely
	 * used to achieve the PHP stream size. It has _NOTHING_ to do with the
	 * encrypted content and is not used in any crypto primitive.
	 */
	private function addPadding(string $data): string {
		return $data . 'xxx';
	}

	/**
	 * generate password hash used to encrypt the users private key
	 *
	 * @param string $uid only used for user keys
	 */
	protected function generatePasswordHash(string $password, string $cipher, string $uid = '', int $iterations = 600000): string {
		$instanceId = $this->config->getSystemValue('instanceid');
		$instanceSecret = $this->config->getSystemValue('secret');
		$salt = hash('sha256', $uid . $instanceId . $instanceSecret, true);
		$keySize = $this->getKeySize($cipher);

		return hash_pbkdf2(
			'sha256',
			$password,
			$salt,
			$iterations,
			$keySize,
			true
		);
	}

	/**
	 * encrypt private key
	 *
	 * @param string $privateKey
	 * @param string $password
	 * @param string $uid for regular users, empty for system keys
	 * @return false|string
	 */
	public function encryptPrivateKey($privateKey, $password, $uid = '') {
		$cipher = $this->getCipher();
		$hash = $this->generatePasswordHash($password, $cipher, $uid);
		$encryptedKey = $this->symmetricEncryptFileContent(
			$privateKey,
			$hash,
			0,
			'0'
		);

		return $encryptedKey;
	}

	/**
	 * @param string $privateKey
	 * @param string $password
	 * @param string $uid for regular users, empty for system keys
	 * @return false|string
	 */
	public function decryptPrivateKey($privateKey, $password = '', $uid = '') {
		$header = $this->parseHeader($privateKey);

		if (isset($header['cipher'])) {
			$cipher = $header['cipher'];
		} else {
			$cipher = $this->getLegacyCipher();
		}

		if (isset($header['keyFormat'])) {
			$keyFormat = $header['keyFormat'];
		} else {
			$keyFormat = self::LEGACY_KEY_FORMAT;
		}

		if ($keyFormat === 'hash') {
			$password = $this->generatePasswordHash($password, $cipher, $uid, 100000);
		} elseif ($keyFormat === 'hash2') {
			$password = $this->generatePasswordHash($password, $cipher, $uid, 600000);
		}

		$binaryEncoding = isset($header['encoding']) && $header['encoding'] === self::BINARY_ENCODING_FORMAT;

		// If we found a header we need to remove it from the key we want to decrypt
		if (!empty($header)) {
			$privateKey = substr($privateKey,
				strpos($privateKey,
					self::HEADER_END) + strlen(self::HEADER_END));
		}

		$plainKey = $this->symmetricDecryptFileContent(
			$privateKey,
			$password,
			$cipher,
			0,
			0,
			$binaryEncoding
		);

		if ($this->isValidPrivateKey($plainKey) === false) {
			return false;
		}

		return $plainKey;
	}

	/**
	 * check if it is a valid private key
	 *
	 * @param string $plainKey
	 * @return bool
	 */
	protected function isValidPrivateKey($plainKey) {
		$res = openssl_get_privatekey($plainKey);
		if (is_object($res) && get_class($res) === 'OpenSSLAsymmetricKey') {
			$sslInfo = openssl_pkey_get_details($res);
			if (isset($sslInfo['key'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $keyFileContents
	 * @param string $passPhrase
	 * @param string $cipher
	 * @param int $version
	 * @param int|string $position
	 * @param boolean $binaryEncoding
	 * @return string
	 * @throws DecryptionFailedException
	 */
	public function symmetricDecryptFileContent($keyFileContents, $passPhrase, $cipher = self::DEFAULT_CIPHER, $version = 0, $position = 0, bool $binaryEncoding = false) {
		if ($keyFileContents == '') {
			return '';
		}

		$catFile = $this->splitMetaData($keyFileContents, $cipher);

		if ($catFile['signature'] !== false) {
			try {
				// First try the new format
				$this->checkSignature($catFile['encrypted'], $passPhrase . '_' . $version . '_' . $position, $catFile['signature']);
			} catch (GenericEncryptionException $e) {
				// For compatibility with old files check the version without _
				$this->checkSignature($catFile['encrypted'], $passPhrase . $version . $position, $catFile['signature']);
			}
		}

		return $this->decrypt($catFile['encrypted'],
			$catFile['iv'],
			$passPhrase,
			$cipher,
			$binaryEncoding);
	}

	/**
	 * check for valid signature
	 *
	 * @throws GenericEncryptionException
	 */
	private function checkSignature(string $data, string $passPhrase, string $expectedSignature): void {
		$enforceSignature = !$this->config->getSystemValueBool('encryption_skip_signature_check', false);

		$signature = $this->createSignature($data, $passPhrase);
		$isCorrectHash = hash_equals($expectedSignature, $signature);

		if (!$isCorrectHash) {
			if ($enforceSignature) {
				throw new GenericEncryptionException('Bad Signature', $this->l->t('Bad Signature'));
			} else {
				$this->logger->info("Signature check skipped", ['app' => 'encryption']);
			}
		}
	}

	/**
	 * create signature
	 */
	private function createSignature(string $data, string $passPhrase): string {
		$passPhrase = hash('sha512', $passPhrase . 'a', true);
		return hash_hmac('sha256', $data, $passPhrase);
	}


	/**
	 * @param bool $hasSignature did the block contain a signature, in this case we use a different padding
	 */
	private function removePadding(string $padded, bool $hasSignature = false): string|false {
		if ($hasSignature === false && substr($padded, -2) === 'xx') {
			return substr($padded, 0, -2);
		} elseif ($hasSignature === true && substr($padded, -3) === 'xxx') {
			return substr($padded, 0, -3);
		}
		return false;
	}

	/**
	 * split meta data from encrypted file
	 * Note: for now, we assume that the meta data always start with the iv
	 *       followed by the signature, if available
	 */
	private function splitMetaData(string $catFile, string $cipher): array {
		if ($this->hasSignature($catFile, $cipher)) {
			$catFile = $this->removePadding($catFile, true);
			$meta = substr($catFile, -93);
			$iv = substr($meta, strlen('00iv00'), 16);
			$sig = substr($meta, 22 + strlen('00sig00'));
			$encrypted = substr($catFile, 0, -93);
		} else {
			$catFile = $this->removePadding($catFile);
			$meta = substr($catFile, -22);
			$iv = substr($meta, -16);
			$sig = false;
			$encrypted = substr($catFile, 0, -22);
		}

		return [
			'encrypted' => $encrypted,
			'iv' => $iv,
			'signature' => $sig
		];
	}

	/**
	 * check if encrypted block is signed
	 *
	 * @throws GenericEncryptionException
	 */
	private function hasSignature(string $catFile, string $cipher): bool {
		$skipSignatureCheck = $this->config->getSystemValueBool('encryption_skip_signature_check', false);

		$meta = substr($catFile, -93);
		$signaturePosition = strpos($meta, '00sig00');

		// If we no longer support the legacy format then everything needs a signature
		if (!$skipSignatureCheck && !$this->supportLegacy && $signaturePosition === false) {
			throw new GenericEncryptionException('Missing Signature', $this->l->t('Missing Signature'));
		}

		// Enforce signature for the new 'CTR' ciphers
		if (!$skipSignatureCheck && $signaturePosition === false && stripos($cipher, 'ctr') !== false) {
			throw new GenericEncryptionException('Missing Signature', $this->l->t('Missing Signature'));
		}

		return ($signaturePosition !== false);
	}


	/**
	 * @throws DecryptionFailedException
	 */
	private function decrypt(string $encryptedContent, string $iv, string $passPhrase = '', string $cipher = self::DEFAULT_CIPHER, bool $binaryEncoding = false): string {
		$options = $binaryEncoding === true ? OPENSSL_RAW_DATA : 0;
		$plainContent = openssl_decrypt($encryptedContent,
			$cipher,
			$passPhrase,
			$options,
			$iv);

		if ($plainContent) {
			return $plainContent;
		} else {
			throw new DecryptionFailedException('Encryption library: Decryption (symmetric) of content failed: ' . openssl_error_string());
		}
	}

	/**
	 * @param string $data
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

			while ($element !== self::HEADER_END) {
				$result[$element] = array_shift($exploded);
				$element = array_shift($exploded);
			}
		}

		return $result;
	}

	/**
	 * generate initialization vector
	 *
	 * @throws GenericEncryptionException
	 */
	private function generateIv(): string {
		return random_bytes(16);
	}

	/**
	 * Generate a cryptographically secure pseudo-random 256-bit ASCII key, used
	 * as file key
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function generateFileKey() {
		return random_bytes(32);
	}

	/**
	 * @param \OpenSSLAsymmetricKey|\OpenSSLCertificate|array|string $privateKey
	 * @throws MultiKeyDecryptException
	 */
	public function multiKeyDecrypt(string $shareKey, $privateKey): string {
		$plainContent = '';

		// decrypt the intermediate key with RSA
		if (openssl_private_decrypt($shareKey, $intermediate, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
			return $intermediate;
		} else {
			throw new MultiKeyDecryptException('multikeydecrypt with share key failed:' . openssl_error_string());
		}
	}

	/**
	 * @param \OpenSSLAsymmetricKey|\OpenSSLCertificate|array|string $privateKey
	 * @throws MultiKeyDecryptException
	 */
	public function multiKeyDecryptLegacy(string $encKeyFile, string $shareKey, $privateKey): string {
		if (!$encKeyFile) {
			throw new MultiKeyDecryptException('Cannot multikey decrypt empty plain content');
		}

		$plainContent = '';
		if ($this->opensslOpen($encKeyFile, $plainContent, $shareKey, $privateKey, 'RC4')) {
			return $plainContent;
		} else {
			throw new MultiKeyDecryptException('multikeydecrypt with share key failed:' . openssl_error_string());
		}
	}

	/**
	 * @param array<string,\OpenSSLAsymmetricKey|\OpenSSLCertificate|array|string> $keyFiles
	 * @throws MultiKeyEncryptException
	 */
	public function multiKeyEncrypt(string $plainContent, array $keyFiles): array {
		if (empty($plainContent)) {
			throw new MultiKeyEncryptException('Cannot multikeyencrypt empty plain content');
		}

		// Set empty vars to be set by openssl by reference
		$shareKeys = [];
		$mappedShareKeys = [];

		// make sure that there is at least one public key to use
		if (count($keyFiles) >= 1) {
			// prepare the encrypted keys
			$shareKeys = [];

			// iterate over the public keys and encrypt the intermediate
			// for each of them with RSA
			foreach ($keyFiles as $tmp_key) {
				if (openssl_public_encrypt($plainContent, $tmp_output, $tmp_key, OPENSSL_PKCS1_OAEP_PADDING)) {
					$shareKeys[] = $tmp_output;
				}
			}

			// set the result if everything worked fine
			if (count($keyFiles) === count($shareKeys)) {
				$i = 0;

				// Ensure each shareKey is labelled with its corresponding key id
				foreach ($keyFiles as $userId => $publicKey) {
					$mappedShareKeys[$userId] = $shareKeys[$i];
					$i++;
				}

				return $mappedShareKeys;
			}
		}
		throw new MultiKeyEncryptException('multikeyencryption failed ' . openssl_error_string());
	}

	/**
	 * @param string $plainContent
	 * @param array $keyFiles
	 * @return array
	 * @throws MultiKeyEncryptException
	 * @deprecated 27.0.0 use multiKeyEncrypt
	 */
	public function multiKeyEncryptLegacy($plainContent, array $keyFiles) {
		// openssl_seal returns false without errors if plaincontent is empty
		// so trigger our own error
		if (empty($plainContent)) {
			throw new MultiKeyEncryptException('Cannot multikeyencrypt empty plain content');
		}

		// Set empty vars to be set by openssl by reference
		$sealed = '';
		$shareKeys = [];
		$mappedShareKeys = [];

		if ($this->opensslSeal($plainContent, $sealed, $shareKeys, $keyFiles, 'RC4')) {
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

	/**
	 * returns the value of $useLegacyBase64Encoding
	 *
	 * @return bool
	 */
	public function useLegacyBase64Encoding(): bool {
		return $this->useLegacyBase64Encoding;
	}

	/**
	 * Uses phpseclib RC4 implementation
	 */
	private function rc4Decrypt(string $data, string $secret): string {
		$rc4 = new RC4();
		/** @psalm-suppress InternalMethod */
		$rc4->setKey($secret);

		return $rc4->decrypt($data);
	}

	/**
	 * Uses phpseclib RC4 implementation
	 */
	private function rc4Encrypt(string $data, string $secret): string {
		$rc4 = new RC4();
		/** @psalm-suppress InternalMethod */
		$rc4->setKey($secret);

		return $rc4->encrypt($data);
	}

	/**
	 * Custom implementation of openssl_open()
	 *
	 * @param \OpenSSLAsymmetricKey|\OpenSSLCertificate|array|string $private_key
	 * @throws DecryptionFailedException
	 */
	private function opensslOpen(string $data, string &$output, string $encrypted_key, $private_key, string $cipher_algo): bool {
		$result = false;

		// check if RC4 is used
		if (strcasecmp($cipher_algo, "rc4") === 0) {
			// decrypt the intermediate key with RSA
			if (openssl_private_decrypt($encrypted_key, $intermediate, $private_key, OPENSSL_PKCS1_PADDING)) {
				// decrypt the file key with the intermediate key
				// using our own RC4 implementation
				$output = $this->rc4Decrypt($data, $intermediate);
				$result = (strlen($output) === strlen($data));
			}
		} else {
			throw new DecryptionFailedException('Unsupported cipher '.$cipher_algo);
		}

		return $result;
	}

	/**
	 * Custom implementation of openssl_seal()
	 *
	 * @deprecated 27.0.0 use multiKeyEncrypt
	 * @throws EncryptionFailedException
	 */
	private function opensslSeal(string $data, string &$sealed_data, array &$encrypted_keys, array $public_key, string $cipher_algo): int|false {
		$result = false;

		// check if RC4 is used
		if (strcasecmp($cipher_algo, "rc4") === 0) {
			// make sure that there is at least one public key to use
			if (count($public_key) >= 1) {
				// generate the intermediate key
				$intermediate = openssl_random_pseudo_bytes(16, $strong_result);

				// check if we got strong random data
				if ($strong_result) {
					// encrypt the file key with the intermediate key
					// using our own RC4 implementation
					$sealed_data = $this->rc4Encrypt($data, $intermediate);
					if (strlen($sealed_data) === strlen($data)) {
						// prepare the encrypted keys
						$encrypted_keys = [];

						// iterate over the public keys and encrypt the intermediate
						// for each of them with RSA
						foreach ($public_key as $tmp_key) {
							if (openssl_public_encrypt($intermediate, $tmp_output, $tmp_key, OPENSSL_PKCS1_PADDING)) {
								$encrypted_keys[] = $tmp_output;
							}
						}

						// set the result if everything worked fine
						if (count($public_key) === count($encrypted_keys)) {
							$result = strlen($sealed_data);
						}
					}
				}
			}
		} else {
			throw new EncryptionFailedException('Unsupported cipher '.$cipher_algo);
		}

		return $result;
	}
}
