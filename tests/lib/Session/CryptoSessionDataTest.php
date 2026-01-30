<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Session;

use OC\Session\CryptoSessionData;
use OC\Session\Memory;
use OCP\ISession;
use OCP\Security\ICrypto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for CryptoSessionData, verifying encrypted session storage,
 * tamper resistance, passphrase boundaries, and round-trip data integrity.
 * Covers edge cases and crypto-specific behaviors beyond the base session contract.
 *
 * Note: ISession API conformity/contract tests are inherited from the parent
 * (Test\Session\Session). Only crypto-specific (and pre-wrapper) additions are
 * defined here.
 */
#[CoversClass(CryptoSessionData::class)]
#[UsesClass(Memory::class)]
class CryptoSessionDataTest extends Session {
	private const DUMMY_PASSPHRASE = 'dummyPassphrase';
	private const TAMPERED_BLOB = 'garbage-data';
	private const MALFORMED_JSON_BLOB = '{not:valid:json}';

	protected ICrypto|MockObject $crypto;
	protected ISession $session;

	protected function setUp(): void {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);

		$this->crypto->method('encrypt')->willReturnCallback(
			fn ($input) => '#' . $input . '#'
		);
		$this->crypto->method('decrypt')->willReturnCallback(
			fn ($input) => ($input === '' || strlen($input) < 2) ? '' : substr($input, 1, -1)
		);

		$this->session = new Memory();
		$this->instance = new CryptoSessionData($this->session, $this->crypto, self::DUMMY_PASSPHRASE);
	}

	/**
	 * Ensure backend never stores plaintext at-rest.
	 */
	public function testSessionDataStoredEncrypted(): void {
		$keyName = 'secret';
		$unencryptedValue = 'superSecretValue123';

		$this->instance->set($keyName, $unencryptedValue);
		$this->instance->close();

		$unencryptedSessionDataJson = json_encode(["$keyName" => "$unencryptedValue"]);
		$expectedEncryptedSessionDataBlob = $this->crypto->encrypt($unencryptedSessionDataJson, self::DUMMY_PASSPHRASE);

		// Retrieve the CryptoSessionData blob directly from lower level session layer to bypass crypto decryption layer
		$encryptedSessionDataBlob = $this->session->get('encrypted_session_data'); // should contain raw encrypted blob not the decrypted data
		// Definitely encrypted?
		$this->assertStringStartsWith('#', $encryptedSessionDataBlob); // Must match stubbed crypto->encrypt()
		$this->assertStringEndsWith('#', $encryptedSessionDataBlob); // ditto
		$this->assertNotSame($unencryptedSessionDataJson, $expectedEncryptedSessionDataBlob);
		$this->assertSame($expectedEncryptedSessionDataBlob, $encryptedSessionDataBlob);
	}

	/**
	 * Ensure various key/value types are storable/retrievable
	 */
	#[DataProvider('roundTripValuesProvider')]
	public function testRoundTripValue($key, $value): void {
		$this->instance->set($key, $value);
		$this->instance->close();
		// Simulate reload
		$instance2 = new CryptoSessionData($this->session, $this->crypto, self::DUMMY_PASSPHRASE);
		$this->assertSame($value, $instance2->get($key));
	}

	public static function roundTripValuesProvider(): array {
		return [
			'simple string' => ['foo', 'bar'],
			'unicode value' => ['uni', 'héllo 🌍'],
			'large value' => ['big', str_repeat('x', 4096)],
			'large array' => ['thousand', json_encode(self::makeLargeArray())],
			'empty string' => ['', ''],
		];
	}

	/* Helper */
	private static function makeLargeArray(int $size = 1000): array {
		$result = [];
		for ($i = 0; $i < $size; $i++) {
			$result["key$i"] = "val$i";
		}
		return $result;
	}

	/**
	 * Ensure removed values are not accessible after flush/reload.
	 */
	public function testRemovedValueIsGoneAfterClose(): void {
		$this->instance->set('temp', 'gone soon');
		$this->instance->remove('temp');
		$this->instance->close();

		$instance2 = new CryptoSessionData($this->session, $this->crypto, self::DUMMY_PASSPHRASE);
		$this->assertNull($instance2->get('temp'));
	}

	/**
	 * Ensure tampering is handled robustly.
	 */
	public function testTamperedBlobReturnsNull(): void {
		$this->instance->set('foo', 'bar');
		$this->instance->close();
		// Bypass crypto layer and tamper the lower level blob
		$this->session->set('encrypted_session_data', self::TAMPERED_BLOB);

		$instance2 = new CryptoSessionData($this->session, $this->crypto, self::DUMMY_PASSPHRASE);
		$this->assertNull($instance2->get('foo'));
		$this->assertNull($instance2->get('notfoo'));
	}

	/**
	 * Ensure malformed JSON is handled robustly.
	 */
	public function testMalformedJsonBlobReturnsNull(): void {
		$this->instance->set('foo', 'bar');
		$this->instance->close();
		$this->session->set('encrypted_session_data', '#' . self::MALFORMED_JSON_BLOB . '#');
		$instance2 = new CryptoSessionData($this->session, $this->crypto, self::DUMMY_PASSPHRASE);
		$this->assertNull($instance2->get('foo'));
	}

	/**
	 * Ensure an invalid passphrase is handled appropriately.
	 */
	public function testWrongPassphraseGivesNoAccess(): void {
		// Override ICrypto mock/stubs for this test only
		$crypto = $this->createPassphraseAwareCryptoMock();

		// Override main instance with local ISession and local ICrypto mock/stubs
		$session = new Memory();
		$instance = new CryptoSessionData($session, $crypto, self::DUMMY_PASSPHRASE);

		$instance->set('secure', 'yes');
		$instance->close();

		$instance2 = new CryptoSessionData($session, $crypto, 'NOT_THE_DUMMY_PASSPHRASE');
		$this->assertNull($instance2->get('secure'));
		$this->assertFalse($instance2->exists('secure'));
	}

	/* Helper */
	private function createPassphraseAwareCryptoMock(): ICrypto {
		$crypto = $this->createMock(ICrypto::class);

		$crypto->method('encrypt')->willReturnCallback(function ($plain, $passphrase = null) {
			// Set up: store a value with the passphrase embedded (fake encryption)
			return $passphrase . '#' . $plain . '#' . $passphrase;
		});
		$crypto->method('decrypt')->willReturnCallback(function ($input, $passphrase = null) {
			// Only successfully decrypt if the embedded passphrase matches
			if (strpos($input, $passphrase . '#') === 0 && strrpos($input, '#' . $passphrase) === strlen($input) - strlen('#' . $passphrase)) {
				// Strip off passphrase markers and return the "decrypted" string
				return substr($input, strlen($passphrase . '#'), -strlen('#' . $passphrase));
			}
			// Fail to decrypt
			return '';
		});

		return $crypto;
	}

	/**
	 * Ensure closes are idempotent and safe.
	 */
	public function testDoubleCloseDoesNotCorrupt(): void {
		$this->instance->set('safe', 'value');
		$this->instance->close();
		$blobBefore = $this->session->get('encrypted_session_data');
		$this->instance->close(); // Should do nothing harmful
		$blobAfter = $this->session->get('encrypted_session_data');
		$this->assertSame($blobBefore, $blobAfter);
	}
}
