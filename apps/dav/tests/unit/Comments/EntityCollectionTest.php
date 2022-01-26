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

use DateTime;
use OCA\DAV\Comments\CommentNode;
use OCA\DAV\Comments\EntityCollection;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class EntityCollectionTest extends TestCase {

	/** @var ICommentsManager|MockObject */
	protected $commentsManager;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var LoggerInterface|MockObject */
	protected $logger;
	/** @var EntityCollection */
	protected $collection;
	/** @var IUserSession|MockObject */
	protected $userSession;

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

	public function testGetId() {
		$this->assertSame('19', $this->collection->getId());
	}

	/**
	 * @throws NotFound
	 */
	public function testGetChild() {
		$this->commentsManager->expects($this->once())
			->method('get')
			->with('55')
			->willReturn(
				$this->createMock(IComment::class)
			);

		$node = $this->collection->getChild('55');
		$this->assertTrue($node instanceof CommentNode);
	}


	public function testGetChildException() {
		$this->expectException(NotFound::class);

		$this->commentsManager->expects($this->once())
			->method('get')
			->with('55')
			->will($this->throwException(new NotFoundException()));

		$this->collection->getChild('55');
	}

	public function testGetChildren() {
		$this->commentsManager->expects($this->once())
			->method('getForObject')
			->with('files', '19')
			->willReturn([
				$this->createMock(IComment::class)
			]);

		$result = $this->collection->getChildren();

		$this->assertCount(1, $result);
		$this->assertTrue($result[0] instanceof CommentNode);
	}

	public function testFindChildren() {
		$dt = new DateTime('2016-01-10 18:48:00');
		$this->commentsManager->expects($this->once())
			->method('getForObject')
			->with('files', '19', 5, 15, $dt)
			->willReturn([
				$this->createMock(IComment::class)
			]);

		$result = $this->collection->findChildren(5, 15, $dt);

		$this->assertCount(1, $result);
		$this->assertTrue($result[0] instanceof CommentNode);
	}

	public function testChildExistsTrue() {
		$this->assertTrue($this->collection->childExists('44'));
	}

	public function testChildExistsFalse() {
		$this->commentsManager->expects($this->once())
			->method('get')
			->with('44')
			->will($this->throwException(new NotFoundException()));

		$this->assertFalse($this->collection->childExists('44'));
	}
}
