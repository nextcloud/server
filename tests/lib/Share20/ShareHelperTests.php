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
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use Test\TestCase;

class ShareHelperTests extends TestCase {

	/** @var IRootFolder|\PHPUnit_Framework_MockObject_MockObject */
	private $rootFolder;

	/** @var ShareHelper */
	private $helper;

	public function setUp() {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);

		$this->helper = new ShareHelper($this->rootFolder);
	}

	/**
	 * uid1 - Exists with valid node
	 * uid2 - Does not exist
	 * uid3 - Exists but no valid node
	 * uid4 - Exists with valid node
	 */
	public function testGetPathsForAccessList() {
		/** @var Folder[]|\PHPUnit_Framework_MockObject_MockObject[] $userFolder */
		$userFolder = [
			'uid1' => $this->createMock(Folder::class),
			'uid3' => $this->createMock(Folder::class),
			'uid4' => $this->createMock(Folder::class),
		];

		$this->rootFolder->method('getUserFolder')
			->willReturnCallback(function($uid) use ($userFolder) {
				if (isset($userFolder[$uid])) {
					return $userFolder[$uid];
				}
				throw new NotFoundException();
			});

		/** @var Node|\PHPUnit_Framework_MockObject_MockObject $node */
		$node = $this->createMock(Node::class);
		$node->method('getId')
			->willReturn(42);

		$userFolder['uid1']->method('getById')
			->with(42)
			->willReturn([$node]);
		$userFolder['uid3']->method('getById')
			->with(42)
			->willReturn([]);
		$userFolder['uid4']->method('getById')
			->with(42)
			->willReturn([$node]);

		$expects = [
			'uid1' => [$node],
			'uid4' => [$node],
		];

		$result = $this->helper->getPathsForAccessList($node, ['uid1', 'uid2', 'uid3', 'uid4']);

		$this->assertSame($expects, $result);
	}
}
