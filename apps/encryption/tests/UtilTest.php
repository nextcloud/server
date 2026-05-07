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
use OCA\Encryption\Util;
use OCP\Config\IUserConfig;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UtilTest extends TestCase {

	protected Util $instance;
	protected static $tempStorage = [];

	protected IAppConfig&MockObject $appConfigMock;
	protected IUserConfig&MockObject $userConfigMock;
	protected View&MockObject $filesMock;
	protected IUserManager&MockObject $userManagerMock;
	protected IMountPoint&MockObject $mountMock;

	public function testSetRecoveryForUser(): void {
		$this->instance->setRecoveryForUser(true);
		$this->assertArrayHasKey('recoveryEnabled', self::$tempStorage);
	}

	public function testIsRecoveryEnabledForUser(): void {
		$this->assertTrue($this->instance->isRecoveryEnabledForUser('admin'));

		// Assert recovery will return default value if not set
		unset(self::$tempStorage['recoveryEnabled']);
		$this->assertFalse($this->instance->isRecoveryEnabledForUser('admin'));
	}

	public function testUserHasFiles(): void {
		$this->filesMock->expects($this->once())
			->method('file_exists')
			->willReturn(true);

		$this->assertTrue($this->instance->userHasFiles('admin'));
	}

	protected function setUp(): void {
		parent::setUp();
		$this->mountMock = $this->createMock(IMountPoint::class);
		$this->filesMock = $this->createMock(View::class);
		$this->userManagerMock = $this->createMock(IUserManager::class);

		/** @var Crypt $cryptMock */
		$cryptMock = $this->getMockBuilder(Crypt::class)
			->disableOriginalConstructor()
			->getMock();

		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('admin');

		/** @var IUserSession|MockObject $userSessionMock */
		$userSessionMock = $this->createMock(IUserSession::class);
		$userSessionMock->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$userSessionMock->expects($this->any())
			->method('isLoggedIn')
			->willReturn(true);

		$this->appConfigMock = $this->createMock(IAppConfig::class);
		$this->userConfigMock = $this->createMock(IUserConfig::class);

		$this->userConfigMock->expects($this->any())
			->method('getValueBool')
			->willReturnCallback([$this, 'getValueTester']);

		$this->userConfigMock->expects($this->any())
			->method('setValueBool')
			->willReturnCallback(function (string $userId, string $app, string $key, bool $value): bool {
				self::$tempStorage[$key] = $value;
				return true;
			});

		$this->instance = new Util($this->filesMock, $cryptMock, $userSessionMock, $this->appConfigMock, $this->userConfigMock, $this->userManagerMock);
	}

	public function setValueTester(string $userId, string $app, string $key, bool $value): void {
		self::$tempStorage[$key] = $value;
	}

	public function getValueTester(string $userId, string $app, string $key, bool $default = false): bool {
		return self::$tempStorage[$key] ?? $default;
	}

	/**
	 *
	 * @param string $value
	 * @param bool $expect
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestIsMasterKeyEnabled')]
	public function testIsMasterKeyEnabled(bool $value, bool $expect): void {
		$this->appConfigMock->expects($this->once())->method('getValueBool')
			->with('encryption', 'useMasterKey', true)->willReturn($value);
		$this->assertSame($expect,
			$this->instance->isMasterKeyEnabled()
		);
	}

	public static function dataTestIsMasterKeyEnabled(): array {
		return [
			[false, false],
			[true, true]
		];
	}

	/**
	 * @param bool $returnValue return value from getValueBool()
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestShouldEncryptHomeStorage')]
	public function testShouldEncryptHomeStorage(bool $returnValue, bool $expected): void {
		$this->appConfigMock->expects($this->once())->method('getValueBool')
			->with('encryption', 'encryptHomeStorage', true)
			->willReturn($returnValue);

		$this->assertSame($expected,
			$this->instance->shouldEncryptHomeStorage());
	}

	public static function dataTestShouldEncryptHomeStorage(): array {
		return [
			[true, true],
			[false, false]
		];
	}

	/**
	 * @param bool $value
	 * @param bool $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestSetEncryptHomeStorage')]
	public function testSetEncryptHomeStorage(bool $value, bool $expected): void {
		$this->appConfigMock->expects($this->once())->method('setValueBool')
			->with('encryption', 'encryptHomeStorage', $expected);
		$this->instance->setEncryptHomeStorage($value);
	}

	public static function dataTestSetEncryptHomeStorage(): array {
		return [
			[true, true],
			[false, false]
		];
	}

	public function testGetStorage(): void {
		$return = $this->getMockBuilder(IStorage::class)
			->disableOriginalConstructor()
			->getMock();

		$path = '/foo/bar.txt';
		$this->filesMock->expects($this->once())->method('getMount')->with($path)
			->willReturn($this->mountMock);
		$this->mountMock->expects($this->once())->method('getStorage')->willReturn($return);

		$this->assertEquals($return, $this->instance->getStorage($path));
	}
}
