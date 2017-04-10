<?php
/**
 * @copyright 2017, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace Test\Share20;

use OC\Share20\ShareHelper;
use OCP\Files\Node;
use OCP\Share\IManager;
use Test\TestCase;

class ShareHelperTests extends TestCase {

	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	private $manager;

	/** @var ShareHelper */
	private $helper;

	public function setUp() {
		parent::setUp();

		$this->manager = $this->createMock(IManager::class);

		$this->helper = new ShareHelper($this->manager);
	}

	public function dataGetPathsForAccessList() {
		return [
			[[], [], false, [], [], false, [
				'users' => [],
				'remotes' => [],
			]],
			[['user1', 'user2'], ['user1' => 'foo', 'user2' => 'bar'], true, [], [], false, [
				'users' => ['user1' => 'foo', 'user2' => 'bar'],
				'remotes' => [],
			]],
			[[], [], false, ['remote1', 'remote2'], ['remote1' => 'qwe', 'remote2' => 'rtz'], true, [
				'users' => [],
				'remotes' => ['remote1' => 'qwe', 'remote2' => 'rtz'],
			]],
			[['user1', 'user2'], ['user1' => 'foo', 'user2' => 'bar'], true, ['remote1', 'remote2'], ['remote1' => 'qwe', 'remote2' => 'rtz'], true, [
				'users' => ['user1' => 'foo', 'user2' => 'bar'],
				'remotes' => ['remote1' => 'qwe', 'remote2' => 'rtz'],
			]],
		];
	}

	/**
	 * @dataProvider dataGetPathsForAccessList
	 */
	public function testGetPathsForAccessList(array $userList, array $userMap, $resolveUsers, array $remoteList, array $remoteMap, $resolveRemotes, array $expected) {
		$this->manager->expects($this->once())
			->method('getAccessList')
			->willReturn([
				'users' => $userList,
				'remote' => $remoteList,
			]);

		/** @var Node|\PHPUnit_Framework_MockObject_MockObject $node */
		$node = $this->createMock(Node::class);
		/** @var ShareHelper|\PHPUnit_Framework_MockObject_MockObject $helper */
		$helper = $this->getMockBuilder(ShareHelper::class)
			->setConstructorArgs([$this->manager])
			->setMethods(['getPathsForUsers', 'getPathsForRemotes'])
			->getMock();

		$helper->expects($resolveUsers ? $this->once() : $this->never())
			->method('getPathsForUsers')
			->with($node, $userList)
			->willReturn($userMap);

		$helper->expects($resolveRemotes ? $this->once() : $this->never())
			->method('getPathsForRemotes')
			->with($node, $remoteList)
			->willReturn($remoteMap);

		$this->assertSame($expected, $helper->getPathsForAccessList($node));
	}

	public function dataGetMountedPath() {
		return [
			['/admin/files/foobar', '/foobar'],
			['/admin/files/foo/bar', '/foo/bar'],
		];
	}

	/**
	 * @dataProvider dataGetMountedPath
	 * @param string $path
	 * @param string $expected
	 */
	public function testGetMountedPath($path, $expected) {
		/** @var Node|\PHPUnit_Framework_MockObject_MockObject $node */
		$node = $this->createMock(Node::class);
		$node->expects($this->once())
			->method('getPath')
			->willReturn($path);

		$this->assertSame($expected, self::invokePrivate($this->helper, 'getMountedPath', [$node]));
	}
}
