<?php

declare(strict_types=1);

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
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Node;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use Test\TestCase;

class NotificationsTest extends TestCase {
	protected NotificationsController $notificationsController;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->notificationsController = $this->createInstanceWithMocks(
			NotificationsController::class,
			[
				'appName' => 'comments',
			]
		);
	}

	public function testViewGuestRedirect(): void {
		$this->mocks[ICommentsManager::class]->expects($this->never())
			->method('get');

		$this->mocks[IRootFolder::class]->expects($this->never())
			->method('getUserFolder');

		$this->mocks[IUserSession::class]->expects($this->once())
			->method('getUser')
			->willReturn(null);

		$this->mocks[IManager::class]->expects($this->never())
			->method('createNotification');
		$this->mocks[IManager::class]->expects($this->never())
			->method('markProcessed');

		$this->mocks[IURLGenerator::class]->expects($this->exactly(2))
			->method('linkToRoute')
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

		$this->mocks[ICommentsManager::class]->expects($this->any())
			->method('get')
			->with('42')
			->willReturn($comment);

		$file = $this->createMock(Node::class);
		$folder = $this->createMock(IUserFolder::class);
		$user = $this->createMock(IUser::class);

		$this->mocks[IRootFolder::class]->expects($this->once())
			->method('getUserFolder')
			->willReturn($folder);

		$folder->expects($this->once())
			->method('getFirstNodeById')
			->willReturn($file);

		$this->mocks[IUserSession::class]->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$notification = $this->createMock(INotification::class);
		$notification->expects($this->any())
			->method($this->anything())
			->willReturn($notification);

		$this->mocks[IManager::class]->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->mocks[IManager::class]->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$response = $this->notificationsController->view('42');
		$this->assertInstanceOf(RedirectResponse::class, $response);
	}

	public function testViewInvalidComment(): void {
		$this->mocks[ICommentsManager::class]->expects($this->any())
			->method('get')
			->with('42')
			->willThrowException(new NotFoundException());

		$this->mocks[IRootFolder::class]->expects($this->never())
			->method('getUserFolder');

		$user = $this->createMock(IUser::class);

		$this->mocks[IUserSession::class]->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->mocks[IManager::class]->expects($this->never())
			->method('createNotification');
		$this->mocks[IManager::class]->expects($this->never())
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

		$this->mocks[ICommentsManager::class]->expects($this->any())
			->method('get')
			->with('42')
			->willReturn($comment);

		$folder = $this->createMock(IUserFolder::class);

		$this->mocks[IRootFolder::class]->expects($this->once())
			->method('getUserFolder')
			->willReturn($folder);

		$folder->expects($this->once())
			->method('getFirstNodeById')
			->willReturn(null);

		$user = $this->createMock(IUser::class);

		$this->mocks[IUserSession::class]->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$notification = $this->createMock(INotification::class);
		$notification->expects($this->any())
			->method($this->anything())
			->willReturn($notification);

		$this->mocks[IManager::class]->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->mocks[IManager::class]->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$response = $this->notificationsController->view('42');
		$this->assertInstanceOf(NotFoundResponse::class, $response);
	}
}
