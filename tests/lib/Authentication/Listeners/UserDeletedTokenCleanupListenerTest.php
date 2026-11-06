<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Listeners;

use Exception;
use OC\Authentication\Listeners\UserDeletedTokenCleanupListener;
use OC\Authentication\Token\Manager;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UserDeletedTokenCleanupListenerTest extends TestCase {
	/** @var UserDeletedTokenCleanupListener */
	private $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->listener = $this->createInstanceWithMocks(UserDeletedTokenCleanupListener::class);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();
		$this->getAutoMock(Manager::class)->expects($this->never())->method('invalidateTokensOfUser');
		$this->getAutoMock(LoggerInterface::class)->expects($this->never())->method('error');

		$this->listener->handle($event);
	}

	public function testHandleWithErrors(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$event = new UserDeletedEvent($user);
		$exception = new Exception('nope');
		$this->getAutoMock(Manager::class)->expects($this->once())
			->method('invalidateTokensOfUser')
			->with('user123', null)
			->willThrowException($exception);
		$this->getAutoMock(LoggerInterface::class)->expects($this->once())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandle(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$event = new UserDeletedEvent($user);
		$this->getAutoMock(Manager::class)->expects($this->once())
			->method('invalidateTokensOfUser')
			->with('user123', null);
		$this->getAutoMock(LoggerInterface::class)->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}
}
