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
 * @group DB
 *
 * @package Test\Files\Storage
 */
class LocalTest extends Storage {
	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	protected function setUp(): void {
		parent::setUp();

		$this->tmpDir = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->instance = new Local(['datadir' => $this->tmpDir]);
	}

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
}
