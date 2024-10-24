<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Tests\Unit\Notification;

use OCA\Comments\Notification\Notifier;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\UnknownNotificationException;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class NotifierTest extends TestCase {
	/** @var Notifier */
	protected $notifier;
	/** @var IFactory|MockObject */
	protected $l10nFactory;
	/** @var IL10N|MockObject */
	protected $l;
	/** @var IRootFolder|MockObject */
	protected $folder;
	/** @var ICommentsManager|MockObject */
	protected $commentsManager;
	/** @var IURLGenerator|MockObject */
	protected $url;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var INotification|MockObject */
	protected $notification;
	/** @var IComment|MockObject */
	protected $comment;
	/** @var string */
	protected $lc = 'tlh_KX';

	protected function setUp(): void {
		parent::setUp();

		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->folder = $this->createMock(IRootFolder::class);
		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->notifier = new Notifier(
			$this->l10nFactory,
			$this->folder,
			$this->commentsManager,
			$this->url,
			$this->userManager
		);

		$this->l = $this->createMock(IL10N::class);
		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});

		$this->notification = $this->createMock(INotification::class);
		$this->comment = $this->createMock(IComment::class);
	}

	public function testPrepareSuccess(): void {
		$fileName = 'Gre\'thor.odp';
		$displayName = 'Huraga';
		$message = '@Huraga mentioned you in a comment on "Gre\'thor.odp"';

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

		$userFolder = $this->createMock(Folder::class);
		$this->folder->expects($this->once())
			->method('getUserFolder')
			->with('you')
			->willReturn($userFolder);
		$userFolder->expects($this->once())
			->method('getById')
			->with('678')
			->willReturn([$node]);

		$this->notification->expects($this->exactly(2))
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

		$this->url->expects($this->once())
			->method('imagePath')
			->with('core', 'actions/comment.svg')
			->willReturn('image-path');
		$this->url->expects($this->once())
			->method('getAbsoluteURL')
			->with('image-path')
			->willReturn('absolute-image-path');

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

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

		$this->commentsManager
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);
		$this->commentsManager
			->expects($this->once())
			->method('resolveDisplayName')
			->with('user', 'you')
			->willReturn('Your name');

		$this->userManager
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
		$message = 'You were mentioned on "Gre\'thor.odp", in a comment by an account that has since been deleted';

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

		$userFolder = $this->createMock(Folder::class);
		$this->folder->expects($this->once())
			->method('getUserFolder')
			->with('you')
			->willReturn($userFolder);
		$userFolder->expects($this->once())
			->method('getById')
			->with('678')
			->willReturn([$node]);

		$this->notification->expects($this->exactly(2))
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

		$this->url->expects($this->once())
			->method('imagePath')
			->with('core', 'actions/comment.svg')
			->willReturn('image-path');
		$this->url->expects($this->once())
			->method('getAbsoluteURL')
			->with('image-path')
			->willReturn('absolute-image-path');

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

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

		$this->commentsManager
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);
		$this->commentsManager
			->expects($this->once())
			->method('resolveDisplayName')
			->with('user', 'you')
			->willReturn('Your name');

		$this->userManager
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

		$this->folder
			->expects($this->never())
			->method('getById');

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

		$this->l10nFactory
			->expects($this->never())
			->method('get');

		$this->commentsManager
			->expects($this->never())
			->method('get');

		$this->userManager
			->expects($this->never())
			->method('getDisplayName');

		$this->notifier->prepare($this->notification, $this->lc);
	}


	public function testPrepareNotFound(): void {
		$this->expectException(UnknownNotificationException::class);

		$this->folder
			->expects($this->never())
			->method('getById');

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

		$this->l10nFactory
			->expects($this->never())
			->method('get');

		$this->commentsManager
			->expects($this->once())
			->method('get')
			->willThrowException(new NotFoundException());

		$this->userManager
			->expects($this->never())
			->method('getDisplayName');

		$this->notifier->prepare($this->notification, $this->lc);
	}


	public function testPrepareDifferentSubject(): void {
		$this->expectException(UnknownNotificationException::class);

		$displayName = 'Huraga';

		$this->folder
			->expects($this->never())
			->method('getById');

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

		$this->l
			->expects($this->never())
			->method('t');

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->commentsManager
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);

		$this->userManager
			->expects($this->once())
			->method('getDisplayName')
			->with('huraga')
			->willReturn($displayName);

		$this->notifier->prepare($this->notification, $this->lc);
	}


	public function testPrepareNotFiles(): void {
		$this->expectException(UnknownNotificationException::class);

		$displayName = 'Huraga';

		$this->folder
			->expects($this->never())
			->method('getById');

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

		$this->l
			->expects($this->never())
			->method('t');

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->commentsManager
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);

		$this->userManager
			->expects($this->once())
			->method('getDisplayName')
			->with('huraga')
			->willReturn($displayName);

		$this->notifier->prepare($this->notification, $this->lc);
	}


	public function testPrepareUnresolvableFileID(): void {
		$this->expectException(AlreadyProcessedException::class);

		$displayName = 'Huraga';

		$userFolder = $this->createMock(Folder::class);
		$this->folder->expects($this->once())
			->method('getUserFolder')
			->with('you')
			->willReturn($userFolder);
		$userFolder->expects($this->once())
			->method('getById')
			->with('678')
			->willReturn([]);

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

		$this->l
			->expects($this->never())
			->method('t');

		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->willReturn($this->l);

		$this->comment
			->expects($this->any())
			->method('getActorId')
			->willReturn('huraga');
		$this->comment
			->expects($this->any())
			->method('getActorType')
			->willReturn('users');

		$this->commentsManager
			->expects($this->once())
			->method('get')
			->willReturn($this->comment);

		$this->userManager
			->expects($this->once())
			->method('getDisplayName')
			->with('huraga')
			->willReturn($displayName);

		$this->notifier->prepare($this->notification, $this->lc);
	}
}
