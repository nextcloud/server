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



namespace OCA\Encryption\Tests\Hooks;


use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Hooks\UserHooks;
use Test\TestCase;

/**
 * Class UserHooksTest
 *
 * @group DB
 * @package OCA\Encryption\Tests\Hooks
 */
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
	private $userManagerMock;

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
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $loggerMock;
	/**
	 * @var UserHooks
	 */
	private $instance;

	private $params = ['uid' => 'testUser', 'password' => 'password'];

	public function testLogin() {
		$this->userSetupMock->expects($this->once())
			->method('setupUser')
			->willReturnOnConsecutiveCalls(true, false);

		$this->keyManagerMock->expects($this->once())
			->method('init')
			->with('testUser', 'password');

		$this->assertNull($this->instance->login($this->params));
	}

	public function testLogout() {
		$this->sessionMock->expects($this->once())
			->method('clear');
		$this->instance->logout();
		$this->assertTrue(true);
	}

	public function testPostCreateUser() {
		$this->userSetupMock->expects($this->once())
			->method('setupUser');

		$this->instance->postCreateUser($this->params);
		$this->assertTrue(true);
	}

	public function testPostDeleteUser() {
		$this->keyManagerMock->expects($this->once())
			->method('deletePublicKey')
			->with('testUser');

		$this->instance->postDeleteUser($this->params);
		$this->assertTrue(true);
	}

	/**
	 * @dataProvider dataTestPreSetPassphrase
	 */
	public function testPreSetPassphrase($canChange) {

		/** @var UserHooks | \PHPUnit_Framework_MockObject_MockObject  $instance */
		$instance = $this->getMockBuilder('OCA\Encryption\Hooks\UserHooks')
			->setConstructorArgs(
				[
					$this->keyManagerMock,
					$this->userManagerMock,
					$this->loggerMock,
					$this->userSetupMock,
					$this->userSessionMock,
					$this->utilMock,
					$this->sessionMock,
					$this->cryptMock,
					$this->recoveryMock
				]
			)
			->setMethods(['setPassphrase'])
			->getMock();

		$userMock = $this->getMock('OCP\IUser');

		$this->userManagerMock->expects($this->once())
			->method('get')
			->with($this->params['uid'])
			->willReturn($userMock);
		$userMock->expects($this->once())
			->method('canChangePassword')
			->willReturn($canChange);

		if ($canChange) {
			// in this case the password will be changed in the post hook
			$instance->expects($this->never())->method('setPassphrase');
		} else {
			// if user can't change the password we update the encryption
			// key password already in the pre hook
			$instance->expects($this->once())
				->method('setPassphrase')
				->with($this->params);
		}

		$instance->preSetPassphrase($this->params);
	}

	public function dataTestPreSetPassphrase() {
		return [
			[true],
			[false]
		];
	}

	public function testSetPassphrase() {
		$this->sessionMock->expects($this->exactly(4))
			->method('getPrivateKey')
			->willReturnOnConsecutiveCalls(true, false);

		$this->cryptMock->expects($this->exactly(4))
			->method('encryptPrivateKey')
			->willReturn(true);

		$this->cryptMock->expects($this->any())
			->method('generateHeader')
			->willReturn(Crypt::HEADER_START . ':Cipher:test:' . Crypt::HEADER_END);

		$this->keyManagerMock->expects($this->exactly(4))
			->method('setPrivateKey')
			->willReturnCallback(function ($user, $key) {
				$header = substr($key, 0, strlen(Crypt::HEADER_START));
				$this->assertSame(
					Crypt::HEADER_START,
					$header, 'every encrypted file should start with a header');
			});

		$this->assertNull($this->instance->setPassphrase($this->params));
		$this->params['recoveryPassword'] = 'password';

		$this->recoveryMock->expects($this->exactly(3))
			->method('isRecoveryEnabledForUser')
			->with('testUser')
			->willReturnOnConsecutiveCalls(true, false);


		$this->instance = $this->getMockBuilder('OCA\Encryption\Hooks\UserHooks')
			->setConstructorArgs(
				[
					$this->keyManagerMock,
					$this->userManagerMock,
					$this->loggerMock,
					$this->userSetupMock,
					$this->userSessionMock,
					$this->utilMock,
					$this->sessionMock,
					$this->cryptMock,
					$this->recoveryMock
				]
			)->setMethods(['initMountPoints'])->getMock();

		$this->instance->expects($this->exactly(3))->method('initMountPoints');

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

	public function testSetPasswordNoUser() {
		$this->sessionMock->expects($this->once())
			->method('getPrivateKey')
			->willReturn(true);

		$userSessionMock = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();

		$userSessionMock->expects($this->any())->method('getUser')->will($this->returnValue(null));

		$this->recoveryMock->expects($this->once())
			->method('isRecoveryEnabledForUser')
			->with('testUser')
			->willReturn(false);

		$userHooks = $this->getMockBuilder('OCA\Encryption\Hooks\UserHooks')
			->setConstructorArgs(
				[
					$this->keyManagerMock,
					$this->userManagerMock,
					$this->loggerMock,
					$this->userSetupMock,
					$userSessionMock,
					$this->utilMock,
					$this->sessionMock,
					$this->cryptMock,
					$this->recoveryMock
				]
			)->setMethods(['initMountPoints'])->getMock();

		/** @var \OCA\Encryption\Hooks\UserHooks $userHooks */
		$this->assertNull($userHooks->setPassphrase($this->params));
	}

	public function testPostPasswordReset() {
		$this->keyManagerMock->expects($this->once())
			->method('deleteUserKeys')
			->with('testUser');

		$this->userSetupMock->expects($this->once())
			->method('setupUser')
			->with('testUser', 'password');

		$this->instance->postPasswordReset($this->params);
		$this->assertTrue(true);
	}

	protected function setUp() {
		parent::setUp();
		$this->loggerMock = $this->getMock('OCP\ILogger');
		$this->keyManagerMock = $this->getMockBuilder('OCA\Encryption\KeyManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userManagerMock = $this->getMockBuilder('OCP\IUserManager')
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
		$this->utilMock->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);

		$this->instance = $this->getMockBuilder('OCA\Encryption\Hooks\UserHooks')
			->setConstructorArgs(
				[
					$this->keyManagerMock,
					$this->userManagerMock,
					$this->loggerMock,
					$this->userSetupMock,
					$this->userSessionMock,
					$this->utilMock,
					$this->sessionMock,
					$this->cryptMock,
					$this->recoveryMock
				]
			)->setMethods(['setupFS'])->getMock();

	}

}
