<?php
/**
 * @copyright Copyright (c) 2017, ownCloud GmbH
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\DAV\Tests\Unit\Avatars;

use OCA\DAV\Avatars\AvatarHome;
use OCA\DAV\Avatars\AvatarNode;
use OCP\IAvatar;
use OCP\IAvatarManager;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class AvatarHomeTest extends TestCase {

	/** @var AvatarHome */
	private $home;

	/** @var IAvatarManager | MockObject */
	private $avatarManager;

	protected function setUp(): void {
		parent::setUp();
		$this->avatarManager = $this->createMock(IAvatarManager::class);
		$this->home = new AvatarHome(['uri' => 'principals/users/admin'], $this->avatarManager);
	}

	/**
	 * @dataProvider providesForbiddenMethods
	 */
	public function testForbiddenMethods(string $method) {
		$this->expectException(Forbidden::class);

		$this->home->$method('');
	}

	public function providesForbiddenMethods(): array {
		return [
			['createFile'],
			['createDirectory'],
			['delete'],
			['setName']
		];
	}

	public function testGetName() {
		$n = $this->home->getName();
		self::assertEquals('admin', $n);
	}

	public function providesTestGetChild(): array {
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
	 * @throws MethodNotAllowed|NotFound
	 */
	public function testGetChild(?string $expectedException, bool $hasAvatar, string $path) {
		if ($expectedException !== null) {
			$this->expectException($expectedException);
		}

		$avatar = $this->createMock(IAvatar::class);
		$avatar->method('exists')->willReturn($hasAvatar);

		$this->avatarManager->expects($this->any())->method('getAvatar')->with('admin')->willReturn($avatar);
		$avatarNode = $this->home->getChild($path);
		$this->assertInstanceOf(AvatarNode::class, $avatarNode);
	}

	public function testGetChildren() {
		$avatarNodes = $this->home->getChildren();
		self::assertCount(0, $avatarNodes);

		$avatar = $this->createMock(IAvatar::class);
		$avatar->expects($this->once())->method('exists')->willReturn(true);
		$this->avatarManager->expects($this->any())->method('getAvatar')->with('admin')->willReturn($avatar);
		$avatarNodes = $this->home->getChildren();
		self::assertCount(1, $avatarNodes);
	}

	/**
	 * @dataProvider providesTestGetChild
	 */
	public function testChildExists(?string $expectedException, bool $hasAvatar, string $path) {
		$avatar = $this->createMock(IAvatar::class);
		$avatar->method('exists')->willReturn($hasAvatar);

		$this->avatarManager->expects($this->any())->method('getAvatar')->with('admin')->willReturn($avatar);
		$childExists = $this->home->childExists($path);
		$this->assertEquals($hasAvatar, $childExists);
	}

	public function testGetLastModified() {
		self::assertNull($this->home->getLastModified());
	}
}
