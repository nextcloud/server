<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Tests\Unit\Notification;

use OCA\Comments\Activity\Listener as ActivityListener;
use OCA\Comments\Listener\CommentsEventListener;
use OCA\Comments\Notification\Listener as NotificationListener;
use OCP\Comments\CommentsEvent;
use OCP\Comments\IComment;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class EventHandlerTest extends TestCase {
	protected ActivityListener&MockObject $activityListener;
	protected NotificationListener&MockObject $notificationListener;
	protected CommentsEventListener $eventHandler;

	protected function setUp(): void {
		parent::setUp();

		$this->activityListener = $this->createMock(ActivityListener::class);
		$this->notificationListener = $this->createMock(NotificationListener::class);

		$this->eventHandler = new CommentsEventListener($this->activityListener, $this->notificationListener);
	}

	public function testNotFiles(): void {
		/** @var IComment|MockObject $comment */
		$comment = $this->createMock(IComment::class);
		$comment->expects($this->once())
			->method('getObjectType')
			->willReturn('smiles');

		/** @var CommentsEvent|MockObject $event */
		$event = $this->createMock(CommentsEvent::class);
		$event->expects($this->once())
			->method('getComment')
			->willReturn($comment);
		$event->expects($this->never())
			->method('getEvent');

		$this->eventHandler->handle($event);
	}

	public static function handledProvider(): array {
		return [
			[CommentsEvent::EVENT_DELETE],
			[CommentsEvent::EVENT_UPDATE],
			[CommentsEvent::EVENT_PRE_UPDATE],
			[CommentsEvent::EVENT_ADD]
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('handledProvider')]
	public function testHandled(string $eventType): void {
		/** @var IComment|MockObject $comment */
		$comment = $this->createMock(IComment::class);
		$comment->expects($this->once())
			->method('getObjectType')
			->willReturn('files');

		/** @var CommentsEvent|MockObject $event */
		$event = $this->createMock(CommentsEvent::class);
		$event->expects($this->atLeastOnce())
			->method('getComment')
			->willReturn($comment);
		$event->expects($this->atLeastOnce())
			->method('getEvent')
			->willReturn($eventType);

		$this->notificationListener->expects($this->once())
			->method('evaluate')
			->with($event);

		$this->activityListener->expects($this->any())
			->method('commentEvent')
			->with($event);

		$this->eventHandler->handle($event);
	}
}
