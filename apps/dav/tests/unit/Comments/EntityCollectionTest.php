<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Comments;

use OCA\DAV\Comments\CommentNode;
use OCA\DAV\Comments\EntityCollection;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class EntityCollectionTest extends \Test\TestCase {
	protected ICommentsManager&MockObject $commentsManager;
	protected IUserManager&MockObject $userManager;
	protected LoggerInterface&MockObject $logger;
	protected IUserSession&MockObject $userSession;
	protected EntityCollection $collection;

	protected function setUp(): void {
		parent::setUp();

		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->collection = new EntityCollection(
			'19',
			'files',
			$this->commentsManager,
			$this->userManager,
			$this->userSession,
			$this->logger
		);
	}

	public function testGetId(): void {
		$this->assertSame($this->collection->getId(), '19');
	}

	public function testGetChild(): void {
		$this->commentsManager->expects($this->once())
			->method('get')
			->with('55')
			->willReturn(
				$this->getMockBuilder(IComment::class)
					->disableOriginalConstructor()
					->getMock()
			);

		$node = $this->collection->getChild('55');
		$this->assertInstanceOf(CommentNode::class, $node);
	}


	public function testGetChildException(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->commentsManager->expects($this->once())
			->method('get')
			->with('55')
			->will($this->throwException(new NotFoundException()));

		$this->collection->getChild('55');
	}

	public function testGetChildren(): void {
		$this->commentsManager->expects($this->once())
			->method('getForObject')
			->with('files', '19')
			->willReturn([
				$this->getMockBuilder(IComment::class)
					->disableOriginalConstructor()
					->getMock()
			]);

		$result = $this->collection->getChildren();

		$this->assertCount(1, $result);
		$this->assertInstanceOf(CommentNode::class, $result[0]);
	}

	public function testFindChildren(): void {
		$dt = new \DateTime('2016-01-10 18:48:00');
		$this->commentsManager->expects($this->once())
			->method('getForObject')
			->with('files', '19', 5, 15, $dt)
			->willReturn([
				$this->getMockBuilder(IComment::class)
					->disableOriginalConstructor()
					->getMock()
			]);

		$result = $this->collection->findChildren(5, 15, $dt);

		$this->assertCount(1, $result);
		$this->assertInstanceOf(CommentNode::class, $result[0]);
	}

	public function testChildExistsTrue(): void {
		$this->assertTrue($this->collection->childExists('44'));
	}

	public function testChildExistsFalse(): void {
		$this->commentsManager->expects($this->once())
			->method('get')
			->with('44')
			->will($this->throwException(new NotFoundException()));

		$this->assertFalse($this->collection->childExists('44'));
	}
}
