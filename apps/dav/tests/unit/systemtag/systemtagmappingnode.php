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
use OC\SystemTag\SystemTag;
use OCP\SystemTag\TagNotFoundException;

class SystemTagMappingNode extends SystemTagNode {

	/**
	 * @var \OCA\DAV\SystemTag\SystemTagMappingNode
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
	 * @var \OCP\SystemTag\ISystemTag
	 */
	private $tag;

	protected function setUp() {
		parent::setUp();

		$this->tag = new SystemTag(1, 'Test', true, false);
		$this->tagManager = $this->getMock('\OCP\SystemTag\ISystemTagManager');
		$this->tagMapper = $this->getMock('\OCP\SystemTag\ISystemTagObjectMapper');

		$this->node = new \OCA\DAV\SystemTag\SystemTagMappingNode(
			$this->tag,
			123,
			'files',
			$this->tagManager,
			$this->tagMapper
		);
	}

	public function testGetters() {
		parent::testGetters();
		$this->assertEquals(123, $this->node->getObjectId());
		$this->assertEquals('files', $this->node->getObjectType());
	}

	public function testDeleteTag() {
		$this->tagManager->expects($this->never())
			->method('deleteTags');
		$this->tagMapper->expects($this->once())
			->method('unassignTags')
			->with(123, 'files', 1);

		$this->node->delete();
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testDeleteTagNotFound() {
		$this->tagMapper->expects($this->once())
			->method('unassignTags')
			->with(123, 'files', 1)
			->will($this->throwException(new TagNotFoundException()));

		$this->node->delete();
	}
}
