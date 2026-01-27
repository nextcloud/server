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
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test case for OC\Session\CryptoSessionData using in-memory session storage.
 * Reuses session contract tests but verifies they hold with encrypted storage 
 * (i.e., session values are encrypted/decrypted transparently).
 */
class CryptoSessionDataTest extends Session {
	protected ICrypto|MockObject $crypto;
	protected ISession $session;

	protected function setUp(): void {
		parent::setUp();

		$this->session = new Memory();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->crypto->method('encrypt')
			->willReturnCallback(fn($input) =>
				'#' . $input . '#');
		$this->crypto->method('decrypt')
			->willReturnCallback(fn($input) =>
				($input === '' || strlen($input) < 2) ? '' : substr($input, 1, -1));

		$this->instance = new CryptoSessionData($this->session, $this->crypto, 'PASS');
	}

	/* Basic API conformity/contract tests are in parent class; these are crypto specific pre-wrapper additions */

	public function testSessionDataStoredEncrypted(): void {
		$keyName = 'secret';
		$unencryptedValue = 'superSecretValue123';
		
		$this->instance->set('secret', 'superSecretValue123');
		$this->instance->close();

		$unencryptedSessionDataJson = json_encode(["$keyName" => "$unencryptedValue"]);
		$expectedEncryptedSessionDataBlob = $this->crypto->encrypt($unencryptedSessionDataJson, 'PASS');

		// Retrieve the CryptoSessionData blob directly from lower level session layer to guarantee bypass of crypto layer
		$encryptedSessionDataBlob = $this->session->get('encrypted_session_data'); // should contain raw encrypted blob not the decrypted data
		// Definitely encrypted?
		$this->assertStringStartsWith('#', $encryptedSessionDataBlob); // Must match mocked crypto->encrypt()
		$this->assertStringEndsWith('#', $encryptedSessionDataBlob); // ditto
		$this->assertFalse($expectedEncryptedSessionDataBlob === $unencryptedSessionDataJson);
		// Expected before/after?
		$this->assertSame($expectedEncryptedSessionDataBlob, $encryptedSessionDataBlob);
	}

	public function testLargeAndUnicodeValuesRoundTrip() {
		$unicodeValue = "héllo 🌍";
		$largeValue = str_repeat('x', 4096);
		$this->instance->set('unicode', $unicodeValue);
		$this->instance->set('big', $largeValue);
		$this->instance->close();
		// Simulate reload 
		$instance2 = new CryptoSessionData($this->session, $this->crypto, 'PASS');
		$this->assertSame($unicodeValue, $instance2->get('unicode'));
		$this->assertSame($largeValue, $instance2->get('big'));
	}

	public function testLargeArrayRoundTrip() {
		$bigArray = [];
		for ($i = 0; $i < 1000; $i++) {
			$bigArray["key$i"] = "val$i";
		}
		$this->instance->set('thousand', json_encode($bigArray));
		$this->instance->close();

		$instance2 = new CryptoSessionData($this->session, $this->crypto, 'PASS');
		$this->assertSame(json_encode($bigArray), $instance2->get('thousand'));
	}

	public function testRemovedValueIsGoneAfterClose() {
		$this->instance->set('temp', 'gone soon');
		$this->instance->remove('temp');
		$this->instance->close();

		$instance2 = new CryptoSessionData($this->session, $this->crypto, 'PASS');
		$this->assertNull($instance2->get('temp'));
	}

	public function testTamperedBlobReturnsNull() {
		$this->instance->set('foo', 'bar');
		$this->instance->close();
		// Tamper the lower level blob
		$this->session->set('encrypted_session_data', 'garbage-data');

		$instance2 = new CryptoSessionData($this->session, $this->crypto, 'PASS');
		$this->assertNull($instance2->get('foo'));
		$this->assertNull($instance2->get('notfoo'));
	}

	public function testWrongPassphraseGivesNoAccess() {
		// Override ICrypto mock/stubs for this test only
		$crypto = $this->createMock(ICrypto::class);
		$crypto->method('encrypt')->willReturnCallback(function($plain, $passphrase = null) {
			// Set up: store a value with the passphrase embedded (fake encryption)
			return $passphrase . '#' . $plain . '#' . $passphrase;
		});
		$crypto->method('decrypt')->willReturnCallback(function($input, $passphrase = null) {
			// Only successfully decrypt if the embedded passphrase matches
			if (strpos($input, $passphrase . '#') === 0 && strrpos($input, '#' . $passphrase) === strlen($input) - strlen('#' . $passphrase)) {
				// Strip off passphrase markers and return the "decrypted" string
				return substr($input, strlen($passphrase . '#'), -strlen('#' . $passphrase));
			}
			// Fail to decrypt
			return '';
		});

		// Override main instance with local ISession and local ICrypto mock/stubs
		$session = new Memory();
		$instance = new CryptoSessionData($session, $crypto, 'PASS');

		$instance->set('secure', 'yes');
		$instance->close();

		$instance2 = new CryptoSessionData($session, $crypto, 'DIFFERENT');
		$this->assertNull($instance2->get('secure'));
		$this->assertFalse($instance2->exists('secure'));
	}

	public function testEmptyKeyValue() {
		$this->instance->set('', '');
		$this->instance->close();
		$instance2 = new CryptoSessionData($this->session, $this->crypto, 'PASS');
		$this->assertSame('', $instance2->get(''));
	}

	public function testDoubleCloseDoesNotCorrupt() {
		$this->instance->set('safe', 'value');
		$this->instance->close();
		$blobBefore = $this->session->get('encrypted_session_data');
		$this->instance->close(); // Should do nothing harmful
		$blobAfter = $this->session->get('encrypted_session_data');
		$this->assertSame($blobBefore, $blobAfter);
	}
}
