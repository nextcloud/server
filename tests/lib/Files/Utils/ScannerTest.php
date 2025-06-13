<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Utils;

use OC\Files\Filesystem;
use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Temporary;
use OC\Files\Utils\Scanner;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Config\IMountProviderCollection;
use OCP\Files\Storage\IStorageFactory;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class TestScanner extends Scanner {
	/**
	 * @var \OC\Files\Mount\MountPoint[] $mounts
	 */
	private $mounts = [];

	/**
	 * @param \OC\Files\Mount\MountPoint $mount
	 */
	public function addMount($mount) {
		$this->mounts[] = $mount;
	}

	protected function getMounts($dir) {
		return $this->mounts;
	}
}

/**
 * Class ScannerTest
 *
 * @group DB
 *
 * @package Test\Files\Utils
 */
class ScannerTest extends \Test\TestCase {
	/**
	 * @var \Test\Util\User\Dummy
	 */
	private $userBackend;

	protected function setUp(): void {
		parent::setUp();

		$this->userBackend = new \Test\Util\User\Dummy();
		Server::get(IUserManager::class)->registerBackend($this->userBackend);
		$this->loginAsUser();
	}

	protected function tearDown(): void {
		$this->logout();
		Server::get(IUserManager::class)->removeBackend($this->userBackend);
		parent::tearDown();
	}

	public function testReuseExistingRoot(): void {
		$storage = new Temporary([]);
		$mount = new MountPoint($storage, '');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', 'qwerty');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');

		$scanner = new TestScanner('', Server::get(IDBConnection::class), $this->createMock(IEventDispatcher::class), Server::get(LoggerInterface::class));
		$scanner->addMount($mount);

		$scanner->scan('');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$oldRoot = $cache->get('');

		$scanner->scan('');
		$newRoot = $cache->get('');
		$this->assertEquals($oldRoot, $newRoot);
	}

	public function testReuseExistingFile(): void {
		$storage = new Temporary([]);
		$mount = new MountPoint($storage, '');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', 'qwerty');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');

		$scanner = new TestScanner('', Server::get(IDBConnection::class), $this->createMock(IEventDispatcher::class), Server::get(LoggerInterface::class));
		$scanner->addMount($mount);

		$scanner->scan('');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$old = $cache->get('folder/bar.txt');

		$scanner->scan('');
		$new = $cache->get('folder/bar.txt');
		$this->assertEquals($old, $new);
	}

	public function testScanSubMount(): void {
		$uid = $this->getUniqueID();
		$this->userBackend->createUser($uid, 'test');

		$mountProvider = $this->createMock(IMountProvider::class);

		$storage = new Temporary([]);
		$mount = new MountPoint($storage, '/' . $uid . '/files/foo');

		$mountProvider->expects($this->any())
			->method('getMountsForUser')
			->willReturnCallback(function (IUser $user, IStorageFactory $storageFactory) use ($mount, $uid) {
				if ($user->getUID() === $uid) {
					return [$mount];
				} else {
					return [];
				}
			});

		Server::get(IMountProviderCollection::class)->registerProvider($mountProvider);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', 'qwerty');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');

		$scanner = new Scanner($uid, Server::get(IDBConnection::class), Server::get(IEventDispatcher::class), Server::get(LoggerInterface::class));

		$this->assertFalse($cache->inCache('folder/bar.txt'));
		$scanner->scan('/' . $uid . '/files/foo');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
	}

	public static function invalidPathProvider(): array {
		return [
			[
				'../',
			],
			[
				'..\\',
			],
			[
				'../..\\../',
			],
		];
	}

	/**
	 * @dataProvider invalidPathProvider
	 * @param string $invalidPath
	 */
	public function testInvalidPathScanning($invalidPath): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid path to scan');

		$scanner = new TestScanner('', Server::get(IDBConnection::class), $this->createMock(IEventDispatcher::class), Server::get(LoggerInterface::class));
		$scanner->scan($invalidPath);
	}

	public function testPropagateEtag(): void {
		$storage = new Temporary([]);
		$mount = new MountPoint($storage, '');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');
		$storage->touch('folder/bar.txt', time() - 200);

		$scanner = new TestScanner('', Server::get(IDBConnection::class), $this->createMock(IEventDispatcher::class), Server::get(LoggerInterface::class));
		$scanner->addMount($mount);

		$scanner->scan('');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$oldRoot = $cache->get('');

		$storage->file_put_contents('folder/bar.txt', 'qwerty');
		$scanner->scan('');
		$newRoot = $cache->get('');

		$this->assertNotEquals($oldRoot->getEtag(), $newRoot->getEtag());
	}

	public function testShallow(): void {
		$storage = new Temporary([]);
		$mount = new MountPoint($storage, '');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->mkdir('folder/subfolder');
		$storage->file_put_contents('foo.txt', 'qwerty');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');
		$storage->file_put_contents('folder/subfolder/foobar.txt', 'qwerty');

		$scanner = new TestScanner('', Server::get(IDBConnection::class), $this->createMock(IEventDispatcher::class), Server::get(LoggerInterface::class));
		$scanner->addMount($mount);

		$scanner->scan('', $recusive = false);
		$this->assertTrue($cache->inCache('folder'));
		$this->assertFalse($cache->inCache('folder/subfolder'));
		$this->assertTrue($cache->inCache('foo.txt'));
		$this->assertFalse($cache->inCache('folder/bar.txt'));
		$this->assertFalse($cache->inCache('folder/subfolder/foobar.txt'));

		$scanner->scan('folder', $recusive = false);
		$this->assertTrue($cache->inCache('folder'));
		$this->assertTrue($cache->inCache('folder/subfolder'));
		$this->assertTrue($cache->inCache('foo.txt'));
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$this->assertFalse($cache->inCache('folder/subfolder/foobar.txt'));
	}
}
