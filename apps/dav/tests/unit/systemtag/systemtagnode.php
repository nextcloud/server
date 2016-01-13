<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
