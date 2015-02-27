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

class Test_Mount_Config_Dummy_Storage {
	public function __construct($params) {
		if (isset($params['simulateFail']) && $params['simulateFail'] == true) {
			throw new \Exception('Simulated config validation fail');
		}
	}

	public function test() {
		return true;
	}
}

class Test_Mount_Config_Hook_Test {
	static $signal;
	static $params;

	public static function setUpHooks() {
		self::clear();
		\OCP\Util::connectHook(
			\OC\Files\Filesystem::CLASSNAME,
			\OC\Files\Filesystem::signal_create_mount,
			'\Test_Mount_Config_Hook_Test', 'createHookCallback');
		\OCP\Util::connectHook(
			\OC\Files\Filesystem::CLASSNAME,
			\OC\Files\Filesystem::signal_delete_mount,
			'\Test_Mount_Config_Hook_Test', 'deleteHookCallback');
	}

	public static function clear() {
		self::$signal = null;
		self::$params = null;
	}

	public static function createHookCallback($params) {
		self::$signal = \OC\Files\Filesystem::signal_create_mount;
		self::$params = $params;
	}

	public static function deleteHookCallback($params) {
		self::$signal = \OC\Files\Filesystem::signal_delete_mount;
		self::$params = $params;
	}

	public static function getLastCall() {
		return array(self::$signal, self::$params);
	}
}

/**
 * Class Test_Mount_Config
 */
class Test_Mount_Config extends \Test\TestCase {

	private $dataDir;
	private $userHome;
	private $oldAllowedBackends;
	private $allBackends;

	const TEST_USER1 = 'user1';
	const TEST_USER2 = 'user2';
	const TEST_GROUP1 = 'group1';
	const TEST_GROUP1B = 'group1b';
	const TEST_GROUP2 = 'group2';
	const TEST_GROUP2B = 'group2b';

	protected function setUp() {
		parent::setUp();

		OC_Mount_Config::registerBackend('Test_Mount_Config_Dummy_Storage', array(
				'backend' => 'dummy',
				'priority' => 150,
				'configuration' => array()
			)
		);

		\OC_User::createUser(self::TEST_USER1, self::TEST_USER1);
		\OC_User::createUser(self::TEST_USER2, self::TEST_USER2);

		\OC_Group::createGroup(self::TEST_GROUP1);
		\OC_Group::createGroup(self::TEST_GROUP1B);
		\OC_Group::addToGroup(self::TEST_USER1, self::TEST_GROUP1);
		\OC_Group::addToGroup(self::TEST_USER1, self::TEST_GROUP1B);
		\OC_Group::createGroup(self::TEST_GROUP2);
		\OC_Group::createGroup(self::TEST_GROUP2B);
		\OC_Group::addToGroup(self::TEST_USER2, self::TEST_GROUP2);
		\OC_Group::addToGroup(self::TEST_USER2, self::TEST_GROUP2B);

		\OC_User::setUserId(self::TEST_USER1);
		$this->userHome = \OC_User::getHome(self::TEST_USER1);
		@mkdir($this->userHome);

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
		Test_Mount_Config_Hook_Test::setupHooks();
	}

	protected function tearDown() {
		Test_Mount_Config_Hook_Test::clear();
		OC_Mount_Config::$skipTest = false;

		\OC_User::deleteUser(self::TEST_USER2);
		\OC_User::deleteUser(self::TEST_USER1);
		\OC_Group::deleteGroup(self::TEST_GROUP1);
		\OC_Group::deleteGroup(self::TEST_GROUP1B);
		\OC_Group::deleteGroup(self::TEST_GROUP2);
		\OC_Group::deleteGroup(self::TEST_GROUP2B);

		@unlink($this->dataDir . '/mount.json');

		OCP\Config::setAppValue(
			'files_external',
			'user_mounting_backends',
			$this->oldAllowedBackends
		);

		parent::tearDown();
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
	 * Write the user config, to simulate existing files
	 */
	private function writeUserConfig($config) {
		$configFile = $this->userHome . '/mount.json';
		file_put_contents($configFile, json_encode($config));
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
	}

	/**
	 * Test adding a global mount point
	 */
	public function testAddGlobalMountPoint() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = 'all';
		$isPersonal = false;

		$storageOptions = array(
			'host' => 'localhost',
			'user' => 'testuser',
			'password' => '12345',
		);

		$this->assertEquals(true, OC_Mount_Config::addMountPoint('/ext', '\OC\Files\Storage\SFTP', $storageOptions, $mountType, $applicable, $isPersonal));

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
		$applicable = self::TEST_USER1;
		$isPersonal = true;

		$storageOptions = array(
			'host' => 'localhost',
			'user' => 'testuser',
			'password' => '12345',
		);

		$this->assertEquals(true, OC_Mount_Config::addMountPoint('/ext', '\OC\Files\Storage\SFTP', $storageOptions, $mountType, $applicable, $isPersonal));

		$config = $this->readUserConfig();
		$this->assertEquals(1, count($config));
		$this->assertTrue(isset($config[$mountType]));
		$this->assertTrue(isset($config[$mountType][$applicable]));
		$this->assertTrue(isset($config[$mountType][$applicable]['/' . self::TEST_USER1 . '/files/ext']));
		$this->assertEquals(
			'\OC\Files\Storage\SFTP',
			$config[$mountType][$applicable]['/' . self::TEST_USER1 . '/files/ext']['class']
		);
	}

	/**
	 * Test adding a personal mount point using disallowed backend
	 */
	public function testAddDisallowedBackendMountPointSingleUser() {
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
		$isPersonal = true;

		// local
		$this->assertFalse(OC_Mount_Config::addMountPoint('/ext', '\OC\Files\storage\local', array(), $mountType, $applicable, $isPersonal));

		unset($this->allBackends['\OC\Files\Storage\SFTP']);
		OCP\Config::setAppValue(
			'files_external',
			'user_mounting_backends',
			implode(',', array_keys($this->allBackends))
		);

		$storageOptions = array(
			'host' => 'localhost',
			'user' => 'testuser',
			'password' => '12345',
		);

		// non-local but forbidden
		$this->assertFalse(OC_Mount_Config::addMountPoint('/ext', '\OC\Files\Storage\SFTP', $storageOptions, $mountType, $applicable, $isPersonal));

		$this->assertFalse(file_exists($this->userHome . '/mount.json'));
	}

	/**
	 * Test adding a mount point with an non-existant backend
	 */
	public function testAddMountPointUnexistClass() {
		$storageClass = 'Unexist_Storage';
		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
		$isPersonal = false;
		$this->assertFalse(OC_Mount_Config::addMountPoint('/ext', $storageClass, array(), $mountType, $applicable, $isPersonal));

	}

	/**
	 * Provider for testing configurations with different
	 * "applicable" values (all, user, groups)
	 */
	public function applicableConfigProvider() {
		return array(
			// applicable to "all"
			array(
				OC_Mount_Config::MOUNT_TYPE_USER,
				'all',
				array(
					'users' => array('all'),
					'groups' => array()
				)
			),
			// applicable to single user
			array(
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				array(
					'users' => array(self::TEST_USER1),
					'groups' => array()
				)
			),
			// applicable to single group
			array(
				OC_Mount_Config::MOUNT_TYPE_GROUP,
				self::TEST_GROUP1,
				array(
					'users' => array(),
					'groups' => array(self::TEST_GROUP1)
				)
			),
		);
	}

	/**
	 * Test reading and writing global config
	 *
	 * @dataProvider applicableConfigProvider
	 */
	public function testReadWriteGlobalConfig($mountType, $applicable, $expectApplicableArray) {

		$mountType = $mountType;
		$applicable = $applicable;
		$isPersonal = false;
		$options = array(
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
				$options,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// re-read config
		$config = OC_Mount_Config::getSystemMountPoints();
		$this->assertEquals(1, count($config));
		$this->assertEquals('\OC\Files\Storage\SMB', $config[0]['class']);
		$this->assertEquals('ext', $config[0]['mountpoint']);
		$this->assertEquals($expectApplicableArray, $config[0]['applicable']);
		$savedOptions = $config[0]['options'];
		$this->assertEquals($options, $savedOptions);
		// key order needs to be preserved for the UI...
		$this->assertEquals(array_keys($options), array_keys($savedOptions));
	}

	/**
	 * Test reading and writing config
	 */
	public function testReadWritePersonalConfig() {

		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
		$isPersonal = true;
		$options = array(
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
				$options,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// re-read config
		$config = OC_Mount_Config::getPersonalMountPoints();
		$this->assertEquals(1, count($config));
		$this->assertEquals('\OC\Files\Storage\SMB', $config[0]['class']);
		$this->assertEquals('ext', $config[0]['mountpoint']);
		$savedOptions = $config[0]['options'];
		$this->assertEquals($options, $savedOptions);
		// key order needs to be preserved for the UI...
		$this->assertEquals(array_keys($options), array_keys($savedOptions));
	}

	public function testHooks() {
		$mountPoint = '/test';
		$mountType = 'user';
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
				$mountPoint,
				'\OC\Files\Storage\SMB',
				$mountConfig,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		list($hookName, $params) = Test_Mount_Config_Hook_Test::getLastCall();
		$this->assertEquals(
			\OC\Files\Filesystem::signal_create_mount,
			$hookName
		);
		$this->assertEquals(
			$mountPoint,
			$params[\OC\Files\Filesystem::signal_param_path]
		);
		$this->assertEquals(
			$mountType,
			$params[\OC\Files\Filesystem::signal_param_mount_type]
		);
		$this->assertEquals(
			$applicable,
			$params[\OC\Files\Filesystem::signal_param_users]
		);

		Test_Mount_Config_Hook_Test::clear();

		// edit
		$mountConfig['host'] = 'anothersmbhost';
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				$mountPoint,
				'\OC\Files\Storage\SMB',
				$mountConfig,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// hook must not be called on edit
		list($hookName, $params) = Test_Mount_Config_Hook_Test::getLastCall();
		$this->assertEquals(
			null,
			$hookName
		);

		Test_Mount_Config_Hook_Test::clear();

		$this->assertTrue(
			OC_Mount_Config::removeMountPoint(
				$mountPoint,
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		list($hookName, $params) = Test_Mount_Config_Hook_Test::getLastCall();
		$this->assertEquals(
			\OC\Files\Filesystem::signal_delete_mount,
			$hookName
		);
		$this->assertEquals(
			$mountPoint,
			$params[\OC\Files\Filesystem::signal_param_path]
		);
		$this->assertEquals(
			$mountType,
			$params[\OC\Files\Filesystem::signal_param_mount_type]
		);
		$this->assertEquals(
			$applicable,
			$params[\OC\Files\Filesystem::signal_param_users]
		);
	}

	/**
	 * Test password obfuscation
	 */
	public function testPasswordObfuscation() {

		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
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

		// note: password re-reading is covered by testReadWritePersonalConfig

		// check that password inside the file is NOT in plain text
		$config = $this->readUserConfig();
		$savedConfig = $config[$mountType][$applicable]['/' . self::TEST_USER1 . '/files/ext']['options'];

		// no more clear text password in file (kept because of key order)
		$this->assertEquals('', $savedConfig['password']);

		// encrypted password is present
		$this->assertNotEquals($mountConfig['password'], $savedConfig['password_encrypted']);
	}

	/**
	 * Test read legacy passwords
	 */
	public function testReadLegacyPassword() {

		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$applicable = self::TEST_USER1;
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

		$config = $this->readUserConfig();
		// simulate non-encrypted password situation
		$config[$mountType][$applicable]['/' . self::TEST_USER1 . '/files/ext']['options']['password'] = 'smbpasswd';

		$this->writeUserConfig($config);

		// re-read config, password was read correctly
		$config = OC_Mount_Config::getPersonalMountPoints();
		$savedMountConfig = $config[0]['options'];
		$this->assertEquals($mountConfig, $savedMountConfig);
	}

	public function mountDataProvider() {
		return array(
			// Tests for visible mount points
			// system mount point for all users
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_USER,
				'all',
				self::TEST_USER1,
				true,
			),
			// system mount point for a specific user
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				self::TEST_USER1,
				true,
			),
			// system mount point for a specific group
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_GROUP,
				self::TEST_GROUP1,
				self::TEST_USER1,
				true,
			),
			// user mount point
			array(
				true,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				self::TEST_USER1,
				true,
			),

			// Tests for non-visible mount points
			// system mount point for another user
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER2,
				self::TEST_USER1,
				false,
			),
			// system mount point for a specific group
			array(
				false,
				OC_Mount_Config::MOUNT_TYPE_GROUP,
				self::TEST_GROUP2,
				self::TEST_USER1,
				false,
			),
			// user mount point
			array(
				true,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				self::TEST_USER2,
				false,
			),
		);
	}

	/**
	 * Test mount points used at mount time, making sure
	 * the configuration is prepared properly.
	 *
	 * @dataProvider mountDataProvider
	 * @param bool $isPersonal true for personal mount point, false for system mount point
	 * @param string $mountType mount type
	 * @param string $applicable target user/group or "all"
	 * @param string $testUser user for which to retrieve the mount points
	 * @param bool $expectVisible whether to expect the mount point to be visible for $testUser
	 */
	public function testMount($isPersonal, $mountType, $applicable, $testUser, $expectVisible) {

		$mountConfig = array(
			'host' => 'someost',
			'user' => 'someuser',
			'password' => 'somepassword',
			'root' => 'someroot',
			'share' => '',
		);

		// add mount point as "test" user
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

		// check mount points in the perspective of user $testUser
		\OC_User::setUserId($testUser);

		$mountPoints = OC_Mount_Config::getAbsoluteMountPoints($testUser);
		if ($expectVisible) {
			$this->assertEquals(1, count($mountPoints));
			$this->assertTrue(isset($mountPoints['/' . self::TEST_USER1 . '/files/ext']));
			$this->assertEquals('\OC\Files\Storage\SMB', $mountPoints['/' . self::TEST_USER1 . '/files/ext']['class']);
			$this->assertEquals($mountConfig, $mountPoints['/' . self::TEST_USER1 . '/files/ext']['options']);
		}
		else {
			$this->assertEquals(0, count($mountPoints));
		}
	}

	/**
	 * Test the same config for multiple users.
	 * The config will be merged by getSystemMountPoints().
	 */
	public function testConfigMerging() {

		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$isPersonal = false;
		$options = array(
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
				$options,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				$isPersonal
			)
		);

		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$options,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER2,
				$isPersonal
			)
		);

		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$options,
				OC_Mount_Config::MOUNT_TYPE_GROUP,
				self::TEST_GROUP2,
				$isPersonal
			)
		);

		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$options,
				OC_Mount_Config::MOUNT_TYPE_GROUP,
				self::TEST_GROUP1,
				$isPersonal
			)
		);

		// re-read config
		$config = OC_Mount_Config::getSystemMountPoints();
		$this->assertEquals(1, count($config));
		$this->assertEquals('\OC\Files\Storage\SMB', $config[0]['class']);
		$this->assertEquals('ext', $config[0]['mountpoint']);
		$this->assertEquals($options, $config[0]['options']);
		$this->assertEquals(array(self::TEST_USER1, self::TEST_USER2), $config[0]['applicable']['users']);
		$this->assertEquals(array(self::TEST_GROUP2, self::TEST_GROUP1), $config[0]['applicable']['groups']);
	}

	/**
	 * Create then re-read mount points configs where the mount points
	 * have the same path, the config must NOT be merged.
	 */
	public function testRereadMountpointWithSamePath() {

		$mountType = OC_Mount_Config::MOUNT_TYPE_USER;
		$isPersonal = false;
		$options1 = array(
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
				$options1,
				$mountType,
				self::TEST_USER1,
				$isPersonal
			)
		);

		$options2 = array(
			'host' => 'anothersmbhost',
			'user' => 'anothersmbuser',
			'password' => 'anothersmbpassword',
			'share' => 'anothersmbshare',
			'root' => 'anothersmbroot'
		);
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$options2,
				$mountType,
				self::TEST_USER2,
				$isPersonal
			)
		);

		// re-read config
		$config = OC_Mount_Config::getSystemMountPoints();
		$this->assertEquals(2, count($config));
		$this->assertEquals('\OC\Files\Storage\SMB', $config[0]['class']);
		$this->assertEquals('ext', $config[0]['mountpoint']);
		$this->assertEquals($options1, $config[0]['options']);
		$this->assertEquals('\OC\Files\Storage\SMB', $config[1]['class']);
		$this->assertEquals('ext', $config[1]['mountpoint']);
		$this->assertEquals($options2, $config[1]['options']);
	}

	public function priorityDataProvider() {
		return array(

		// test 1 - group vs group
		array(
			array(
				array(
					'isPersonal' => false,
					'mountType' => OC_Mount_Config::MOUNT_TYPE_GROUP,
					'applicable' => self::TEST_GROUP1,
					'priority' => 50
				),
				array(
					'isPersonal' => false,
					'mountType' => OC_Mount_Config::MOUNT_TYPE_GROUP,
					'applicable' => self::TEST_GROUP1B,
					'priority' => 60
				)
			),
			1
		),
		// test 2 - user vs personal
		array(
			array(
				array(
					'isPersonal' => false,
					'mountType' => OC_Mount_Config::MOUNT_TYPE_USER,
					'applicable' => self::TEST_USER1,
					'priority' => 2000
				),
				array(
					'isPersonal' => true,
					'mountType' => OC_Mount_Config::MOUNT_TYPE_USER,
					'applicable' => self::TEST_USER1,
					'priority' => null
				)
			),
			1
		),
		// test 3 - all vs group vs user
		array(
			array(
				array(
					'isPersonal' => false,
					'mountType' => OC_Mount_Config::MOUNT_TYPE_USER,
					'applicable' => 'all',
					'priority' => 70
				),
				array(
					'isPersonal' => false,
					'mountType' => OC_Mount_Config::MOUNT_TYPE_GROUP,
					'applicable' => self::TEST_GROUP1,
					'priority' => 60
				),
				array(
					'isPersonal' => false,
					'mountType' => OC_Mount_Config::MOUNT_TYPE_USER,
					'applicable' => self::TEST_USER1,
					'priority' => 50
				)
			),
			2
		)

		);
	}

	/**
	 * Ensure priorities are being respected
	 * Test user is self::TEST_USER1
	 *
	 * @dataProvider priorityDataProvider
	 * @param array[] $mounts array of associative array of mount parameters:
	 *	bool $isPersonal
	 *	string $mountType
	 *	string $applicable
	 *	int|null $priority null for personal
	 * @param int $expected index of expected visible mount
	 */
	public function testPriority($mounts, $expected) {

		$mountConfig = array(
			'host' => 'somehost',
			'user' => 'someuser',
			'password' => 'somepassword',
			'root' => 'someroot',
			'share' => '',
		);

		// Add mount points
		foreach($mounts as $i => $mount) {
			$this->assertTrue(
				OC_Mount_Config::addMountPoint(
					'/ext',
					'\OC\Files\Storage\SMB',
					$mountConfig + array('id' => $i),
					$mount['mountType'],
					$mount['applicable'],
					$mount['isPersonal'],
					$mount['priority']
				)
			);
		}

		// Get mount points for user
		$mountPoints = OC_Mount_Config::getAbsoluteMountPoints(self::TEST_USER1);

		$this->assertEquals(1, count($mountPoints));
		$this->assertEquals($expected, $mountPoints['/'.self::TEST_USER1.'/files/ext']['options']['id']);
	}

	/**
	 * Test for persistence of priority when changing mount options
	 */
	public function testPriorityPersistence() {

		$class = '\OC\Files\Storage\SMB';
		$priority = 123;
		$mountConfig = array(
			'host' => 'somehost',
			'user' => 'someuser',
			'password' => 'somepassword',
			'root' => 'someroot',
			'share' => '',
		);

		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				$class,
				$mountConfig,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				false,
				$priority
			)
		);

		// Check for correct priority
		$mountPoints = OC_Mount_Config::getAbsoluteMountPoints(self::TEST_USER1);
		$this->assertEquals($priority,
			$mountPoints['/'.self::TEST_USER1.'/files/ext']['priority']);

		// Simulate changed mount options (without priority set)
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				$class,
				$mountConfig,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				false
			)
		);

		// Check for correct priority
		$mountPoints = OC_Mount_Config::getAbsoluteMountPoints(self::TEST_USER1);
		$this->assertEquals($priority,
			$mountPoints['/'.self::TEST_USER1.'/files/ext']['priority']);
	}

	/*
	 * Test for correct personal configuration loading in file sharing scenarios
	 */
	public function testMultiUserPersonalConfigLoading() {
		$mountConfig = array(
			'host' => 'somehost',
			'user' => 'someuser',
			'password' => 'somepassword',
			'root' => 'someroot',
			'share' => '',
		);

		// Create personal mount point
		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				'\OC\Files\Storage\SMB',
				$mountConfig,
				OC_Mount_Config::MOUNT_TYPE_USER,
				self::TEST_USER1,
				true
			)
		);

		// Ensure other user can read mount points
		\OC_User::setUserId(self::TEST_USER2);
		$mountPointsMe = OC_Mount_Config::getAbsoluteMountPoints(self::TEST_USER2);
		$mountPointsOther = OC_Mount_Config::getAbsoluteMountPoints(self::TEST_USER1);

		$this->assertEquals(0, count($mountPointsMe));
		$this->assertEquals(1, count($mountPointsOther));
		$this->assertTrue(isset($mountPointsOther['/'.self::TEST_USER1.'/files/ext']));
		$this->assertEquals('\OC\Files\Storage\SMB',
			$mountPointsOther['/'.self::TEST_USER1.'/files/ext']['class']);
		$this->assertEquals($mountConfig,
			$mountPointsOther['/'.self::TEST_USER1.'/files/ext']['options']);
	}

	public function testAllowWritingIncompleteConfigIfStorageContructorFails() {
		$storageClass = 'Test_Mount_Config_Dummy_Storage';
		$mountType = 'user';
		$applicable = 'all';
		$isPersonal = false;

		$this->assertTrue(
			OC_Mount_Config::addMountPoint(
				'/ext',
				$storageClass,
				array('simulateFail' => true),
				$mountType,
				$applicable,
				$isPersonal
			)
		);

		// config can be retrieved afterwards
		$mounts = OC_Mount_Config::getSystemMountPoints();
		$this->assertEquals(1, count($mounts));

		// no storage id was set
		$this->assertFalse(isset($mounts[0]['storage_id']));
	}
}
