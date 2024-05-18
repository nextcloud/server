<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Comments;

use OCA\DAV\Comments\EntityCollection;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class EntityCollectionTest extends \Test\TestCase {

	/** @var \OCP\Comments\ICommentsManager|\PHPUnit\Framework\MockObject\MockObject */
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

		$this->collection = new \OCA\DAV\Comments\EntityCollection(
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
		$this->assertTrue($node instanceof \OCA\DAV\Comments\CommentNode);
	}


	public function testGetChildException(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->commentsManager->expects($this->once())
			->method('get')
			->with('55')
			->will($this->throwException(new \OCP\Comments\NotFoundException()));

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
		$this->assertTrue($result[0] instanceof \OCA\DAV\Comments\CommentNode);
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
		$this->assertTrue($result[0] instanceof \OCA\DAV\Comments\CommentNode);
	}

	public function testChildExistsTrue(): void {
		$this->assertTrue($this->collection->childExists('44'));
	}

	public function testChildExistsFalse(): void {
		$this->commentsManager->expects($this->once())
			->method('get')
			->with('44')
			->will($this->throwException(new \OCP\Comments\NotFoundException()));

		$this->assertFalse($this->collection->childExists('44'));
	}
}
