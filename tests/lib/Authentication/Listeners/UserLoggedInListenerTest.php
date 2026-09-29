<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Listeners;

use OC\Authentication\Listeners\UserLoggedInListener;
use OC\Authentication\Token\Manager;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\User\Events\UserLoggedInEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserLoggedInListenerTest extends TestCase {
	private Manager&MockObject $manager;
	private IUser&MockObject $user;
	private UserLoggedInListener $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('user123');

		$this->listener = new UserLoggedInListener($this->manager);
	}

	public function testHandleUnrelated(): void {
		$this->manager->expects($this->never())->method('updatePasswords');
		$this->listener->handle(new Event());
	}

	public static function dataHandleSkipsPasswordUpdate(): array {
		return [
			'passwordless login' => [null, false],
			'empty password' => ['', false],
			'token login' => ['secret', true],
		];
	}

	#[DataProvider('dataHandleSkipsPasswordUpdate')]
	public function testHandleSkipsPasswordUpdate(?string $password, bool $isTokenLogin): void {
		$event = new UserLoggedInEvent($this->user, 'user123', $password, $isTokenLogin);
		$this->manager->expects($this->never())->method('updatePasswords');

		$this->listener->handle($event);
	}

	public function testHandleUpdatesPasswords(): void {
		$event = new UserLoggedInEvent($this->user, 'user123', 'secret', false);
		$this->manager->expects($this->once())
			->method('updatePasswords')
			->with('user123', 'secret');

		$this->listener->handle($event);
	}
}
