<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\SystemTag;

use OC\SystemTag\SystemTag;
use OCA\DAV\SystemTag\SystemTagsObjectMappingCollection;
use OCP\IUser;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

class SystemTagsObjectMappingCollectionTest extends \Test\TestCase {
	private ISystemTagManager&MockObject $tagManager;
	private ISystemTagObjectMapper&MockObject $tagMapper;
	private IUser&MockObject $user;

	protected function setUp(): void {
		parent::setUp();

		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		$this->user = $this->createMock(IUser::class);
	}

	public function getNode(array $writableNodeIds = []): SystemTagsObjectMappingCollection {
		return new SystemTagsObjectMappingCollection(
			'111',
			'files',
			$this->user,
			$this->tagManager,
			$this->tagMapper,
			fn ($id): bool => in_array($id, $writableNodeIds),
		);
	}

	public function testAssignTag(): void {
		$tag = new SystemTag('1', 'Test', true, true);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);
		$this->tagManager->expects($this->once())
			->method('canUserAssignTag')
			->with($tag)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn([$tag]);
		$this->tagMapper->expects($this->once())
			->method('assignTags')
			->with(111, 'files', '555');

		$this->getNode([111])->createFile('555');
	}

	public function testAssignTagForbidden(): void {
		$tag = new SystemTag('1', 'Test', true, true);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);
		$this->tagManager->expects($this->once())
			->method('canUserAssignTag')
			->with($tag)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn([$tag]);
		$this->tagMapper->expects($this->never())
			->method('assignTags');

		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);
		$this->getNode()->createFile('555');
	}

	public static function permissionsProvider(): array {
		return [
			// invisible, tag does not exist for user
			[false, true, '\Sabre\DAV\Exception\PreconditionFailed'],
			// visible but static, cannot assign tag
			[true, false, '\Sabre\DAV\Exception\Forbidden'],
		];
	}

	/**
	 * @dataProvider permissionsProvider
	 */
	public function testAssignTagNoPermission(bool $userVisible, bool $userAssignable, string $expectedException): void {
		$tag = new SystemTag('1', 'Test', $userVisible, $userAssignable);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn($userVisible);
		$this->tagManager->expects($this->any())
			->method('canUserAssignTag')
			->with($tag)
			->willReturn($userAssignable);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn([$tag]);
		$this->tagMapper->expects($this->never())
			->method('assignTags');

		$thrown = null;
		try {
			$this->getNode()->createFile('555');
		} catch (\Exception $e) {
			$thrown = $e;
		}

		$this->assertInstanceOf($expectedException, $thrown);
	}


	public function testAssignTagNotFound(): void {
		$this->expectException(\Sabre\DAV\Exception\PreconditionFailed::class);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->will($this->throwException(new TagNotFoundException()));

		$this->getNode()->createFile('555');
	}


	public function testForbiddenCreateDirectory(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->getNode()->createDirectory('789');
	}

	public function testGetChild(): void {
		$tag = new SystemTag('555', 'TheTag', true, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555', true)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn(['555' => $tag]);

		$childNode = $this->getNode()->getChild('555');

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagMappingNode', $childNode);
		$this->assertEquals('555', $childNode->getName());
	}


	public function testGetChildNonVisible(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$tag = new SystemTag('555', 'TheTag', false, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555', true)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn(['555' => $tag]);

		$this->getNode()->getChild('555');
	}


	public function testGetChildRelationNotFound(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '777')
			->willReturn(false);

		$this->getNode()->getChild('777');
	}


	public function testGetChildInvalidId(): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', 'badid')
			->will($this->throwException(new \InvalidArgumentException()));

		$this->getNode()->getChild('badid');
	}


	public function testGetChildTagDoesNotExist(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '777')
			->will($this->throwException(new TagNotFoundException()));

		$this->getNode()->getChild('777');
	}

	public function testGetChildren(): void {
		$tag1 = new SystemTag('555', 'TagOne', true, false);
		$tag2 = new SystemTag('556', 'TagTwo', true, true);
		$tag3 = new SystemTag('557', 'InvisibleTag', false, true);

		$this->tagMapper->expects($this->once())
			->method('getTagIdsForObjects')
			->with([111], 'files')
			->willReturn(['111' => ['555', '556', '557']]);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555', '556', '557'])
			->willReturn(['555' => $tag1, '556' => $tag2, '557' => $tag3]);

		$this->tagManager->expects($this->exactly(3))
			->method('canUserSeeTag')
			->willReturnCallback(function ($tag) {
				return $tag->isUserVisible();
			});

		$children = $this->getNode()->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagMappingNode', $children[0]);
		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagMappingNode', $children[1]);

		$this->assertEquals(111, $children[0]->getObjectId());
		$this->assertEquals('files', $children[0]->getObjectType());
		$this->assertEquals($tag1, $children[0]->getSystemTag());

		$this->assertEquals(111, $children[1]->getObjectId());
		$this->assertEquals('files', $children[1]->getObjectType());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	public function testChildExistsWithVisibleTag(): void {
		$tag = new SystemTag('555', 'TagOne', true, false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn([$tag]);

		$this->assertTrue($this->getNode()->childExists('555'));
	}

	public function testChildExistsWithInvisibleTag(): void {
		$tag = new SystemTag('555', 'TagOne', false, false);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['555'])
			->willReturn([$tag]);

		$this->assertFalse($this->getNode()->childExists('555'));
	}

	public function testChildExistsNotFound(): void {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->willReturn(false);

		$this->assertFalse($this->getNode()->childExists('555'));
	}

	public function testChildExistsTagNotFound(): void {
		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->throwException(new TagNotFoundException()));

		$this->assertFalse($this->getNode()->childExists('555'));
	}


	public function testChildExistsInvalidId(): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);

		$this->tagMapper->expects($this->once())
			->method('haveTag')
			->with([111], 'files', '555')
			->will($this->throwException(new \InvalidArgumentException()));

		$this->getNode()->childExists('555');
	}


	public function testDelete(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->getNode()->delete();
	}


	public function testSetName(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->getNode()->setName('somethingelse');
	}

	public function testGetName(): void {
		$this->assertEquals('111', $this->getNode()->getName());
	}
}
