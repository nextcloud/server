<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Listener;

use OCA\Files_External\Lib\Auth\Password\LoginCredentials;
use OCA\Files_External\Listener\StorePasswordListener;
use OCP\IUser;
use OCP\Security\ICredentialsManager;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserLoggedInEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 */
class StorePasswordListenerTest extends TestCase {
	/** @var MockObject|IUser */
	protected $mockedUser;

	protected function setUp(): void {
		parent::setUp();
		$this->mockedUser = $this->createMock(IUser::class);
		$this->mockedUser
			->expects($this->any())
			->method('getUID')
			->willReturn('test');
	}

	/**
	 * @param array|false|null $initialCredentials
	 * @param UserLoggedInEvent|PasswordUpdatedEvent $event
	 * @param array|null $expectedCredentials
	 */
	public function getMockedCredentialManager($initialCredentials, $event, $expectedCredentials) {
		$mockedCredentialsManager = $this->createMock(ICredentialsManager::class);

		if ($initialCredentials !== null) {
			$mockedCredentialsManager
				->expects($this->once())
				->method('retrieve')
				->with(
					$this->equalTo('test'),
					$this->equalTo(LoginCredentials::CREDENTIALS_IDENTIFIER),
				)
				->willReturn($initialCredentials);
		} else {
			$mockedCredentialsManager
				->expects($this->never())
				->method('retrieve');
		}

		if ($expectedCredentials !== null) {
			$mockedCredentialsManager
				->expects($this->once())
				->method('store')
				->with(
					$this->equalTo('test'),
					$this->equalTo(LoginCredentials::CREDENTIALS_IDENTIFIER),
					$this->equalTo($expectedCredentials),
				);
		} else {
			$mockedCredentialsManager
				->expects($this->never())
				->method('store');
		}

		$storePasswordListener = new StorePasswordListener($mockedCredentialsManager);
		$storePasswordListener->handle($event);
	}

	public function testClassicLoginSameCredentials(): void {
		$this->getMockedCredentialManager(
			[
				'user' => 'test',
				'password' => 'password',
			],
			new UserLoggedInEvent($this->mockedUser, 'test', 'password', false),
			null,
		);
	}

	public function testClassicLoginNewPassword(): void {
		$this->getMockedCredentialManager(
			[
				'user' => 'test',
				'password' => 'password',
			],
			new UserLoggedInEvent($this->mockedUser, 'test', 'password2', false),
			[
				'user' => 'test',
				'password' => 'password2',
			],
		);
	}

	public function testClassicLoginNewUser(): void {
		$this->getMockedCredentialManager(
			[
				'user' => 'test',
				'password' => 'password',
			],
			new UserLoggedInEvent($this->mockedUser, 'test2', 'password', false),
			[
				'user' => 'test2',
				'password' => 'password',
			],
		);
	}

	public function testSSOLogin(): void {
		$this->getMockedCredentialManager(
			[
				'user' => 'test',
				'password' => 'password',
			],
			new UserLoggedInEvent($this->mockedUser, 'test', null, false),
			null,
		);
	}

	public function testPasswordUpdated(): void {
		$this->getMockedCredentialManager(
			[
				'user' => 'test',
				'password' => 'password',
			],
			new PasswordUpdatedEvent($this->mockedUser, 'password2'),
			[
				'user' => 'test',
				'password' => 'password2',
			],
		);
	}

	public function testUserLoginWithToken(): void {
		$this->getMockedCredentialManager(
			null,
			new UserLoggedInEvent($this->mockedUser, 'test', 'password', true),
			null,
		);
	}

	public function testNoInitialCredentials(): void {
		$this->getMockedCredentialManager(
			false,
			new PasswordUpdatedEvent($this->mockedUser, 'test', 'password'),
			null,
		);
	}
}
