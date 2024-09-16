<?php
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
use Test\TestCase;

class EventHandlerTest extends TestCase {
	/** @var CommentsEventListener */
	protected $eventHandler;

	/** @var ActivityListener|\PHPUnit\Framework\MockObject\MockObject */
	protected $activityListener;

	/** @var NotificationListener|\PHPUnit\Framework\MockObject\MockObject */
	protected $notificationListener;

	protected function setUp(): void {
		parent::setUp();

		$this->activityListener = $this->getMockBuilder(ActivityListener::class)
			->disableOriginalConstructor()
			->getMock();

		$this->notificationListener = $this->getMockBuilder(NotificationListener::class)
			->disableOriginalConstructor()
			->getMock();

		$this->eventHandler = new CommentsEventListener($this->activityListener, $this->notificationListener);
	}

	public function testNotFiles(): void {
		/** @var IComment|\PHPUnit\Framework\MockObject\MockObject $comment */
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->once())
			->method('getObjectType')
			->willReturn('smiles');

		/** @var CommentsEvent|\PHPUnit\Framework\MockObject\MockObject $event */
		$event = $this->getMockBuilder(CommentsEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->willReturn($comment);
		$event->expects($this->never())
			->method('getEvent');

		$this->eventHandler->handle($event);
	}

	public function handledProvider() {
		return [
			[CommentsEvent::EVENT_DELETE],
			[CommentsEvent::EVENT_UPDATE],
			[CommentsEvent::EVENT_PRE_UPDATE],
			[CommentsEvent::EVENT_ADD]
		];
	}

	/**
	 * @dataProvider handledProvider
	 * @param string $eventType
	 */
	public function testHandled($eventType): void {
		/** @var IComment|\PHPUnit\Framework\MockObject\MockObject $comment */
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->once())
			->method('getObjectType')
			->willReturn('files');

		/** @var CommentsEvent|\PHPUnit\Framework\MockObject\MockObject $event */
		$event = $this->getMockBuilder(CommentsEvent::class)
			->disableOriginalConstructor()
			->getMock();
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
