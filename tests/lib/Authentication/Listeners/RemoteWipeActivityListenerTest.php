<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Events;

use OC\Activity\Event as IActivityEvent;
use OC\Authentication\Events\RemoteWipeFinished;
use OC\Authentication\Events\RemoteWipeStarted;
use OC\Authentication\Listeners\RemoteWipeActivityListener;
use OC\Authentication\Token\IToken;
use OCP\Activity\IManager as IActivityManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RemoteWipeActivityListenerTest extends TestCase {
	/** @var IActivityManager|MockObject */
	private $activityManager;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IEventListener */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->activityManager = $this->createMock(IActivityManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new RemoteWipeActivityListener(
			$this->activityManager,
			$this->logger
		);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();

		$this->listener->handle($event);

		$this->addToAssertionCount(1);
	}

	public function testHandleRemoteWipeStarted(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeStarted($token);
		$activityEvent = $this->createMock(IActivityEvent::class);
		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($activityEvent);
		$activityEvent->expects($this->once())
			->method('setApp')
			->with('core')
			->willReturnSelf();
		$activityEvent->expects($this->once())
			->method('setType')
			->with('security')
			->willReturnSelf();
		$token->method('getUID')->willReturn('user123');
		$activityEvent->expects($this->once())
			->method('setAuthor')
			->with('user123')
			->willReturnSelf();
		$activityEvent->expects($this->once())
			->method('setAffectedUser')
			->with('user123')
			->willReturnSelf();
		$token->method('getName')->willReturn('Token 1');
		$activityEvent->expects($this->once())
			->method('setSubject')
			->with('remote_wipe_start', ['name' => 'Token 1'])
			->willReturnSelf();
		$this->activityManager->expects($this->once())
			->method('publish');

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeStartedCanNotPublish(): void {
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeStarted($token);
		$this->activityManager->expects($this->once())
			->method('generateEvent');
		$this->activityManager->expects($this->once())
			->method('publish')
			->willThrowException(new \BadMethodCallException());

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeFinished(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeFinished($token);
		$activityEvent = $this->createMock(IActivityEvent::class);
		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($activityEvent);
		$activityEvent->expects($this->once())
			->method('setApp')
			->with('core')
			->willReturnSelf();
		$activityEvent->expects($this->once())
			->method('setType')
			->with('security')
			->willReturnSelf();
		$token->method('getUID')->willReturn('user123');
		$activityEvent->expects($this->once())
			->method('setAuthor')
			->with('user123')
			->willReturnSelf();
		$activityEvent->expects($this->once())
			->method('setAffectedUser')
			->with('user123')
			->willReturnSelf();
		$token->method('getName')->willReturn('Token 1');
		$activityEvent->expects($this->once())
			->method('setSubject')
			->with('remote_wipe_finish', ['name' => 'Token 1'])
			->willReturnSelf();
		$this->activityManager->expects($this->once())
			->method('publish');

		$this->listener->handle($event);
	}
}
