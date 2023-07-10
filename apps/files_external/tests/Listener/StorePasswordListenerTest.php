<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_External\Tests\Listener;

use OCA\Files_External\Lib\Auth\Password\LoginCredentials;
use OCA\Files_External\Listener\StorePasswordListener;
use OCP\IUser;
use OCP\Security\ICredentialsManager;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserLoggedInEvent;
use Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

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

	public function testClassicLoginSameCredentials() {
		$this->getMockedCredentialManager(
			[
				'user' => 'test',
				'password' => 'password',
			],
			new UserLoggedInEvent($this->mockedUser, 'test', 'password', false),
			null,
		);
	}

	public function testClassicLoginNewPassword() {
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

	public function testClassicLoginNewUser() {
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

	public function testSSOLogin() {
		$this->getMockedCredentialManager(
			[
				'user' => 'test',
				'password' => 'password',
			],
			new UserLoggedInEvent($this->mockedUser, 'test', null, false),
			null,
		);
	}

	public function testPasswordUpdated() {
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

	public function testUserLoginWithToken() {
		$this->getMockedCredentialManager(
			null,
			new UserLoggedInEvent($this->mockedUser, 'test', 'password', true),
			null,
		);
	}

	public function testNoInitialCredentials() {
		$this->getMockedCredentialManager(
			false,
			new PasswordUpdatedEvent($this->mockedUser, 'test', 'password'),
			null,
		);
	}
}
