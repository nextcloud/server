<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\SystemTag;

use OC\SystemTag\SystemTag;
use OCA\DAV\SystemTag\SystemTagsByIdCollection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

class SystemTagsByIdCollectionTest extends \Test\TestCase {
	private ISystemTagManager&MockObject $tagManager;
	private IUser&MockObject $user;

	protected function setUp(): void {
		parent::setUp();

		$this->tagManager = $this->createMock(ISystemTagManager::class);
	}

	public function getNode(bool $isAdmin = true) {
		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('testuser');

		/** @var IUserSession&MockObject */
		$userSession = $this->createMock(IUserSession::class);
		$userSession->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		/** @var IGroupManager&MockObject */
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->expects($this->any())
			->method('isAdmin')
			->with('testuser')
			->willReturn($isAdmin);

		/** @var ISystemTagObjectMapper&MockObject */
		$tagMapper = $this->createMock(ISystemTagObjectMapper::class);
		return new SystemTagsByIdCollection(
			$this->tagManager,
			$userSession,
			$groupManager,
			$tagMapper,
		);
	}

	public static function adminFlagProvider(): array {
		return [[true], [false]];
	}


	public function testForbiddenCreateFile(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->getNode()->createFile('555');
	}


	public function testForbiddenCreateDirectory(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->getNode()->createDirectory('789');
	}

	public function testGetChild(): void {
		$tag = new SystemTag('123', 'Test', true, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn(true);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->willReturn([$tag]);

		$childNode = $this->getNode()->getChild('123');

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $childNode);
		$this->assertEquals('123', $childNode->getName());
		$this->assertEquals($tag, $childNode->getSystemTag());
	}


	public function testGetChildInvalidName(): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['invalid'])
			->will($this->throwException(new \InvalidArgumentException()));

		$this->getNode()->getChild('invalid');
	}


	public function testGetChildNotFound(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['444'])
			->will($this->throwException(new TagNotFoundException()));

		$this->getNode()->getChild('444');
	}


	public function testGetChildUserNotVisible(): void {
		$this->expectException(\Sabre\DAV\Exception\NotFound::class);

		$tag = new SystemTag('123', 'Test', false, false);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->willReturn([$tag]);

		$this->getNode(false)->getChild('123');
	}

	public function testGetChildrenAdmin(): void {
		$tag1 = new SystemTag('123', 'One', true, false);
		$tag2 = new SystemTag('456', 'Two', true, true);

		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(null)
			->willReturn([$tag1, $tag2]);

		$children = $this->getNode(true)->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[0]);
		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[1]);
		$this->assertEquals($tag1, $children[0]->getSystemTag());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	public function testGetChildrenNonAdmin(): void {
		$tag1 = new SystemTag('123', 'One', true, false);
		$tag2 = new SystemTag('456', 'Two', true, true);

		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(true)
			->willReturn([$tag1, $tag2]);

		$children = $this->getNode(false)->getChildren();

		$this->assertCount(2, $children);

		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[0]);
		$this->assertInstanceOf('\OCA\DAV\SystemTag\SystemTagNode', $children[1]);
		$this->assertEquals($tag1, $children[0]->getSystemTag());
		$this->assertEquals($tag2, $children[1]->getSystemTag());
	}

	public function testGetChildrenEmpty(): void {
		$this->tagManager->expects($this->once())
			->method('getAllTags')
			->with(null)
			->willReturn([]);
		$this->assertCount(0, $this->getNode()->getChildren());
	}

	public static function childExistsProvider(): array {
		return [
			[true, true],
			[false, false],
		];
	}

	/**
	 * @dataProvider childExistsProvider
	 */
	public function testChildExists(bool $userVisible, bool $expectedResult): void {
		$tag = new SystemTag('123', 'One', $userVisible, false);
		$this->tagManager->expects($this->once())
			->method('canUserSeeTag')
			->with($tag)
			->willReturn($userVisible);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->willReturn([$tag]);

		$this->assertEquals($expectedResult, $this->getNode()->childExists('123'));
	}

	public function testChildExistsNotFound(): void {
		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['123'])
			->will($this->throwException(new TagNotFoundException()));

		$this->assertFalse($this->getNode()->childExists('123'));
	}


	public function testChildExistsBadRequest(): void {
		$this->expectException(\Sabre\DAV\Exception\BadRequest::class);

		$this->tagManager->expects($this->once())
			->method('getTagsByIds')
			->with(['invalid'])
			->will($this->throwException(new \InvalidArgumentException()));

		$this->getNode()->childExists('invalid');
	}
}
