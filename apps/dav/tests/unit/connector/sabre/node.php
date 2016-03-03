<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\DAV\Tests\Unit\Connector\Sabre;

class Node extends \Test\TestCase {
	public function davPermissionsProvider() {
		return array(
			array(\OCP\Constants::PERMISSION_ALL, 'file', false, false, 'RDNVW'),
			array(\OCP\Constants::PERMISSION_ALL, 'dir', false, false, 'RDNVCK'),
			array(\OCP\Constants::PERMISSION_ALL, 'file', true, false, 'SRDNVW'),
			array(\OCP\Constants::PERMISSION_ALL, 'file', true, true, 'SRMDNVW'),
			array(\OCP\Constants::PERMISSION_ALL - \OCP\Constants::PERMISSION_SHARE, 'file', true, false, 'SDNVW'),
			array(\OCP\Constants::PERMISSION_ALL - \OCP\Constants::PERMISSION_UPDATE, 'file', false, false, 'RD'),
			array(\OCP\Constants::PERMISSION_ALL - \OCP\Constants::PERMISSION_DELETE, 'file', false, false, 'RNVW'),
			array(\OCP\Constants::PERMISSION_ALL - \OCP\Constants::PERMISSION_CREATE, 'file', false, false, 'RDNVW'),
			array(\OCP\Constants::PERMISSION_ALL - \OCP\Constants::PERMISSION_CREATE, 'dir', false, false, 'RDNV'),
		);
	}

	/**
	 * @dataProvider davPermissionsProvider
	 */
	public function testDavPermissions($permissions, $type, $shared, $mounted, $expected) {
		$info = $this->getMockBuilder('\OC\Files\FileInfo')
			->disableOriginalConstructor()
			->setMethods(array('getPermissions', 'isShared', 'isMounted', 'getType'))
			->getMock();
		$info->expects($this->any())
			->method('getPermissions')
			->will($this->returnValue($permissions));
		$info->expects($this->any())
			->method('isShared')
			->will($this->returnValue($shared));
		$info->expects($this->any())
			->method('isMounted')
			->will($this->returnValue($mounted));
		$info->expects($this->any())
			->method('getType')
			->will($this->returnValue($type));
		$view = $this->getMock('\OC\Files\View');

		$node = new  \OCA\DAV\Connector\Sabre\File($view, $info);
		$this->assertEquals($expected, $node->getDavPermissions());
	}

	public function sharePermissionsProvider() {
		return [
			[\OCP\Files\FileInfo::TYPE_FILE, 1, 0],
			[\OCP\Files\FileInfo::TYPE_FILE, 3, 0],
			[\OCP\Files\FileInfo::TYPE_FILE, 5, 0],
			[\OCP\Files\FileInfo::TYPE_FILE, 7, 0],
			[\OCP\Files\FileInfo::TYPE_FILE, 9, 0],
			[\OCP\Files\FileInfo::TYPE_FILE, 11, 0],
			[\OCP\Files\FileInfo::TYPE_FILE, 13, 0],
			[\OCP\Files\FileInfo::TYPE_FILE, 15, 0],
			[\OCP\Files\FileInfo::TYPE_FILE, 17, 17],
			[\OCP\Files\FileInfo::TYPE_FILE, 19, 19],
			[\OCP\Files\FileInfo::TYPE_FILE, 21, 17],
			[\OCP\Files\FileInfo::TYPE_FILE, 23, 19],
			[\OCP\Files\FileInfo::TYPE_FILE, 25, 17],
			[\OCP\Files\FileInfo::TYPE_FILE, 27, 19],
			[\OCP\Files\FileInfo::TYPE_FILE, 29, 17],
			[\OCP\Files\FileInfo::TYPE_FILE, 30, 0],
			[\OCP\Files\FileInfo::TYPE_FILE, 31, 19],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 1, 0],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 3, 0],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 5, 0],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 7, 0],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 9, 0],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 11, 0],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 13, 0],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 15, 0],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 17, 17],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 19, 19],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 21, 21],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 23, 23],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 25, 25],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 27, 27],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 29, 29],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 30, 0],
			[\OCP\Files\FileInfo::TYPE_FOLDER, 31, 31],
		];
	}

	/**
	 * @dataProvider sharePermissionsProvider
	 */
	public function testSharePermissions($type, $permissions, $expected) {
		$storage = $this->getMock('\OCP\Files\Storage');

		$storage->method('getPermissions')->willReturn($permissions);

		$info = $this->getMockBuilder('\OC\Files\FileInfo')
			->disableOriginalConstructor()
			->setMethods(array('getStorage', 'getType'))
			->getMock();

		$info->method('getStorage')->willReturn($storage);
		$info->method('getType')->willReturn($type);

		$view = $this->getMock('\OC\Files\View');

		$node = new  \OCA\DAV\Connector\Sabre\File($view, $info);
		$this->assertEquals($expected, $node->getSharePermissions());
	}
}
