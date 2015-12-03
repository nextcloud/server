<?php
/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\DAV\Tests\Unit\SystemTag;


use OC\SystemTag\SystemTag;
use OCP\SystemTag\TagNotFoundException;
use OCP\SystemTag\TagAlreadyExistsException;

class SystemTagsObjectMappingCollection extends \Test\TestCase {

	/**
	 * @var \OCA\DAV\SystemTag\SystemTagsObjectTypeCollection
	 */
	private $node;

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

		$this->node = new \OCA\DAV\SystemTag\SystemTagsObjectMappingCollection (
			111,
			'files',
			$this->tagManager,
			$this->tagMapper
		);
	}

	public function testAssignTag() {
		$this->tagMapper->expects($this->once())
			->method('assignTags')
			->with(111, 'files', '555');

		$this->node->createFile('555');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\PreconditionFailed
	 */
	public function testAssignTagNotFound() {
		$this->tagMapper->expects($this->once())
			->method('assignTags')
			->with(111, 'files', '555')
			->will($this->throwException(new TagNotFoundException()));

		$this->node->createFile('555');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testForbiddenCreateDirectory() {
		$this->node->createDirectory('789');
	}

	public function testGetChild() {
		$tag = new SystemTag(555, 'TheTag', true, false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with(111, 'files', '555', true)
			->will($this->returnValue(true));

		$this->tagManager->expects($this->once())
			->method('getTagsById')
			->with('555')
			->will($this->returnValue([$tag]));

		$childNode = $this->node->getChild('555');

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $childNode);
		$this->assertEquals('555', $childNode->getName());
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildRelationNotFound() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with(111, 'files', '777')
			->will($this->returnValue(false));

		$this->node->getChild('777');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\BadRequest
	 */
	public function testGetChildInvalidId() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with(111, 'files', 'badid')
			->will($this->throwException(new \InvalidArgumentException()));

		$this->node->getChild('badid');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildTagDoesNotExist() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with(111, 'files', '777')
			->will($this->throwException(new TagNotFoundException()));

		$this->node->getChild('777');
	}

	public function testGetChildren() {
		$tag1 = new SystemTag(555, 'TagOne', true, false);
		$tag2 = new SystemTag(556, 'TagTwo', true, true);

		$this->tagMapper->expects($this->once())
			->method('getTagIdsForObjects')
			->with(111, 'files')
			->will($this->returnValue(['111' => ['555', '556']]));

		$this->tagManager->expects($this->once())
			->method('getTagsById')
			->with(['555', '556'])
			->will($this->returnValue(['555' => $tag1, '666' => $tag2]));

		$children = $this->node->getChildren();

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

	public function testChildExists() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with(111, 'files', '555')
			->will($this->returnValue(true));

		$this->assertTrue($this->node->childExists('555'));
	}

	public function testChildExistsNotFound() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with(111, 'files', '555')
			->will($this->returnValue(false));

		$this->assertFalse($this->node->childExists('555'));
	}

	public function testChildExistsTagNotFound() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with(111, 'files', '555')
			->will($this->throwException(new TagNotFoundException()));

		$this->assertFalse($this->node->childExists('555'));
	}

	/**
	 * @expectedException Sabre\DAV\Exception\BadRequest
	 */
	public function testChildExistsInvalidId() {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with(111, 'files', '555')
			->will($this->throwException(new \InvalidArgumentException()));

		$this->node->childExists('555');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testDelete() {
		$this->node->delete();
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testSetName() {
		$this->node->setName('somethingelse');
	}

	public function testGetName() {
		$this->assertEquals('111', $this->node->getName());
	}
}
