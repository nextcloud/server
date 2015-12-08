<?php
/**
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\DAV\Tests\Unit\SystemTag;

use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\Conflict;

use OC\SystemTag\SystemTag;
use OCP\SystemTag\TagNotFoundException;
use OCP\SystemTag\TagAlreadyExistsException;

class SystemTagNode extends \Test\TestCase {

	/**
	 * @var \OCA\DAV\SystemTag\SystemTagNode
	 */
	private $node;

	/**
	 * @var \OCP\SystemTag\ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var \OCP\SystemTag\ISystemTag
	 */
	private $tag;

	protected function setUp() {
		parent::setUp();

		$this->tag = new SystemTag(1, 'Test', true, false);
		$this->tagManager = $this->getMock('\OCP\SystemTag\ISystemTagManager');

		$this->node = new \OCA\DAV\SystemTag\SystemTagNode($this->tag, $this->tagManager);
	}

	public function testGetters() {
		$this->assertEquals('1', $this->node->getName());
		$this->assertEquals($this->tag, $this->node->getSystemTag());
	}

	/**
	 * @expectedException Sabre\DAV\Exception\MethodNotAllowed
	 */
	public function testSetName() {
		$this->node->setName('2');
	}

	public function testUpdateTag() {
		$this->tagManager->expects($this->once())
			->method('updateTag')
			->with(1, 'Renamed', false, true);
		$this->node->update('Renamed', false, true);
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Conflict
	 */
	public function testUpdateTagAlreadyExists() {
		$this->tagManager->expects($this->once())
			->method('updateTag')
			->with(1, 'Renamed', false, true)
			->will($this->throwException(new TagAlreadyExistsException()));
		$this->node->update('Renamed', false, true);
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testUpdateTagNotFound() {
		$this->tagManager->expects($this->once())
			->method('updateTag')
			->with(1, 'Renamed', false, true)
			->will($this->throwException(new TagNotFoundException()));
		$this->node->update('Renamed', false, true);
	}

	public function testDeleteTag() {
		$this->tagManager->expects($this->once())
			->method('deleteTags')
			->with('1');
		$this->node->delete();
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testDeleteTagNotFound() {
		$this->tagManager->expects($this->once())
			->method('deleteTags')
			->with('1')
			->will($this->throwException(new TagNotFoundException()));
		$this->node->delete();
	}
}
