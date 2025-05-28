<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests;

use OCA\Encryption\Exceptions\PrivateKeyMissingException;
use OCA\Encryption\Session;
use OCP\ISession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SessionTest extends TestCase {
	private static $tempStorage = [];

	protected Session $instance;
	protected ISession&MockObject $sessionMock;

	public function testThatGetPrivateKeyThrowsExceptionWhenNotSet(): void {
		$this->expectException(PrivateKeyMissingException::class);
		$this->expectExceptionMessage('Private Key missing for user: please try to log-out and log-in again');

		$this->instance->getPrivateKey();
	}

	/**
	 * @depends testThatGetPrivateKeyThrowsExceptionWhenNotSet
	 */
	public function testSetAndGetPrivateKey(): void {
		$this->instance->setPrivateKey('dummyPrivateKey');
		$this->assertEquals('dummyPrivateKey', $this->instance->getPrivateKey());
	}

	/**
	 * @depends testSetAndGetPrivateKey
	 */
	public function testIsPrivateKeySet(): void {
		$this->instance->setPrivateKey('dummyPrivateKey');
		$this->assertTrue($this->instance->isPrivateKeySet());

		unset(self::$tempStorage['privateKey']);
		$this->assertFalse($this->instance->isPrivateKeySet());

		// Set private key back so we can test clear method
		self::$tempStorage['privateKey'] = 'dummyPrivateKey';
	}

	public function testDecryptAllModeActivated(): void {
		$this->instance->prepareDecryptAll('user1', 'usersKey');
		$this->assertTrue($this->instance->decryptAllModeActivated());
		$this->assertSame('user1', $this->instance->getDecryptAllUid());
		$this->assertSame('usersKey', $this->instance->getDecryptAllKey());
	}

	public function testDecryptAllModeDeactivated(): void {
		$this->assertFalse($this->instance->decryptAllModeActivated());
	}

	/**
	 * @expectExceptionMessage 'Please activate decrypt all mode first'
	 */
	public function testGetDecryptAllUidException(): void {
		$this->expectException(\Exception::class);

		$this->instance->getDecryptAllUid();
	}

	/**
	 * @expectExceptionMessage 'No uid found while in decrypt all mode'
	 */
	public function testGetDecryptAllUidException2(): void {
		$this->expectException(\Exception::class);

		$this->instance->prepareDecryptAll(null, 'key');
		$this->instance->getDecryptAllUid();
	}

	/**
	 * @expectExceptionMessage 'Please activate decrypt all mode first'
	 */
	public function testGetDecryptAllKeyException(): void {
		$this->expectException(PrivateKeyMissingException::class);

		$this->instance->getDecryptAllKey();
	}

	/**
	 * @expectExceptionMessage 'No key found while in decrypt all mode'
	 */
	public function testGetDecryptAllKeyException2(): void {
		$this->expectException(PrivateKeyMissingException::class);

		$this->instance->prepareDecryptAll('user', null);
		$this->instance->getDecryptAllKey();
	}


	public function testSetAndGetStatusWillSetAndReturn(): void {
		// Check if get status will return 0 if it has not been set before
		$this->assertEquals(0, $this->instance->getStatus());

		$this->instance->setStatus(Session::NOT_INITIALIZED);
		$this->assertEquals(0, $this->instance->getStatus());

		$this->instance->setStatus(Session::INIT_EXECUTED);
		$this->assertEquals(1, $this->instance->getStatus());

		$this->instance->setStatus(Session::INIT_SUCCESSFUL);
		$this->assertEquals(2, $this->instance->getStatus());
	}

	/**
	 * @dataProvider dataTestIsReady
	 *
	 * @param int $status
	 * @param bool $expected
	 */
	public function testIsReady($status, $expected): void {
		/** @var Session&MockObject $instance */
		$instance = $this->getMockBuilder(Session::class)
			->setConstructorArgs([$this->sessionMock])
			->onlyMethods(['getStatus'])
			->getMock();

		$instance->expects($this->once())->method('getStatus')
			->willReturn($status);

		$this->assertSame($expected, $instance->isReady());
	}

	public static function dataTestIsReady(): array {
		return [
			[Session::INIT_SUCCESSFUL, true],
			[Session::INIT_EXECUTED, false],
			[Session::NOT_INITIALIZED, false],
		];
	}

	/**
	 * @param $key
	 * @param $value
	 */
	public function setValueTester($key, $value) {
		self::$tempStorage[$key] = $value;
	}

	/**
	 * @param $key
	 */
	public function removeValueTester($key) {
		unset(self::$tempStorage[$key]);
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function getValueTester($key) {
		if (!empty(self::$tempStorage[$key])) {
			return self::$tempStorage[$key];
		}
		return null;
	}


	public function testClearWillRemoveValues(): void {
		$this->instance->setPrivateKey('privateKey');
		$this->instance->setStatus('initStatus');
		$this->instance->prepareDecryptAll('user', 'key');
		$this->assertNotEmpty(self::$tempStorage);
		$this->instance->clear();
		$this->assertEmpty(self::$tempStorage);
	}


	protected function setUp(): void {
		parent::setUp();
		$this->sessionMock = $this->createMock(ISession::class);

		$this->sessionMock->expects($this->any())
			->method('set')
			->willReturnCallback([$this, 'setValueTester']);

		$this->sessionMock->expects($this->any())
			->method('get')
			->willReturnCallback([$this, 'getValueTester']);

		$this->sessionMock->expects($this->any())
			->method('remove')
			->willReturnCallback([$this, 'removeValueTester']);


		$this->instance = new Session($this->sessionMock);
	}

	protected function tearDown(): void {
		self::$tempStorage = [];
		parent::tearDown();
	}
}
