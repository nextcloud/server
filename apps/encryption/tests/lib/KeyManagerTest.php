<?php
/**
 * @author Clark Tomlinson  <fallen013@gmail.com>
 * @since 3/5/15, 10:53 AM
 * @link http:/www.clarkt.com
 * @copyright Clark Tomlinson © 2015
 *
 */

namespace OCA\Encryption\Tests;


use OC\Files\View;
use OCA\Encryption\KeyManager;
use Test\TestCase;

class KeyManagerTest extends TestCase {
	/**
	 * @var bool
	 */
	private static $trashbinState;
	/**
	 * @var KeyManager
	 */
	private $instance;
	/**
	 * @var string
	 */
	private static $testUser = 'test-keyManager-user.dot';
	/**
	 * @var
	 */
	private $dummyKeys;
	/**
	 * @var string
	 */
	private $userId;
	/**
	 * @var string
	 */
	private $userPassword;
	/**
	 * @var \OC\Files\View
	 */
	private $view;
	/**
	 * @var string
	 */
	private $dataDir;

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

	/**
	 *
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// Remember files_trashbin state
		self::$trashbinState = \OC_App::isEnabled('files_trashbin');

		// We dont want tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');

		$userManager = \OC::$server->getUserManager();
		$userManager->createUser(self::$testUser,
			self::$testUser);

		// Create test user
		parent::loginAsUser(self::$testUser);
	}

	public function setUp() {
		parent::setUp();
		$this->userId = 'user1';
		$this->systemKeyId = 'systemKeyId';
		$this->keyStorageMock = $this->getMock('OCP\Encryption\Keys\IStorage');

		/*
		$keyStorageMock->method('getUserKey')
			->will($this->returnValue(false));
		$keyStorageMock->method('setUserKey')
			->will($this->returnValue(true));
		 */

		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()
			->getMock();
		$this->configMock = $this->getMock('OCP\IConfig');
		$this->configMock->expects($this->any())
			->method('getAppValue')
			->willReturn($this->systemKeyId);
		$this->userMock = $this->getMock('OCP\IUserSession');

		/*
		$userMock
			->method('getUID')
			->will($this->returnValue('admin'));
		 */

		$this->sessionMock = $this->getMockBuilder('OCA\Encryption\Session')
			->disableOriginalConstructor()
			->getMock();
		$this->logMock = $this->getMock('OCP\ILogger');
		$this->utilMock = $this->getMockBuilder('OCA\Encryption\Util')
			->disableOriginalConstructor()
			->getMock();
	}

	public function testDeleteShareKey() {
		$this->keyStorageMock->expects($this->any())
			->method('deleteFileKey')
			->with($this->equalTo('/path'), $this->equalTo('keyId.shareKey'))
			->willReturn(true);
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertTrue(
			$keymanager->deleteShareKey('/path', 'keyId')
		);
	}


	public function testGetPrivateKey() {
		$this->keyStorageMock->expects($this->any())
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->equalTo('privateKey'))
			->willReturn('privateKey');
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertSame('privateKey',
			$keymanager->getPrivateKey($this->userId)
		);
	}

	public function testGetPublicKey() {
		$this->keyStorageMock->expects($this->any())
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->equalTo('publicKey'))
			->willReturn('publicKey');
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertSame('publicKey',
			$keymanager->getPublicKey($this->userId)
		);
	}

	public function testRecoveryKeyExists() {
		$this->keyStorageMock->expects($this->any())
			->method('getSystemUserKey')
			->with($this->equalTo($this->systemKeyId . '.publicKey'))
			->willReturn('recoveryKey');
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertTrue($keymanager->recoveryKeyExists());
	}

	/**
	 *
	 */
	public function testCheckRecoveryKeyPassword() {
		$this->keyStorageMock->expects($this->any())
			->method('getSystemUserKey')
			->with($this->equalTo($this->systemKeyId . '.privateKey'))
			->willReturn('recoveryKey');
		$this->cryptMock->expects($this->any())
			->method('decryptPrivateKey')
			->with($this->equalTo('recoveryKey'), $this->equalTo('pass'))
			->willReturn('decryptedRecoveryKey');
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertTrue($keymanager->checkRecoveryPassword('pass'));
	}


	public function testSetPublicKey() {
		$this->keyStorageMock->expects($this->any())
			->method('setUserKey')
			->with(
				$this->equalTo($this->userId),
				$this->equalTo('publicKey'),
				$this->equalTo('key'))
			->willReturn(true);
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertTrue(
			$keymanager->setPublicKey($this->userId, 'key')
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
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertTrue(
			$keymanager->setPrivateKey($this->userId, 'key')
		);
	}

	public function testUserHasKeys() {
		$this->keyStorageMock->expects($this->exactly(2))
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->anything())
			->willReturn('key');
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertTrue(
			$keymanager->userHasKeys($this->userId)
		);
	}

	/**
	 *
	 */
	public function testInit() {
		$this->keyStorageMock->expects($this->any())
			->method('getUserKey')
			->with($this->equalTo($this->userId), $this->equalTo('privateKey'))
			->willReturn('privateKey');
		$this->cryptMock->expects($this->any())
			->method('decryptPrivateKey')
			->with($this->equalTo('privateKey'), $this->equalTo('pass'))
			->willReturn('decryptedPrivateKey');
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertTrue(
			$keymanager->init($this->userId, 'pass')
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
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertTrue(
			$keymanager->setRecoveryKey('pass', array('publicKey' => 'publicKey', 'privateKey' => 'privateKey'))
		);
	}

	public function setSystemPrivateKey() {
		$this->keyStorageMock->expects($this->exactly(1))
			->method('setSystemUserKey')
			->with($this->equalTo('keyId.privateKey'), $this->equalTo('key'))
			->willReturn(true);
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertTrue(
			$keymanager->setSystemPrivateKey('keyId', 'key')
		);
	}

	public function getSystemPrivateKey() {
		$this->keyStorageMock->expects($this->exactly(1))
			->method('setSystemUserKey')
			->with($this->equalTo('keyId.privateKey'))
			->willReturn('systemPrivateKey');
		$keymanager = new KeyManager(
			$this->keyStorageMock,
			$this->cryptMock,
			$this->configMock,
			$this->userMock,
			$this->sessionMock,
			$this->logMock,
			$this->utilMock);

		$this->assertSame('systemPrivateKey',
			$keymanager->getSystemPrivateKey('keyId')
		);
	}


}
