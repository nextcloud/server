<?php

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
use Psr\Log\LoggerInterface;

class EntityCollectionTest extends \Test\TestCase {

	/** @var ICommentsManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $commentsManager;
	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;
	/** @var EntityCollection */
	protected $collection;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $userSession;

	protected function setUp(): void {
		parent::setUp();

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
		$this->assertTrue($node instanceof CommentNode);
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

		$this->assertSame(count($result), 1);
		$this->assertTrue($result[0] instanceof CommentNode);
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

		$this->assertSame(count($result), 1);
		$this->assertTrue($result[0] instanceof CommentNode);
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
