<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Utils;

use OC\Files\Filesystem;
use OC\Files\Mount\MountPoint;
use OC\Files\SetupManager;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Test\Util\User\Dummy;

class TestScanner extends Scanner {
	/** @var array<string, MountPoint> $mounts */
	private array $mounts = [];

	public function addMount(MountPoint $mount): void {
		$this->mounts[$mount->getMountPoint()] = $mount;
	}

	#[\Override]
	protected function getMounts(string $dir): array {
		return $this->mounts;
	}
}

class ScannerTest extends TestCase {
	private Dummy $userBackend;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userBackend = new Dummy();
		Server::get(IUserManager::class)->registerBackend($this->userBackend);
		$this->loginAsUser();
	}

	#[\Override]
	protected function tearDown(): void {
		$this->logout();
		Server::get(IUserManager::class)->removeBackend($this->userBackend);
		parent::tearDown();
	}

	#[Group('DB')]
	public function testReuseExistingRoot(): void {
		$storage = new Temporary([]);
		$mount = new MountPoint($storage, '');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', 'qwerty');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');

		$scanner = new TestScanner(
			Server::get(IUserManager::class)->get(''),
			Server::get(IDBConnection::class),
			$this->createMock(IEventDispatcher::class),
			Server::get(LoggerInterface::class),
			Server::get(SetupManager::class),
		);
		$scanner->addMount($mount);

		$scanner->scan('');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$oldRoot = $cache->get('');

		$scanner->scan('');
		$newRoot = $cache->get('');
		$this->assertEquals($oldRoot, $newRoot);
	}

	#[Group('DB')]
	public function testReuseExistingFile(): void {
		$storage = new Temporary([]);
		$mount = new MountPoint($storage, '');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', 'qwerty');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');

		$scanner = new TestScanner(
			Server::get(IUserManager::class)->get(''),
			Server::get(IDBConnection::class),
			$this->createMock(IEventDispatcher::class),
			Server::get(LoggerInterface::class),
			Server::get(SetupManager::class),
		);
		$scanner->addMount($mount);

		$scanner->scan('');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$old = $cache->get('folder/bar.txt');

		$scanner->scan('');
		$new = $cache->get('folder/bar.txt');
		$this->assertEquals($old, $new);
	}

	#[Group('DB')]
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

		$scanner = new Scanner(
			Server::get(IUserManager::class)->get($uid),
			Server::get(IDBConnection::class),
			Server::get(IEventDispatcher::class),
			Server::get(LoggerInterface::class),
			Server::get(SetupManager::class),
		);

		$this->assertFalse($cache->inCache('folder/bar.txt'));
		$scanner->scan('/' . $uid . '/files/foo');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
	}

	public static function invalidPathProvider(): \Generator {
		yield [ '../' ];
		yield [ '..\\' ];
		yield [ '../..\\../' ];
	}

	#[DataProvider(methodName: 'invalidPathProvider')]
	public function testInvalidPathScanning(string $invalidPath): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid path to scan');

		$scanner = new TestScanner(
			Server::get(IUserManager::class)->get(''),
			Server::get(IDBConnection::class),
			$this->createMock(IEventDispatcher::class),
			Server::get(LoggerInterface::class),
			Server::get(SetupManager::class),
		);
		$scanner->scan($invalidPath);
	}

	#[Group('DB')]
	public function testPropagateEtag(): void {
		$storage = new Temporary([]);
		$mount = new MountPoint($storage, '');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');
		$storage->touch('folder/bar.txt', time() - 200);

		$scanner = new TestScanner(
			Server::get(IUserManager::class)->get(''),
			Server::get(IDBConnection::class),
			$this->createMock(IEventDispatcher::class),
			Server::get(LoggerInterface::class),
			Server::get(SetupManager::class),
		);
		$scanner->addMount($mount);

		$scanner->scan('');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$oldRoot = $cache->get('');

		$storage->file_put_contents('folder/bar.txt', 'qwerty');
		$scanner->scan('');
		$newRoot = $cache->get('');

		$this->assertNotEquals($oldRoot->getEtag(), $newRoot->getEtag());
	}

	#[Group('DB')]
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

		$scanner = new TestScanner(
			Server::get(IUserManager::class)->get(''),
			Server::get(IDBConnection::class),
			$this->createMock(IEventDispatcher::class),
			Server::get(LoggerInterface::class),
			Server::get(SetupManager::class),
		);
		$scanner->addMount($mount);

		$scanner->scan('', false);
		$this->assertTrue($cache->inCache('folder'));
		$this->assertFalse($cache->inCache('folder/subfolder'));
		$this->assertTrue($cache->inCache('foo.txt'));
		$this->assertFalse($cache->inCache('folder/bar.txt'));
		$this->assertFalse($cache->inCache('folder/subfolder/foobar.txt'));

		$scanner->scan('folder', false);
		$this->assertTrue($cache->inCache('folder'));
		$this->assertTrue($cache->inCache('folder/subfolder'));
		$this->assertTrue($cache->inCache('foo.txt'));
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$this->assertFalse($cache->inCache('folder/subfolder/foobar.txt'));
	}
}
