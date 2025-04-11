<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Comments\Tests\Unit\Controller;

use OCA\Comments\Controller\NotificationsController;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use Test\TestCase;

class NotificationsTest extends TestCase {
	/** @var NotificationsController */
	protected $notificationsController;

	/** @var ICommentsManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $commentsManager;

	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	protected $rootFolder;

	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $session;

	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $notificationManager;

	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $urlGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->notificationManager = $this->createMock(IManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->notificationsController = new NotificationsController(
			'comments',
			$this->createMock(IRequest::class),
			$this->commentsManager,
			$this->rootFolder,
			$this->urlGenerator,
			$this->notificationManager,
			$this->session
		);
	}

	public function testViewGuestRedirect(): void {
		$this->commentsManager->expects($this->never())
			->method('get');

		$this->rootFolder->expects($this->never())
			->method('getUserFolder');

		$this->session->expects($this->once())
			->method('getUser')
			->willReturn(null);

		$this->notificationManager->expects($this->never())
			->method('createNotification');
		$this->notificationManager->expects($this->never())
			->method('markProcessed');

		$this->urlGenerator->expects($this->exactly(2))
			->method('linkToRoute')
			->withConsecutive(
				['comments.Notifications.view', ['id' => '42']],
				['core.login.showLoginForm', ['redirect_url' => 'link-to-comment']]
			)
			->willReturnMap([
				['comments.Notifications.view', ['id' => '42'], 'link-to-comment'],
				['core.login.showLoginForm', ['redirect_url' => 'link-to-comment'], 'link-to-login'],
			]);

		/** @var RedirectResponse $response */
		$response = $this->notificationsController->view('42');
		$this->assertInstanceOf(RedirectResponse::class, $response);
		$this->assertSame('link-to-login', $response->getRedirectURL());
	}

	public function testViewSuccess(): void {
		$comment = $this->createMock(IComment::class);
		$comment->expects($this->any())
			->method('getObjectType')
			->willReturn('files');
		$comment->expects($this->any())
			->method('getId')
			->willReturn('1234');

		$this->commentsManager->expects($this->any())
			->method('get')
			->with('42')
			->willReturn($comment);

		$file = $this->createMock(Node::class);
		$folder = $this->createMock(Folder::class);
		$user = $this->createMock(IUser::class);

		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->willReturn($folder);

		$folder->expects($this->once())
			->method('getById')
			->willReturn([$file]);

		$this->session->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$notification = $this->createMock(INotification::class);
		$notification->expects($this->any())
			->method($this->anything())
			->willReturn($notification);

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$response = $this->notificationsController->view('42');
		$this->assertInstanceOf(RedirectResponse::class, $response);
	}

	public function testViewInvalidComment(): void {
		$this->commentsManager->expects($this->any())
			->method('get')
			->with('42')
			->will($this->throwException(new NotFoundException()));

		$this->rootFolder->expects($this->never())
			->method('getUserFolder');

		$user = $this->createMock(IUser::class);

		$this->session->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->notificationManager->expects($this->never())
			->method('createNotification');
		$this->notificationManager->expects($this->never())
			->method('markProcessed');

		$response = $this->notificationsController->view('42');
		$this->assertInstanceOf(NotFoundResponse::class, $response);
	}

	public function testViewNoFile(): void {
		$comment = $this->createMock(IComment::class);
		$comment->expects($this->any())
			->method('getObjectType')
			->willReturn('files');
		$comment->expects($this->any())
			->method('getId')
			->willReturn('1234');

		$this->commentsManager->expects($this->any())
			->method('get')
			->with('42')
			->willReturn($comment);

		$folder = $this->createMock(Folder::class);

		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->willReturn($folder);

		$folder->expects($this->once())
			->method('getById')
			->willReturn([]);

		$user = $this->createMock(IUser::class);

		$this->session->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$notification = $this->createMock(INotification::class);
		$notification->expects($this->any())
			->method($this->anything())
			->willReturn($notification);

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$response = $this->notificationsController->view('42');
		$this->assertInstanceOf(NotFoundResponse::class, $response);
	}
}
