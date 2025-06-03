<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\SystemTag;

use OC\SystemTag\SystemTag;
use OCA\DAV\SystemTag\SystemTagNode;
use OCP\IUser;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagAlreadyExistsException;
use OCP\SystemTag\TagNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Forbidden;

class SystemTagNodeTest extends \Test\TestCase {
	private ISystemTagManager&MockObject $tagManager;
	private ISystemTagObjectMapper&MockObject $tagMapper;
	private IUser&MockObject $user;

	protected function setUp(): void {
		parent::setUp();

		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$this->user = $this->createMock(IUser::class);
	}

	protected function getTagNode($isAdmin = true, $tag = null) {
		if ($tag === null) {
			$tag = new SystemTag('1', 'Test', true, true);
		}
		return new SystemTagNode(
			$tag,
			$this->user,
			$isAdmin,
			$this->tagManager,
			$this->tagMapper,
		);
	}

	public static function adminFlagProvider(): array {
		return [[true], [false]];
	}

	/**
	 * @dataProvider adminFlagProvider
	 */
	public function testGetters(bool $isAdmin): void {
		$tag = new SystemTag('1', 'Test', true, true);
		$node = $this->getTagNode($isAdmin, $tag);
		$this->assertEquals('1', $node->getName());
		$this->assertEquals($tag, $node->getSystemTag());
	}


	public function testSetName(): void {
		$this->expectException(\Sabre\DAV\Exception\MethodNotAllowed::class);

		$this->getTagNode()->setName('2');
	}

	public static function tagNodeProvider(): array {
		return [
			// admin
			[
				true,
				new SystemTag('1', 'Original', true, true),
				['Renamed', true, true, null]
			],
			[
				true,
				new SystemTag('1', 'Original', true, true),
				['Original', false, false, null]
			],
			// non-admin
			[
				// renaming allowed
				false,
				new SystemTag('1', 'Original', true, true),
				['Rename', true, true, '0082c9']
			],
		];
	}

	/**
	 * @dataProvider tagNodeProvider
	 */
	public function testUpdateTag(bool $isAdmin, ISystemTag $originalTag, array $changedArgs): void {
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
			->with(1, $changedArgs[0], $changedArgs[1], $changedArgs[2], $changedArgs[3]);
		$this->getTagNode($isAdmin, $originalTag)
			->update($changedArgs[0], $changedArgs[1], $changedArgs[2], $changedArgs[3]);
	}

	public static function tagNodeProviderPermissionException(): array {
		return [
			[
				// changing permissions not allowed
				new SystemTag('1', 'Original', true, true),
				['Original', false, true, ''],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// changing permissions not allowed
				new SystemTag('1', 'Original', true, true),
				['Original', true, false, ''],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// changing permissions not allowed
				new SystemTag('1', 'Original', true, true),
				['Original', false, false, ''],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// changing non-assignable not allowed
				new SystemTag('1', 'Original', true, false),
				['Rename', true, false, ''],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// changing non-assignable not allowed
				new SystemTag('1', 'Original', true, false),
				['Original', true, true, ''],
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// invisible tag does not exist
				new SystemTag('1', 'Original', false, false),
				['Rename', false, false, ''],
				'Sabre\DAV\Exception\NotFound',
			],
		];
	}

	/**
	 * @dataProvider tagNodeProviderPermissionException
	 */
	public function testUpdateTagPermissionException(ISystemTag $originalTag, array $changedArgs, string $expectedException): void {
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
				->update($changedArgs[0], $changedArgs[1], $changedArgs[2], $changedArgs[3]);
		} catch (\Exception $e) {
			$thrown = $e;
		}

		$this->assertInstanceOf($expectedException, $thrown);
	}


	public function testUpdateTagAlreadyExists(): void {
		$this->expectException(\Sabre\DAV\Exception\Conflict::class);

		$tag = new SystemTag('1', 'tag1', true, true);
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
		$this->getTagNode(false, $tag)->update('Renamed', true, true, null);
	}


	public function testUpdateTagNotFound(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$tag = new SystemTag('1', 'tag1', true, true);
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
		$this->getTagNode(false, $tag)->update('Renamed', true, true, null);
	}

	/**
	 * @dataProvider adminFlagProvider
	 */
	public function testDeleteTag(bool $isAdmin): void {
		$tag = new SystemTag('1', 'tag1', true, true);
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

	public static function tagNodeDeleteProviderPermissionException(): array {
		return [
			[
				// cannot delete invisible tag
				new SystemTag('1', 'Original', false, true),
				'Sabre\DAV\Exception\Forbidden',
			],
			[
				// cannot delete non-assignable tag
				new SystemTag('1', 'Original', true, false),
				'Sabre\DAV\Exception\Forbidden',
			],
		];
	}

	/**
	 * @dataProvider tagNodeDeleteProviderPermissionException
	 */
	public function testDeleteTagPermissionException(ISystemTag $tag, string $expectedException): void {
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn($tag->isUserVisible());
		$this->tagManager->expects($this->never())
			->method('deleteTags');

		$this->expectException($expectedException);
		$this->getTagNode(false, $tag)->delete();
	}


	public function testDeleteTagNotFound(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$tag = new SystemTag('1', 'tag1', true, true);
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
