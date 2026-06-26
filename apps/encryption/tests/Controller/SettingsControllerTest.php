<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests\Controller;

use OCA\Encryption\Controller\SettingsController;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SettingsControllerTest extends TestCase {

	protected SettingsController $controller;

	protected IRequest&MockObject $requestMock;
	protected IL10N&MockObject $l10nMock;
	protected IUserManager&MockObject $userManagerMock;
	protected IUserSession&MockObject $userSessionMock;
	protected KeyManager&MockObject $keyManagerMock;
	protected Crypt&MockObject $cryptMock;
	protected Session&MockObject $sessionMock;
	protected IUser&MockObject $user;
	protected ISession&MockObject $ocSessionMock;
	protected Util&MockObject $utilMock;

	protected function setUp(): void {
		parent::setUp();

		$this->requestMock = $this->createMock(IRequest::class);

		$this->l10nMock = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()->getMock();

		$this->l10nMock->expects($this->any())
			->method('t')
			->willReturnCallback(function ($message) {
				return $message;
			});

		$this->userManagerMock = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()->getMock();

		$this->keyManagerMock = $this->getMockBuilder(KeyManager::class)
			->disableOriginalConstructor()->getMock();

		$this->cryptMock = $this->getMockBuilder(Crypt::class)
			->disableOriginalConstructor()->getMock();

		$this->ocSessionMock = $this->getMockBuilder(ISession::class)->disableOriginalConstructor()->getMock();

		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('testUserUid');

		$this->userSessionMock = $this->createMock(IUserSession::class);
		$this->userSessionMock->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$this->sessionMock = $this->getMockBuilder(Session::class)
			->disableOriginalConstructor()->getMock();

		$this->utilMock = $this->getMockBuilder(Util::class)
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
	public function testUpdatePrivateKeyPasswordWrongNewPassword(): void {
		$oldPassword = 'old';
		$newPassword = 'new';

		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('uid');

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
	public function testUpdatePrivateKeyPasswordWrongOldPassword(): void {
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
	public function testUpdatePrivateKeyPassword(): void {
		$oldPassword = 'old';
		$newPassword = 'new';

		$this->ocSessionMock->expects($this->once())
			->method('get')
			->with('loginname')
			->willReturn('testUser');

		$this->userManagerMock
			->expects($this->exactly(2))
			->method('checkPassword')
			->willReturnMap([
				['testUserUid', 'new', false],
				['testUser', 'new', true],
			]);

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

	public function testSetEncryptHomeStorage(): void {
		$value = true;
		$this->utilMock->expects($this->once())->method('setEncryptHomeStorage')->with($value);
		$this->controller->setEncryptHomeStorage($value);
	}
}
