<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Comments\Tests\Unit\Notification;

use OCA\Comments\Notification\Listener;
use OCP\Comments\Events\BeforeCommentUpdatedEvent;
use OCP\Comments\Events\CommentAddedEvent;
use OCP\Comments\Events\CommentDeletedEvent;
use OCP\Comments\Events\CommentUpdatedEvent;
use OCP\Comments\IComment;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ListenerTest extends TestCase {
	protected IManager&MockObject $notificationManager;
	protected IUserManager&MockObject $userManager;
	protected IURLGenerator&MockObject $urlGenerator;
	protected Listener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->notificationManager = $this->createMock(IManager::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->listener = new Listener(
			$this->notificationManager,
			$this->userManager
		);
	}

	public static function eventProvider(): array {
		return [
			['add', 'notify'],
			['update', 'notify'],
			['pre_update', 'markProcessed'],
			['delete', 'markProcessed']
		];
	}

	/**
	 * @param string $eventType
	 * @param string $notificationMethod
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'eventProvider')]
	public function testEvaluate(string $eventType, $notificationMethod): void {
		/** @var IComment|MockObject $comment */
		$comment = $this->createMock(IComment::class);
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

		$event = match ($eventType) {
			'add' => new CommentAddedEvent($comment),
			'pre_update' => new BeforeCommentUpdatedEvent($comment),
			'update' => new CommentUpdatedEvent($comment),
			'delete' => new CommentDeletedEvent($comment),
		};

		/** @var INotification|MockObject $notification */
		$notification = $this->createMock(INotification::class);
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
			->willReturnMap([
				['foobar', true],
				['barfoo', true],
				['foo@bar.com', true],
				['bar@foo.org@foobar.io', true],
				['23452-4333-54353-2342', true],
				['yolo', true]
			]);

		$this->listener->evaluate($event);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'eventProvider')]
	public function testEvaluateNoMentions(string $eventType): void {
		/** @var IComment|MockObject $comment */
		$comment = $this->createMock(IComment::class);
		$comment->expects($this->any())
			->method('getObjectType')
			->willReturn('files');
		$comment->expects($this->any())
			->method('getCreationDateTime')
			->willReturn(new \DateTime());
		$comment->expects($this->once())
			->method('getMentions')
			->willReturn([]);

		$event = match ($eventType) {
			'add' => new CommentAddedEvent($comment),
			'pre_update' => new BeforeCommentUpdatedEvent($comment),
			'update' => new CommentUpdatedEvent($comment),
			'delete' => new CommentDeletedEvent($comment),
		};

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
		/** @var IComment|MockObject $comment */
		$comment = $this->createMock(IComment::class);
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

		$event = new CommentAddedEvent($comment);

		/** @var INotification|MockObject $notification */
		$notification = $this->createMock(INotification::class);
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
			->with('foobar')
			->willReturn(false);

		$this->listener->evaluate($event);
	}
}
