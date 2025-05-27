<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\SystemTag;

use OC\SystemTag\SystemTag;
use OCA\DAV\SystemTag\SystemTagMappingNode;
use OCP\IUser;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

class SystemTagMappingNodeTest extends \Test\TestCase {
	private ISystemTagManager&MockObject $tagManager;
	private ISystemTagObjectMapper&MockObject $tagMapper;
	private IUser&MockObject $user;

	protected function setUp(): void {
		parent::setUp();

		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$this->user = $this->createMock(IUser::class);
	}

	public function getMappingNode($tag = null, array $writableNodeIds = []) {
		if ($tag === null) {
			$tag = new SystemTag('1', 'Test', true, true);
		}
		return new SystemTagMappingNode(
			$tag,
			'123',
			'files',
			$this->user,
			$this->tagManager,
			$this->tagMapper,
			fn ($id): bool => in_array($id, $writableNodeIds),
		);
	}

	public function testGetters(): void {
		$tag = new SystemTag('1', 'Test', true, false);
		$node = $this->getMappingNode($tag);
		$this->assertEquals('1', $node->getName());
		$this->assertEquals($tag, $node->getSystemTag());
		$this->assertEquals(123, $node->getObjectId());
		$this->assertEquals('files', $node->getObjectType());
	}

	public function testDeleteTag(): void {
		$node = $this->getMappingNode(null, [123]);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($node->getSystemTag())
			->willReturn(true);
		$this->tagManager->expects($this->once())
			->method('canUserAssignTag')
			->with($node->getSystemTag())
			->willReturn(true);
		$this->tagManager->expects($this->never())
			->method('deleteTags');
		$this->tagMapper->expects($this->once())
			->method('unassignTags')
			->with(123, 'files', 1);

		$node->delete();
	}

	public function testDeleteTagForbidden(): void {
		$node = $this->getMappingNode();
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($node->getSystemTag())
			->willReturn(true);
		$this->tagManager->expects($this->once())
			->method('canUserAssignTag')
			->with($node->getSystemTag())
			->willReturn(true);
		$this->tagManager->expects($this->never())
			->method('deleteTags');
		$this->tagMapper->expects($this->never())
			->method('unassignTags');

		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$node->delete();
	}

	public static function tagNodeDeleteProviderPermissionException(): array {
		return [
			[
				// cannot unassign invisible tag
				new SystemTag('1', 'Original', false, true),
				'Sabre\DAV\Exception\NotFound',
			],
			[
				// cannot unassign non-assignable tag
				new SystemTag('1', 'Original', true, false),
				'Sabre\DAV\Exception\Forbidden',
			],
		];
	}

	/**
	 * @dataProvider tagNodeDeleteProviderPermissionException
	 */
	public function testDeleteTagExpectedException(ISystemTag $tag, $expectedException): void {
		$this->tagManager->expects($this->any())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn($tag->isUserVisible());
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->with($tag)
			->willReturn($tag->isUserAssignable());
		$this->tagManager->expects($this->never())
			->method('deleteTags');
		$this->tagMapper->expects($this->never())
			->method('unassignTags');

		$thrown = null;
		try {
			$this->getMappingNode($tag)->delete();
		} catch (\Exception $e) {
			$thrown = $e;
		}

		$this->assertInstanceOf($expectedException, $thrown);
	}


	public function testDeleteTagNotFound(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		// assuming the tag existed at the time the node was created,
		// but got deleted concurrently in the database
		$tag = new SystemTag('1', 'Test', true, true);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn($tag->isUserVisible());
		$this->tagManager->expects($this->once())
			->method('canUserAssignTag')
			->with($tag)
			->willReturn($tag->isUserAssignable());
		$this->tagMapper->expects($this->once())
			->method('unassignTags')
			->with(123, 'files', 1)
			->will($this->throwException(new TagNotFoundException()));

		$this->getMappingNode($tag, [123])->delete();
	}
}
