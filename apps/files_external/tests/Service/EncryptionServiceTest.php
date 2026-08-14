<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Service;

use OCA\Files_External\Service\EncryptionService;
use OCP\IConfig;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;

class EncryptionServiceTest extends \Test\TestCase {
	private IConfig&MockObject $config;
	private ISecureRandom&MockObject $secureRandom;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
	}

	private function service(string $passwordSalt): EncryptionService {
		$this->config->method('getSystemValue')
			->with('passwordsalt', '')
			->willReturn($passwordSalt);
		return new EncryptionService($this->config, $this->secureRandom);
	}

	public static function saltProvider(): array {
		return [
			'typical 30-char salt' => [str_repeat('a', 30)],
			'16-char salt' => [str_repeat('b', 16)],
			'24-char salt' => [str_repeat('c', 24)],
			'32-char salt' => [str_repeat('d', 32)],
			'long 40-char salt' => [str_repeat('e', 40)],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('saltProvider')]
	public function testEncryptDecryptRoundTrip(string $passwordSalt): void {
		$this->secureRandom->method('generate')->willReturn(str_repeat("\x11", 16));
		$service = $this->service($passwordSalt);

		$options = $service->encryptPasswords(['password' => 'my-secret-password']);
		$this->assertArrayHasKey('password_encrypted', $options);
		$this->assertSame('', $options['password']);

		$decrypted = $service->decryptPasswords(['password_encrypted' => $options['password_encrypted']]);
		$this->assertSame('my-secret-password', $decrypted['password']);
		$this->assertArrayNotHasKey('password_encrypted', $decrypted);
	}

	/**
	 * Ciphertexts written with phpseclib v2 must remain decryptable after the
	 * upgrade to phpseclib v3. v2 accepted arbitrary-length keys and normalized
	 * them (round up to 16/24/32 bytes, read whole 4-byte words only, zero-pad
	 * missing high words). Here we recreate such a ciphertext with OpenSSL — an
	 * independent AES implementation — using that exact effective key.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('saltProvider')]
	public function testDecryptsLegacyPhpseclibV2Ciphertext(string $passwordSalt): void {
		$plaintext = 'legacy-external-storage-password';

		// Reproduce the phpseclib v2 effective AES key
		$length = strlen($passwordSalt);
		$keyLength = $length <= 16 ? 16 : ($length <= 24 ? 24 : 32);
		$effectiveKey = substr(str_pad(substr($passwordSalt, 0, intdiv($length, 4) * 4), $keyLength, "\0"), 0, $keyLength);

		$iv = str_repeat("\x22", 16);
		$rawCiphertext = openssl_encrypt($plaintext, 'aes-' . ($keyLength * 8) . '-cbc', $effectiveKey, OPENSSL_RAW_DATA, $iv);
		$stored = base64_encode($iv . $rawCiphertext);

		$service = $this->service($passwordSalt);
		$decrypted = $service->decryptPasswords(['password_encrypted' => $stored]);
		$this->assertSame($plaintext, $decrypted['password']);
	}
}
