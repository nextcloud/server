<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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


use OCA\Encryption\KeyManager;
use Test\TestCase;

class KeyManagerTest extends TestCase {
	/**
	 * @var KeyManager
	 */
	private $instance;
	/**
	 * @var string
	 */
	private $userId;

	/** @var string */
	private $systemKeyId;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $keyStorageMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $cryptMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $userMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $sessionMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $logMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $utilMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $configMock;

	public function setUp() {
		parent::setUp();
		$this->userId = 'user1';
		$this->systemKeyId = 'systemKeyId';
		$this->keyStorageMock = $this->getMock('OCP\Encryption\Keys\IStorage');
		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()
			->getMock();
		$this->configMock = $this->getMock('OCP\IConfig');
		$this->configMock->expects($this->any())
			->method('getAppValue')
			->willReturn($this->systemKeyId);
		$this->userMock = $this->getMock('OCP\IUserSession');
		$this->sessionMock = $this->getMockBuilder('OCA\Encryption\Session')
			->disableOriginalConstructor()
			->getMock();
		$this->logMock = $this->getMock('OCP\ILogger');
		$this->utilMock = $this->getMockBuilder('OCA\Encryption\Util')
			->disableOriginalConstructor()
			->getMock();

		$this->instance = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);
	}

	public function testDeleteShareKey() {
		$this->keyStorageMock->expects($this->any())
			->method('deleteFileKey')
			->with($this->equalTo('/path'), $this->equalTo('keyId.shareKey'))
			->willReturn(true);

		$this->assertTrue(
			$this->instance->deleteShareKey('/path', 'keyId')
		);
	}

	public function testGetPrivateKey() {
		$this->keyStorageMock->expects($this->any())
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->equalTo('privateKey'))
			->willReturn('privateKey');


		$this->assertSame('privateKey',
			$this->instance->getPrivateKey($this->userId)
		);
	}

	public function testGetPublicKey() {
		$this->keyStorageMock->expects($this->any())
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->equalTo('publicKey'))
			->willReturn('publicKey');


		$this->assertSame('publicKey',
			$this->instance->getPublicKey($this->userId)
		);
	}

	public function testRecoveryKeyExists() {
		$this->keyStorageMock->expects($this->any())
			->method('getSystemUserKey')
			->with($this->equalTo($this->systemKeyId . '.publicKey'))
			->willReturn('recoveryKey');


		$this->assertTrue($this->instance->recoveryKeyExists());
	}

	public function testCheckRecoveryKeyPassword() {
		$this->keyStorageMock->expects($this->any())
			->method('getSystemUserKey')
			->with($this->equalTo($this->systemKeyId . '.privateKey'))
			->willReturn('recoveryKey');
		$this->cryptMock->expects($this->any())
			->method('decryptPrivateKey')
			->with($this->equalTo('recoveryKey'), $this->equalTo('pass'))
			->willReturn('decryptedRecoveryKey');

		$this->assertTrue($this->instance->checkRecoveryPassword('pass'));
	}

	public function testSetPublicKey() {
		$this->keyStorageMock->expects($this->any())
			->method('setUserKey')
			->with(
				$this->equalTo($this->userId),
				$this->equalTo('publicKey'),
				$this->equalTo('key'))
			->willReturn(true);


		$this->assertTrue(
			$this->instance->setPublicKey($this->userId, 'key')
		);
	}

	public function testSetPrivateKey() {
		$this->keyStorageMock->expects($this->any())
			->method('setUserKey')
			->with(
				$this->equalTo($this->userId),
				$this->equalTo('privateKey'),
				$this->equalTo('key'))
			->willReturn(true);


		$this->assertTrue(
			$this->instance->setPrivateKey($this->userId, 'key')
		);
	}

	public function testUserHasKeys() {
		$this->keyStorageMock->expects($this->exactly(2))
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->anything())
			->willReturn('key');


		$this->assertTrue(
			$this->instance->userHasKeys($this->userId)
		);
	}

	public function testInit() {
		$this->keyStorageMock->expects($this->any())
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->equalTo('privateKey'))
			->willReturn('privateKey');
		$this->cryptMock->expects($this->any())
			->method('decryptPrivateKey')
			->with($this->equalTo('privateKey'), $this->equalTo('pass'))
			->willReturn('decryptedPrivateKey');


		$this->assertTrue(
			$this->instance->init($this->userId, 'pass')
		);

	}

	public function testSetRecoveryKey() {
		$this->keyStorageMock->expects($this->exactly(2))
			->method('setSystemUserKey')
			->willReturn(true);
		$this->cryptMock->expects($this->any())
			->method('symmetricEncryptFileContent')
			->with($this->equalTo('privateKey'), $this->equalTo('pass'))
			->willReturn('decryptedPrivateKey');


		$this->assertTrue(
			$this->instance->setRecoveryKey('pass',
				array('publicKey' => 'publicKey', 'privateKey' => 'privateKey'))
		);
	}

	public function testSetSystemPrivateKey() {
		$this->keyStorageMock->expects($this->exactly(1))
			->method('setSystemUserKey')
			->with($this->equalTo('keyId.privateKey'), $this->equalTo('key'))
			->willReturn(true);


		$this->assertTrue(
			$this->instance->setSystemPrivateKey('keyId', 'key')
		);
	}

	public function testGetSystemPrivateKey() {
		$this->keyStorageMock->expects($this->exactly(1))
			->method('getSystemUserKey')
			->with($this->equalTo('keyId.privateKey'))
			->willReturn('systemPrivateKey');


		$this->assertSame('systemPrivateKey',
			$this->instance->getSystemPrivateKey('keyId')
		);
	}

	public function testGetEncryptedFileKey() {
		$this->keyStorageMock->expects($this->once())
			->method('getFileKey')
			->with('/', 'fileKey')
			->willReturn(true);

		$this->assertTrue($this->instance->getEncryptedFileKey('/'));
	}

	public function testGetFileKey() {
		$this->keyStorageMock->expects($this->exactly(4))
			->method('getFileKey')
			->willReturn(true);

		$this->keyStorageMock->expects($this->once())
			->method('getSystemUserKey')
			->willReturn(true);

		$this->cryptMock->expects($this->once())
			->method('symmetricDecryptFileContent')
			->willReturn(true);

		$this->cryptMock->expects($this->once())
			->method('multiKeyDecrypt')
			->willReturn(true);

		$this->assertTrue($this->instance->getFileKey('/', null));
		$this->assertEmpty($this->instance->getFileKey('/', $this->userId));
	}

	public function testDeletePrivateKey() {
		$this->keyStorageMock->expects($this->once())
			->method('deleteUserKey')
			->with('user1', 'privateKey')
			->willReturn(true);

		$this->assertTrue(\Test_Helper::invokePrivate($this->instance,
			'deletePrivateKey',
			[$this->userId]));
	}

	public function testDeleteAllFileKeys() {
		$this->keyStorageMock->expects($this->once())
			->method('deleteAllFileKeys')
			->willReturn(true);

		$this->assertTrue($this->instance->deleteAllFileKeys('/'));
	}
}
