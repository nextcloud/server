<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
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

namespace OCA\DAV\Tests\Unit\SystemTag;


use OC\SystemTag\SystemTag;
use OCP\SystemTag\TagNotFoundException;

class SystemTagsObjectMappingCollection extends \Test\TestCase {

	/**
	 * @var \OCP\SystemTag\ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var \OCP\SystemTag\ISystemTagMapper
	 */
	private $tagMapper;

	protected function setUp() {
		parent::setUp();

		$this->tagManager = $this->getMock('\OCP\SystemTag\ISystemTagManager');
		$this->tagMapper = $this->getMock('\OCP\SystemTag\ISystemTagObjectMapper');
	}

	public function getNode($isAdmin = true) {
		return new \OCA\DAV\SystemTag\SystemTagsObjectMappingCollection (
			111,
			'files',
			$isAdmin,
			$this->tagManager,
			$this->tagMapper
		);
	}

	public function testAssignTagAsAdmin() {
		$this->tagManager->expects($this->never())
			->method('getTagsByIds');
		$this->tagMapper->expects($this->once())
			->method('assignTags')
			->with(111, 'files', '555');

		$this->getNode(true)->createFile('555');
	}

	public function testAssignTagAsUser() {
		$tag = new SystemTag('1', 'Test', true, true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with('555')
			->will($this->returnValue([$tag]));
		$this->tagMapper->expects($this->once())
			->method('assignTags')
			->with(111, 'files', '555');

		$this->getNode(false)->createFile('555');
	}

	public function permissionsProvider() {
		return [
			// invisible, tag does not exist for user
			[false, true, '\Sabre\DAV\Exception\PreconditionFailed'],
			// visible but static, cannot assign tag
			[true, false, '\Sabre\DAV\Exception\Forbidden'],
		];
	}

	/**
	 * @dataProvider permissionsProvider
	 */
	public function testAssignTagAsUserNoPermission($userVisible, $userAssignable, $expectedException) {
		$tag = new SystemTag('1', 'Test', $userVisible, $userAssignable);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with('555')
			->will($this->returnValue([$tag]));
		$this->tagMapper->expects($this->never())
			->method('assignTags');

		$thrown = null;
		try {
			$this->getNode(false)->createFile('555');
		} catch (\Exception $e) {
			$thrown = $e;
		}

		$this->assertInstanceOf($expectedException, $thrown);
	}

	/**
	 * @expectedException Sabre\DAV\Exception\PreconditionFailed
	 */
	public function testAssignTagNotFound() {
		$this->tagMapper->expects($this->once())
			->method('assignTags')
			->with(111, 'files', '555')
			->will($this->throwException(new TagNotFoundException()));

		$this->getNode()->createFile('555');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testForbiddenCreateDirectory() {
		$this->getNode()->createDirectory('789');
	}

	public function getChildProvider() {
		return [
			[
				true,
				true,
			],
			[
				true,
				false,
			],
			[
				false,
				true,
			],
		];
	}

	/**
	 * @dataProvider getChildProvider
	 */
	public function testGetChild($isAdmin, $userVisible) {
		$tag = new SystemTag(555, 'TheTag', $userVisible, false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555', true)
			->will($this->returnValue(true));

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->will($this->returnValue(['555' => $tag]));

		$childNode = $this->getNode($isAdmin)->getChild('555');

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $childNode);
		$this->assertEquals('555', $childNode->getName());
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildUserNonVisible() {
		$tag = new SystemTag(555, 'TheTag', false, false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555', true)
			->will($this->returnValue(true));

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->will($this->returnValue(['555' => $tag]));

		$this->getNode(false)->getChild('555');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildRelationNotFound() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '777')
			->will($this->returnValue(false));

		$this->getNode()->getChild('777');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\BadRequest
	 */
	public function testGetChildInvalidId() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', 'badid')
			->will($this->throwException(new \InvalidArgumentException()));

		$this->getNode()->getChild('badid');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildTagDoesNotExist() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '777')
			->will($this->throwException(new TagNotFoundException()));

		$this->getNode()->getChild('777');
	}

	public function testGetChildrenAsAdmin() {
		$tag1 = new SystemTag(555, 'TagOne', true, false);
		$tag2 = new SystemTag(556, 'TagTwo', true, true);
		$tag3 = new SystemTag(557, 'InvisibleTag', false, true);

		$this->tagMapper->expects($this->once())
			->method('getTagIdsForObjects')
			->with([111], 'files')
			->will($this->returnValue(['111' => ['555', '556', '557']]));

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555', '556', '557'])
			->will($this->returnValue(['555' => $tag1, '556' => $tag2, '557' => $tag3]));

		$children = $this->getNode(true)->getChildren();

		$this->assertCount(3, $children);

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagMappingNode', $children[0]);
		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagMappingNode', $children[1]);
		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagMappingNode', $children[2]);

		$this->assertEquals(111, $children[0]->getObjectId());
		$this->assertEquals('files', $children[0]->getObjectType());
		$this->assertEquals($tag1, $children[0]->getSystemTag());

		$this->assertEquals(111, $children[1]->getObjectId());
		$this->assertEquals('files', $children[1]->getObjectType());
		$this->assertEquals($tag2, $children[1]->getSystemTag());

		$this->assertEquals(111, $children[2]->getObjectId());
		$this->assertEquals('files', $children[2]->getObjectType());
		$this->assertEquals($tag3, $children[2]->getSystemTag());
	}

	public function testGetChildrenAsUser() {
		$tag1 = new SystemTag(555, 'TagOne', true, false);
		$tag2 = new SystemTag(556, 'TagTwo', true, true);
		$tag3 = new SystemTag(557, 'InvisibleTag', false, true);

		$this->tagMapper->expects($this->once())
			->method('getTagIdsForObjects')
			->with([111], 'files')
			->will($this->returnValue(['111' => ['555', '556', '557']]));

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555', '556', '557'])
			->will($this->returnValue(['555' => $tag1, '556' => $tag2, '557' => $tag3]));

		$children = $this->getNode(false)->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagMappingNode', $children[0]);
		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagMappingNode', $children[1]);

		$this->assertEquals(111, $children[0]->getObjectId());
		$this->assertEquals('files', $children[0]->getObjectType());
		$this->assertEquals($tag1, $children[0]->getSystemTag());

		$this->assertEquals(111, $children[1]->getObjectId());
		$this->assertEquals('files', $children[1]->getObjectType());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	public function testChildExistsAsAdmin() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->returnValue(true));

		$this->assertTrue($this->getNode(true)->childExists('555'));
	}

	public function testChildExistsWithVisibleTagAsUser() {
		$tag = new SystemTag(555, 'TagOne', true, false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->returnValue(true));

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with('555')
			->will($this->returnValue([$tag]));

		$this->assertTrue($this->getNode(false)->childExists('555'));
	}

	public function testChildExistsWithInvisibleTagAsUser() {
		$tag = new SystemTag(555, 'TagOne', false, false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->returnValue(true));

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with('555')
			->will($this->returnValue([$tag]));

		$this->assertFalse($this->getNode(false)->childExists('555'));
	}

	public function testChildExistsNotFound() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->returnValue(false));

		$this->assertFalse($this->getNode()->childExists('555'));
	}

	public function testChildExistsTagNotFound() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->throwException(new TagNotFoundException()));

		$this->assertFalse($this->getNode()->childExists('555'));
	}

	/**
	 * @expectedException Sabre\DAV\Exception\BadRequest
	 */
	public function testChildExistsInvalidId() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->throwException(new \InvalidArgumentException()));

		$this->getNode()->childExists('555');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testDelete() {
		$this->getNode()->delete();
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testSetName() {
		$this->getNode()->setName('somethingelse');
	}

	public function testGetName() {
		$this->assertEquals('111', $this->getNode()->getName());
	}
}
