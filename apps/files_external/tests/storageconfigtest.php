<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files_external\Tests;

use \OCA\Files_external\Lib\StorageConfig;

class StorageConfigTest extends \Test\TestCase {

	public function testJsonSerialization() {
		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('test');
		$storageConfig->setBackendClass('\OC\Files\Storage\SMB');
		$storageConfig->setBackendOptions(['user' => 'test', 'password' => 'password123']);
		$storageConfig->setPriority(128);
		$storageConfig->setApplicableUsers(['user1', 'user2']);
		$storageConfig->setApplicableGroups(['group1', 'group2']);
		$storageConfig->setMountOptions(['preview' => false]);

		$json = $storageConfig->jsonSerialize();

		$this->assertEquals(1, $json['id']);
		$this->assertEquals('/test', $json['mountPoint']);
		$this->assertEquals('\OC\Files\Storage\SMB', $json['backendClass']);
		$this->assertEquals('test', $json['backendOptions']['user']);
		$this->assertEquals('password123', $json['backendOptions']['password']);
		$this->assertEquals(128, $json['priority']);
		$this->assertEquals(['user1', 'user2'], $json['applicableUsers']);
		$this->assertEquals(['group1', 'group2'], $json['applicableGroups']);
		$this->assertEquals(['preview' => false], $json['mountOptions']);
	}

}
