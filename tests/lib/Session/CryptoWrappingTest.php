<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Session;

use OC\Session\CryptoSessionData;
use OC\Session\Memory;
use OCP\ISession;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Test cases for the internal logic of OC\Session\CryptoSessionData.
 * Focuses on correct encryption/decryption of session data and wrapping behavior.
 *
 * TODO: Should really be testing CryptoWrapper!
 */
class CryptoWrappingTest extends TestCase {
	protected ICrypto|MockObject $crypto;
	protected ISession|MockObject $session;
	protected CryptoSessionData $instance;
	
	protected string $passphrase = 'PASS';

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

		// Encrypted session handler under test
		$this->instance = new CryptoSessionData($this->session, $this->crypto, $this->passphrase);
	}

	public function testUnwrappingGet(): void {
		$keyName = 'someKey';
		$unencryptedValue = 'foobar';
		$encryptedValue = $this->crypto->encrypt($unencryptedValue);

		$this->instance->set($keyName, $unencryptedValue);

		$this->assertTrue($this->instance->exists($keyName));
		$this->assertSame($unencryptedValue, $this->instance->get($keyName));
	}
}
