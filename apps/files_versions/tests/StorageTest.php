<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\files_versions\tests;

use OC\Files\View;
use OCA\Files_Versions\Expiration;
use OCA\Files_Versions\Storage;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Server;
use Test\TestCase;
use Test\Traits\UserTrait;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class StorageTest extends TestCase {
	use UserTrait;

	private $versionsRoot;
	private $userFolder;
	private int $expireTimestamp = 10;

	protected function setUp(): void {
		parent::setUp();

		$expiration = $this->createMock(Expiration::class);
		$expiration->method('getMaxAgeAsTimestamp')
			->willReturnCallback(function () {
				return $this->expireTimestamp;
			});
		$this->overwriteService(Expiration::class, $expiration);

		\OC::$server->boot();

		$this->createUser('version_test', '');
		$this->loginAsUser('version_test');
		/** @var IRootFolder $root */
		$root = Server::get(IRootFolder::class);
		$this->userFolder = $root->getUserFolder('version_test');
	}

	protected function createPastFile(string $path, int $mtime): void {
		try {
			$file = $this->userFolder->get($path);
			$file->putContent((string)$mtime);
		} catch (NotFoundException $e) {
			$file = $this->userFolder->newFile($path, (string)$mtime);
		}
		$file->touch($mtime);
	}

	public function testExpireMaxAge(): void {
		$this->userFolder->newFolder('folder1');
		$this->userFolder->newFolder('folder1/sub1');
		$this->userFolder->newFolder('folder2');

		$this->createPastFile('file1', 100);
		$this->createPastFile('file1', 500);
		$this->createPastFile('file1', 900);

		$this->createPastFile('folder1/file2', 100);
		$this->createPastFile('folder1/file2', 200);
		$this->createPastFile('folder1/file2', 300);

		$this->createPastFile('folder1/sub1/file3', 400);
		$this->createPastFile('folder1/sub1/file3', 500);
		$this->createPastFile('folder1/sub1/file3', 600);

		$this->createPastFile('folder2/file4', 100);
		$this->createPastFile('folder2/file4', 600);
		$this->createPastFile('folder2/file4', 800);

		$this->assertCount(2, Storage::getVersions('version_test', 'file1'));
		$this->assertCount(2, Storage::getVersions('version_test', 'folder1/file2'));
		$this->assertCount(2, Storage::getVersions('version_test', 'folder1/sub1/file3'));
		$this->assertCount(2, Storage::getVersions('version_test', 'folder2/file4'));

		$this->expireTimestamp = 150;
		Storage::expireOlderThanMaxForUser('version_test');

		$this->assertCount(1, Storage::getVersions('version_test', 'file1'));
		$this->assertCount(1, Storage::getVersions('version_test', 'folder1/file2'));
		$this->assertCount(2, Storage::getVersions('version_test', 'folder1/sub1/file3'));
		$this->assertCount(1, Storage::getVersions('version_test', 'folder2/file4'));

		$this->expireTimestamp = 550;
		Storage::expireOlderThanMaxForUser('version_test');

		$this->assertCount(0, Storage::getVersions('version_test', 'file1'));
		$this->assertCount(0, Storage::getVersions('version_test', 'folder1/file2'));
		$this->assertCount(0, Storage::getVersions('version_test', 'folder1/sub1/file3'));
		$this->assertCount(1, Storage::getVersions('version_test', 'folder2/file4'));
	}

	/**
	 * Regression test: when copyFileContents() fails to acquire the lock on the
	 * target file, the lock already held on the source file must be released
	 * before the exception propagates. Otherwise the source lock leaks and a
	 * retry (or any later access) collides with a lock nobody owns anymore.
	 */
	public function testCopyFileContentsReleasesSourceLockWhenTargetLockFails(): void {
		$source = 'files/source.txt';
		$target = 'files/target.txt';
		$lockedException = new LockedException($target);

		$storage = $this->createMock(\OC\Files\Storage\Storage::class);

		$view = $this->createMock(View::class);
		$view->method('resolvePath')->willReturn([$storage, 'internal']);

		// The source lock is acquired first and succeeds; acquiring the target
		// lock then throws (e.g. a concurrent holder under load).
		$view->expects($this->exactly(2))
			->method('lockFile')
			->willReturnCallback(function (string $path) use ($target, $lockedException): void {
				if ($path === $target) {
					throw $lockedException;
				}
			});

		// The already-held source lock must be released exactly once, and the
		// never-acquired target lock must not be touched.
		$view->expects($this->once())
			->method('unlockFile')
			->with($source, ILockingProvider::LOCK_EXCLUSIVE);

		$this->expectExceptionObject($lockedException);

		$method = new \ReflectionMethod(Storage::class, 'copyFileContents');
		$method->invoke(null, $view, $source, $target);
	}
}
