<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Security\CSRF;

/**
 * Class CsrfToken represents the stored or provided CSRF token. To mitigate
 * BREACH alike vulnerabilities the token is returned in an encrypted value as
 * well in an unencrypted value. For display measures to the user always the
 * unencrypted one should be chosen.
 *
 * @package OC\Security\CSRF
 */
class CsrfToken {
	private string $encryptedValue = '';

	/**
	 * @param string $value Value of the token. Can be encrypted or not encrypted.
	 */
	public function __construct(
		private string $value,
	) {
	}

	/**
	 * Encrypted value of the token. This is used to mitigate BREACH alike
	 * vulnerabilities. For display measures do use this functionality.
	 */
	public function getEncryptedValue(): string {
		if ($this->encryptedValue === '') {
			$sharedSecret = random_bytes(\strlen($this->value));
			$this->encryptedValue = base64_encode($this->value ^ $sharedSecret) . ':' . base64_encode($sharedSecret);
		}

		return $this->encryptedValue;
	}

	/**
	 * The unencrypted value of the token. Used for decrypting an already
	 * encrypted token.
	 */
	public function getDecryptedValue(): string {
		$token = explode(':', $this->value);
		if (\count($token) !== 2) {
			return '';
		}
		$obfuscatedToken = $token[0];
		$secret = $token[1];
		return base64_decode($obfuscatedToken) ^ base64_decode($secret);
	}
}
