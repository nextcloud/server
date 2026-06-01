<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Service;

use OCP\IConfig;
use OCP\Security\ISecureRandom;
use phpseclib\Crypt\AES;

class EncryptionService {
	public function __construct(
		private IConfig $config,
		private ISecureRandom $secureRandom,
	) {
	}

	/**
	 * Encrypt passwords in the given config options
	 *
	 * @param array $options mount options
	 * @return array updated options
	 */
	public function encryptPasswords(array $options): array {
		if (isset($options['password'])) {
			$options['password_encrypted'] = $this->encryptPassword($options['password']);
			// do not unset the password, we want to keep the keys order
			// on load... because that's how the UI currently works
			$options['password'] = '';
		}
		return $options;
	}

	/**
	 * Decrypt passwords in the given config options
	 *
	 * @param array $options mount options
	 * @return array updated options
	 */
	public function decryptPasswords(array $options): array {
		// note: legacy options might still have the unencrypted password in the "password" field
		if (isset($options['password_encrypted'])) {
			$options['password'] = $this->decryptPassword($options['password_encrypted']);
			unset($options['password_encrypted']);
		}
		return $options;
	}

	/**
	 * Encrypt a single password
	 */
	private function encryptPassword(string $password): string {
		$cipher = $this->getCipher();
		$iv = $this->secureRandom->generate(16);
		$cipher->setIV($iv);
		return base64_encode($iv . $cipher->encrypt($password));
	}

	/**
	 * Decrypts a single password
	 */
	private function decryptPassword(string $encryptedPassword): string {
		$cipher = $this->getCipher();
		$binaryPassword = base64_decode($encryptedPassword);
		$iv = substr($binaryPassword, 0, 16);
		$cipher->setIV($iv);
		$binaryPassword = substr($binaryPassword, 16);
		return $cipher->decrypt($binaryPassword);
	}

	/**
	 * Returns the encryption cipher
	 */
	private function getCipher(): AES {
		$cipher = new AES(AES::MODE_CBC);
		$cipher->setKey($this->config->getSystemValue('passwordsalt', null));
		return $cipher;
	}
}
