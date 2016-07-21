<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCA\Encryption\Tests;


use OCA\Encryption\Util;
use Test\TestCase;

class UtilTest extends TestCase {
	private static $tempStorage = [];

	/** @var \OCP\IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $configMock;

	/** @var \OC\Files\View|\PHPUnit_Framework_MockObject_MockObject */
	private $filesMock;

	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManagerMock;

	/** @var \OCP\Files\Mount\IMountPoint|\PHPUnit_Framework_MockObject_MockObject */
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
			->will($this->returnValue(true));

		$this->assertTrue($this->instance->userHasFiles('admin'));
	}

	protected function setUp() {
		parent::setUp();
		$this->mountMock = $this->getMock('\OCP\Files\Mount\IMountPoint');
		$this->filesMock = $this->getMock('OC\Files\View');
		$this->userManagerMock = $this->getMock('\OCP\IUserManager');

		/** @var \OCA\Encryption\Crypto\Crypt $cryptMock */
		$cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()
			->getMock();
		/** @var \OCP\ILogger $loggerMock */
		$loggerMock = $this->getMock('OCP\ILogger');
		/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject $userSessionMock */
		$userSessionMock = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->setMethods([
				'isLoggedIn',
				'getUID',
				'login',
				'logout',
				'setUser',
				'getUser'
			])
			->getMock();

		$userSessionMock->method('isLoggedIn')->will($this->returnValue(true));

		$userSessionMock->method('getUID')->will($this->returnValue('admin'));

		$userSessionMock->expects($this->any())
			->method($this->anything())
			->will($this->returnSelf());


		$this->configMock = $this->getMock('OCP\IConfig');

		$this->configMock->expects($this->any())
			->method('getUserValue')
			->will($this->returnCallback([$this, 'getValueTester']));

		$this->configMock->expects($this->any())
			->method('setUserValue')
			->will($this->returnCallback([$this, 'setValueTester']));

		$this->instance = new Util($this->filesMock, $cryptMock, $loggerMock, $userSessionMock, $this->configMock, $this->userManagerMock);
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
			->with('encryption', 'useMasterKey', '0')->willReturn($value);
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
		$return = $this->getMockBuilder('OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();

		$path = '/foo/bar.txt';
		$this->filesMock->expects($this->once())->method('getMount')->with($path)
			->willReturn($this->mountMock);
		$this->mountMock->expects($this->once())->method('getStorage')->willReturn($return);

		$this->assertEquals($return, $this->instance->getStorage($path));
	}

}
