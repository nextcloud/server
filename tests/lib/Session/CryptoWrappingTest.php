<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Session;

use OC\Session\CryptoSessionData;
use OC\Session\CryptoWrapper;
use OC\Session\Memory;
use OCP\IRequest;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Unit tests for CryptoWrapper, focusing on session wrapping logic,
 * passphrase handling (cookie and generation), and integration with
 * CryptoSessionData. Ensures robust construction and non-duplication
 * of crypto-wrapped sessions.
 *
 * Only wrapper-specific crypto behavior is tested here;
 * core session encryption contract is covered in CryptoSessionDataTest.
 *
 * @see Test\Session\CryptoSessionDataTest For crypto storage testing logic.
 */
#[CoversClass(CryptoWrapper::class)]
#[UsesClass(Memory::class)]
#[UsesClass(CryptoSessionData::class)]
class CryptoWrappingTest extends TestCase {
	private const DUMMY_PASSPHRASE = 'dummyPassphrase';
	private const COOKIE_PASSPHRASE = 'cookiePassphrase';
	private const GENERATED_PASSPHRASE = 'generatedPassphrase';
	private const SERVER_PROTOCOL = 'https';

	protected ICrypto&MockObject $crypto;
	protected ISecureRandom&MockObject $random;
	protected IRequest&MockObject $request;

	protected function setUp(): void {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->request = $this->createMock(IRequest::class);
	}

	/**
	 * Ensure wrapSession returns a CryptoSessionData when passed a basic session.
	 */
	public function testWrapSessionReturnsCryptoSessionData(): void {
		$generatedPassphrase128 = str_pad(self::GENERATED_PASSPHRASE, 128, '_' . __FUNCTION__, STR_PAD_RIGHT);
		$this->random->method('generate')->willReturn($generatedPassphrase128);

		$this->request->method('getCookie')->willReturn(null);
		$this->request->method('getServerProtocol')->willReturn(self::SERVER_PROTOCOL);

		$session = new Memory();

		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$wrappedSession = $cryptoWrapper->wrapSession($session);

		$this->assertInstanceOf(CryptoSessionData::class, $wrappedSession);
	}

	/**
	 * Ensure wrapSession returns the same instance if already wrapped.
	 */
	public function testWrapSessionDoesNotDoubleWrap(): void {
		$alreadyWrapped = $this->createMock(CryptoSessionData::class);

		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$wrappedSession = $cryptoWrapper->wrapSession($alreadyWrapped);

		$this->assertSame($alreadyWrapped, $wrappedSession);
	}

	/**
	 * Ensure a passphrase is generated and stored if no cookie is present.
	 */
	public function testPassphraseGeneratedIfNoCookie(): void {
		$expectedPassphrase = str_pad(self::GENERATED_PASSPHRASE, 128, '_' . __FUNCTION__, STR_PAD_RIGHT);
		$this->random->expects($this->once())->method('generate')->with(128)->willReturn($expectedPassphrase);

		$this->request->method('getCookie')->willReturn(null);
		$this->request->method('getServerProtocol')->willReturn(self::SERVER_PROTOCOL);

		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$ref = new \ReflectionProperty($cryptoWrapper, 'passphrase');
		$ref->setAccessible(true);

		$this->assertTrue($ref->getValue($cryptoWrapper) !== null);
		$this->assertSame($expectedPassphrase, $ref->getValue($cryptoWrapper));
	}

	/**
	 * Ensure only the passphrase from cookie is used if present.
	 */
	public function testPassphraseReusedIfCookiePresent(): void {
		$cookieVal = self::COOKIE_PASSPHRASE;
		$this->request->method('getCookie')->willReturn($cookieVal);

		$this->random->expects($this->never())->method('generate');
		$this->request->method('getServerProtocol')->willReturn(self::SERVER_PROTOCOL);

		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$ref = new \ReflectionProperty($cryptoWrapper, 'passphrase');
		$ref->setAccessible(true);

		$this->assertSame($cookieVal, $ref->getValue($cryptoWrapper));
	}

	/**
	 * Ensure wrapSession throws if passed a non-ISession object (robustness).
	 */
	public function testWrapSessionThrowsTypeErrorOnInvalidInput(): void {
		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$this->expectException(\TypeError::class);
		$cryptoWrapper->wrapSession(new \stdClass());
	}

	/**
	 * Full integration: wrap, set, get, flush, and encrypted blob.
	 */
	public function testIntegrationWrapSetAndGet(): void {
		$keyName = 'someKey';
		$unencryptedValue = 'foobar';
		$expectedPassphrase = str_pad(self::GENERATED_PASSPHRASE, 128, '_' . __FUNCTION__, STR_PAD_RIGHT);

		$this->crypto->method('encrypt')->willReturnCallback(
			fn ($input) => '#' . $input . '#'
		);
		$this->crypto->method('decrypt')->willReturnCallback(
			fn ($input) => ($input === '' || strlen($input) < 2) ? '' : substr($input, 1, -1)
		);

		$this->random->method('generate')->with(128)->willReturn($expectedPassphrase);
		$this->request->method('getCookie')->willReturn(null);
		$this->request->method('getServerProtocol')->willReturn(self::SERVER_PROTOCOL);

		$session = new Memory();
		$cryptoWrapper = new CryptoWrapper($this->crypto, $this->random, $this->request);
		$wrappedSession = $cryptoWrapper->wrapSession($session);

		$wrappedSession->set($keyName, $unencryptedValue);
		$wrappedSession->close();

		$this->assertTrue($wrappedSession->exists($keyName));
		$this->assertSame($unencryptedValue, $wrappedSession->get($keyName));

		$unencryptedSessionDataJson = json_encode(["$keyName" => "$unencryptedValue"]);
		$expectedEncryptedSessionDataBlob = $this->crypto->encrypt($unencryptedSessionDataJson, $expectedPassphrase);

		// Retrieve the CryptoSessionData blob directly from lower level session layer to guarantee bypass of crypto layer
		$encryptedSessionDataBlob = $session->get('encrypted_session_data');
		// Definitely encrypted?
		$this->assertStringStartsWith('#', $encryptedSessionDataBlob); // Must match mocked crypto->encrypt()
		$this->assertStringEndsWith('#', $encryptedSessionDataBlob); // ditto
		$this->assertFalse($expectedEncryptedSessionDataBlob === $unencryptedSessionDataJson);
		$this->assertSame($expectedEncryptedSessionDataBlob, $encryptedSessionDataBlob);
	}
}
