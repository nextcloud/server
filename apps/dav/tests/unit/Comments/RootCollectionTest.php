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

use OCA\DAV\Comments\EntityTypeCollection as EntityTypeCollectionImplementation;
use OCP\Comments\CommentsEntityEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RootCollectionTest extends \Test\TestCase {

	/** @var \OCP\Comments\ICommentsManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $commentsManager;
	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var \OCP\ILogger|\PHPUnit_Framework_MockObject_MockObject */
	protected $logger;
	/** @var \OCA\DAV\Comments\RootCollection */
	protected $collection;
	/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;
	/** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface */
	protected $dispatcher;
	/** @var \OCP\IUser|\PHPUnit_Framework_MockObject_MockObject */
	protected $user;

	public function setUp() {
		parent::setUp();

		$this->user = $this->getMockBuilder('\OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$this->commentsManager = $this->getMockBuilder('\OCP\Comments\ICommentsManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('\OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->dispatcher = new EventDispatcher();
		$this->logger = $this->getMockBuilder('\OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();

		$this->collection = new \OCA\DAV\Comments\RootCollection(
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
			->will($this->returnValue('alice'));

		$this->userSession->expects($this->once())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->dispatcher->addListener(CommentsEntityEvent::EVENT_ENTITY, function(CommentsEntityEvent $event) {
			$event->addEntityCollection('files', function() {
				return true;
			});
		});
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testCreateFile() {
		$this->collection->createFile('foo');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testCreateDirectory() {
		$this->collection->createDirectory('foo');
	}

	public function testGetChild() {
		$this->prepareForInitCollections();
		$etc = $this->collection->getChild('files');
		$this->assertTrue($etc instanceof EntityTypeCollectionImplementation);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildInvalid() {
		$this->prepareForInitCollections();
		$this->collection->getChild('robots');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotAuthenticated
	 */
	public function testGetChildNoAuth() {
		$this->collection->getChild('files');
	}

	public function testGetChildren() {
		$this->prepareForInitCollections();
		$children = $this->collection->getChildren();
		$this->assertFalse(empty($children));
		foreach($children as $child) {
			$this->assertTrue($child instanceof EntityTypeCollectionImplementation);
		}
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotAuthenticated
	 */
	public function testGetChildrenNoAuth() {
		$this->collection->getChildren();
	}

	public function testChildExistsYes() {
		$this->prepareForInitCollections();
		$this->assertTrue($this->collection->childExists('files'));
	}

	public function testChildExistsNo() {
		$this->prepareForInitCollections();
		$this->assertFalse($this->collection->childExists('robots'));
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotAuthenticated
	 */
	public function testChildExistsNoAuth() {
		$this->collection->childExists('files');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDelete() {
		$this->collection->delete();
	}

	public function testGetName() {
		$this->assertSame('comments', $this->collection->getName());
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testSetName() {
		$this->collection->setName('foobar');
	}

	public function testGetLastModified() {
		$this->assertSame(null, $this->collection->getLastModified());
	}
}
