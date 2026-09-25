<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Listeners;

use Exception;
use OC\Authentication\Listeners\UserDeletedTokenCleanupListener;
use OC\Authentication\Token\IToken;
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
		$this->mocks[Manager::class]->expects($this->never())->method('getTokenByUser');
		$this->mocks[LoggerInterface::class]->expects($this->never())->method('error');

		$this->listener->handle($event);
	}

	public function testHandleWithErrors(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$event = new UserDeletedEvent($user);
		$exception = new Exception('nope');
		$this->mocks[Manager::class]->expects($this->once())
			->method('getTokenByUser')
			->with('user123')
			->willThrowException($exception);
		$this->mocks[LoggerInterface::class]->expects($this->once())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandle(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$event = new UserDeletedEvent($user);
		$token1 = $this->createMock(IToken::class);
		$token1->method('getId')->willReturn(1);
		$token2 = $this->createMock(IToken::class);
		$token2->method('getId')->willReturn(2);
		$token3 = $this->createMock(IToken::class);
		$token3->method('getId')->willReturn(3);
		$this->mocks[Manager::class]->expects($this->once())
			->method('getTokenByUser')
			->with('user123')
			->willReturn([
				$token1,
				$token2,
				$token3,
			]);

		$calls = [
			['user123', 1],
			['user123', 2],
			['user123', 3],
		];
		$this->mocks[Manager::class]->expects($this->exactly(3))
			->method('invalidateTokenById')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});
		$this->mocks[LoggerInterface::class]->expects($this->never())
			->method('error');

		$this->listener->handle($event);
	}
}
