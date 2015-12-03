<?php
/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\DAV\Tests\Unit\SystemTag;

class SystemTagsObjectTypeCollection extends \Test\TestCase {

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

		$this->node = new \OCA\DAV\SystemTag\SystemTagsObjectTypeCollection(
			'files',
			$this->tagManager,
			$this->tagMapper
		);
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
		$childNode = $this->node->getChild('files');

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagsObjectMappingCollection', $childNode);
		$this->assertEquals('files', $childNode->getName());
	}

	/**
	 * @expectedException Sabre\DAV\Exception\MethodNotAllowed
	 */
	public function testGetChildren() {
		$this->node->getChildren();
	}

	public function testChildExists() {
		$this->assertTrue($this->node->childExists('123'));
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
		$this->assertEquals('files', $this->node->getName());
	}
}
