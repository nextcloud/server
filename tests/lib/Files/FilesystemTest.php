<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Robin Appelman icewind@owncloud.com
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

namespace Test\Files;

use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Temporary;
use OC\User\NoUserException;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IUser;

class DummyMountProvider implements IMountProvider {
	private $mounts = [];

	/**
	 * @param array $mounts
	 */
	public function __construct(array $mounts) {
		$this->mounts = $mounts;
	}

	/**
	 * Get the pre-registered mount points
	 *
	 * @param IUser $user
	 * @param IStorageFactory $loader
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function  getMountsForUser(IUser $user, IStorageFactory $loader) {
		return isset($this->mounts[$user->getUID()]) ? $this->mounts[$user->getUID()] : [];
	}
}

/**
 * Class FilesystemTest
 *
 * @group DB
 *
 * @package Test\Files
 */
class FilesystemTest extends \Test\TestCase {

	const TEST_FILESYSTEM_USER1 = "test-filesystem-user1";
	const TEST_FILESYSTEM_USER2 = "test-filesystem-user1";

	/**
	 * @var array tmpDirs
	 */
	private $tmpDirs = array();

	/**
	 * @return array
	 */
	private function getStorageData() {
		$dir = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->tmpDirs[] = $dir;
		return array('datadir' => $dir);
	}

	protected function setUp() {
		parent::setUp();
		$userBackend = new \Test\Util\User\Dummy();
		$userBackend->createUser(self::TEST_FILESYSTEM_USER1, self::TEST_FILESYSTEM_USER1);
		$userBackend->createUser(self::TEST_FILESYSTEM_USER2, self::TEST_FILESYSTEM_USER2);
		\OC::$server->getUserManager()->registerBackend($userBackend);
		$this->loginAsUser();
	}

	protected function tearDown() {
		foreach ($this->tmpDirs as $dir) {
			\OC_Helper::rmdirr($dir);
		}

		$this->logout();
		$this->invokePrivate('\OC\Files\Filesystem', 'normalizedPathCache', [null]);
		parent::tearDown();
	}

	public function testMount() {
		\OC\Files\Filesystem::mount('\OC\Files\Storage\Local', self::getStorageData(), '/');
		$this->assertEquals('/', \OC\Files\Filesystem::getMountPoint('/'));
		$this->assertEquals('/', \OC\Files\Filesystem::getMountPoint('/some/folder'));
		list(, $internalPath) = \OC\Files\Filesystem::resolvePath('/');
		$this->assertEquals('', $internalPath);
		list(, $internalPath) = \OC\Files\Filesystem::resolvePath('/some/folder');
		$this->assertEquals('some/folder', $internalPath);

		\OC\Files\Filesystem::mount('\OC\Files\Storage\Local', self::getStorageData(), '/some');
		$this->assertEquals('/', \OC\Files\Filesystem::getMountPoint('/'));
		$this->assertEquals('/some/', \OC\Files\Filesystem::getMountPoint('/some/folder'));
		$this->assertEquals('/some/', \OC\Files\Filesystem::getMountPoint('/some/'));
		$this->assertEquals('/some/', \OC\Files\Filesystem::getMountPoint('/some'));
		list(, $internalPath) = \OC\Files\Filesystem::resolvePath('/some/folder');
		$this->assertEquals('folder', $internalPath);
	}

	public function normalizePathData() {
		return array(
			array('/', ''),
			array('/', '/'),
			array('/', '//'),
			array('/', '/', false),
			array('/', '//', false),

			array('/path', '/path/'),
			array('/path/', '/path/', false),
			array('/path', 'path'),

			array('/foo/bar', '/foo//bar/'),
			array('/foo/bar/', '/foo//bar/', false),
			array('/foo/bar', '/foo////bar'),
			array('/foo/bar', '/foo/////bar'),
			array('/foo/bar', '/foo/bar/.'),
			array('/foo/bar', '/foo/bar/./'),
			array('/foo/bar/', '/foo/bar/./', false),
			array('/foo/bar', '/foo/bar/./.'),
			array('/foo/bar', '/foo/bar/././'),
			array('/foo/bar/', '/foo/bar/././', false),
			array('/foo/bar', '/foo/./bar/'),
			array('/foo/bar/', '/foo/./bar/', false),
			array('/foo/.bar', '/foo/.bar/'),
			array('/foo/.bar/', '/foo/.bar/', false),
			array('/foo/.bar/tee', '/foo/.bar/tee'),

			// Windows paths
			array('/', ''),
			array('/', '\\'),
			array('/', '\\', false),
			array('/', '\\\\'),
			array('/', '\\\\', false),

			array('/path', '\\path'),
			array('/path', '\\path', false),
			array('/path', '\\path\\'),
			array('/path/', '\\path\\', false),

			array('/foo/bar', '\\foo\\\\bar\\'),
			array('/foo/bar/', '\\foo\\\\bar\\', false),
			array('/foo/bar', '\\foo\\\\\\\\bar'),
			array('/foo/bar', '\\foo\\\\\\\\\\bar'),
			array('/foo/bar', '\\foo\\bar\\.'),
			array('/foo/bar', '\\foo\\bar\\.\\'),
			array('/foo/bar/', '\\foo\\bar\\.\\', false),
			array('/foo/bar', '\\foo\\bar\\.\\.'),
			array('/foo/bar', '\\foo\\bar\\.\\.\\'),
			array('/foo/bar/', '\\foo\\bar\\.\\.\\', false),
			array('/foo/bar', '\\foo\\.\\bar\\'),
			array('/foo/bar/', '\\foo\\.\\bar\\', false),
			array('/foo/.bar', '\\foo\\.bar\\'),
			array('/foo/.bar/', '\\foo\\.bar\\', false),
			array('/foo/.bar/tee', '\\foo\\.bar\\tee'),

			// Absolute windows paths NOT marked as absolute
			array('/C:', 'C:\\'),
			array('/C:/', 'C:\\', false),
			array('/C:/tests', 'C:\\tests'),
			array('/C:/tests', 'C:\\tests', false),
			array('/C:/tests', 'C:\\tests\\'),
			array('/C:/tests/', 'C:\\tests\\', false),

			// normalize does not resolve '..' (by design)
			array('/foo/..', '/foo/../'),
			array('/foo/..', '\\foo\\..\\'),
		);
	}

	/**
	 * @dataProvider normalizePathData
	 */
	public function testNormalizePath($expected, $path, $stripTrailingSlash = true) {
		$this->assertEquals($expected, \OC\Files\Filesystem::normalizePath($path, $stripTrailingSlash));
	}

	public function normalizePathKeepUnicodeData() {
		$nfdName = 'ümlaut';
		$nfcName = 'ümlaut';
		return [
			['/' . $nfcName, $nfcName, true],
			['/' . $nfcName, $nfcName, false],
			['/' . $nfdName, $nfdName, true],
			['/' . $nfcName, $nfdName, false],
		];
	}

	/**
	 * @dataProvider normalizePathKeepUnicodeData
	 */
	public function testNormalizePathKeepUnicode($expected, $path, $keepUnicode = false) {
		$this->assertEquals($expected, \OC\Files\Filesystem::normalizePath($path, true, false, $keepUnicode));
	}

	public function testNormalizePathKeepUnicodeCache() {
		$nfdName = 'ümlaut';
		$nfcName = 'ümlaut';
		// call in succession due to cache
		$this->assertEquals('/' . $nfcName, \OC\Files\Filesystem::normalizePath($nfdName, true, false, false));
		$this->assertEquals('/' . $nfdName, \OC\Files\Filesystem::normalizePath($nfdName, true, false, true));
	}

	public function isValidPathData() {
		return array(
			array('/', true),
			array('/path', true),
			array('/foo/bar', true),
			array('/foo//bar/', true),
			array('/foo////bar', true),
			array('/foo//\///bar', true),
			array('/foo/bar/.', true),
			array('/foo/bar/./', true),
			array('/foo/bar/./.', true),
			array('/foo/bar/././', true),
			array('/foo/bar/././..bar', true),
			array('/foo/bar/././..bar/a', true),
			array('/foo/bar/././..', false),
			array('/foo/bar/././../', false),
			array('/foo/bar/.././', false),
			array('/foo/bar/../../', false),
			array('/foo/bar/../..\\', false),
			array('..', false),
			array('../', false),
			array('../foo/bar', false),
			array('..\foo/bar', false),
		);
	}

	/**
	 * @dataProvider isValidPathData
	 */
	public function testIsValidPath($path, $expected) {
		$this->assertSame($expected, \OC\Files\Filesystem::isValidPath($path));
	}

	public function isFileBlacklistedData() {
		return array(
			array('/etc/foo/bar/foo.txt', false),
			array('\etc\foo/bar\foo.txt', false),
			array('.htaccess', true),
			array('.htaccess/', true),
			array('.htaccess\\', true),
			array('/etc/foo\bar/.htaccess\\', true),
			array('/etc/foo\bar/.htaccess/', true),
			array('/etc/foo\bar/.htaccess/foo', false),
			array('//foo//bar/\.htaccess/', true),
			array('\foo\bar\.HTAccess', true),
		);
	}

	/**
	 * @dataProvider isFileBlacklistedData
	 */
	public function testIsFileBlacklisted($path, $expected) {
		$this->assertSame($expected, \OC\Files\Filesystem::isFileBlacklisted($path));
	}

	public function testNormalizePathUTF8() {
		if (!class_exists('Patchwork\PHP\Shim\Normalizer')) {
			$this->markTestSkipped('UTF8 normalizer Patchwork was not found');
		}

		$this->assertEquals("/foo/bar\xC3\xBC", \OC\Files\Filesystem::normalizePath("/foo/baru\xCC\x88"));
		$this->assertEquals("/foo/bar\xC3\xBC", \OC\Files\Filesystem::normalizePath("\\foo\\baru\xCC\x88"));
	}

	public function testHooks() {
		if (\OC\Files\Filesystem::getView()) {
			$user = \OC_User::getUser();
		} else {
			$user = self::TEST_FILESYSTEM_USER1;
			$backend = new \Test\Util\User\Dummy();
			\OC_User::useBackend($backend);
			$backend->createUser($user, $user);
			$userObj = \OC::$server->getUserManager()->get($user);
			\OC::$server->getUserSession()->setUser($userObj);
			\OC\Files\Filesystem::init($user, '/' . $user . '/files');

		}
		\OC_Hook::clear('OC_Filesystem');
		\OC_Hook::connect('OC_Filesystem', 'post_write', $this, 'dummyHook');

		\OC\Files\Filesystem::mount('OC\Files\Storage\Temporary', array(), '/');

		$rootView = new \OC\Files\View('');
		$rootView->mkdir('/' . $user);
		$rootView->mkdir('/' . $user . '/files');

//		\OC\Files\Filesystem::file_put_contents('/foo', 'foo');
		\OC\Files\Filesystem::mkdir('/bar');
//		\OC\Files\Filesystem::file_put_contents('/bar//foo', 'foo');

		$tmpFile = \OC::$server->getTempManager()->getTemporaryFile();
		file_put_contents($tmpFile, 'foo');
		$fh = fopen($tmpFile, 'r');
//		\OC\Files\Filesystem::file_put_contents('/bar//foo', $fh);
	}

	/**
	 * Tests that an exception is thrown when passed user does not exist.
	 *
	 * @expectedException \OC\User\NoUserException
	 */
	public function testLocalMountWhenUserDoesNotExist() {
		$userId = $this->getUniqueID('user_');

		\OC\Files\Filesystem::initMountPoints($userId);
	}

	/**
	 * @expectedException \OC\User\NoUserException
	 */
	public function testNullUserThrows() {
		\OC\Files\Filesystem::initMountPoints(null);
	}

	public function testNullUserThrowsTwice() {
		$thrown = 0;
		try {
			\OC\Files\Filesystem::initMountPoints(null);
		} catch (NoUserException $e) {
			$thrown++;
		}
		try {
			\OC\Files\Filesystem::initMountPoints(null);
		} catch (NoUserException $e) {
			$thrown++;
		}
		$this->assertEquals(2, $thrown);
	}

	/**
	 * Tests that an exception is thrown when passed user does not exist.
	 */
	public function testLocalMountWhenUserDoesNotExistTwice() {
		$thrown = 0;
		$userId = $this->getUniqueID('user_');

		try {
			\OC\Files\Filesystem::initMountPoints($userId);
		} catch (NoUserException $e) {
			$thrown++;
		}

		try {
			\OC\Files\Filesystem::initMountPoints($userId);
		} catch (NoUserException $e) {
			$thrown++;
		}

		$this->assertEquals(2, $thrown);
	}

	/**
	 * Tests that the home storage is used for the user's mount point
	 */
	public function testHomeMount() {
		$userId = $this->getUniqueID('user_');

		\OC::$server->getUserManager()->createUser($userId, $userId);

		\OC\Files\Filesystem::initMountPoints($userId);

		$homeMount = \OC\Files\Filesystem::getStorage('/' . $userId . '/');

		$this->assertTrue($homeMount->instanceOfStorage('\OCP\Files\IHomeStorage'));
		if (getenv('RUN_OBJECTSTORE_TESTS')) {
			$this->assertTrue($homeMount->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage'));
			$this->assertEquals('object::user:' . $userId, $homeMount->getId());
		} else {
			$this->assertTrue($homeMount->instanceOfStorage('\OC\Files\Storage\Home'));
			$this->assertEquals('home::' . $userId, $homeMount->getId());
		}

		$user = \OC::$server->getUserManager()->get($userId);
		if ($user !== null) { $user->delete(); }
	}

	/**
	 * Tests that the home storage is used in legacy mode
	 * for the user's mount point
	 */
	public function testLegacyHomeMount() {
		if (getenv('RUN_OBJECTSTORE_TESTS')) {
			$this->markTestSkipped('legacy storage unrelated to objectstore environments');
		}
		$datadir = \OC::$server->getConfig()->getSystemValue("datadirectory", \OC::$SERVERROOT . "/data");
		$userId = $this->getUniqueID('user_');

		// insert storage into DB by constructing it
		// to make initMountsPoint find its existence
		$localStorage = new \OC\Files\Storage\Local(array('datadir' => $datadir . '/' . $userId . '/'));
		// this will trigger the insert
		$cache = $localStorage->getCache();

		\OC::$server->getUserManager()->createUser($userId, $userId);
		\OC\Files\Filesystem::initMountPoints($userId);

		$homeMount = \OC\Files\Filesystem::getStorage('/' . $userId . '/');

		$this->assertTrue($homeMount->instanceOfStorage('\OC\Files\Storage\Home'));
		$this->assertEquals('local::' . $datadir . '/' . $userId . '/', $homeMount->getId());

		$user = \OC::$server->getUserManager()->get($userId);
		if ($user !== null) { $user->delete(); }
		// delete storage entry
		$cache->clear();
	}

	public function dummyHook($arguments) {
		$path = $arguments['path'];
		$this->assertEquals($path, \OC\Files\Filesystem::normalizePath($path)); //the path passed to the hook should already be normalized
	}

	/**
	 * Test that the default cache dir is part of the user's home
	 */
	public function testMountDefaultCacheDir() {
		$userId = $this->getUniqueID('user_');
		$config = \OC::$server->getConfig();
		$oldCachePath = $config->getSystemValue('cache_path', '');
		// no cache path configured
		$config->setSystemValue('cache_path', '');

		\OC::$server->getUserManager()->createUser($userId, $userId);
		\OC\Files\Filesystem::initMountPoints($userId);

		$this->assertEquals(
			'/' . $userId . '/',
			\OC\Files\Filesystem::getMountPoint('/' . $userId . '/cache')
		);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath('/' . $userId . '/cache');
		$this->assertTrue($storage->instanceOfStorage('\OCP\Files\IHomeStorage'));
		$this->assertEquals('cache', $internalPath);
		$user = \OC::$server->getUserManager()->get($userId);
		if ($user !== null) { $user->delete(); }

		$config->setSystemValue('cache_path', $oldCachePath);
	}

	/**
	 * Test that an external cache is mounted into
	 * the user's home
	 */
	public function testMountExternalCacheDir() {
		$userId = $this->getUniqueID('user_');

		$config = \OC::$server->getConfig();
		$oldCachePath = $config->getSystemValue('cache_path', '');
		// set cache path to temp dir
		$cachePath = \OC::$server->getTempManager()->getTemporaryFolder() . '/extcache';
		$config->setSystemValue('cache_path', $cachePath);

		\OC::$server->getUserManager()->createUser($userId, $userId);
		\OC\Files\Filesystem::initMountPoints($userId);

		$this->assertEquals(
			'/' . $userId . '/cache/',
			\OC\Files\Filesystem::getMountPoint('/' . $userId . '/cache')
		);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath('/' . $userId . '/cache');
		$this->assertTrue($storage->instanceOfStorage('\OC\Files\Storage\Local'));
		$this->assertEquals('', $internalPath);
		$user = \OC::$server->getUserManager()->get($userId);
		if ($user !== null) { $user->delete(); }

		$config->setSystemValue('cache_path', $oldCachePath);
	}

	public function testRegisterMountProviderAfterSetup() {
		\OC\Files\Filesystem::initMountPoints(self::TEST_FILESYSTEM_USER2);
		$this->assertEquals('/', \OC\Files\Filesystem::getMountPoint('/foo/bar'));
		$mount = new MountPoint(new Temporary([]), '/foo/bar');
		$mountProvider = new DummyMountProvider([self::TEST_FILESYSTEM_USER2 => [$mount]]);
		\OC::$server->getMountProviderCollection()->registerProvider($mountProvider);
		$this->assertEquals('/foo/bar/', \OC\Files\Filesystem::getMountPoint('/foo/bar'));
	}
}
