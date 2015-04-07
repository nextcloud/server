<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 */



namespace OCA\Encryption\Tests\Hooks;


use OCA\Encryption\Hooks\UserHooks;
use Test\TestCase;

class UserHooksTest extends TestCase {
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $utilMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $recoveryMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $sessionMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $keyManagerMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $userSetupMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $userSessionMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $cryptMock;
	/**
	 * @var UserHooks
	 */
	private $instance;

	private $params = ['uid' => 'testUser', 'password' => 'password'];

	public function testLogin() {
		$this->userSetupMock->expects($this->exactly(2))
			->method('setupUser')
			->willReturnOnConsecutiveCalls(true, false);

		$this->keyManagerMock->expects($this->once())
			->method('init')
			->with('testUser', 'password');

		$this->assertNull($this->instance->login($this->params));
		$this->assertFalse($this->instance->login($this->params));
	}

	public function testLogout() {
		$this->sessionMock->expects($this->once())
			->method('clear');

		$this->assertNull($this->instance->logout());
	}

	public function testPostCreateUser() {
		$this->userSetupMock->expects($this->once())
			->method('setupUser');

		$this->assertNull($this->instance->postCreateUser($this->params));
	}

	public function testPostDeleteUser() {
		$this->keyManagerMock->expects($this->once())
			->method('deletePublicKey')
			->with('testUser');

		$this->assertNull($this->instance->postDeleteUser($this->params));
	}

	public function testPreSetPassphrase() {
		$this->userSessionMock->expects($this->once())
			->method('canChangePassword');

		$this->assertNull($this->instance->preSetPassphrase($this->params));
	}

	public function testSetPassphrase() {
		$this->sessionMock->expects($this->exactly(4))
			->method('getPrivateKey')
			->willReturnOnConsecutiveCalls(true, false);

		$this->cryptMock->expects($this->exactly(4))
			->method('symmetricEncryptFileContent')
			->willReturn(true);

		$this->keyManagerMock->expects($this->exactly(4))
			->method('setPrivateKey');

		$this->assertNull($this->instance->setPassphrase($this->params));
		$this->params['recoveryPassword'] = 'password';

		$this->recoveryMock->expects($this->exactly(3))
			->method('isRecoveryEnabledForUser')
			->with('testUser')
			->willReturnOnConsecutiveCalls(true, false);


		// Test first if statement
		$this->assertNull($this->instance->setPassphrase($this->params));

		// Test Second if conditional
		$this->keyManagerMock->expects($this->exactly(2))
			->method('userHasKeys')
			->with('testUser')
			->willReturn(true);

		$this->assertNull($this->instance->setPassphrase($this->params));

		// Test third and final if condition
		$this->utilMock->expects($this->once())
			->method('userHasFiles')
			->with('testUser')
			->willReturn(false);

		$this->cryptMock->expects($this->once())
			->method('createKeyPair');

		$this->keyManagerMock->expects($this->once())
			->method('setPrivateKey');

		$this->recoveryMock->expects($this->once())
			->method('recoverUsersFiles')
			->with('password', 'testUser');

		$this->assertNull($this->instance->setPassphrase($this->params));
	}

	public function testPostPasswordReset() {
		$this->keyManagerMock->expects($this->once())
			->method('replaceUserKeys')
			->with('testUser');

		$this->userSetupMock->expects($this->once())
			->method('setupServerSide')
			->with('testUser', 'password');

		$this->assertNull($this->instance->postPasswordReset($this->params));
	}

	protected function setUp() {
		parent::setUp();
		$loggerMock = $this->getMock('OCP\ILogger');
		$this->keyManagerMock = $this->getMockBuilder('OCA\Encryption\KeyManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSetupMock = $this->getMockBuilder('OCA\Encryption\Users\Setup')
			->disableOriginalConstructor()
			->getMock();

		$this->userSessionMock = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->setMethods([
				'isLoggedIn',
				'getUID',
				'login',
				'logout',
				'setUser',
				'getUser',
				'canChangePassword'
			])
			->getMock();

		$this->userSessionMock->expects($this->any())->method('getUID')->will($this->returnValue('testUser'));

		$this->userSessionMock->expects($this->any())
			->method($this->anything())
			->will($this->returnSelf());

		$utilMock = $this->getMockBuilder('OCA\Encryption\Util')
			->disableOriginalConstructor()
			->getMock();

		$sessionMock = $this->getMockBuilder('OCA\Encryption\Session')
			->disableOriginalConstructor()
			->getMock();

		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()
			->getMock();
		$recoveryMock = $this->getMockBuilder('OCA\Encryption\Recovery')
			->disableOriginalConstructor()
			->getMock();

		$this->sessionMock = $sessionMock;
		$this->recoveryMock = $recoveryMock;
		$this->utilMock = $utilMock;
		$this->instance = new UserHooks($this->keyManagerMock,
			$loggerMock,
			$this->userSetupMock,
			$this->userSessionMock,
			$this->utilMock,
			$this->sessionMock,
			$this->cryptMock,
			$this->recoveryMock
		);

	}

}
