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

	private $dataDir;
	private $userHome;
	private $oldAllowedBackends;
	private $allBackends;

	public function setUp() {
		\OC_User::setUserId('test');
		$this->userHome = \OC_User::getHome('test');
		mkdir($this->userHome);

		$this->dataDir = \OC_Config::getValue(
			'datadirectory',
			\OC::$SERVERROOT . '/data/'
		);
		$this->oldAllowedBackends = OCP\Config::getAppValue(
			'files_external',
			'user_mounting_backends',
			''
		);
		$this->allBackends = OC_Mount_Config::getBackends();
		OCP\Config::setAppValue(
			'files_external',
			'user_mounting_backends',
			implode(',', array_keys($this->allBackends))
		);

		OC_Mount_Config::$skipTest = true;
	}

	public function tearDown() {
		OC_Mount_Config::$skipTest = false;

		@unlink($this->dataDir . '/mount.json');
		@unlink($this->userHome . '/mount.json');
		rmdir($this->userHome);

		OCP\Config::setAppValue(
			'files_external',
			'user_mounting_backends',
			$this->oldAllowedBackends
		);
	}

	/**
	 * Reads the global config, for checking
	 */
	private function readGlobalConfig() {
		$configFile = $this->dataDir . '/mount.json';
		return json_decode(file_get_contents($configFile), true);
	}

	/**
	 * Reads the user config, for checking
	 */
	private function readUserConfig() {
		$configFile = $this->userHome . '/mount.json';
		return json_decode(file_get_contents($configFile), true);
	}

	/**
	 * Test mount point validation
	 */
	public function testAddMountPointValidation() {
		$storageClass = 'Test_Mount_Config_Dummy_Storage';
		$mountType = 'user';
		$applicable = 'all';
		$isPersonal = false;
		$this->assertFalse(OC_Mount_Config::addMountPoint('', $storageClass, array(), $mountType, $applicable, $isPersonal));
		$this->assertFalse(OC_Mount_Config::addMountPoint('/', $storageClass, array(), $mountType, $applicable, $isPersonal));
		$this->assertFalse(OC_Mount_Config::addMountPoint('Shared', $storageClass, array(), $mountType, $applicable, $isPersonal));
		$this->assertFalse(OC_Mount_Config::addMountPoint('/Shared', $storageClass, array(), $mountType, $applicable, $isPersonal));

	}

	/**
	 * Test adding a global mount point
	 */
	public function testAddGlobalMountPoint() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = 'all';
		$isPersonal = false;

		$this->assertEquals(true, OC_Mount_Config::addMountPoint('/ext', '\OC\Files\Storage\SFTP', array(), $mountType, $applicable, $isPersonal));

		$config = $this->readGlobalConfig();
		$this->assertEquals(1, count($config));
		$this->assertTrue(isset($config[$mountType]));
		$this->assertTrue(isset($config[$mountType][$applicable]));
		$this->assertTrue(isset($config[$mountType][$applicable]['/$user/files/ext']));
		$this->assertEquals(
			'\OC\Files\Storage\SFTP',
			$config[$mountType][$applicable]['/$user/files/ext']['class']
		);
	}

	/**
	 * Test adding a personal mount point
	 */
	public function testAddMountPointSingleUser() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = 'test';
		$isPersonal = true;

		$this->assertEquals(true, OC_Mount_Config::addMountPoint('/ext', '\OC\Files\Storage\SFTP', array(), $mountType, $applicable, $isPersonal));

		$config = $this->readUserConfig();
		$this->assertEquals(1, count($config));
		$this->assertTrue(isset($config[$mountType]));
		$this->assertTrue(isset($config[$mountType][$applicable]));
		$this->assertTrue(isset($config[$mountType][$applicable]['/test/files/ext']));
		$this->assertEquals(
			'\OC\Files\Storage\SFTP',
			$config[$mountType][$applicable]['/test/files/ext']['class']
		);
	}

	/**
	 * Test adding a personal mount point using disallowed backend
	 */
	public function testAddDisallowedBackendMountPointSingleUser() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = 'test';
		$isPersonal = true;

		// local
		$this->assertFalse(OC_Mount_Config::addMountPoint('/ext', '\OC\Files\storage\local', array(), $mountType, $applicable, $isPersonal));

		unset($this->allBackends['\OC\Files\Storage\SFTP']);
		OCP\Config::setAppValue(
			'files_external',
			'user_mounting_backends',
			implode(',', array_keys($this->allBackends))
		);

		// non-local but forbidden
		$this->assertFalse(OC_Mount_Config::addMountPoint('/ext', '\OC\Files\Storage\SFTP', array(), $mountType, $applicable, $isPersonal));

		$this->assertFalse(file_exists($this->userHome . '/mount.json'));
	}

	/**
	 * Test adding a mount point with an non-existant backend
	 */
	public function testAddMountPointUnexistClass() {
		$storageClass = 'Unexist_Storage';
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = 'test';
		$isPersonal = false;
		$this->assertFalse(OC_Mount_Config::addMountPoint('/ext', $storageClass, array(), $mountType, $applicable, $isPersonal));

	}

	/**
	 * Test reading and writing global config
	 */
	public function testReadWriteGlobalConfig() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = 'all';
		$isPersonal = false;
		$mountConfig = array(
			'host' => 'smbhost',
			'user' => 'smbuser',
			'password' => 'smbpassword',
			'share' => 'smbshare',
			'root' => 'smbroot'
		);

		// write config
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$mountConfig,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// re-read config
		$config = OC_Mount_Config::getSystemMountPoints();
		$this->assertEquals(1, count($config));
		$this->assertTrue(isset($config['ext']));
		$this->assertEquals('\OC\Files\Storage\SMB', $config['ext']['class']);
		$savedMountConfig = $config['ext']['configuration'];
		$this->assertEquals($mountConfig, $savedMountConfig);
	}

	/**
	 * Test reading and writing config
	 */
	public function testReadWritePersonalConfig() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = 'test';
		$isPersonal = true;
		$mountConfig = array(
			'host' => 'smbhost',
			'user' => 'smbuser',
			'password' => 'smbpassword',
			'share' => 'smbshare',
			'root' => 'smbroot'
		);

		// write config
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$mountConfig,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// re-read config
		$config = OC_Mount_Config::getPersonalMountPoints();
		$this->assertEquals(1, count($config));
		$this->assertTrue(isset($config['ext']));
		$this->assertEquals('\OC\Files\Storage\SMB', $config['ext']['class']);
		$savedMountConfig = $config['ext']['configuration'];
		$this->assertEquals($mountConfig, $savedMountConfig);
	}
}
