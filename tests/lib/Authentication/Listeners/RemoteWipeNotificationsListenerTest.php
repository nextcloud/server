<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Events;

use DateTime;
use OC\Authentication\Events\RemoteWipeFinished;
use OC\Authentication\Events\RemoteWipeStarted;
use OC\Authentication\Listeners\RemoteWipeNotificationsListener;
use OC\Authentication\Token\IToken;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use Test\TestCase;

class RemoteWipeNotificationsListenerTest extends TestCase {
	/** @var IEventListener */
	private $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->listener = $this->createInstanceWithMocks(RemoteWipeNotificationsListener::class);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandleRemoteWipeStarted(): void {
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeStarted($token);
		$notification = $this->createMock(INotification::class);
		$this->mocks[INotificationManager::class]->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$notification->expects($this->once())
			->method('setApp')
			->with('auth')
			->willReturnSelf();
		$token->method('getUID')->willReturn('user123');
		$notification->expects($this->once())
			->method('setUser')
			->with('user123')
			->willReturnSelf();
		$now = new DateTime();
		$this->mocks[ITimeFactory::class]->method('getDateTime')->willReturn($now);
		$notification->expects($this->once())
			->method('setDateTime')
			->with($now)
			->willReturnSelf();
		$token->method('getId')->willReturn(123);
		$notification->expects($this->once())
			->method('setObject')
			->with('token', '123')
			->willReturnSelf();
		$token->method('getName')->willReturn('Token 1');
		$notification->expects($this->once())
			->method('setSubject')
			->with('remote_wipe_start', [
				'name' => 'Token 1'
			])
			->willReturnSelf();
		$this->mocks[INotificationManager::class]->expects($this->once())
			->method('notify');

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeFinished(): void {
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeFinished($token);
		$notification = $this->createMock(INotification::class);
		$this->mocks[INotificationManager::class]->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$notification->expects($this->once())
			->method('setApp')
			->with('auth')
			->willReturnSelf();
		$token->method('getUID')->willReturn('user123');
		$notification->expects($this->once())
			->method('setUser')
			->with('user123')
			->willReturnSelf();
		$now = new DateTime();
		$this->mocks[ITimeFactory::class]->method('getDateTime')->willReturn($now);
		$notification->expects($this->once())
			->method('setDateTime')
			->with($now)
			->willReturnSelf();
		$token->method('getId')->willReturn(123);
		$notification->expects($this->once())
			->method('setObject')
			->with('token', '123')
			->willReturnSelf();
		$token->method('getName')->willReturn('Token 1');
		$notification->expects($this->once())
			->method('setSubject')
			->with('remote_wipe_finish', [
				'name' => 'Token 1'
			])
			->willReturnSelf();
		$this->mocks[INotificationManager::class]->expects($this->once())
			->method('notify');

		$this->listener->handle($event);
	}
}
