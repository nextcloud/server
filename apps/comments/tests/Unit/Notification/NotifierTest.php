<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Comments\Tests\Unit\Notification;

use OCA\Comments\Notification\Notifier;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Node;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\UnknownNotificationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class NotifierTest extends TestCase {
	protected INotification&MockObject $notification;
	protected IComment&MockObject $comment;
	protected Notifier $notifier;
	protected string $lc = 'tlh_KX';

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->notifier = $this->createInstanceWithMocks(Notifier::class);

		$this->notification = $this->createMock(INotification::class);
		$this->comment = $this->createMock(IComment::class);
	}

	public function testPrepareSuccess(): void {
		$fileName = 'Gre\'thor.odp';
		$displayName = 'Huraga';

		/** @var Node&MockObject $node */
		$node = $this->createMock(Node::class);
		$node
			->expects($this->atLeastOnce())
			->method('getName')
			->willReturn($fileName);
		$node
			->expects($this->atLeastOnce())
			->method('getPath')
			->willReturn('/you/files/' . $fileName);

		$userFolder = $this->createMock(IUserFolder::class);
		$this->getAutoMock(IRootFolder::class)->expects($this->once())
			->method('getUserFolder')
			->with('you')
			->willReturn($userFolder);
		$userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with('678')
			->willReturn($node);

		$this->notification->expects($this->once())
			->method('getUser')
			->willReturn('you');
		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('mention');
		$this->notification
			->expects($this->once())
			->method('getSubjectParameters')
			->willReturn(['files', '678']);
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');
		$this->notification
			->expects($this->once())
			->method('setRichSubject')
			->with('{user} mentioned you in a comment on "{file}"', $this->anything())
			->willReturnSelf();
		$this->notification
			->expects($this->once())
			->method('setRichMessage')
			->with('Hi {mention-user1}!', ['mention-user1' => ['type' => 'user', 'id' => 'you', 'name' => 'Your name']])
			->willReturnSelf();
		$this->notification
			->expects($this->never())
			->method('setParsedMessage');
		$this->notification
			->expects($this->once())
			->method('setIcon')
			->with('absolute-image-path')
			->willReturnSelf();

		$this->getAutoMock(IURLGenerator::class)->expects($this->once())
			->method('imagePath')
			->with('core', 'actions/comment.svg')
			->willReturn('image-path');
		$this->getAutoMock(IURLGenerator::class)->expects($this->once())
			->method('getAbsoluteURL')
			->with('image-path')
			->willReturn('absolute-image-path');

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');
		$this->comment
			->expects($this->any())
			->method('getMessage')
			->willReturn('Hi @you!');
		$this->comment
			->expects($this->any())
			->method('getMentions')
			->willReturn([['type' => 'user', 'id' => 'you']]);
		$this->comment->expects($this->atLeastOnce())
			->method('getId')
			->willReturn('1234');

		$this->getAutoMock(ICommentsManager::class)
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);
		$this->getAutoMock(ICommentsManager::class)
			->expects($this->once())
			->method('resolveDisplayName')
			->with('user', 'you')
			->willReturn('Your name');

		$this->getAutoMock(IUserManager::class)
			->expects($this->exactly(2))
			->method('getDisplayName')
			->willReturnMap([
				['huraga', $displayName],
				['you', 'You'],
			]);

		$this->notifier->prepare($this->notification, $this->lc);
	}

	public function testPrepareSuccessDeletedUser(): void {
		$fileName = 'Gre\'thor.odp';

		/** @var Node|MockObject $node */
		$node = $this->createMock(Node::class);
		$node
			->expects($this->atLeastOnce())
			->method('getName')
			->willReturn($fileName);
		$node
			->expects($this->atLeastOnce())
			->method('getPath')
			->willReturn('/you/files/' . $fileName);

		$userFolder = $this->createMock(IUserFolder::class);
		$this->getAutoMock(IRootFolder::class)->expects($this->once())
			->method('getUserFolder')
			->with('you')
			->willReturn($userFolder);
		$userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with('678')
			->willReturn($node);

		$this->notification->expects($this->once())
			->method('getUser')
			->willReturn('you');
		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('mention');
		$this->notification
			->expects($this->once())
			->method('getSubjectParameters')
			->willReturn(['files', '678']);
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');
		$this->notification
			->expects($this->once())
			->method('setRichSubject')
			->with('You were mentioned on "{file}", in a comment by an account that has since been deleted', $this->anything())
			->willReturnSelf();
		$this->notification
			->expects($this->once())
			->method('setRichMessage')
			->with('Hi {mention-user1}!', ['mention-user1' => ['type' => 'user', 'id' => 'you', 'name' => 'Your name']])
			->willReturnSelf();
		$this->notification
			->expects($this->never())
			->method('setParsedMessage');
		$this->notification
			->expects($this->once())
			->method('setIcon')
			->with('absolute-image-path')
			->willReturnSelf();

		$this->getAutoMock(IURLGenerator::class)->expects($this->once())
			->method('imagePath')
			->with('core', 'actions/comment.svg')
			->willReturn('image-path');
		$this->getAutoMock(IURLGenerator::class)->expects($this->once())
			->method('getAbsoluteURL')
			->with('image-path')
			->willReturn('absolute-image-path');

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn(ICommentsManager::DELETED_USER);
		$this->comment
			->expects($this->any())
			->method('getMessage')
			->willReturn('Hi @you!');
		$this->comment
			->expects($this->any())
			->method('getMentions')
			->willReturn([['type' => 'user', 'id' => 'you']]);

		$this->getAutoMock(ICommentsManager::class)
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);
		$this->getAutoMock(ICommentsManager::class)
			->expects($this->once())
			->method('resolveDisplayName')
			->with('user', 'you')
			->willReturn('Your name');

		$this->getAutoMock(IUserManager::class)
			->expects($this->once())
			->method('getDisplayName')
			->willReturnMap([
				['huraga', null],
				['you', 'You'],
			]);

		$this->notifier->prepare($this->notification, $this->lc);
	}

	public function testPrepareDifferentApp(): void {
		$this->expectException(UnknownNotificationException::class);

		$this->getAutoMock(IRootFolder::class)
			->expects($this->never())
			->method('getFirstNodeById');

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('constructions');
		$this->notification
			->expects($this->never())
			->method('getSubject');
		$this->notification
			->expects($this->never())
			->method('getSubjectParameters');
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->getAutoMock(ICommentsManager::class)
			->expects($this->never())
			->method('get');

		$this->getAutoMock(IUserManager::class)
			->expects($this->never())
			->method('getDisplayName');

		$this->notifier->prepare($this->notification, $this->lc);
	}

	public function testPrepareNotFound(): void {
		$this->expectException(UnknownNotificationException::class);

		$this->getAutoMock(IRootFolder::class)
			->expects($this->never())
			->method('getFirstNodeById');

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->never())
			->method('getSubject');
		$this->notification
			->expects($this->never())
			->method('getSubjectParameters');
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->getAutoMock(ICommentsManager::class)
			->expects($this->once())
			->method('get')
			->willThrowException(new NotFoundException());

		$this->getAutoMock(IUserManager::class)
			->expects($this->never())
			->method('getDisplayName');

		$this->notifier->prepare($this->notification, $this->lc);
	}

	public function testPrepareDifferentSubject(): void {
		$this->expectException(UnknownNotificationException::class);

		$displayName = 'Huraga';

		$this->getAutoMock(IRootFolder::class)
			->expects($this->never())
			->method('getFirstNodeById');

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('unlike');
		$this->notification
			->expects($this->never())
			->method('getSubjectParameters');
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->getAutoMock(ICommentsManager::class)
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);

		$this->getAutoMock(IUserManager::class)
			->expects($this->once())
			->method('getDisplayName')
			->with('huraga')
			->willReturn($displayName);

		$this->notifier->prepare($this->notification, $this->lc);
	}

	public function testPrepareNotFiles(): void {
		$this->expectException(UnknownNotificationException::class);

		$displayName = 'Huraga';

		$this->getAutoMock(IRootFolder::class)
			->expects($this->never())
			->method('getFirstNodeById');

		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('mention');
		$this->notification
			->expects($this->once())
			->method('getSubjectParameters')
			->willReturn(['ships', '678']);
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->getAutoMock(ICommentsManager::class)
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);

		$this->getAutoMock(IUserManager::class)
			->expects($this->once())
			->method('getDisplayName')
			->with('huraga')
			->willReturn($displayName);

		$this->notifier->prepare($this->notification, $this->lc);
	}

	public function testPrepareUnresolvableFileID(): void {
		$this->expectException(AlreadyProcessedException::class);

		$displayName = 'Huraga';

		$userFolder = $this->createMock(IUserFolder::class);
		$this->getAutoMock(IRootFolder::class)->expects($this->once())
			->method('getUserFolder')
			->with('you')
			->willReturn($userFolder);
		$userFolder->expects($this->once())
			->method('getFirstNodeById')
			->with('678')
			->willReturn(null);

		$this->notification->expects($this->once())
			->method('getUser')
			->willReturn('you');
		$this->notification
			->expects($this->once())
			->method('getApp')
			->willReturn('comments');
		$this->notification
			->expects($this->once())
			->method('getSubject')
			->willReturn('mention');
		$this->notification
			->expects($this->once())
			->method('getSubjectParameters')
			->willReturn(['files', '678']);
		$this->notification
			->expects($this->never())
			->method('setParsedSubject');

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->getAutoMock(ICommentsManager::class)
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);

		$this->getAutoMock(IUserManager::class)
			->expects($this->once())
			->method('getDisplayName')
			->with('huraga')
			->willReturn($displayName);

		$this->notifier->prepare($this->notification, $this->lc);
	}

	public static function dataPrepareReadMark(): array {
		return [
			'read after comment' => ['2026-01-02 00:00:00', true],
			'read at comment time' => ['2026-01-01 12:00:00', true],
			'read before comment' => ['2026-01-01 00:00:00', false],
			'never read' => [null, false],
		];
	}

	#[DataProvider('dataPrepareReadMark')]
	public function testPrepareReadMark(?string $readMark, bool $alreadyRead): void {
		$this->expectException(AlreadyProcessedException::class);

		$user = $this->createMock(IUser::class);
		$this->getAutoMock(IUserManager::class)
			->method('get')
			->with('you')
			->willReturn($user);

		$this->notification->method('getUser')->willReturn('you');
		$this->notification->method('getApp')->willReturn('comments');
		$this->notification->method('getSubject')->willReturn('mention');
		$this->notification->method('getSubjectParameters')->willReturn(['files', '678']);

		$this->comment->method('getActorId')->willReturn('huraga');
		$this->comment->method('getActorType')->willReturn('users');
		$this->comment->method('getCreationDateTime')->willReturn(new \DateTime('2026-01-01 12:00:00'));

		$this->getAutoMock(ICommentsManager::class)
			->method('get')
			->willReturn($this->comment);
		$this->getAutoMock(ICommentsManager::class)
			->expects($this->once())
			->method('getReadMark')
			->with('files', '678', $user)
			->willReturn($readMark === null ? null : new \DateTime($readMark));

		$userFolder = $this->createMock(IUserFolder::class);
		$userFolder->method('getFirstNodeById')->willReturn(null);
		$this->getAutoMock(IRootFolder::class)->expects($alreadyRead ? $this->never() : $this->once())
			->method('getUserFolder')
			->with('you')
			->willReturn($userFolder);

		$this->notifier->prepare($this->notification, $this->lc);
	}
}
