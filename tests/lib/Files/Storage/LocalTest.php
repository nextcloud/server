<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

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

		$this->tmpDir = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->instance = new \OC\Files\Storage\Local(['datadir' => $this->tmpDir]);
	}

	protected function tearDown(): void {
		\OC_Helper::rmdirr($this->tmpDir);
		parent::tearDown();
	}

	public function testStableEtag() {
		$this->instance->file_put_contents('test.txt', 'foobar');
		$etag1 = $this->instance->getETag('test.txt');
		$etag2 = $this->instance->getETag('test.txt');
		$this->assertEquals($etag1, $etag2);
	}

	public function testEtagChange() {
		$this->instance->file_put_contents('test.txt', 'foo');
		$this->instance->touch('test.txt', time() - 2);
		$etag1 = $this->instance->getETag('test.txt');
		$this->instance->file_put_contents('test.txt', 'bar');
		$etag2 = $this->instance->getETag('test.txt');
		$this->assertNotEquals($etag1, $etag2);
	}


	public function testInvalidArgumentsEmptyArray() {
		$this->expectException(\InvalidArgumentException::class);

		new \OC\Files\Storage\Local([]);
	}


	public function testInvalidArgumentsNoArray() {
		$this->expectException(\InvalidArgumentException::class);

		new \OC\Files\Storage\Local(null);
	}


	public function testDisallowSymlinksOutsideDatadir() {
		$this->expectException(\OCP\Files\ForbiddenException::class);

		$subDir1 = $this->tmpDir . 'sub1';
		$subDir2 = $this->tmpDir . 'sub2';
		$sym = $this->tmpDir . 'sub1/sym';
		mkdir($subDir1);
		mkdir($subDir2);

		symlink($subDir2, $sym);

		$storage = new \OC\Files\Storage\Local(['datadir' => $subDir1]);

		$storage->file_put_contents('sym/foo', 'bar');
	}

	public function testDisallowSymlinksInsideDatadir() {
		$subDir1 = $this->tmpDir . 'sub1';
		$subDir2 = $this->tmpDir . 'sub1/sub2';
		$sym = $this->tmpDir . 'sub1/sym';
		mkdir($subDir1);
		mkdir($subDir2);

		symlink($subDir2, $sym);

		$storage = new \OC\Files\Storage\Local(['datadir' => $subDir1]);

		$storage->file_put_contents('sym/foo', 'bar');
		$this->addToAssertionCount(1);
	}

	public function testWriteUmaskFilePutContents() {
		$oldMask = umask(0333);
		$this->instance->file_put_contents('test.txt', 'sad');
		umask($oldMask);
		$this->assertTrue($this->instance->isUpdatable('test.txt'));
	}

	public function testWriteUmaskMkdir() {
		$oldMask = umask(0333);
		$this->instance->mkdir('test.txt');
		umask($oldMask);
		$this->assertTrue($this->instance->isUpdatable('test.txt'));
	}

	public function testWriteUmaskFopen() {
		$oldMask = umask(0333);
		$handle = $this->instance->fopen('test.txt', 'w');
		fwrite($handle, 'foo');
		fclose($handle);
		umask($oldMask);
		$this->assertTrue($this->instance->isUpdatable('test.txt'));
	}

	public function testWriteUmaskCopy() {
		$this->instance->file_put_contents('source.txt', 'sad');
		$oldMask = umask(0333);
		$this->instance->copy('source.txt', 'test.txt');
		umask($oldMask);
		$this->assertTrue($this->instance->isUpdatable('test.txt'));
	}

	public function testUnavailableExternal() {
		$this->expectException(\OCP\Files\StorageNotAvailableException::class);
		$this->instance = new \OC\Files\Storage\Local(['datadir' => $this->tmpDir . '/unexist', 'isExternal' => true]);
	}

	public function testUnavailableNonExternal() {
		$this->instance = new \OC\Files\Storage\Local(['datadir' => $this->tmpDir . '/unexist']);
		// no exception thrown
		$this->assertNotNull($this->instance);
	}
}
