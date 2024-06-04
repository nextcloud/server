<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests;

use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Util;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UtilTest extends TestCase {
	private static $tempStorage = [];

	/** @var \OCP\IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $configMock;

	/** @var \OC\Files\View|\PHPUnit\Framework\MockObject\MockObject */
	private $filesMock;

	/** @var \OCP\IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManagerMock;

	/** @var \OCP\Files\Mount\IMountPoint|\PHPUnit\Framework\MockObject\MockObject */
	private $mountMock;

	/** @var Util */
	private $instance;

	public function testSetRecoveryForUser() {
		$this->instance->setRecoveryForUser('1');
		$this->assertArrayHasKey('recoveryEnabled', self::$tempStorage);
	}

	public function testIsRecoveryEnabledForUser() {
		$this->assertTrue($this->instance->isRecoveryEnabledForUser('admin'));

		// Assert recovery will return default value if not set
		unset(self::$tempStorage['recoveryEnabled']);
		$this->assertEquals(0, $this->instance->isRecoveryEnabledForUser('admin'));
	}

	public function testUserHasFiles() {
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

		/** @var \OCA\Encryption\Crypto\Crypt $cryptMock */
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

		$this->configMock = $this->createMock(IConfig::class);

		$this->configMock->expects($this->any())
			->method('getUserValue')
			->willReturnCallback([$this, 'getValueTester']);

		$this->configMock->expects($this->any())
			->method('setUserValue')
			->willReturnCallback([$this, 'setValueTester']);

		$this->instance = new Util($this->filesMock, $cryptMock, $userSessionMock, $this->configMock, $this->userManagerMock);
	}

	/**
	 * @param $userId
	 * @param $app
	 * @param $key
	 * @param $value
	 */
	public function setValueTester($userId, $app, $key, $value) {
		self::$tempStorage[$key] = $value;
	}

	/**
	 * @param $userId
	 * @param $app
	 * @param $key
	 * @param $default
	 * @return mixed
	 */
	public function getValueTester($userId, $app, $key, $default) {
		if (!empty(self::$tempStorage[$key])) {
			return self::$tempStorage[$key];
		}
		return $default ?: null;
	}

	/**
	 * @dataProvider dataTestIsMasterKeyEnabled
	 *
	 * @param string $value
	 * @param bool $expect
	 */
	public function testIsMasterKeyEnabled($value, $expect) {
		$this->configMock->expects($this->once())->method('getAppValue')
			->with('encryption', 'useMasterKey', '1')->willReturn($value);
		$this->assertSame($expect,
			$this->instance->isMasterKeyEnabled()
		);
	}

	public function dataTestIsMasterKeyEnabled() {
		return [
			['0', false],
			['1', true]
		];
	}

	/**
	 * @dataProvider dataTestShouldEncryptHomeStorage
	 * @param string $returnValue return value from getAppValue()
	 * @param bool $expected
	 */
	public function testShouldEncryptHomeStorage($returnValue, $expected) {
		$this->configMock->expects($this->once())->method('getAppValue')
			->with('encryption', 'encryptHomeStorage', '1')
			->willReturn($returnValue);

		$this->assertSame($expected,
			$this->instance->shouldEncryptHomeStorage());
	}

	public function dataTestShouldEncryptHomeStorage() {
		return [
			['1', true],
			['0', false]
		];
	}

	/**
	 * @dataProvider dataTestSetEncryptHomeStorage
	 * @param $value
	 * @param $expected
	 */
	public function testSetEncryptHomeStorage($value, $expected) {
		$this->configMock->expects($this->once())->method('setAppValue')
			->with('encryption', 'encryptHomeStorage', $expected);
		$this->instance->setEncryptHomeStorage($value);
	}

	public function dataTestSetEncryptHomeStorage() {
		return [
			[true, '1'],
			[false, '0']
		];
	}

	public function testGetStorage() {
		$return = $this->getMockBuilder(Storage::class)
			->disableOriginalConstructor()
			->getMock();

		$path = '/foo/bar.txt';
		$this->filesMock->expects($this->once())->method('getMount')->with($path)
			->willReturn($this->mountMock);
		$this->mountMock->expects($this->once())->method('getStorage')->willReturn($return);

		$this->assertEquals($return, $this->instance->getStorage($path));
	}
}
