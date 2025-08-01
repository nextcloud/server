<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests;

use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Recovery;
use OCP\Encryption\IFile;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RecoveryTest extends TestCase {
	private static $tempStorage = [];
	/**
	 * @var IFile|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $fileMock;
	/**
	 * @var View|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $viewMock;
	/**
	 * @var IUserSession|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $userSessionMock;
	/**
	 * @var MockObject|IUser
	 */
	private $user;
	/**
	 * @var KeyManager|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $keyManagerMock;
	/**
	 * @var IConfig|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $configMock;
	/**
	 * @var Crypt|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $cryptMock;
	/**
	 * @var Recovery
	 */
	private $instance;

	public function testEnableAdminRecoverySuccessful(): void {
		$this->keyManagerMock->expects($this->exactly(2))
			->method('recoveryKeyExists')
			->willReturnOnConsecutiveCalls(false, true);

		$this->cryptMock->expects($this->once())
			->method('createKeyPair')
			->willReturn([
				'publicKey' => 'privateKey',
				'privateKey' => 'publicKey',
			]);

		$this->keyManagerMock->expects($this->once())
			->method('setRecoveryKey')
			->willReturn(false);

		$this->keyManagerMock->expects($this->exactly(2))
			->method('checkRecoveryPassword')
			->willReturnOnConsecutiveCalls(true, true);

		$this->assertTrue($this->instance->enableAdminRecovery('password'));
		$this->assertArrayHasKey('recoveryAdminEnabled', self::$tempStorage);
		$this->assertEquals(1, self::$tempStorage['recoveryAdminEnabled']);

		$this->assertTrue($this->instance->enableAdminRecovery('password'));
	}

	public function testEnableAdminRecoveryCouldNotCheckPassword(): void {
		$this->keyManagerMock->expects($this->exactly(2))
			->method('recoveryKeyExists')
			->willReturnOnConsecutiveCalls(false, true);

		$this->cryptMock->expects($this->once())
			->method('createKeyPair')
			->willReturn([
				'publicKey' => 'privateKey',
				'privateKey' => 'publicKey',
			]);

		$this->keyManagerMock->expects($this->once())
			->method('setRecoveryKey')
			->willReturn(false);

		$this->keyManagerMock->expects($this->exactly(2))
			->method('checkRecoveryPassword')
			->willReturnOnConsecutiveCalls(true, false);

		$this->assertTrue($this->instance->enableAdminRecovery('password'));
		$this->assertArrayHasKey('recoveryAdminEnabled', self::$tempStorage);
		$this->assertEquals(1, self::$tempStorage['recoveryAdminEnabled']);

		$this->assertFalse($this->instance->enableAdminRecovery('password'));
	}

	public function testEnableAdminRecoveryCouldNotCreateKey(): void {
		$this->keyManagerMock->expects($this->once())
			->method('recoveryKeyExists')
			->willReturn(false);

		$this->cryptMock->expects($this->once())
			->method('createKeyPair')
			->willReturn(false);

		$this->assertFalse($this->instance->enableAdminRecovery('password'));
	}

	public function testChangeRecoveryKeyPasswordSuccessful(): void {
		$this->assertFalse($this->instance->changeRecoveryKeyPassword('password',
			'passwordOld'));

		$this->keyManagerMock->expects($this->once())
			->method('getSystemPrivateKey');

		$this->cryptMock->expects($this->once())
			->method('decryptPrivateKey');

		$this->cryptMock->expects($this->once())
			->method('encryptPrivateKey')
			->willReturn(true);

		$this->assertTrue($this->instance->changeRecoveryKeyPassword('password',
			'passwordOld'));
	}

	public function testChangeRecoveryKeyPasswordCouldNotDecryptPrivateRecoveryKey(): void {
		$this->assertFalse($this->instance->changeRecoveryKeyPassword('password', 'passwordOld'));

		$this->keyManagerMock->expects($this->once())
			->method('getSystemPrivateKey');

		$this->cryptMock->expects($this->once())
			->method('decryptPrivateKey')
			->willReturn(false);

		$this->assertFalse($this->instance->changeRecoveryKeyPassword('password', 'passwordOld'));
	}

	public function testDisableAdminRecovery(): void {
		$this->keyManagerMock->expects($this->exactly(2))
			->method('checkRecoveryPassword')
			->willReturnOnConsecutiveCalls(true, false);

		$this->assertArrayHasKey('recoveryAdminEnabled', self::$tempStorage);
		$this->assertTrue($this->instance->disableAdminRecovery('password'));
		$this->assertEquals(0, self::$tempStorage['recoveryAdminEnabled']);

		$this->assertFalse($this->instance->disableAdminRecovery('password'));
	}

	public function testIsRecoveryEnabledForUser(): void {
		$this->configMock->expects($this->exactly(2))
			->method('getUserValue')
			->willReturnOnConsecutiveCalls('1', '0');

		$this->assertTrue($this->instance->isRecoveryEnabledForUser());
		$this->assertFalse($this->instance->isRecoveryEnabledForUser('admin'));
	}

	public function testIsRecoveryKeyEnabled(): void {
		$this->assertFalse($this->instance->isRecoveryKeyEnabled());
		self::$tempStorage['recoveryAdminEnabled'] = '1';
		$this->assertTrue($this->instance->isRecoveryKeyEnabled());
	}

	public function testSetRecoveryFolderForUser(): void {
		$this->viewMock->expects($this->exactly(2))
			->method('getDirectoryContent')
			->willReturn([]);
		$this->assertTrue($this->instance->setRecoveryForUser(0));
		$this->assertTrue($this->instance->setRecoveryForUser('1'));
	}

	public function testRecoverUserFiles(): void {
		$this->viewMock->expects($this->once())
			->method('getDirectoryContent')
			->willReturn([]);

		$this->cryptMock->expects($this->once())
			->method('decryptPrivateKey')
			->willReturn('privateKey');
		$this->instance->recoverUsersFiles('password', 'admin');
		$this->addToAssertionCount(1);
	}

	public function testRecoverFile(): void {
		$this->keyManagerMock->expects($this->once())
			->method('getEncryptedFileKey')
			->willReturn(true);

		$this->keyManagerMock->expects($this->once())
			->method('getShareKey')
			->willReturn(true);

		$this->cryptMock->expects($this->once())
			->method('multiKeyDecryptLegacy')
			->willReturn('multiKeyDecryptLegacyResult');

		$this->fileMock->expects($this->once())
			->method('getAccessList')
			->willReturn(['users' => ['admin']]);

		$this->keyManagerMock->expects($this->once())
			->method('getPublicKey')
			->willReturn('publicKey');

		$this->keyManagerMock->expects($this->once())
			->method('addSystemKeys')
			->with($this->anything(), $this->anything(), $this->equalTo('admin'))
			->willReturn(['admin' => 'publicKey']);


		$this->cryptMock->expects($this->once())
			->method('multiKeyEncrypt')
			->willReturn(['admin' => 'shareKey']);

		$this->keyManagerMock->expects($this->once())
			->method('deleteLegacyFileKey');
		$this->keyManagerMock->expects($this->once())
			->method('setShareKey');

		$this->assertNull(self::invokePrivate($this->instance,
			'recoverFile',
			['/', 'testkey', 'admin']));
	}

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('admin');

		$this->userSessionMock = $this->createMock(IUserSession::class);
		$this->userSessionMock->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
		$this->userSessionMock->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);

		$this->cryptMock = $this->getMockBuilder(Crypt::class)->disableOriginalConstructor()->getMock();
		$this->keyManagerMock = $this->getMockBuilder(KeyManager::class)->disableOriginalConstructor()->getMock();
		$this->configMock = $this->createMock(IConfig::class);
		$this->fileMock = $this->createMock(IFile::class);
		$this->viewMock = $this->createMock(View::class);

		$this->configMock->expects($this->any())
			->method('setAppValue')
			->willReturnCallback([$this, 'setValueTester']);

		$this->configMock->expects($this->any())
			->method('getAppValue')
			->willReturnCallback([$this, 'getValueTester']);

		$this->instance = new Recovery($this->userSessionMock,
			$this->cryptMock,
			$this->keyManagerMock,
			$this->configMock,
			$this->fileMock,
			$this->viewMock);
	}


	/**
	 * @param $app
	 * @param $key
	 * @param $value
	 */
	public function setValueTester($app, $key, $value) {
		self::$tempStorage[$key] = $value;
	}

	/**
	 * @param $key
	 */
	public function removeValueTester($key) {
		unset(self::$tempStorage[$key]);
	}

	/**
	 * @param $app
	 * @param $key
	 * @return mixed
	 */
	public function getValueTester($app, $key) {
		if (!empty(self::$tempStorage[$key])) {
			return self::$tempStorage[$key];
		}
		return null;
	}
}
