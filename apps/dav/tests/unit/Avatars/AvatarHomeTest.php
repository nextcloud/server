<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\Unit\Avatars;

use OCA\DAV\Avatars\AvatarHome;
use OCA\DAV\Avatars\AvatarNode;
use OCP\IAvatar;
use OCP\IAvatarManager;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class AvatarHomeTest extends TestCase {

	/** @var AvatarHome */
	private $home;

	/** @var IAvatarManager | \PHPUnit\Framework\MockObject\MockObject */
	private $avatarManager;

	protected function setUp(): void {
		parent::setUp();
		$this->avatarManager = $this->createMock(IAvatarManager::class);
		$this->home = new AvatarHome(['uri' => 'principals/users/admin'], $this->avatarManager);
	}

	/**
	 * @dataProvider providesForbiddenMethods
	 */
	public function testForbiddenMethods($method): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$this->home->$method('');
	}

	public function providesForbiddenMethods() {
		return [
			['createFile'],
			['createDirectory'],
			['delete'],
			['setName']
		];
	}

	public function testGetName(): void {
		$n = $this->home->getName();
		self::assertEquals('admin', $n);
	}

	public function providesTestGetChild() {
		return [
			[MethodNotAllowed::class, false, ''],
			[MethodNotAllowed::class, false, 'bla.foo'],
			[MethodNotAllowed::class, false, 'bla.png'],
			[NotFound::class, false, '512.png'],
			[null, true, '512.png'],
		];
	}

	/**
	 * @dataProvider providesTestGetChild
	 */
	public function testGetChild($expectedException, $hasAvatar, $path): void {
		if ($expectedException !== null) {
			$this->expectException($expectedException);
		}

		$avatar = $this->createMock(IAvatar::class);
		$avatar->method('exists')->willReturn($hasAvatar);

		$this->avatarManager->expects($this->any())->method('getAvatar')->with('admin')->willReturn($avatar);
		$avatarNode = $this->home->getChild($path);
		$this->assertInstanceOf(AvatarNode::class, $avatarNode);
	}

	public function testGetChildren(): void {
		$avatarNodes = $this->home->getChildren();
		self::assertEquals(0, count($avatarNodes));

		$avatar = $this->createMock(IAvatar::class);
		$avatar->expects($this->once())->method('exists')->willReturn(true);
		$this->avatarManager->expects($this->any())->method('getAvatar')->with('admin')->willReturn($avatar);
		$avatarNodes = $this->home->getChildren();
		self::assertEquals(1, count($avatarNodes));
	}

	/**
	 * @dataProvider providesTestGetChild
	 */
	public function testChildExists($expectedException, $hasAvatar, $path): void {
		$avatar = $this->createMock(IAvatar::class);
		$avatar->method('exists')->willReturn($hasAvatar);

		$this->avatarManager->expects($this->any())->method('getAvatar')->with('admin')->willReturn($avatar);
		$childExists = $this->home->childExists($path);
		$this->assertEquals($hasAvatar, $childExists);
	}

	public function testGetLastModified(): void {
		self::assertNull($this->home->getLastModified());
	}
}
