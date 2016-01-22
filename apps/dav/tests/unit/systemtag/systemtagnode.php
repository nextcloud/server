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
	 * @var \OCP\SystemTag\ISystemTagManager
	 */
	private $tagManager;

	protected function setUp() {
		parent::setUp();

		$this->tagManager = $this->getMock('\OCP\SystemTag\ISystemTagManager');
	}

	protected function getTagNode($isAdmin = true, $tag = null) {
		if ($tag === null) {
			$tag = new SystemTag(1, 'Test', true, true);
		}
		return new \OCA\DAV\SystemTag\SystemTagNode(
			$tag,
			$isAdmin,
			$this->tagManager
		);
	}

	public function adminFlagProvider() {
		return [[true], [false]];
	}

	/**
	 * @dataProvider adminFlagProvider
	 */
	public function testGetters($isAdmin) {
		$tag = new SystemTag('1', 'Test', true, true);
		$node = $this->getTagNode($isAdmin, $tag);
		$this->assertEquals('1', $node->getName());
		$this->assertEquals($tag, $node->getSystemTag());
	}

	/**
	 * @expectedException Sabre\DAV\Exception\MethodNotAllowed
	 */
	public function testSetName() {
		$this->getTagNode()->setName('2');
	}

	public function tagNodeProvider() {
		return [
			// admin
			[
				true,
				new SystemTag(1, 'Original', true, true),
				['Renamed', true, true]
			],
			[
				true,
				new SystemTag(1, 'Original', true, true),
				['Original', false, false]
			],
			// non-admin
			[
				// renaming allowed
				false,
				new SystemTag(1, 'Original', true, true),
				['Rename', true, true]
			],
		];
	}

	/**
	 * @dataProvider tagNodeProvider
	 */
	public function testUpdateTag($isAdmin, $originalTag, $changedArgs) {
		$this->tagManager->expects($this->once())
			->method('updateTag')
			->with(1, $changedArgs[0], $changedArgs[1], $changedArgs[2]);
		$this->getTagNode($isAdmin, $originalTag)
			->update($changedArgs[0], $changedArgs[1], $changedArgs[2]);
	}

	public function tagNodeProviderPermissionException() {
		return [
			[
				// changing permissions not allowed
				new SystemTag(1, 'Original', true, true),
				['Original', false, true],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// changing permissions not allowed
				new SystemTag(1, 'Original', true, true),
				['Original', true, false],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// changing permissions not allowed
				new SystemTag(1, 'Original', true, true),
				['Original', false, false],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// changing non-assignable not allowed
				new SystemTag(1, 'Original', true, false),
				['Rename', true, false],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// changing non-assignable not allowed
				new SystemTag(1, 'Original', true, false),
				['Original', true, true],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// invisible tag does not exist
				new SystemTag(1, 'Original', false, false),
				['Rename', false, false],
				'Sabre\DAV\Exception\NotFound',
			],
		];
	}

	/**
	 * @dataProvider tagNodeProviderPermissionException
	 */
	public function testUpdateTagPermissionException($originalTag, $changedArgs, $expectedException = null) {
		$this->tagManager->expects($this->never())
			->method('updateTag');

		$thrown = null;

		try {
			$this->getTagNode(false, $originalTag)
				->update($changedArgs[0], $changedArgs[1], $changedArgs[2]);
		} catch (\Exception $e) {
			$thrown = $e;
		}

		$this->assertInstanceOf($expectedException, $thrown);
	}

	/**
	 * @expectedException Sabre\DAV\Exception\Conflict
	 */
	public function testUpdateTagAlreadyExists() {
		$this->tagManager->expects($this->once())
			->method('updateTag')
			->with(1, 'Renamed', false, true)
			->will($this->throwException(new TagAlreadyExistsException()));
		$this->getTagNode()->update('Renamed', false, true);
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testUpdateTagNotFound() {
		$this->tagManager->expects($this->once())
			->method('updateTag')
			->with(1, 'Renamed', false, true)
			->will($this->throwException(new TagNotFoundException()));
		$this->getTagNode()->update('Renamed', false, true);
	}

	/**
	 * @dataProvider adminFlagProvider
	 */
	public function testDeleteTag($isAdmin) {
		$this->tagManager->expects($this->once())
			->method('deleteTags')
			->with('1');
		$this->getTagNode($isAdmin)->delete();
	}

	public function tagNodeDeleteProviderPermissionException() {
		return [
			[
				// cannot delete invisible tag
				new SystemTag(1, 'Original', false, true),
				'Sabre\DAV\Exception\NotFound',
			],
			[
				// cannot delete non-assignable tag
				new SystemTag(1, 'Original', true, false),
				'Sabre\DAV\Exception\Forbidden',
			],
		];
	}

	/**
	 * @dataProvider tagNodeDeleteProviderPermissionException
	 */
	public function testDeleteTagPermissionException($tag, $expectedException) {
		$this->tagManager->expects($this->never())
			->method('deleteTags');

		try {
			$this->getTagNode(false, $tag)->delete();
		} catch (\Exception $e) {
			$thrown = $e;
		}

		$this->assertInstanceOf($expectedException, $thrown);
	}

	/**
	 * @expectedException Sabre\DAV\Exception\NotFound
	 */
	public function testDeleteTagNotFound() {
		$this->tagManager->expects($this->once())
			->method('deleteTags')
			->with('1')
			->will($this->throwException(new TagNotFoundException()));
		$this->getTagNode()->delete();
	}
}
