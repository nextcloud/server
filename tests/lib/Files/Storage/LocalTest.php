<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Storage\Local;
use OC\Files\Storage\Wrapper\Jail;
use OCP\Files;
use OCP\Files\ForbiddenException;
use OCP\Files\StorageNotAvailableException;
use OCP\ITempManager;
use OCP\Server;

/**
 * Class LocalTest
 *
 *
 * @package Test\Files\Storage
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class LocalTest extends Storage {
	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->tmpDir = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->instance = new Local(['datadir' => $this->tmpDir]);
	}

	#[\Override]
	protected function tearDown(): void {
		Files::rmdirr($this->tmpDir);
		parent::tearDown();
	}

	public function testStableEtag(): void {
		$this->instance->file_put_contents('test.txt', 'foobar');
		$etag1 = $this->instance->getETag('test.txt');
		$etag2 = $this->instance->getETag('test.txt');
		$this->assertEquals($etag1, $etag2);
	}

	public function testEtagChange(): void {
		$this->instance->file_put_contents('test.txt', 'foo');
		$this->instance->touch('test.txt', time() - 2);
		$etag1 = $this->instance->getETag('test.txt');
		$this->instance->file_put_contents('test.txt', 'bar');
		$etag2 = $this->instance->getETag('test.txt');
		$this->assertNotEquals($etag1, $etag2);
	}

	public function testInvalidArgumentsEmptyArray(): void {
		$this->expectException(\InvalidArgumentException::class);

		new Local([]);
	}

	public function testInvalidArgumentsNoArray(): void {
		$this->expectException(\InvalidArgumentException::class);

		new Local([]);
	}

	public function testDisallowSymlinksOutsideDatadir(): void {
		$this->expectException(ForbiddenException::class);

		$subDir1 = $this->tmpDir . 'sub1';
		$subDir2 = $this->tmpDir . 'sub2';
		$sym = $this->tmpDir . 'sub1/sym';
		mkdir($subDir1);
		mkdir($subDir2);

		symlink($subDir2, $sym);

		$storage = new Local(['datadir' => $subDir1]);

		$storage->file_put_contents('sym/foo', 'bar');
	}

	public function testDisallowSymlinksInsideDatadir(): void {
		$subDir1 = $this->tmpDir . 'sub1';
		$subDir2 = $this->tmpDir . 'sub1/sub2';
		$sym = $this->tmpDir . 'sub1/sym';
		mkdir($subDir1);
		mkdir($subDir2);

		symlink($subDir2, $sym);

		$storage = new Local(['datadir' => $subDir1]);

		$storage->file_put_contents('sym/foo', 'bar');
		$this->addToAssertionCount(1);
	}

	public function testWriteUmaskFilePutContents(): void {
		$oldMask = umask(0333);
		$this->instance->file_put_contents('test.txt', 'sad');
		umask($oldMask);
		$this->assertTrue($this->instance->isUpdatable('test.txt'));
	}

	public function testWriteUmaskMkdir(): void {
		$oldMask = umask(0333);
		$this->instance->mkdir('test.txt');
		umask($oldMask);
		$this->assertTrue($this->instance->isUpdatable('test.txt'));
	}

	public function testWriteUmaskFopen(): void {
		$oldMask = umask(0333);
		$handle = $this->instance->fopen('test.txt', 'w');
		fwrite($handle, 'foo');
		fclose($handle);
		umask($oldMask);
		$this->assertTrue($this->instance->isUpdatable('test.txt'));
	}

	public function testWriteUmaskCopy(): void {
		$this->instance->file_put_contents('source.txt', 'sad');
		$oldMask = umask(0333);
		$this->instance->copy('source.txt', 'test.txt');
		umask($oldMask);
		$this->assertTrue($this->instance->isUpdatable('test.txt'));
	}

	public function testUnavailableExternal(): void {
		$this->expectException(StorageNotAvailableException::class);
		$this->instance = new Local(['datadir' => $this->tmpDir . '/unexist', 'isExternal' => true]);
	}

	public function testUnavailableNonExternal(): void {
		$this->instance = new Local(['datadir' => $this->tmpDir . '/unexist']);
		// no exception thrown
		$this->assertNotNull($this->instance);
	}

	public function testMoveNestedJail(): void {
		$this->instance->mkdir('foo');
		$this->instance->mkdir('foo/bar');
		$this->instance->mkdir('target');
		$this->instance->file_put_contents('foo/bar/file.txt', 'foo');
		$jail1 = new Jail([
			'storage' => $this->instance,
			'root' => 'foo'
		]);
		$jail2 = new Jail([
			'storage' => $jail1,
			'root' => 'bar'
		]);
		$jail3 = new Jail([
			'storage' => $this->instance,
			'root' => 'target'
		]);
		$jail3->moveFromStorage($jail2, 'file.txt', 'file.txt');
		$this->assertTrue($this->instance->file_exists('target/file.txt'));
	}

	public function testFopenRestoresUmaskWhenTheWritePathThrows(): void {
		$storage = new class(['datadir' => $this->tmpDir]) extends Local {
			#[\Override]
			public function __construct(array $parameters) {
				parent::__construct($parameters);
				$this->unlinkOnTruncate = true;
			}

			#[\Override]
			public function unlink(string $path): bool {
				throw new \RuntimeException('unlink failed');
			}
		};

		$ambient = umask(0077);
		$thrown = false;
		try {
			$storage->fopen('target.txt', 'w');
		} catch (\RuntimeException) {
			$thrown = true;
		}
		$leaked = umask($ambient);

		$this->assertTrue($thrown, 'the exception has to propagate');
		$this->assertSame(0077, $leaked, 'umask has to be restored when the write path throws');
	}

	public function testFopenRecoveryLeavesEntriesOutsideTheDataDirectoryAlone(): void {
		if (!function_exists('exec')) {
			$this->markTestSkipped('exec() is required to change the path type out of process');
		}

		$dataDir = rtrim($this->tmpDir, '/');
		$stalePath = $this->tmpDir . 'folder';
		$file = $stalePath . '/a.txt';

		// opened only to get the "not a directory" entry into the realpath cache
		$this->instance->file_put_contents('folder', 'its a file');
		$handle = $this->instance->fopen('folder', 'r');
		$this->assertIsResource($handle);
		fclose($handle);

		if ((realpath_cache_get()[$stalePath]['is_dir'] ?? null) !== false) {
			$this->markTestSkipped('the realpath cache of this setup does not hold the directory flag');
		}

		realpath(__DIR__);
		realpath($dataDir);
		$this->assertArrayHasKey(__DIR__, realpath_cache_get());
		$this->assertArrayHasKey($dataDir, realpath_cache_get());

		exec(
			'rm -f ' . escapeshellarg($stalePath)
			. ' && mkdir -p ' . escapeshellarg($stalePath)
			. ' && printf abc > ' . escapeshellarg($file),
			$output,
			$status
		);
		$this->assertSame(0, $status, 'failed to replace the file with a directory');

		$handle = $this->instance->fopen('folder/a.txt', 'r');
		$this->assertIsResource($handle);
		fclose($handle);

		$cache = realpath_cache_get();
		$this->assertArrayHasKey(__DIR__, $cache, 'entries outside the data directory have to survive');
		$this->assertArrayHasKey($dataDir, $cache, 'the walk has to stop at the data directory');
	}

	public static function dataStaleRealpathCache(): array {
		return [
			// invalidating only the direct parent passes the first and fails the second
			'stale direct parent' => ['staleName' => 'folder', 'filePath' => 'folder/a.txt'],
			'stale grandparent' => ['staleName' => 'nickname', 'filePath' => 'nickname/folder/a.txt'],
		];
	}

	/**
	 * The type change has to happen out of process: PHP drops its own realpath cache
	 * entry when it is the one calling unlink() and mkdir(), so doing it here would
	 * leave nothing stale and the test would pass either way.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataStaleRealpathCache')]
	public function testFopenRecoversFromStaleRealpathCache(string $staleName, string $filePath): void {
		if (!function_exists('exec')) {
			$this->markTestSkipped('exec() is required to change the path type out of process');
		}

		$stalePath = $this->tmpDir . $staleName;
		$file = $this->tmpDir . $filePath;

		// opened only to get the "not a directory" entry into the realpath cache
		$this->instance->file_put_contents($staleName, 'its a file');
		$handle = $this->instance->fopen($staleName, 'r');
		$this->assertIsResource($handle);
		fclose($handle);

		if ((realpath_cache_get()[$stalePath]['is_dir'] ?? null) !== false) {
			$this->markTestSkipped('the realpath cache of this setup does not hold the directory flag');
		}

		exec(
			'rm -f ' . escapeshellarg($stalePath)
			. ' && mkdir -p ' . escapeshellarg(dirname($file))
			. ' && printf abc > ' . escapeshellarg($file),
			$output,
			$status
		);
		$this->assertSame(0, $status, 'failed to replace the file with a directory');

		$handle = $this->instance->fopen($filePath, 'r');
		$this->assertIsResource($handle, 'fopen() has to recover from the stale realpath cache entry');
		$this->assertSame('abc', stream_get_contents($handle));
		fclose($handle);
	}
	private function collectNames(\Traversable $content): array {
		$names = [];
		foreach ($content as $metadata) {
			$names[] = $metadata['name'];
		}
		sort($names);
		return $names;
	}

	public function testGetDirectoryContentSkipsSelfReferencingSymlink(): void {
		mkdir($this->tmpDir . 'dir');
		mkdir($this->tmpDir . 'dir/real');
		symlink($this->tmpDir . 'dir', $this->tmpDir . 'dir/self');

		$storage = new Local(['datadir' => $this->tmpDir]);

		$this->assertEquals(['real'], $this->collectNames($storage->getDirectoryContent('dir')));
	}

	public function testGetDirectoryContentSkipsParentSymlink(): void {
		mkdir($this->tmpDir . 'a');
		mkdir($this->tmpDir . 'a/b');
		mkdir($this->tmpDir . 'a/b/real');
		symlink($this->tmpDir . 'a', $this->tmpDir . 'a/b/up');

		$storage = new Local(['datadir' => $this->tmpDir]);

		$this->assertEquals(['real'], $this->collectNames($storage->getDirectoryContent('a/b')));
	}

	/**
	 * a -> b while b -> a: neither target is an ancestor of its own parent, so a
	 * parent-only check walks the pair forever.
	 */
	public function testGetDirectoryContentSkipsIndirectCycle(): void {
		mkdir($this->tmpDir . 'x');
		mkdir($this->tmpDir . 'y');
		symlink($this->tmpDir . 'y', $this->tmpDir . 'x/toy');
		symlink($this->tmpDir . 'x', $this->tmpDir . 'y/tox');

		$storage = new Local(['datadir' => $this->tmpDir]);

		// Entering x/toy lands in y; y's link back to x closes the cycle.
		$this->assertEquals([], $this->collectNames($storage->getDirectoryContent('x/toy')));
	}

	/**
	 * The loops we care about sit under ancestors that are themselves symlinks,
	 * so the logical path and the resolved path are on different branches.
	 * Comparing a logical path against a resolved one misses exactly this.
	 */
	public function testGetDirectoryContentSkipsLoopReachedThroughASymlinkedAncestor(): void {
		mkdir($this->tmpDir . 'real');
		mkdir($this->tmpDir . 'real/leaf');
		symlink($this->tmpDir . 'real', $this->tmpDir . 'alias');
		symlink($this->tmpDir . 'real', $this->tmpDir . 'real/leaf/back');

		$storage = new Local(['datadir' => $this->tmpDir]);

		// Reached as alias/leaf, whose resolved parent is real/leaf: 'back'
		// resolves to 'real', an ancestor, even though the logical path says alias/.
		$this->assertEquals([], $this->collectNames($storage->getDirectoryContent('alias/leaf')));
	}

	public function testGetDirectoryContentKeepsNonLoopingSymlinks(): void {
		mkdir($this->tmpDir . 'a');
		mkdir($this->tmpDir . 'other');
		mkdir($this->tmpDir . 'other/deep');
		file_put_contents($this->tmpDir . 'other/f.txt', 'x');
		symlink($this->tmpDir . 'other', $this->tmpDir . 'a/sibling');
		symlink($this->tmpDir . 'other/deep', $this->tmpDir . 'a/deeper');
		symlink($this->tmpDir . 'other/f.txt', $this->tmpDir . 'a/afile');

		$storage = new Local(['datadir' => $this->tmpDir]);

		$this->assertEquals(
			['afile', 'deeper', 'sibling'],
			$this->collectNames($storage->getDirectoryContent('a'))
		);
	}

	/**
	 * Enumeration is filtered, resolution is not: a file behind a directory
	 * symlink stays reachable. Guards the behaviour asserted by
	 * testDisallowSymlinksInsideDatadir.
	 */
	public function testLoopingSymlinkStillResolvesForFileAccess(): void {
		mkdir($this->tmpDir . 'dir');
		symlink($this->tmpDir . 'dir', $this->tmpDir . 'dir/self');

		$storage = new Local(['datadir' => $this->tmpDir]);
		$storage->file_put_contents('dir/self/foo', 'bar');

		$this->assertEquals('bar', $storage->file_get_contents('dir/foo'));
	}

}
