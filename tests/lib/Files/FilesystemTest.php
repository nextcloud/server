<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files;

use OC\Files\Filesystem;
use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Temporary;
use OC\Files\View;
use OC\User\NoUserException;
use OCP\Files;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Server;

class DummyMountProvider implements IMountProvider {
	/**
	 * @param array $mounts
	 */
	public function __construct(
		private array $mounts,
	) {
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
		$dir = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->tmpDirs[] = $dir;
		return ['datadir' => $dir];
	}

	protected function setUp(): void {
		parent::setUp();
		$userBackend = new \Test\Util\User\Dummy();
		$userBackend->createUser(self::TEST_FILESYSTEM_USER1, self::TEST_FILESYSTEM_USER1);
		$userBackend->createUser(self::TEST_FILESYSTEM_USER2, self::TEST_FILESYSTEM_USER2);
		Server::get(IUserManager::class)->registerBackend($userBackend);
		$this->loginAsUser();
	}

	protected function tearDown(): void {
		foreach ($this->tmpDirs as $dir) {
			Files::rmdirr($dir);
		}

		$this->logout();
		$this->invokePrivate('\OC\Files\Filesystem', 'normalizedPathCache', [null]);
		parent::tearDown();
	}

	public function testMount(): void {
		Filesystem::mount('\OC\Files\Storage\Local', self::getStorageData(), '/');
		$this->assertEquals('/', Filesystem::getMountPoint('/'));
		$this->assertEquals('/', Filesystem::getMountPoint('/some/folder'));
		[, $internalPath] = Filesystem::resolvePath('/');
		$this->assertEquals('', $internalPath);
		[, $internalPath] = Filesystem::resolvePath('/some/folder');
		$this->assertEquals('some/folder', $internalPath);

		Filesystem::mount('\OC\Files\Storage\Local', self::getStorageData(), '/some');
		$this->assertEquals('/', Filesystem::getMountPoint('/'));
		$this->assertEquals('/some/', Filesystem::getMountPoint('/some/folder'));
		$this->assertEquals('/some/', Filesystem::getMountPoint('/some/'));
		$this->assertEquals('/some/', Filesystem::getMountPoint('/some'));
		[, $internalPath] = Filesystem::resolvePath('/some/folder');
		$this->assertEquals('folder', $internalPath);
	}

	public static function normalizePathData(): array {
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
		$this->assertEquals($expected, Filesystem::normalizePath($path, $stripTrailingSlash));
	}

	public static function normalizePathKeepUnicodeData(): array {
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
		$this->assertEquals($expected, Filesystem::normalizePath($path, true, false, $keepUnicode));
	}

	public function testNormalizePathKeepUnicodeCache(): void {
		$nfdName = 'ümlaut';
		$nfcName = 'ümlaut';
		// call in succession due to cache
		$this->assertEquals('/' . $nfcName, Filesystem::normalizePath($nfdName, true, false, false));
		$this->assertEquals('/' . $nfdName, Filesystem::normalizePath($nfdName, true, false, true));
	}

	public static function isValidPathData(): array {
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
		$this->assertSame($expected, Filesystem::isValidPath($path));
	}

	public static function isFileBlacklistedData(): array {
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
		$this->assertSame($expected, Filesystem::isFileBlacklisted($path));
	}

	public function testNormalizePathUTF8(): void {
		if (!class_exists('Patchwork\PHP\Shim\Normalizer')) {
			$this->markTestSkipped('UTF8 normalizer Patchwork was not found');
		}

		$this->assertEquals("/foo/bar\xC3\xBC", Filesystem::normalizePath("/foo/baru\xCC\x88"));
		$this->assertEquals("/foo/bar\xC3\xBC", Filesystem::normalizePath("\\foo\\baru\xCC\x88"));
	}

	public function testHooks(): void {
		if (Filesystem::getView()) {
			$user = \OC_User::getUser();
		} else {
			$user = self::TEST_FILESYSTEM_USER1;
			$backend = new \Test\Util\User\Dummy();
			Server::get(IUserManager::class)->registerBackend($backend);
			$backend->createUser($user, $user);
			$userObj = Server::get(IUserManager::class)->get($user);
			Server::get(IUserSession::class)->setUser($userObj);
			Filesystem::init($user, '/' . $user . '/files');
		}
		\OC_Hook::clear('OC_Filesystem');
		\OC_Hook::connect('OC_Filesystem', 'post_write', $this, 'dummyHook');

		Filesystem::mount('OC\Files\Storage\Temporary', [], '/');

		$rootView = new View('');
		$rootView->mkdir('/' . $user);
		$rootView->mkdir('/' . $user . '/files');

		//		\OC\Files\Filesystem::file_put_contents('/foo', 'foo');
		Filesystem::mkdir('/bar');
		//		\OC\Files\Filesystem::file_put_contents('/bar//foo', 'foo');

		$tmpFile = Server::get(ITempManager::class)->getTemporaryFile();
		file_put_contents($tmpFile, 'foo');
		$fh = fopen($tmpFile, 'r');
		//		\OC\Files\Filesystem::file_put_contents('/bar//foo', $fh);
	}

	/**
	 * Tests that an exception is thrown when passed user does not exist.
	 *
	 */
	public function testLocalMountWhenUserDoesNotExist(): void {
		$this->expectException(NoUserException::class);

		$userId = $this->getUniqueID('user_');

		Filesystem::initMountPoints($userId);
	}


	public function testNullUserThrows(): void {
		$this->expectException(NoUserException::class);

		Filesystem::initMountPoints(null);
	}

	public function testNullUserThrowsTwice(): void {
		$thrown = 0;
		try {
			Filesystem::initMountPoints(null);
		} catch (NoUserException $e) {
			$thrown++;
		}
		try {
			Filesystem::initMountPoints(null);
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
			Filesystem::initMountPoints($userId);
		} catch (NoUserException $e) {
			$thrown++;
		}

		try {
			Filesystem::initMountPoints($userId);
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

		Server::get(IUserManager::class)->createUser($userId, $userId);

		Filesystem::initMountPoints($userId);

		$homeMount = Filesystem::getStorage('/' . $userId . '/');

		$this->assertTrue($homeMount->instanceOfStorage('\OCP\Files\IHomeStorage'));
		if ($homeMount->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage')) {
			$this->assertEquals('object::user:' . $userId, $homeMount->getId());
		} elseif ($homeMount->instanceOfStorage('\OC\Files\Storage\Home')) {
			$this->assertEquals('home::' . $userId, $homeMount->getId());
		}

		$user = Server::get(IUserManager::class)->get($userId);
		if ($user !== null) {
			$user->delete();
		}
	}

	public function dummyHook($arguments) {
		$path = $arguments['path'];
		$this->assertEquals($path, Filesystem::normalizePath($path)); //the path passed to the hook should already be normalized
	}

	/**
	 * Test that the default cache dir is part of the user's home
	 */
	public function testMountDefaultCacheDir(): void {
		$userId = $this->getUniqueID('user_');
		$config = Server::get(IConfig::class);
		$oldCachePath = $config->getSystemValueString('cache_path', '');
		// no cache path configured
		$config->setSystemValue('cache_path', '');

		Server::get(IUserManager::class)->createUser($userId, $userId);
		Filesystem::initMountPoints($userId);

		$this->assertEquals(
			'/' . $userId . '/',
			Filesystem::getMountPoint('/' . $userId . '/cache')
		);
		[$storage, $internalPath] = Filesystem::resolvePath('/' . $userId . '/cache');
		$this->assertTrue($storage->instanceOfStorage('\OCP\Files\IHomeStorage'));
		$this->assertEquals('cache', $internalPath);
		$user = Server::get(IUserManager::class)->get($userId);
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

		$config = Server::get(IConfig::class);
		$oldCachePath = $config->getSystemValueString('cache_path', '');
		// set cache path to temp dir
		$cachePath = Server::get(ITempManager::class)->getTemporaryFolder() . '/extcache';
		$config->setSystemValue('cache_path', $cachePath);

		Server::get(IUserManager::class)->createUser($userId, $userId);
		Filesystem::initMountPoints($userId);

		$this->assertEquals(
			'/' . $userId . '/cache/',
			Filesystem::getMountPoint('/' . $userId . '/cache')
		);
		[$storage, $internalPath] = Filesystem::resolvePath('/' . $userId . '/cache');
		$this->assertTrue($storage->instanceOfStorage('\OC\Files\Storage\Local'));
		$this->assertEquals('', $internalPath);
		$user = Server::get(IUserManager::class)->get($userId);
		if ($user !== null) {
			$user->delete();
		}

		$config->setSystemValue('cache_path', $oldCachePath);
	}

	public function testRegisterMountProviderAfterSetup(): void {
		Filesystem::initMountPoints(self::TEST_FILESYSTEM_USER2);
		$this->assertEquals('/', Filesystem::getMountPoint('/foo/bar'));
		$mount = new MountPoint(new Temporary([]), '/foo/bar');
		$mountProvider = new DummyMountProvider([self::TEST_FILESYSTEM_USER2 => [$mount]]);
		Server::get(IMountProviderCollection::class)->registerProvider($mountProvider);
		$this->assertEquals('/foo/bar/', Filesystem::getMountPoint('/foo/bar'));
	}
}
