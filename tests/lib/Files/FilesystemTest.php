<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	public function getMountsForUser(IUser $user, IStorageFactory $loader) {
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
	public const TEST_FILESYSTEM_USER1 = 'test-filesystem-user1';
	public const TEST_FILESYSTEM_USER2 = 'test-filesystem-user1';

	/**
	 * @var array tmpDirs
	 */
	private $tmpDirs = [];

	/**
	 * @return array
	 */
	private function getStorageData() {
		$dir = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->tmpDirs[] = $dir;
		return ['datadir' => $dir];
	}

	protected function setUp(): void {
		parent::setUp();
		$userBackend = new \Test\Util\User\Dummy();
		$userBackend->createUser(self::TEST_FILESYSTEM_USER1, self::TEST_FILESYSTEM_USER1);
		$userBackend->createUser(self::TEST_FILESYSTEM_USER2, self::TEST_FILESYSTEM_USER2);
		\OC::$server->getUserManager()->registerBackend($userBackend);
		$this->loginAsUser();
	}

	protected function tearDown(): void {
		foreach ($this->tmpDirs as $dir) {
			\OC_Helper::rmdirr($dir);
		}

		$this->logout();
		$this->invokePrivate('\OC\Files\Filesystem', 'normalizedPathCache', [null]);
		parent::tearDown();
	}

	public function testMount(): void {
		\OC\Files\Filesystem::mount('\OC\Files\Storage\Local', self::getStorageData(), '/');
		$this->assertEquals('/', \OC\Files\Filesystem::getMountPoint('/'));
		$this->assertEquals('/', \OC\Files\Filesystem::getMountPoint('/some/folder'));
		[, $internalPath] = \OC\Files\Filesystem::resolvePath('/');
		$this->assertEquals('', $internalPath);
		[, $internalPath] = \OC\Files\Filesystem::resolvePath('/some/folder');
		$this->assertEquals('some/folder', $internalPath);

		\OC\Files\Filesystem::mount('\OC\Files\Storage\Local', self::getStorageData(), '/some');
		$this->assertEquals('/', \OC\Files\Filesystem::getMountPoint('/'));
		$this->assertEquals('/some/', \OC\Files\Filesystem::getMountPoint('/some/folder'));
		$this->assertEquals('/some/', \OC\Files\Filesystem::getMountPoint('/some/'));
		$this->assertEquals('/some/', \OC\Files\Filesystem::getMountPoint('/some'));
		[, $internalPath] = \OC\Files\Filesystem::resolvePath('/some/folder');
		$this->assertEquals('folder', $internalPath);
	}

	public function normalizePathData() {
		return [
			['/', ''],
			['/', '/'],
			['/', '//'],
			['/', '/', false],
			['/', '//', false],

			['/path', '/path/'],
			['/path/', '/path/', false],
			['/path', 'path'],

			['/foo/bar', '/foo//bar/'],
			['/foo/bar/', '/foo//bar/', false],
			['/foo/bar', '/foo////bar'],
			['/foo/bar', '/foo/////bar'],
			['/foo/bar', '/foo/bar/.'],
			['/foo/bar', '/foo/bar/./'],
			['/foo/bar/', '/foo/bar/./', false],
			['/foo/bar', '/foo/bar/./.'],
			['/foo/bar', '/foo/bar/././'],
			['/foo/bar/', '/foo/bar/././', false],
			['/foo/bar', '/foo/./bar/'],
			['/foo/bar/', '/foo/./bar/', false],
			['/foo/.bar', '/foo/.bar/'],
			['/foo/.bar/', '/foo/.bar/', false],
			['/foo/.bar/tee', '/foo/.bar/tee'],
			['/foo/bar.', '/foo/bar./'],
			['/foo/bar./', '/foo/bar./', false],
			['/foo/bar./tee', '/foo/bar./tee'],
			['/foo/.bar.', '/foo/.bar./'],
			['/foo/.bar./', '/foo/.bar./', false],
			['/foo/.bar./tee', '/foo/.bar./tee'],

			['/foo/bar', '/.////././//./foo/.///././//./bar/././/./.'],
			['/foo/bar/', '/.////././//./foo/.///././//./bar/./././.', false],
			['/foo/bar', '/.////././//./foo/.///././//./bar/././/././'],
			['/foo/bar/', '/.////././//./foo/.///././//./bar/././/././', false],
			['/foo/.bar', '/.////././//./foo/./././/./.bar/././/././'],
			['/foo/.bar/', '/.////././//./foo/./././/./.bar/././/././', false],
			['/foo/.bar/tee./', '/.////././//./foo/./././/./.bar/tee././/././', false],
			['/foo/bar.', '/.////././//./foo/./././/./bar./././/././'],
			['/foo/bar./', '/.////././//./foo/./././/./bar./././/././', false],
			['/foo/bar./tee./', '/.////././//./foo/./././/./bar./tee././/././', false],
			['/foo/.bar.', '/.////././//./foo/./././/./.bar./././/././'],
			['/foo/.bar./', '/.////././//./foo/./././/./.bar./././././', false],
			['/foo/.bar./tee./', '/.////././//./foo/./././/./.bar./tee././././', false],

			// Windows paths
			['/', ''],
			['/', '\\'],
			['/', '\\', false],
			['/', '\\\\'],
			['/', '\\\\', false],

			['/path', '\\path'],
			['/path', '\\path', false],
			['/path', '\\path\\'],
			['/path/', '\\path\\', false],

			['/foo/bar', '\\foo\\\\bar\\'],
			['/foo/bar/', '\\foo\\\\bar\\', false],
			['/foo/bar', '\\foo\\\\\\\\bar'],
			['/foo/bar', '\\foo\\\\\\\\\\bar'],
			['/foo/bar', '\\foo\\bar\\.'],
			['/foo/bar', '\\foo\\bar\\.\\'],
			['/foo/bar/', '\\foo\\bar\\.\\', false],
			['/foo/bar', '\\foo\\bar\\.\\.'],
			['/foo/bar', '\\foo\\bar\\.\\.\\'],
			['/foo/bar/', '\\foo\\bar\\.\\.\\', false],
			['/foo/bar', '\\foo\\.\\bar\\'],
			['/foo/bar/', '\\foo\\.\\bar\\', false],
			['/foo/.bar', '\\foo\\.bar\\'],
			['/foo/.bar/', '\\foo\\.bar\\', false],
			['/foo/.bar/tee', '\\foo\\.bar\\tee'],

			// Absolute windows paths NOT marked as absolute
			['/C:', 'C:\\'],
			['/C:/', 'C:\\', false],
			['/C:/tests', 'C:\\tests'],
			['/C:/tests', 'C:\\tests', false],
			['/C:/tests', 'C:\\tests\\'],
			['/C:/tests/', 'C:\\tests\\', false],
			['/C:/tests/bar', 'C:\\tests\\.\\.\\bar'],
			['/C:/tests/bar/', 'C:\\tests\\.\\.\\bar\\.\\', false],

			// normalize does not resolve '..' (by design)
			['/foo/..', '/foo/../'],
			['/foo/../bar', '/foo/../bar/.'],
			['/foo/..', '\\foo\\..\\'],
			['/foo/../bar', '\\foo\\..\\bar'],
		];
	}

	/**
	 * @dataProvider normalizePathData
	 */
	public function testNormalizePath($expected, $path, $stripTrailingSlash = true): void {
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
	public function testNormalizePathKeepUnicode($expected, $path, $keepUnicode = false): void {
		$this->assertEquals($expected, \OC\Files\Filesystem::normalizePath($path, true, false, $keepUnicode));
	}

	public function testNormalizePathKeepUnicodeCache(): void {
		$nfdName = 'ümlaut';
		$nfcName = 'ümlaut';
		// call in succession due to cache
		$this->assertEquals('/' . $nfcName, \OC\Files\Filesystem::normalizePath($nfdName, true, false, false));
		$this->assertEquals('/' . $nfdName, \OC\Files\Filesystem::normalizePath($nfdName, true, false, true));
	}

	public function isValidPathData() {
		return [
			['/', true],
			['/path', true],
			['/foo/bar', true],
			['/foo//bar/', true],
			['/foo////bar', true],
			['/foo//\///bar', true],
			['/foo/bar/.', true],
			['/foo/bar/./', true],
			['/foo/bar/./.', true],
			['/foo/bar/././', true],
			['/foo/bar/././..bar', true],
			['/foo/bar/././..bar/a', true],
			['/foo/bar/././..', false],
			['/foo/bar/././../', false],
			['/foo/bar/.././', false],
			['/foo/bar/../../', false],
			['/foo/bar/../..\\', false],
			['..', false],
			['../', false],
			['../foo/bar', false],
			['..\foo/bar', false],
		];
	}

	/**
	 * @dataProvider isValidPathData
	 */
	public function testIsValidPath($path, $expected): void {
		$this->assertSame($expected, \OC\Files\Filesystem::isValidPath($path));
	}

	public function isFileBlacklistedData() {
		return [
			['/etc/foo/bar/foo.txt', false],
			['\etc\foo/bar\foo.txt', false],
			['.htaccess', true],
			['.htaccess/', true],
			['.htaccess\\', true],
			['/etc/foo\bar/.htaccess\\', true],
			['/etc/foo\bar/.htaccess/', true],
			['/etc/foo\bar/.htaccess/foo', false],
			['//foo//bar/\.htaccess/', true],
			['\foo\bar\.HTAccess', true],
		];
	}

	/**
	 * @dataProvider isFileBlacklistedData
	 */
	public function testIsFileBlacklisted($path, $expected): void {
		$this->assertSame($expected, \OC\Files\Filesystem::isFileBlacklisted($path));
	}

	public function testNormalizePathUTF8(): void {
		if (!class_exists('Patchwork\PHP\Shim\Normalizer')) {
			$this->markTestSkipped('UTF8 normalizer Patchwork was not found');
		}

		$this->assertEquals("/foo/bar\xC3\xBC", \OC\Files\Filesystem::normalizePath("/foo/baru\xCC\x88"));
		$this->assertEquals("/foo/bar\xC3\xBC", \OC\Files\Filesystem::normalizePath("\\foo\\baru\xCC\x88"));
	}

	public function testHooks(): void {
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

		\OC\Files\Filesystem::mount('OC\Files\Storage\Temporary', [], '/');

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
	 */
	public function testLocalMountWhenUserDoesNotExist(): void {
		$this->expectException(\OC\User\NoUserException::class);

		$userId = $this->getUniqueID('user_');

		\OC\Files\Filesystem::initMountPoints($userId);
	}


	public function testNullUserThrows(): void {
		$this->expectException(\OC\User\NoUserException::class);

		\OC\Files\Filesystem::initMountPoints(null);
	}

	public function testNullUserThrowsTwice(): void {
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
	public function testLocalMountWhenUserDoesNotExistTwice(): void {
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
	public function testHomeMount(): void {
		$userId = $this->getUniqueID('user_');

		\OC::$server->getUserManager()->createUser($userId, $userId);

		\OC\Files\Filesystem::initMountPoints($userId);

		$homeMount = \OC\Files\Filesystem::getStorage('/' . $userId . '/');

		$this->assertTrue($homeMount->instanceOfStorage('\OCP\Files\IHomeStorage'));
		if ($homeMount->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage')) {
			$this->assertEquals('object::user:' . $userId, $homeMount->getId());
		} elseif ($homeMount->instanceOfStorage('\OC\Files\Storage\Home')) {
			$this->assertEquals('home::' . $userId, $homeMount->getId());
		}

		$user = \OC::$server->getUserManager()->get($userId);
		if ($user !== null) {
			$user->delete();
		}
	}

	public function dummyHook($arguments) {
		$path = $arguments['path'];
		$this->assertEquals($path, \OC\Files\Filesystem::normalizePath($path)); //the path passed to the hook should already be normalized
	}

	/**
	 * Test that the default cache dir is part of the user's home
	 */
	public function testMountDefaultCacheDir(): void {
		$userId = $this->getUniqueID('user_');
		$config = \OC::$server->getConfig();
		$oldCachePath = $config->getSystemValueString('cache_path', '');
		// no cache path configured
		$config->setSystemValue('cache_path', '');

		\OC::$server->getUserManager()->createUser($userId, $userId);
		\OC\Files\Filesystem::initMountPoints($userId);

		$this->assertEquals(
			'/' . $userId . '/',
			\OC\Files\Filesystem::getMountPoint('/' . $userId . '/cache')
		);
		[$storage, $internalPath] = \OC\Files\Filesystem::resolvePath('/' . $userId . '/cache');
		$this->assertTrue($storage->instanceOfStorage('\OCP\Files\IHomeStorage'));
		$this->assertEquals('cache', $internalPath);
		$user = \OC::$server->getUserManager()->get($userId);
		if ($user !== null) {
			$user->delete();
		}

		$config->setSystemValue('cache_path', $oldCachePath);
	}

	/**
	 * Test that an external cache is mounted into
	 * the user's home
	 */
	public function testMountExternalCacheDir(): void {
		$userId = $this->getUniqueID('user_');

		$config = \OC::$server->getConfig();
		$oldCachePath = $config->getSystemValueString('cache_path', '');
		// set cache path to temp dir
		$cachePath = \OC::$server->getTempManager()->getTemporaryFolder() . '/extcache';
		$config->setSystemValue('cache_path', $cachePath);

		\OC::$server->getUserManager()->createUser($userId, $userId);
		\OC\Files\Filesystem::initMountPoints($userId);

		$this->assertEquals(
			'/' . $userId . '/cache/',
			\OC\Files\Filesystem::getMountPoint('/' . $userId . '/cache')
		);
		[$storage, $internalPath] = \OC\Files\Filesystem::resolvePath('/' . $userId . '/cache');
		$this->assertTrue($storage->instanceOfStorage('\OC\Files\Storage\Local'));
		$this->assertEquals('', $internalPath);
		$user = \OC::$server->getUserManager()->get($userId);
		if ($user !== null) {
			$user->delete();
		}

		$config->setSystemValue('cache_path', $oldCachePath);
	}

	public function testRegisterMountProviderAfterSetup(): void {
		\OC\Files\Filesystem::initMountPoints(self::TEST_FILESYSTEM_USER2);
		$this->assertEquals('/', \OC\Files\Filesystem::getMountPoint('/foo/bar'));
		$mount = new MountPoint(new Temporary([]), '/foo/bar');
		$mountProvider = new DummyMountProvider([self::TEST_FILESYSTEM_USER2 => [$mount]]);
		\OC::$server->getMountProviderCollection()->registerProvider($mountProvider);
		$this->assertEquals('/foo/bar/', \OC\Files\Filesystem::getMountPoint('/foo/bar'));
	}
}
