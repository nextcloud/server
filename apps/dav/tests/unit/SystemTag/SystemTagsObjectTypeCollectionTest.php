<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\DAV\Tests\unit\SystemTag;

class SystemTagsObjectTypeCollectionTest extends \Test\TestCase {

	/**
	 * @var \OCA\DAV\SystemTag\SystemTagsObjectTypeCollection
	 */
	private $node;

	/**
	 * @var \OCP\SystemTag\ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var \OCP\SystemTag\ISystemTagObjectMapper
	 */
	private $tagMapper;

	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;

	protected function setUp() {
		parent::setUp();

		$this->tagManager = $this->getMockBuilder('\OCP\SystemTag\ISystemTagManager')
			->getMock();
		$this->tagMapper = $this->getMockBuilder('\OCP\SystemTag\ISystemTagObjectMapper')
			->getMock();

		$user = $this->getMockBuilder('\OCP\IUser')
			->getMock();
		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('testuser'));
		$userSession = $this->getMockBuilder('\OCP\IUserSession')
			->getMock();
		$userSession->expects($this->any())
			->method('getUser')
			->will($this->returnValue($user));
		$groupManager = $this->getMockBuilder('\OCP\IGroupManager')
			->getMock();
		$groupManager->expects($this->any())
			->method('isAdmin')
			->with('testuser')
			->will($this->returnValue(true));

		$this->userFolder = $this->getMockBuilder('\OCP\Files\Folder')
			->getMock();
		$userFolder = $this->userFolder;

		$closure = function($name) use ($userFolder) {
			$nodes = $userFolder->getById(intval($name));
			return !empty($nodes);
		};

		$this->node = new \OCA\DAV\SystemTag\SystemTagsObjectTypeCollection(
			'files',
			$this->tagManager,
			$this->tagMapper,
			$userSession,
			$groupManager,
			$closure
		);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testForbiddenCreateFile() {
		$this->node->createFile('555');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testForbiddenCreateDirectory() {
		$this->node->createDirectory('789');
	}

	public function testGetChild() {
		$this->userFolder->expects($this->once())
			->method('getById')
			->with('555')
			->will($this->returnValue([true]));
		$childNode = $this->node->getChild('555');

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagsObjectMappingCollection', $childNode);
		$this->assertEquals('555', $childNode->getName());
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildWithoutAccess() {
		$this->userFolder->expects($this->once())
			->method('getById')
			->with('555')
			->will($this->returnValue([]));
		$this->node->getChild('555');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\MethodNotAllowed
	 */
	public function testGetChildren() {
		$this->node->getChildren();
	}

	public function testChildExists() {
		$this->userFolder->expects($this->once())
			->method('getById')
			->with('123')
			->will($this->returnValue([true]));
		$this->assertTrue($this->node->childExists('123'));
	}

	public function testChildExistsWithoutAccess() {
		$this->userFolder->expects($this->once())
			->method('getById')
			->with('555')
			->will($this->returnValue([]));
		$this->assertFalse($this->node->childExists('555'));
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testDelete() {
		$this->node->delete();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testSetName() {
		$this->node->setName('somethingelse');
	}

	public function testGetName() {
		$this->assertEquals('files', $this->node->getName());
	}
}
