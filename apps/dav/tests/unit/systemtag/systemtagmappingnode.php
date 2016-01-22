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

class SystemTagMappingNode extends \Test\TestCase {

	/**
	 * @var \OCP\SystemTag\ISystemTagManager
	 */
	private $tagManager;

	/**
	 * @var \OCP\SystemTag\ISystemTagObjectMapper
	 */
	private $tagMapper;

	protected function setUp() {
		parent::setUp();

		$this->tagManager = $this->getMock('\OCP\SystemTag\ISystemTagManager');
		$this->tagMapper = $this->getMock('\OCP\SystemTag\ISystemTagObjectMapper');
	}

	public function getMappingNode($isAdmin = true, $tag = null) {
		if ($tag === null) {
			$tag = new SystemTag(1, 'Test', true, true);
		}
		return new \OCA\DAV\SystemTag\SystemTagMappingNode(
			$tag,
			123,
			'files',
			$isAdmin,
			$this->tagManager,
			$this->tagMapper
		);
	}

	public function testGetters() {
		$tag = new SystemTag(1, 'Test', true, false);
		$node = $this->getMappingNode(true, $tag);
		$this->assertEquals('1', $node->getName());
		$this->assertEquals($tag, $node->getSystemTag());
		$this->assertEquals(123, $node->getObjectId());
		$this->assertEquals('files', $node->getObjectType());
	}

	public function adminFlagProvider() {
		return [[true], [false]];
	}

	/**
	 * @dataProvider adminFlagProvider
	 */
	public function testDeleteTag($isAdmin) {
		$this->tagManager->expects($this->never())
			->method('deleteTags');
		$this->tagMapper->expects($this->once())
			->method('unassignTags')
			->with(123, 'files', 1);

		$this->getMappingNode($isAdmin)->delete();
	}

	public function tagNodeDeleteProviderPermissionException() {
		return [
			[
				// cannot unassign invisible tag
				new SystemTag(1, 'Original', false, true),
				'Sabre\DAV\Exception\NotFound',
			],
			[
				// cannot unassign non-assignable tag
				new SystemTag(1, 'Original', true, false),
				'Sabre\DAV\Exception\Forbidden',
			],
		];
	}

	/**
	 * @dataProvider tagNodeDeleteProviderPermissionException
	 */
	public function testDeleteTagExpectedException($tag, $expectedException) {
		$this->tagManager->expects($this->never())
			->method('deleteTags');
		$this->tagMapper->expects($this->never())
			->method('unassignTags');

		$thrown = null;
		try {
			$this->getMappingNode(false, $tag)->delete();
		} catch (\Exception $e) {
			$thrown = $e;
		}

		$this->assertInstanceOf($expectedException, $thrown);
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testDeleteTagNotFound() {
		$this->tagMapper->expects($this->once())
			->method('unassignTags')
			->with(123, 'files', 1)
			->will($this->throwException(new TagNotFoundException()));

		$this->getMappingNode()->delete();
	}
}
