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

class SystemTagsByIdCollection extends \Test\TestCase {

	/**
	 * @var \OCA\DAV\SystemTag\SystemTagsByIdCollection
	 */
	private $node;

	/**
	 * @var \OCP\SystemTag\ISystemTagManager
	 */
	private $tagManager;

	protected function setUp() {
		parent::setUp();

		$this->tagManager = $this->getMock('\OCP\SystemTag\ISystemTagManager');

		$this->node = new \OCA\DAV\SystemTag\SystemTagsByIdCollection($this->tagManager);
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testForbiddenCreateFile() {
		$this->node->createFile('555');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Forbidden
	 */
	public function testForbiddenCreateDirectory() {
		$this->node->createDirectory('789');
	}

	public function testGetChild() {
		$tag = new SystemTag(123, 'Test', true, false);

		$this->tagManager->expects($this->once())
			->method('getTagsById')
			->with('123')
			->will($this->returnValue([$tag]));

		$childNode = $this->node->getChild('123');

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $childNode);
		$this->assertEquals('123', $childNode->getName());
		$this->assertEquals($tag, $childNode->getSystemTag());
	}

	/**
	 * @expectedException Sabre\DAV\Exception\BadRequest
	 */
	public function testGetChildInvalidName() {
		$this->tagManager->expects($this->once())
			->method('getTagsById')
			->with('invalid')
			->will($this->throwException(new \InvalidArgumentException()));

		$this->node->getChild('invalid');
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testGetChildNotFound() {
		$this->tagManager->expects($this->once())
			->method('getTagsById')
			->with('444')
			->will($this->throwException(new TagNotFoundException()));

		$this->node->getChild('444');
	}

	public function testGetChildren() {
		$tag1 = new SystemTag(123, 'One', true, false);
		$tag2 = new SystemTag(456, 'Two', true, true);

		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(true)
			->will($this->returnValue([$tag1, $tag2]));

		$children = $this->node->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[0]);
		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[1]);
		$this->assertEquals($tag1, $children[0]->getSystemTag());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	public function testGetChildrenEmpty() {
		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(true)
			->will($this->returnValue([]));
		$this->assertCount(0, $this->node->getChildren());
	}

	public function testChildExists() {
		$tag = new SystemTag(123, 'One', true, false);

		$this->tagManager->expects($this->once())
			->method('getTagsById')
			->with('123')
			->will($this->returnValue([$tag]));

		$this->assertTrue($this->node->childExists('123'));
	}

	public function testChildExistsNotFound() {
		$this->tagManager->expects($this->once())
			->method('getTagsById')
			->with('123')
			->will($this->throwException(new TagNotFoundException()));

		$this->assertFalse($this->node->childExists('123'));
	}

	/**
	 * @expectedException Sabre\DAV\Exception\BadRequest
	 */
	public function testChildExistsBadRequest() {
		$this->tagManager->expects($this->once())
			->method('getTagsById')
			->with('invalid')
			->will($this->throwException(new \InvalidArgumentException()));

		$this->node->childExists('invalid');
	}
}
