<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
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

namespace OCA\Encryption\Tests\Controller;

use OCA\Encryption\Controller\SettingsController;
use OCA\Encryption\Session;
use OCP\AppFramework\Http;
use Test\TestCase;

class SettingsControllerTest extends TestCase {

	/** @var SettingsController */
	private $controller;

	/** @var \OCP\IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $requestMock;

	/** @var \OCP\IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10nMock;

	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManagerMock;

	/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSessionMock;

	/** @var \OCA\Encryption\KeyManager|\PHPUnit_Framework_MockObject_MockObject */
	private $keyManagerMock;

	/** @var \OCA\Encryption\Crypto\Crypt|\PHPUnit_Framework_MockObject_MockObject */
	private $cryptMock;

	/** @var \OCA\Encryption\Session|\PHPUnit_Framework_MockObject_MockObject */
	private $sessionMock;

	/** @var \OCP\ISession|\PHPUnit_Framework_MockObject_MockObject */
	private $ocSessionMock;

	/** @var \OCA\Encryption\Util|\PHPUnit_Framework_MockObject_MockObject */
	private $utilMock;

	protected function setUp() {

		parent::setUp();

		$this->requestMock = $this->getMock('OCP\IRequest');

		$this->l10nMock = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()->getMock();

		$this->l10nMock->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($message) {
				return $message;
			}));

		$this->userManagerMock = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()->getMock();

		$this->keyManagerMock = $this->getMockBuilder('OCA\Encryption\KeyManager')
			->disableOriginalConstructor()->getMock();

		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()->getMock();

		$this->userSessionMock = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->setMethods([
				'isLoggedIn',
				'getUID',
				'login',
				'logout',
				'setUser',
				'getUser',
				'canChangePassword',
			])
			->getMock();

		$this->ocSessionMock = $this->getMockBuilder('OCP\ISession')->disableOriginalConstructor()->getMock();

		$this->userSessionMock->expects($this->any())
			->method('getUID')
			->willReturn('testUserUid');

		$this->userSessionMock->expects($this->any())
			->method($this->anything())
			->will($this->returnSelf());

		$this->sessionMock = $this->getMockBuilder('OCA\Encryption\Session')
			->disableOriginalConstructor()->getMock();

		$this->utilMock = $this->getMockBuilder('OCA\Encryption\Util')
			->disableOriginalConstructor()
			->getMock();

		$this->controller = new SettingsController(
			'encryption',
			$this->requestMock,
			$this->l10nMock,
			$this->userManagerMock,
			$this->userSessionMock,
			$this->keyManagerMock,
			$this->cryptMock,
			$this->sessionMock,
			$this->ocSessionMock,
			$this->utilMock
		);
	}

	/**
	 * test updatePrivateKeyPassword() if wrong new password was entered
	 */
	public function testUpdatePrivateKeyPasswordWrongNewPassword() {

		$oldPassword = 'old';
		$newPassword = 'new';

		$this->userSessionMock->expects($this->once())->method('getUID')->willReturn('uid');

		$this->userManagerMock
			->expects($this->exactly(2))
			->method('checkPassword')
			->willReturn(false);

		$result = $this->controller->updatePrivateKeyPassword($oldPassword, $newPassword);

		$data = $result->getData();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertSame('The current log-in password was not correct, please try again.',
			$data['message']);
	}

	/**
	 * test updatePrivateKeyPassword() if wrong old password was entered
	 */
	public function testUpdatePrivateKeyPasswordWrongOldPassword() {

		$oldPassword = 'old';
		$newPassword = 'new';

		$this->userManagerMock
			->expects($this->once())
			->method('checkPassword')
			->willReturn(true);

		$this->cryptMock
			->expects($this->once())
			->method('decryptPrivateKey')
			->willReturn(false);

		$result = $this->controller->updatePrivateKeyPassword($oldPassword, $newPassword);

		$data = $result->getData();

		$this->assertSame(Http::STATUS_BAD_REQUEST, $result->getStatus());
		$this->assertSame('The old password was not correct, please try again.',
			$data['message']);
	}

	/**
	 * test updatePrivateKeyPassword() with the correct old and new password
	 */
	public function testUpdatePrivateKeyPassword() {

		$oldPassword = 'old';
		$newPassword = 'new';

		$this->ocSessionMock->expects($this->once())
			->method('get')->with('loginname')->willReturn('testUser');

		$this->userManagerMock
			->expects($this->at(0))
			->method('checkPassword')
			->with('testUserUid', 'new')
			->willReturn(false);
		$this->userManagerMock
			->expects($this->at(1))
			->method('checkPassword')
			->with('testUser', 'new')
			->willReturn(true);



		$this->cryptMock
			->expects($this->once())
			->method('decryptPrivateKey')
			->willReturn('decryptedKey');

		$this->cryptMock
			->expects($this->once())
			->method('encryptPrivateKey')
			->willReturn('encryptedKey');

		$this->cryptMock
			->expects($this->once())
			->method('generateHeader')
			->willReturn('header.');

		// methods which must be called after successful changing the key password
		$this->keyManagerMock
			->expects($this->once())
			->method('setPrivateKey')
			->with($this->equalTo('testUserUid'), $this->equalTo('header.encryptedKey'));

		$this->sessionMock
			->expects($this->once())
			->method('setPrivateKey')
			->with($this->equalTo('decryptedKey'));

		$this->sessionMock
			->expects($this->once())
			->method('setStatus')
			->with($this->equalTo(Session::INIT_SUCCESSFUL));

		$result = $this->controller->updatePrivateKeyPassword($oldPassword, $newPassword);

		$data = $result->getData();

		$this->assertSame(Http::STATUS_OK, $result->getStatus());
		$this->assertSame('Private key password successfully updated.',
			$data['message']);
	}

	function testSetEncryptHomeStorage() {
		$value = true;
		$this->utilMock->expects($this->once())->method('setEncryptHomeStorage')->with($value);
		$this->controller->setEncryptHomeStorage($value);
	}

}
