<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\User\Listeners;

use OC\User\Listeners\UserQuotaChangedListener;
use OCP\EventDispatcher\Event;
use OCP\Files\Cache\ICache;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\User\Events\UserChangedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserQuotaChangedListenerTest extends TestCase {
	private IRootFolder&MockObject $rootFolder;
	private UserQuotaChangedListener $listener;

	protected function setUp(): void {
		parent::setUp();
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->listener = new UserQuotaChangedListener($this->rootFolder);
	}

	public function testIgnoresNonUserChangedEvent(): void {
		$this->rootFolder->expects($this->never())->method('getUserFolder');
		$this->listener->handle($this->createMock(Event::class));
	}

	public function testIgnoresNonQuotaFeature(): void {
		$user = $this->createMock(IUser::class);
		$event = new UserChangedEvent($user, 'displayName', 'Alice', 'Bob');

		$this->rootFolder->expects($this->never())->method('getUserFolder');
		$this->listener->handle($event);
	}

	public function testInvalidatesEtagOnQuotaChange(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$cache = $this->createMock(ICache::class);
		$cache->expects($this->once())
			->method('update')
			->with($this->isInt(), $this->callback(
				fn (array $data) => isset($data['etag']) && $data['etag'] !== ''
			));

		$storage = $this->createMock(IStorage::class);
		$storage->method('getCache')->willReturn($cache);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getStorage')->willReturn($storage);
		$userFolder->method('getId')->willReturn(42);

		$this->rootFolder->method('getUserFolder')->with('alice')->willReturn($userFolder);

		$event = new UserChangedEvent($user, 'quota', '5 GB', '1 GB');
		$this->listener->handle($event);
	}

	public function testSwallowsExceptionGracefully(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$this->rootFolder->method('getUserFolder')
			->willThrowException(new \Exception('Storage unavailable'));

		// Should not throw
		$event = new UserChangedEvent($user, 'quota', '5 GB', '1 GB');
		$this->listener->handle($event);
		$this->addToAssertionCount(1);
	}
}
