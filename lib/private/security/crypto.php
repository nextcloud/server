<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */


namespace OC\Security;

use Crypt_AES;
use Crypt_Hash;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use OCP\Security\StringUtils;
use OCP\IConfig;

/**
 * Class Crypto provides a high-level encryption layer using AES-CBC. If no key has been provided
 * it will use the secret defined in config.php as key. Additionally the message will be HMAC'd.
 *
 * Usage:
 * $encryptWithDefaultPassword = \OC::$server->getCrypto()->encrypt('EncryptedText');
 * $encryptWithCustompassword = \OC::$server->getCrypto()->encrypt('EncryptedText', 'password');
 *
 * @package OC\Security
 */
class Crypto implements ICrypto {
	/** @var Crypt_AES $cipher */
	private $cipher;
	/** @var int */
	private $ivLength = 16;
	/** @var IConfig */
	private $config;
	/** @var ISecureRandom */
	private $random;

	function __construct(IConfig $config, ISecureRandom $random) {
		$this->cipher = new Crypt_AES();
		$this->config = $config;
		$this->random = $random;
	}

	/**
	 * @param string $message The message to authenticate
	 * @param string $password Password to use (defaults to `secret` in config.php)
	 * @return string Calculated HMAC
	 */
	public function calculateHMAC($message, $password = '') {
		if($password === '') {
			$password = $this->config->getSystemValue('secret');
		}

		// Append an "a" behind the password and hash it to prevent reusing the same password as for encryption
		$password = hash('sha512', $password . 'a');

		$hash = new Crypt_Hash('sha512');
		$hash->setKey($password);
		return $hash->hash($message);
	}

	/**
	 * Encrypts a value and adds an HMAC (Encrypt-Then-MAC)
	 * @param string $plaintext
	 * @param string $password Password to encrypt, if not specified the secret from config.php will be taken
	 * @return string Authenticated ciphertext
	 */
	public function encrypt($plaintext, $password = '') {
		if($password === '') {
			$password = $this->config->getSystemValue('secret');
		}
		$this->cipher->setPassword($password);

		$iv = $this->random->getLowStrengthGenerator()->generate($this->ivLength);
		$this->cipher->setIV($iv);

		$ciphertext = bin2hex($this->cipher->encrypt($plaintext));
		$hmac = bin2hex($this->calculateHMAC($ciphertext.$iv, $password));

		return $ciphertext.'|'.$iv.'|'.$hmac;
	}

	/**
	 * Decrypts a value and verifies the HMAC (Encrypt-Then-Mac)
	 * @param string $authenticatedCiphertext
	 * @param string $password Password to encrypt, if not specified the secret from config.php will be taken
	 * @return string plaintext
	 * @throws \Exception If the HMAC does not match
	 */
	public function decrypt($authenticatedCiphertext, $password = '') {
		if($password === '') {
			$password = $this->config->getSystemValue('secret');
		}
		$this->cipher->setPassword($password);

		$parts = explode('|', $authenticatedCiphertext);
		if(sizeof($parts) !== 3) {
			throw new \Exception('Authenticated ciphertext could not be decoded.');
		}

		$ciphertext = hex2bin($parts[0]);
		$iv = $parts[1];
		$hmac = hex2bin($parts[2]);

		$this->cipher->setIV($iv);

		if(!StringUtils::equals($this->calculateHMAC($parts[0].$parts[1], $password), $hmac)) {
			throw new \Exception('HMAC does not match.');
		}

		return $this->cipher->decrypt($ciphertext);
	}

}
