<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\Db\ProviderUserAssignmentDao;
use OC\Authentication\TwoFactorAuth\Registry;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Authentication\TwoFactorAuth\IStatelessProvider;
use OCP\Authentication\TwoFactorAuth\RegistryEvent;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderDisabled;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserRegistered;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserUnregistered;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderUserDeleted;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use Test\TestCase;

class RegistryTest extends TestCase {
	private Registry $registry;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createInstanceWithMocks(Registry::class);
	}

	public function testGetProviderStates(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('user123');
		$state = [
			'twofactor_totp' => true,
		];
		$this->mocks[ProviderUserAssignmentDao::class]->expects($this->once())->method('getState')->willReturn($state);

		$actual = $this->registry->getProviderStates($user);

		$this->assertEquals($state, $actual);
	}

	public function testEnableProvider(): void {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);
		$user->expects($this->once())->method('getUID')->willReturn('user123');
		$provider->expects($this->once())->method('getId')->willReturn('p1');
		$this->mocks[ProviderUserAssignmentDao::class]->expects($this->once())->method('persist')->with('p1', 'user123',
			true);

		$this->mocks[IEventDispatcher::class]->expects($this->once())
			->method('dispatch')
			->with(
				$this->equalTo(IRegistry::EVENT_PROVIDER_ENABLED),
				$this->callback(function (RegistryEvent $e) use ($user, $provider) {
					return $e->getUser() === $user && $e->getProvider() === $provider;
				})
			);
		$this->mocks[IEventDispatcher::class]->expects($this->once())
			->method('dispatchTyped')
			->with(new TwoFactorProviderForUserRegistered(
				$user,
				$provider,
			));

		$this->registry->enableProviderFor($provider, $user);
	}

	public function testEnableStatelessProvider(): void {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IStatelessProvider::class);

		$this->mocks[ProviderUserAssignmentDao::class]->expects($this->never())->method('persist');

		$this->mocks[IEventDispatcher::class]->expects($this->never())->method('dispatch');
		$this->mocks[IEventDispatcher::class]->expects($this->never())->method('dispatchTyped');

		$this->registry->enableProviderFor($provider, $user);
	}

	public function testDisableProvider(): void {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IProvider::class);
		$user->expects($this->once())->method('getUID')->willReturn('user123');
		$provider->expects($this->once())->method('getId')->willReturn('p1');
		$this->mocks[ProviderUserAssignmentDao::class]->expects($this->once())->method('persist')->with('p1', 'user123',
			false);

		$this->mocks[IEventDispatcher::class]->expects($this->once())
			->method('dispatch')
			->with(
				$this->equalTo(IRegistry::EVENT_PROVIDER_DISABLED),
				$this->callback(function (RegistryEvent $e) use ($user, $provider) {
					return $e->getUser() === $user && $e->getProvider() === $provider;
				})
			);
		$this->mocks[IEventDispatcher::class]->expects($this->once())
			->method('dispatchTyped')
			->with(new TwoFactorProviderForUserUnregistered(
				$user,
				$provider,
			));

		$this->registry->disableProviderFor($provider, $user);
	}

	public function testDisableStatelessProvider(): void {
		$user = $this->createMock(IUser::class);
		$provider = $this->createMock(IStatelessProvider::class);

		$this->mocks[ProviderUserAssignmentDao::class]->expects($this->never())->method('persist');

		$this->mocks[IEventDispatcher::class]->expects($this->never())->method('dispatch');
		$this->mocks[IEventDispatcher::class]->expects($this->never())->method('dispatchTyped');

		$this->registry->disableProviderFor($provider, $user);
	}

	public function testDeleteUserData(): void {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())->method('getUID')->willReturn('user123');
		$this->mocks[ProviderUserAssignmentDao::class]->expects($this->once())
			->method('deleteByUser')
			->with('user123')
			->willReturn([
				[
					'provider_id' => 'twofactor_u2f',
				]
			]);

		$calls = [
			[new TwoFactorProviderDisabled('twofactor_u2f')],
			[new TwoFactorProviderUserDeleted($user, 'twofactor_u2f')],
		];
		$this->mocks[IEventDispatcher::class]->expects($this->exactly(2))
			->method('dispatchTyped')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->registry->deleteUserData($user);
	}

	public function testCleanUp(): void {
		$this->mocks[ProviderUserAssignmentDao::class]->expects($this->once())
			->method('deleteAll')
			->with('twofactor_u2f');

		$this->registry->cleanUp('twofactor_u2f');
	}
}
