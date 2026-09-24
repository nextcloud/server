<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Comments\Tests\Unit\Activity;

use OCA\Comments\Activity\Listener;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\App\IAppManager;
use OCP\Comments\Events\CommentAddedEvent;
use OCP\Comments\IComment;
use OCP\Files\Config\ICachedMountFileInfo;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IShareHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ListenerTest extends TestCase {
	protected Listener $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->listener = $this->createInstanceWithMocks(Listener::class);
	}

	public function testCommentEvent(): void {
		$this->mocks[IAppManager::class]->expects($this->any())
			->method('isEnabledForAnyone')
			->with('activity')
			->willReturn(true);

		$comment = $this->createMock(IComment::class);
		$comment->expects($this->any())
			->method('getObjectType')
			->willReturn('files');

		$event = new CommentAddedEvent($comment);

		/** @var IUser|MockObject $ownerUser */
		$ownerUser = $this->createMock(IUser::class);
		$ownerUser->expects($this->any())
			->method('getUID')
			->willReturn('937393');

		/** @var MockObject $mount */
		$mount = $this->createMock(ICachedMountFileInfo::class);
		$mount->expects($this->any())
			->method('getUser')
			->willReturn($ownerUser); // perhaps not the right user, but does not matter in this scenario

		$mounts = [ $mount, $mount ]; // to make sure duplicates are dealt with

		$userMountCache = $this->createMock(IUserMountCache::class);
		$userMountCache->expects($this->any())
			->method('getMountsForFileId')
			->willReturn($mounts);

		$this->mocks[IMountProviderCollection::class]->expects($this->any())
			->method('getMountCache')
			->willReturn($userMountCache);

		$node = $this->createMock(Node::class);

		$ownerFolder = $this->createMock(IUserFolder::class);
		$ownerFolder->expects($this->any())
			->method('getFirstNodeById')
			->willReturn($node);

		$this->mocks[IRootFolder::class]->expects($this->any())
			->method('getUserFolder')
			->willReturn($ownerFolder);

		$al = [ 'users' => [
			'873304' => 'i/got/it/here',
			'254342' => 'there/i/have/it',
			'sandra' => 'and/here/i/placed/it'
		]];
		$this->mocks[IShareHelper::class]->expects($this->any())
			->method('getPathsForAccessList')
			->willReturn($al);

		$this->mocks[IUserSession::class]->expects($this->any())
			->method('getUser')
			->willReturn($ownerUser);

		/** @var MockObject $activity */
		$activity = $this->createMock(IEvent::class);
		$activity->expects($this->exactly(count($al['users'])))
			->method('setAffectedUser');
		$activity->expects($this->once())
			->method('setApp')
			->with('comments')
			->willReturnSelf();
		$activity->expects($this->once())
			->method('setType')
			->with('comments')
			->willReturnSelf();
		$activity->expects($this->once())
			->method('setAuthor')
			->with($ownerUser->getUID())
			->willReturnSelf();
		$activity->expects($this->once())
			->method('setObject')
			->with('files', $this->anything())
			->willReturnSelf();
		$activity->expects($this->once())
			->method('setMessage')
			->with('add_comment_message', $this->anything())
			->willReturnSelf();

		$this->mocks[IManager::class]->expects($this->once())
			->method('generateEvent')
			->willReturn($activity);
		$this->mocks[IManager::class]->expects($this->exactly(count($al['users'])))
			->method('publish');

		$this->listener->commentEvent($event);
	}
}
