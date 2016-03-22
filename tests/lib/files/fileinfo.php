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


namespace Test\Files;

/**
 * Class FileInfo
 *
 * @package Test\Files
 */
class FileInfo extends \Test\TestCase {

	protected function getMockStorage() {
		$storage = $this->getMock('\OCP\Files\Storage');
		$storage->expects($this->any())
			->method('getId')
			->will($this->returnValue('home::someuser'));
		return $storage;
	}

	protected function getMockSharedStorage() {
		$storage = $this->getMock('\OCP\Files\Storage');
		$storage->expects($this->any())
			->method('getId')
			->will($this->returnValue('shared::abcdefg'));
		return $storage;
	}

	protected function getMockMount() {
		$mount = $this->getMock('\OCP\Files\Mount');
		return $mount;
	}

	protected function getMockMoveableMount() {
		$mount = $this->getMock('\OC\Files\Mount\MoveableMount');
		return $mount;
	}

	public function testGetPermissions() {
		$fileInfo = new \OC\Files\FileInfo(
			'/bar/foo',
			$this->getMockStorage(),
			'/bar/foo',
			['permissions' => \OCP\Constants::PERMISSION_ALL],
			$this->getMockMount()
		);

		$this->assertEquals(
			\OCP\Constants::PERMISSION_ALL,
			$fileInfo->getPermissions()
		);
	}

	/**
	 * @dataProvider moveablePermissionsProvider
	 */
	public function testGetPermissionsOnMount($mount, $mountPerms, $expectedPerms) {
		$fileInfo = new \OC\Files\FileInfo(
			'',
			$this->getMockStorage(),
			'',
			['permissions' => $mountPerms],
			$mount
		);

		$this->assertEquals(
			$expectedPerms,
			$fileInfo->getPermissions()
		);
	}

	public function moveablePermissionsProvider() {
		return [
			// regular mount with all permissions
			[
				$this->getMockMount(),
				\OCP\Constants::PERMISSION_ALL,
				\OCP\Constants::PERMISSION_ALL - (\OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE),
			],
			// regular mount with less permissions
			[
				$this->getMockMount(),
				\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE,
				\OCP\Constants::PERMISSION_READ,
			],
			// regular mount with less permissions
			[
				$this->getMockMount(),
				\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_DELETE,
				\OCP\Constants::PERMISSION_READ,
			],
			// moveable mount with all permissions
			[
				$this->getMockMoveableMount(),
				\OCP\Constants::PERMISSION_ALL,
				\OCP\Constants::PERMISSION_ALL,
			],
			// moveable mount with less permissions
			[
				$this->getMockMoveableMount(),
				\OCP\Constants::PERMISSION_READ,
				// adds update and delete permissions even when they were not set
				\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE,
			],
		];
	}
}
