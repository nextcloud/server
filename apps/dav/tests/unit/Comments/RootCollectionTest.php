<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Comments;

use OC\EventDispatcher\EventDispatcher;
use OCA\DAV\Comments\EntityTypeCollection as EntityTypeCollectionImplementation;
use OCA\DAV\Comments\RootCollection;
use OCP\Comments\CommentsEntityEvent;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class RootCollectionTest extends \Test\TestCase {
	protected ICommentsManager&MockObject $commentsManager;
	protected IUserManager&MockObject $userManager;
	protected LoggerInterface&MockObject $logger;
	protected IUserSession&MockObject $userSession;
	protected IEventDispatcher $dispatcher;
	protected IUser&MockObject $user;
	protected RootCollection $collection;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);

		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->dispatcher = new EventDispatcher(
			new \Symfony\Component\EventDispatcher\EventDispatcher(),
			\OC::$server,
			$this->logger
		);

		$this->collection = new RootCollection(
			$this->commentsManager,
			$this->userManager,
			$this->userSession,
			$this->dispatcher,
			$this->logger
		);
	}

	protected function prepareForInitCollections(): void {
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('alice');

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($this->user);

		$this->dispatcher->addListener(CommentsEntityEvent::class, function (CommentsEntityEvent $event): void {
			$event->addEntityCollection('files', function () {
				return true;
			});
		});
	}


	public function testCreateFile(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->collection->createFile('foo');
	}


	public function testCreateDirectory(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->collection->createDirectory('foo');
	}

	public function testGetChild(): void {
		$this->prepareForInitCollections();
		$etc = $this->collection->getChild('files');
		$this->assertInstanceOf(EntityTypeCollectionImplementation::class, $etc);
	}


	public function testGetChildInvalid(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->prepareForInitCollections();
		$this->collection->getChild('robots');
	}


	public function testGetChildNoAuth(): void {
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);

		$this->collection->getChild('files');
	}

	public function testGetChildren(): void {
		$this->prepareForInitCollections();
		$children = $this->collection->getChildren();
		$this->assertFalse(empty($children));
		foreach ($children as $child) {
			$this->assertInstanceOf(EntityTypeCollectionImplementation::class, $child);
		}
	}


	public function testGetChildrenNoAuth(): void {
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);

		$this->collection->getChildren();
	}

	public function testChildExistsYes(): void {
		$this->prepareForInitCollections();
		$this->assertTrue($this->collection->childExists('files'));
	}

	public function testChildExistsNo(): void {
		$this->prepareForInitCollections();
		$this->assertFalse($this->collection->childExists('robots'));
	}


	public function testChildExistsNoAuth(): void {
		$this->expectException(\Sabre\DAV\Exception\NotAuthenticated::class);

		$this->collection->childExists('files');
	}


	public function testDelete(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->collection->delete();
	}

	public function testGetName(): void {
		$this->assertSame('comments', $this->collection->getName());
	}


	public function testSetName(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->collection->setName('foobar');
	}

	public function testGetLastModified(): void {
		$this->assertSame(null, $this->collection->getLastModified());
	}
}
