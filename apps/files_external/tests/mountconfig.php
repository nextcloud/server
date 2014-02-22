<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * Copyright (c) 2013 Vincent Petry <pvince81@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

require_once __DIR__ . '/../../../lib/base.php';

require __DIR__ . '/../lib/config.php';

class Test_Mount_Config_Dummy_Storage {
	public function test() {
		return true;
	}
}

/**
 * Class Test_Mount_Config
 */
class Test_Mount_Config extends \PHPUnit_Framework_TestCase {
	/**
	 * Test mount point validation
	 */
	public function testAddMountPointValidation() {
		$storageClass = 'Test_Mount_Config_Dummy_Storage';
		$mountType = 'user';
		$applicable = 'all';
		$isPersonal = false;
		$this->assertEquals(false, OC_Mount_Config::addMountPoint('', $storageClass, array(), $mountType, $applicable, $isPersonal));
		$this->assertEquals(false, OC_Mount_Config::addMountPoint('/', $storageClass, array(), $mountType, $applicable, $isPersonal));
		$this->assertEquals(false, OC_Mount_Config::addMountPoint('Shared', $storageClass, array(), $mountType, $applicable, $isPersonal));
		$this->assertEquals(false, OC_Mount_Config::addMountPoint('/Shared', $storageClass, array(), $mountType, $applicable, $isPersonal));

	}

	public function testAddMountPointSingleUser() {
		\OC_User::setUserId('test');
		$mountType = 'user';
		$applicable = 'test';
		$isPersonal = true;
		// local
		$this->assertEquals(false, OC_Mount_Config::addMountPoint('/ext', '\OC\Files\storage\local', array(), $mountType, $applicable, $isPersonal));
		// non-local
		// FIXME: can't test this yet as the class (write operation) is not mockable
		// $this->assertEquals(true, OC_Mount_Config::addMountPoint('/ext', '\OC\Files\Storage\SFTP', array(), $mountType, $applicable, $isPersonal));

	}

	public function testAddMountPointUnexistClass() {
		\OC_User::setUserId('test');
		$storageClass = 'Unexist_Storage';
		$mountType = 'user';
		$applicable = 'test';
		$isPersonal = true;
		// local
		// non-local
		$this->assertEquals(false, OC_Mount_Config::addMountPoint('/ext', $storageClass, array(), $mountType, $applicable, $isPersonal));

	}
}
