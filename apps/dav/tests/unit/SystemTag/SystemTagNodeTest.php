<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\SystemTag;

use Exception;
use OC\SystemTag\SystemTag;
use OCA\DAV\SystemTag\SystemTagNode;
use OCP\IUser;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\SystemTag\TagNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class SystemTagNodeTest extends TestCase {

	/**
	 * @var ISystemTagManager|MockObject
	 */
	private $tagManager;

	/**
	 * @var IUser
	 */
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->user = $this->createMock(IUser::class);
	}

	protected function getTagNode($isAdmin = true, $tag = null): SystemTagNode {
		if ($tag === null) {
			$tag = new SystemTag(1, 'Test', true, true);
		}
		return new SystemTagNode(
			$tag,
			$this->user,
			$isAdmin,
			$this->tagManager
		);
	}

	public function adminFlagProvider(): array {
		return [[true], [false]];
	}

	/**
	 * @dataProvider adminFlagProvider
	 */
	public function testGetters(bool $isAdmin) {
		$tag = new SystemTag('1', 'Test', true, true);
		$node = $this->getTagNode($isAdmin, $tag);
		$this->assertEquals('1', $node->getName());
		$this->assertEquals($tag, $node->getSystemTag());
	}


	public function testSetName() {
		$this->expectException(MethodNotAllowed::class);

		$this->getTagNode()->setName('2');
	}

	public function tagNodeProvider(): array {
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
	 * @throws Forbidden
	 * @throws NotFound
	 * @throws Conflict
	 */
	public function testUpdateTag(bool $isAdmin, ISystemTag $originalTag, array $changedArgs) {
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($originalTag)
			->willReturn($originalTag->isUserVisible() || $isAdmin);
		$this->tagManager->expects($this->once())
			->method('canUserAssignTag')
			->with($originalTag)
			->willReturn($originalTag->isUserAssignable() || $isAdmin);
		$this->tagManager->expects($this->once())
			->method('updateTag')
			->with(1, $changedArgs[0], $changedArgs[1], $changedArgs[2]);
		$this->getTagNode($isAdmin, $originalTag)
			->update($changedArgs[0], $changedArgs[1], $changedArgs[2]);
	}

	public function tagNodeProviderPermissionException(): array {
		return [
			[
				// changing permissions not allowed
				new SystemTag(1, 'Original', true, true),
				['Original', false, true],
				Forbidden::class,
			],
			[
				// changing permissions not allowed
				new SystemTag(1, 'Original', true, true),
				['Original', true, false],
				Forbidden::class,
			],
			[
				// changing permissions not allowed
				new SystemTag(1, 'Original', true, true),
				['Original', false, false],
				Forbidden::class,
			],
			[
				// changing non-assignable not allowed
				new SystemTag(1, 'Original', true, false),
				['Rename', true, false],
				Forbidden::class,
			],
			[
				// changing non-assignable not allowed
				new SystemTag(1, 'Original', true, false),
				['Original', true, true],
				Forbidden::class,
			],
			[
				// invisible tag does not exist
				new SystemTag(1, 'Original', false, false),
				['Rename', false, false],
				NotFound::class,
			],
		];
	}

	/**
	 * @dataProvider tagNodeProviderPermissionException
	 */
	public function testUpdateTagPermissionException(ISystemTag $originalTag, array $changedArgs, string $expectedException) {
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->with($originalTag)
			->willReturn($originalTag->isUserVisible());
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->with($originalTag)
			->willReturn($originalTag->isUserAssignable());
		$this->tagManager->expects($this->never())
			->method('updateTag');

		$thrown = null;

		try {
			$this->getTagNode(false, $originalTag)
				->update($changedArgs[0], $changedArgs[1], $changedArgs[2]);
		} catch (Exception $e) {
			$thrown = $e;
		}

		$this->assertInstanceOf($expectedException, $thrown);
	}


	/**
	 * @throws Forbidden
	 * @throws NotFound
	 */
	public function testUpdateTagAlreadyExists() {
		$this->expectException(Conflict::class);

		$tag = new SystemTag(1, 'tag1', true, true);
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->with($tag)
			->willReturn(true);
		$this->tagManager->expects($this->once())
			->method('updateTag')
			->with(1, 'Renamed', true, true)
			->will($this->throwException(new TagAlreadyExistsException()));
		$this->getTagNode(false, $tag)->update('Renamed', true, true);
	}


	/**
	 * @throws Conflict
	 * @throws Forbidden
	 */
	public function testUpdateTagNotFound() {
		$this->expectException(NotFound::class);

		$tag = new SystemTag(1, 'tag1', true, true);
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->with($tag)
			->willReturn(true);
		$this->tagManager->expects($this->once())
			->method('updateTag')
			->with(1, 'Renamed', true, true)
			->will($this->throwException(new TagNotFoundException()));
		$this->getTagNode(false, $tag)->update('Renamed', true, true);
	}

	/**
	 * @dataProvider adminFlagProvider
	 * @throws NotFound
	 */
	public function testDeleteTag(bool $isAdmin) {
		$tag = new SystemTag(1, 'tag1', true, true);
		$this->tagManager->expects($isAdmin ? $this->once() : $this->never())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);
		$this->tagManager->expects($isAdmin ? $this->once() : $this->never())
			->method('deleteTags')
			->with('1');
		if (!$isAdmin) {
			$this->expectException(Forbidden::class);
		}
		$this->getTagNode($isAdmin, $tag)->delete();
	}

	public function tagNodeDeleteProviderPermissionException(): array {
		return [
			[
				// cannot delete invisible tag
				new SystemTag(1, 'Original', false, true),
				Forbidden::class,
			],
			[
				// cannot delete non-assignable tag
				new SystemTag(1, 'Original', true, false),
				Forbidden::class,
			],
		];
	}

	/**
	 * @dataProvider tagNodeDeleteProviderPermissionException
	 * @throws NotFound|Forbidden
	 */
	public function testDeleteTagPermissionException(ISystemTag $tag, string $expectedException) {
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn($tag->isUserVisible());
		$this->tagManager->expects($this->never())
			->method('deleteTags');

		$this->expectException($expectedException);
		$this->getTagNode(false, $tag)->delete();
	}


	/**
	 * @throws Forbidden
	 * @throws NotFound
	 */
	public function testDeleteTagNotFound() {
		$this->expectException(NotFound::class);

		$tag = new SystemTag(1, 'tag1', true, true);
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn($tag->isUserVisible());
		$this->tagManager->expects($this->once())
			->method('deleteTags')
			->with('1')
			->will($this->throwException(new TagNotFoundException()));
		$this->getTagNode(true, $tag)->delete();
	}
}
