<?php

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
use Psr\Log\LoggerInterface;

class RootCollectionTest extends \Test\TestCase {

	/** @var ICommentsManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $commentsManager;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;
	/** @var RootCollection */
	protected $collection;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $userSession;
	/** @var IEventDispatcher */
	protected $dispatcher;
	/** @var IUser|\PHPUnit\Framework\MockObject\MockObject */
	protected $user;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();

		$this->commentsManager = $this->getMockBuilder(ICommentsManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder(IUserSession::class)
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)
			->disableOriginalConstructor()
			->getMock();
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

	protected function prepareForInitCollections() {
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
		$this->assertTrue($etc instanceof EntityTypeCollectionImplementation);
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
			$this->assertTrue($child instanceof EntityTypeCollectionImplementation);
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
