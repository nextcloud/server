<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Session;

use OC\Session\CryptoWrapper;
use OC\Session\CryptoSessionData;
use OC\Session\Memory;
use OCP\IRequest;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
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
	protected ISecureRandom|MockObject $random;
	protected IRequest|MockObject $request;

	protected function setUp(): void {
		parent::setUp();
		
		$this->crypto = $this->createMock(ICrypto::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->request = $this->createMock(IRequest::class);
	}

	public function testWrapSessionReturnsCryptoSessionData(): void {
		$this->random->method('generate')->willReturn(str_repeat('Q', 128));
		$this->request->method('getCookie')->willReturn(null);
		$this->request->method('getServerProtocol')->willReturn('https');

		$session = new Memory();

		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$wrappedSession = $cryptoWrapper->wrapSession($session);

		$this->assertInstanceOf(CryptoSessionData::class, $wrappedSession);
	}

	public function testWrapSessionDoesNotDoubleWrap(): void {
		$alreadyWrapped = $this->createMock(CryptoSessionData::class);

		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$wrappedSession = $cryptoWrapper->wrapSession($alreadyWrapped);

		$this->assertSame($alreadyWrapped, $wrappedSession);
	}

	public function testPassphraseGeneratedIfNoCookie(): void {
		$expectedPassphrase = str_repeat('z', 128);
		$this->random->expects($this->once())->method('generate')->with(128)->willReturn($expectedPassphrase);
		$this->request->method('getCookie')->willReturn(null);
		$this->request->method('getServerProtocol')->willReturn('https');

		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$ref = new \ReflectionProperty($cryptoWrapper, 'passphrase');
		$ref->setAccessible(true);
		$this->assertSame($expectedPassphrase, $ref->getValue($cryptoWrapper));
	}

	public function testPassphraseReusedIfCookiePresent(): void {
		$cookieVal = 'pass_from_cookie';
		$this->request->method('getCookie')->willReturn($cookieVal);
		$this->random->expects($this->never())->method('generate');
		$this->request->method('getServerProtocol')->willReturn('https');

		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$ref = new \ReflectionProperty($cryptoWrapper, 'passphrase');
		$ref->setAccessible(true);
		$this->assertSame($cookieVal, $ref->getValue($cryptoWrapper));
	}

	public function testIntegrationWrapSetAndGet(): void {
		$keyName = 'someKey';
		$unencryptedValue = 'foobar';
		$encryptedValue = $this->crypto->encrypt($unencryptedValue);
		
		$this->crypto->method('encrypt')
			->willReturnCallback(fn($input) =>
				'#' . $input . '#');
		$this->crypto->method('decrypt')
			->willReturnCallback(fn($input) =>
				($input === '' || strlen($input) < 2) ? '' : substr($input, 1, -1));
		$this->random->method('generate')->willReturn(str_repeat('C', 128));
		$this->request->method('getCookie')->willReturn(null);
		$this->request->method('getServerProtocol')->willReturn('https');

		$session = new Memory();
		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);		
		$wrappedSession = $cryptoWrapper->wrapSession($session);
	
		$wrappedSession->set($keyName, $unencryptedValue);

		$this->assertTrue($wrappedSession->exists($keyName));
		$this->assertSame($unencryptedValue, $wrappedSession->get($keyName));

		// Encrypted storage check
		$wrappedSession->close(); // trigger flush so blob gets written out

		$encryptedSessionDataBlob = $session->get('encrypted_session_data'); // should contain raw encrypted blob not the decrypted data
		$expectedEncryptedSessionDataBlob = $this->crypto->encrypt(json_encode(["$keyName" => "$unencryptedValue"]), $this->random->generate(128));
		$this->assertSame($expectedEncryptedSessionDataBlob, $encryptedSessionDataBlob);
	}
}
