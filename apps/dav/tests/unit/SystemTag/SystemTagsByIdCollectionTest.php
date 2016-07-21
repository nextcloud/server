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


use OC\SystemTag\SystemTag;
use OCP\SystemTag\TagNotFoundException;

class SystemTagsByIdCollectionTest extends \Test\TestCase {

	/**
	 * @var \OCP\SystemTag\ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var \OCP\IUser
	 */
	private $user;

	protected function setUp() {
		parent::setUp();

		$this->tagManager = $this->getMockBuilder('\OCP\SystemTag\ISystemTagManager')
			->getMock();
	}

	public function getNode($isAdmin = true) {
		$this->user = $this->getMockBuilder('\OCP\IUser')
			->getMock();
		$this->user->expects($this->any())
			->method('getUID')
			->will($this->returnValue('testuser'));
		$userSession = $this->getMockBuilder('\OCP\IUserSession')
			->getMock();
		$userSession->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));
		$groupManager = $this->getMockBuilder('\OCP\IGroupManager')
			->getMock();
		$groupManager->expects($this->any())
			->method('isAdmin')
			->with('testuser')
			->will($this->returnValue($isAdmin));
		return new \OCA\DAV\SystemTag\SystemTagsByIdCollection(
			$this->tagManager,
			$userSession,
			$groupManager
		);
	}

	public function adminFlagProvider() {
		return [[true], [false]];
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testForbiddenCreateFile() {
		$this->getNode()->createFile('555');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\Forbidden
	 */
	public function testForbiddenCreateDirectory() {
		$this->getNode()->createDirectory('789');
	}

	public function testGetChild() {
		$tag = new SystemTag(123, 'Test', true, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->will($this->returnValue(true));

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->will($this->returnValue([$tag]));

		$childNode = $this->getNode()->getChild('123');

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $childNode);
		$this->assertEquals('123', $childNode->getName());
		$this->assertEquals($tag, $childNode->getSystemTag());
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 */
	public function testGetChildInvalidName() {
		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['invalid'])
			->will($this->throwException(new \InvalidArgumentException()));

		$this->getNode()->getChild('invalid');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildNotFound() {
		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['444'])
			->will($this->throwException(new TagNotFoundException()));

		$this->getNode()->getChild('444');
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildUserNotVisible() {
		$tag = new SystemTag(123, 'Test', false, false);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->will($this->returnValue([$tag]));

		$this->getNode(false)->getChild('123');
	}

	public function testGetChildrenAdmin() {
		$tag1 = new SystemTag(123, 'One', true, false);
		$tag2 = new SystemTag(456, 'Two', true, true);

		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(null)
			->will($this->returnValue([$tag1, $tag2]));

		$children = $this->getNode(true)->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[0]);
		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[1]);
		$this->assertEquals($tag1, $children[0]->getSystemTag());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	public function testGetChildrenNonAdmin() {
		$tag1 = new SystemTag(123, 'One', true, false);
		$tag2 = new SystemTag(456, 'Two', true, true);

		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(true)
			->will($this->returnValue([$tag1, $tag2]));

		$children = $this->getNode(false)->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[0]);
		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[1]);
		$this->assertEquals($tag1, $children[0]->getSystemTag());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	public function testGetChildrenEmpty() {
		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(null)
			->will($this->returnValue([]));
		$this->assertCount(0, $this->getNode()->getChildren());
	}

	public function childExistsProvider() {
		return [
			[true, true],
			[false, false],
		];
	}

	/**
	 * @dataProvider childExistsProvider
	 */
	public function testChildExists($userVisible, $expectedResult) {
		$tag = new SystemTag(123, 'One', $userVisible, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->will($this->returnValue($userVisible));

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->will($this->returnValue([$tag]));

		$this->assertEquals($expectedResult, $this->getNode()->childExists('123'));
	}

	public function testChildExistsNotFound() {
		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->will($this->throwException(new TagNotFoundException()));

		$this->assertFalse($this->getNode()->childExists('123'));
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\BadRequest
	 */
	public function testChildExistsBadRequest() {
		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['invalid'])
			->will($this->throwException(new \InvalidArgumentException()));

		$this->getNode()->childExists('invalid');
	}
}
