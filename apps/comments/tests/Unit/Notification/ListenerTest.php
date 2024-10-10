<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Comments\Tests\Unit\Notification;

use OCA\Comments\Notification\Listener;
use OCP\Comments\CommentsEvent;
use OCP\Comments\IComment;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use Test\TestCase;

class ListenerTest extends TestCase {
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $notificationManager;

	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;

	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $urlGenerator;

	/** @var Listener */
	protected $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->notificationManager = $this->createMock(\OCP\Notification\IManager::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->listener = new Listener(
			$this->notificationManager,
			$this->userManager
		);
	}

	public function eventProvider() {
		return [
			[CommentsEvent::EVENT_ADD, 'notify'],
			[CommentsEvent::EVENT_UPDATE, 'notify'],
			[CommentsEvent::EVENT_PRE_UPDATE, 'markProcessed'],
			[CommentsEvent::EVENT_DELETE, 'markProcessed']
		];
	}

	/**
	 * @dataProvider eventProvider
	 * @param string $eventType
	 * @param string $notificationMethod
	 */
	public function testEvaluate($eventType, $notificationMethod): void {
		/** @var IComment|\PHPUnit\Framework\MockObject\MockObject $comment */
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->willReturn('files');
		$comment->expects($this->any())
			->method('getCreationDateTime')
			->willReturn(new \DateTime());
		$comment->expects($this->once())
			->method('getMentions')
			->willReturn([
				[ 'type' => 'user', 'id' => 'foobar'],
				[ 'type' => 'user', 'id' => 'barfoo'],
				[ 'type' => 'user', 'id' => 'foo@bar.com'],
				[ 'type' => 'user', 'id' => 'bar@foo.org@foobar.io'],
				[ 'type' => 'user', 'id' => '23452-4333-54353-2342'],
				[ 'type' => 'user', 'id' => 'yolo'],
			]);
		$comment->expects($this->atLeastOnce())
			->method('getId')
			->willReturn('1234');

		/** @var CommentsEvent|\PHPUnit\Framework\MockObject\MockObject $event */
		$event = $this->getMockBuilder(CommentsEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->willReturn($comment);
		$event->expects(($this->any()))
			->method(('getEvent'))
			->willReturn($eventType);

		/** @var INotification|\PHPUnit\Framework\MockObject\MockObject $notification */
		$notification = $this->getMockBuilder(INotification::class)->getMock();
		$notification->expects($this->any())
			->method($this->anything())
			->willReturn($notification);
		$notification->expects($this->exactly(6))
			->method('setUser');

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->exactly(6))
			->method($notificationMethod)
			->with($this->isInstanceOf('\OCP\Notification\INotification'));

		$this->userManager->expects($this->exactly(6))
			->method('userExists')
			->withConsecutive(
				['foobar'],
				['barfoo'],
				['foo@bar.com'],
				['bar@foo.org@foobar.io'],
				['23452-4333-54353-2342'],
				['yolo']
			)
			->willReturn(true);

		$this->listener->evaluate($event);
	}

	/**
	 * @dataProvider eventProvider
	 * @param string $eventType
	 */
	public function testEvaluateNoMentions($eventType): void {
		/** @var IComment|\PHPUnit\Framework\MockObject\MockObject $comment */
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->willReturn('files');
		$comment->expects($this->any())
			->method('getCreationDateTime')
			->willReturn(new \DateTime());
		$comment->expects($this->once())
			->method('getMentions')
			->willReturn([]);

		/** @var CommentsEvent|\PHPUnit\Framework\MockObject\MockObject $event */
		$event = $this->getMockBuilder(CommentsEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->willReturn($comment);
		$event->expects(($this->any()))
			->method(('getEvent'))
			->willReturn($eventType);

		$this->notificationManager->expects($this->never())
			->method('createNotification');
		$this->notificationManager->expects($this->never())
			->method('notify');
		$this->notificationManager->expects($this->never())
			->method('markProcessed');

		$this->userManager->expects($this->never())
			->method('userExists');

		$this->listener->evaluate($event);
	}

	public function testEvaluateUserDoesNotExist(): void {
		/** @var IComment|\PHPUnit\Framework\MockObject\MockObject $comment */
		$comment = $this->getMockBuilder(IComment::class)->getMock();
		$comment->expects($this->any())
			->method('getObjectType')
			->willReturn('files');
		$comment->expects($this->any())
			->method('getCreationDateTime')
			->willReturn(new \DateTime());
		$comment->expects($this->once())
			->method('getMentions')
			->willReturn([[ 'type' => 'user', 'id' => 'foobar']]);
		$comment->expects($this->atLeastOnce())
			->method('getId')
			->willReturn('1234');

		/** @var CommentsEvent|\PHPUnit\Framework\MockObject\MockObject $event */
		$event = $this->getMockBuilder(CommentsEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('getComment')
			->willReturn($comment);
		$event->expects(($this->any()))
			->method(('getEvent'))
			->willReturn(CommentsEvent::EVENT_ADD);

		/** @var INotification|\PHPUnit\Framework\MockObject\MockObject $notification */
		$notification = $this->getMockBuilder(INotification::class)->getMock();
		$notification->expects($this->any())
			->method($this->anything())
			->willReturn($notification);
		$notification->expects($this->never())
			->method('setUser');

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->never())
			->method('notify');

		$this->userManager->expects($this->once())
			->method('userExists')
			->withConsecutive(
				['foobar']
			)
			->willReturn(false);

		$this->listener->evaluate($event);
	}
}
