<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Listener;

use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCA\TwoFactorBackupCodes\Listener\ActivityPublisher;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ActivityPublisherTest extends TestCase {
	/** @var IManager|MockObject */
	private $activityManager;

	/** @var LoggerInterface */
	private $logger;

	/** @var ActivityPublisher */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->activityManager = $this->createMock(IManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->listener = new ActivityPublisher($this->activityManager, $this->logger);
	}

	public function testHandleGenericEvent(): void {
		$event = $this->createMock(Event::class);
		$this->activityManager->expects($this->never())
			->method('publish');

		$this->listener->handle($event);
	}

	public function testHandleCodesGeneratedEvent(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('fritz');
		$event = new CodesGenerated($user);
		$activityEvent = $this->createMock(IEvent::class);
		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($activityEvent);
		$activityEvent->expects($this->once())
			->method('setApp')
			->with('twofactor_backupcodes')
			->willReturnSelf();
		$activityEvent->expects($this->once())
			->method('setType')
			->with('security')
			->willReturnSelf();
		$activityEvent->expects($this->once())
			->method('setAuthor')
			->with('fritz')
			->willReturnSelf();
		$activityEvent->expects($this->once())
			->method('setAffectedUser')
			->with('fritz')
			->willReturnSelf();
		$this->activityManager->expects($this->once())
			->method('publish');

		$this->listener->handle($event);
	}
}
