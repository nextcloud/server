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
		$keyStorageMock = $this->getMock('OCP\Encryption\Keys\IStorage');
		$keyStorageMock->method('getUserKey')
			->will($this->returnValue(false));
		$keyStorageMock->method('setUserKey')
			->will($this->returnValue(true));
		$cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()
			->getMock();
		$configMock = $this->getMock('OCP\IConfig');
		$userMock = $this->getMock('OCP\IUserSession');
		$userMock
			->method('getUID')
			->will($this->returnValue('admin'));
		$sessionMock = $this->getMock('OCP\ISession');
		$logMock = $this->getMock('OCP\ILogger');
		$recoveryMock = $this->getMockBuilder('OCA\Encryption\Recovery')
			->disableOriginalConstructor()
			->getMock();

		$this->instance = new KeyManager($keyStorageMock,
			$cryptMock,
			$configMock,
			$userMock,
			$sessionMock,
			$logMock,
			$recoveryMock);

		self::loginAsUser(self::$testUser);
		$this->userId = self::$testUser;
		$this->userPassword = self::$testUser;
		$this->view = new View('/');

		$this->dummyKeys = [
			'privateKey' => 'superinsecureprivatekey',
			'publicKey' => 'superinsecurepublickey'
		];


		$userManager = \OC::$server->getUserManager();

		$userHome = $userManager->get($this->userId)->getHome();

		$this->dataDir = str_replace('/' . $this->userId, '', $userHome);
	}

	protected function tearDown() {
		parent::tearDown();
		$this->view->deleteAll('/' . self::$testUser . '/files_encryption/keys');
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		// Cleanup Test user
		\OC::$server->getUserManager()->get(self::$testUser)->delete();
		// Reset app files_trashbin
		if (self::$trashbinState) {
			\OC_App::enable('files_trashbin');
		}
	}


	/**
	 * @expectedException \OC\Encryption\Exceptions\PrivateKeyMissingException
	 */
	public function testGetPrivateKey() {
		$this->assertFalse($this->instance->getPrivateKey($this->userId));
	}

	/**
	 * @expectedException \OC\Encryption\Exceptions\PublicKeyMissingException
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

	/**
	 *
	 */
	public function testSetPublicKey() {

		$this->assertTrue($this->instance->setPublicKey($this->userId,
			$this->dummyKeys['publicKey']));
	}

	/**
	 *
	 */
	public function testSetPrivateKey() {
		$this->assertTrue($this->instance->setPrivateKey($this->userId,
			$this->dummyKeys['privateKey']));
	}

	/**
	 *
	 */
	public function testUserHasKeys() {
		$this->assertFalse($this->instance->userHasKeys($this->userId));
	}

	/**
	 *
	 */
	public function testInit() {
		$this->assertFalse($this->instance->init($this->userId, 'pass'));
	}


}
