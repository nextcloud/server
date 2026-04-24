<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Provisioning_API\Tests\Listener;

use OCA\Provisioning_API\Listener\UserDataCacheListener;
use OCP\EventDispatcher\Event;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUser;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserChangedEvent;
use OCP\User\Events\UserDeletedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserDataCacheListenerTest extends TestCase {

	private ICache&MockObject $cache;
	private UserDataCacheListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->cache = $this->createMock(ICache::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')
			->with('provisioning_api')
			->willReturn($this->cache);

		$this->listener = new UserDataCacheListener($cacheFactory);
	}

	private function makeUser(string $uid): IUser&MockObject {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		return $user;
	}

	public function testHandleUserChangedEventClearsCache(): void {
		$user = $this->makeUser('alice');
		$event = new UserChangedEvent($user, 'displayName', 'New Name', 'Old Name');

		$this->cache
			->expects($this->once())
			->method('clear')
			->with('user_data_alice');

		$this->listener->handle($event);
	}

	public function testHandleUserDeletedEventClearsCache(): void {
		$user = $this->makeUser('bob');
		$event = new UserDeletedEvent($user);

		$this->cache
			->expects($this->once())
			->method('clear')
			->with('user_data_bob');

		$this->listener->handle($event);
	}

	public function testHandlePasswordUpdatedEventClearsCache(): void {
		$user = $this->makeUser('carol');
		$event = new PasswordUpdatedEvent($user, 'newpassword');

		$this->cache
			->expects($this->once())
			->method('clear')
			->with('user_data_carol');

		$this->listener->handle($event);
	}

	public function testHandleUnrelatedEventDoesNothing(): void {
		$this->cache->expects($this->never())->method('clear');
		$this->cache->expects($this->never())->method('remove');

		$this->listener->handle(new Event());
	}
}
