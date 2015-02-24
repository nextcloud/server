<?php
/**
 * @author Clark Tomlinson  <fallen013@gmail.com>
 * @since 3/5/15, 10:53 AM
 * @link http:/www.clarkt.com
 * @copyright Clark Tomlinson © 2015
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
	 * @var
	 */
	private $userId;
	/**
	 * @var
	 */
	private $dummyKeys;

	public function setUp() {
		parent::setUp();
		$keyStorageMock = $this->getMock('OCP\Encryption\IKeyStorage');
		$cryptMock = $this->getMockBuilder('OCA\Encryption\Crypt')
			->disableOriginalConstructor()
			->getMock();
		$configMock = $this->getMock('OCP\IConfig');
		$userMock = $this->getMock('OCP\IUser');
		$userMock->expects($this->once())
			->method('getUID')
			->will($this->returnValue('admin'));
		$this->userId = 'admin';
		$this->instance = new KeyManager($keyStorageMock, $cryptMock, $configMock, $userMock);

		$this->dummyKeys = ['public' => 'randomweakpublickeyhere',
			'private' => 'randomweakprivatekeyhere'];
	}

	/**
	 * @expectedException OC\Encryption\Exceptions\PrivateKeyMissingException
	 */
	public function testGetPrivateKey() {
		$this->assertFalse($this->instance->getPrivateKey($this->userId));
	}

	/**
	 * @expectedException OC\Encryption\Exceptions\PublicKeyMissingException
	 */
	public function testGetPublicKey() {
		$this->assertFalse($this->instance->getPublicKey($this->userId));
	}

	/**
	 *
	 */
	public function testRecoveryKeyExists() {
		$this->assertFalse($this->instance->recoveryKeyExists());
	}

	/**
	 *
	 */
	public function testCheckRecoveryKeyPassword() {
		$this->assertFalse($this->instance->checkRecoveryPassword('pass'));
	}

	public function testSetPublicKey() {

		$this->assertTrue($this->instance->setPublicKey($this->userId, $this->dummyKeys['public']));
	}

	public function testSetPrivateKey() {
		$this->assertTrue($this->instance->setPrivateKey($this->userId, $this->dummyKeys['private']));
	}

	public function testUserHasKeys() {
		$this->assertFalse($this->instance->userHasKeys($this->userId));
	}


}
