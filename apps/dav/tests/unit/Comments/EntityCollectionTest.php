<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\Comments;

class EntityCollectionTest extends \Test\TestCase {

	/** @var \OCP\Comments\ICommentsManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $commentsManager;
	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var \OCP\ILogger|\PHPUnit_Framework_MockObject_MockObject */
	protected $logger;
	/** @var \OCA\DAV\Comments\EntityCollection */
	protected $collection;
	/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;

	public function setUp() {
		parent::setUp();

		$this->commentsManager = $this->getMockBuilder('\OCP\Comments\ICommentsManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('\OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder('\OCP\ILogger')
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

	public function testGetId() {
		$this->assertSame($this->collection->getId(), '19');
	}

	public function testGetChild() {
		$this->commentsManager->expects($this->once())
			->method('get')
			->with('55')
			->will($this->returnValue(
				$this->getMockBuilder('\OCP\Comments\IComment')
					->disableOriginalConstructor()
					->getMock()
			));

		$node = $this->collection->getChild('55');
		$this->assertTrue($node instanceof \OCA\DAV\Comments\CommentNode);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildException() {
		$this->commentsManager->expects($this->once())
			->method('get')
			->with('55')
			->will($this->throwException(new \OCP\Comments\NotFoundException()));

		$this->collection->getChild('55');
	}

	public function testGetChildren() {
		$this->commentsManager->expects($this->once())
			->method('getForObject')
			->with('files', '19')
			->will($this->returnValue([
				$this->getMockBuilder('\OCP\Comments\IComment')
					->disableOriginalConstructor()
					->getMock()
			]));

		$result = $this->collection->getChildren();

		$this->assertSame(count($result), 1);
		$this->assertTrue($result[0] instanceof \OCA\DAV\Comments\CommentNode);
	}

	public function testFindChildren() {
		$dt = new \DateTime('2016-01-10 18:48:00');
		$this->commentsManager->expects($this->once())
			->method('getForObject')
			->with('files', '19', 5, 15, $dt)
			->will($this->returnValue([
				$this->getMockBuilder('\OCP\Comments\IComment')
					->disableOriginalConstructor()
					->getMock()
			]));

		$result = $this->collection->findChildren(5, 15, $dt);

		$this->assertSame(count($result), 1);
		$this->assertTrue($result[0] instanceof \OCA\DAV\Comments\CommentNode);
	}

	public function testChildExistsTrue() {
		$this->assertTrue($this->collection->childExists('44'));
	}

	public function testChildExistsFalse() {
		$this->commentsManager->expects($this->once())
			->method('get')
			->with('44')
			->will($this->throwException(new \OCP\Comments\NotFoundException()));

		$this->assertFalse($this->collection->childExists('44'));
	}
}
