<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\File\SimpleFS;

use OC\Files\SimpleFS\SimpleFolder;
use OC\Files\Storage\Temporary;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * @group DB
 */
class SimpleFolderTest extends \Test\TestCase {
	use MountProviderTrait;
	use UserTrait;

	/** @var Folder */
	private $folder;

	/** @var Folder */
	private $parentFolder;

	/** @var SimpleFolder */
	private $simpleFolder;

	private $storage;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = new Temporary([]);
		$this->createUser('simple', 'simple');
		$this->registerMount('simple', $this->storage, '/simple/files');
		$this->loginAsUser('simple');

		$this->parentFolder = \OC::$server->getUserFolder('simple');

		$this->folder = $this->parentFolder->newFolder('test');
		$this->simpleFolder = new SimpleFolder($this->folder);
	}

	public function testGetName(): void {
		$this->assertEquals('test', $this->simpleFolder->getName());
	}

	public function testDelete(): void {
		$this->assertTrue($this->parentFolder->nodeExists('test'));
		$this->simpleFolder->delete();
		$this->assertFalse($this->parentFolder->nodeExists('test'));
	}

	public function testFileExists(): void {
		$this->folder->newFile('exists');

		$this->assertFalse($this->simpleFolder->fileExists('not-exists'));
		$this->assertTrue($this->simpleFolder->fileExists('exists'));
	}

	public function testGetFile(): void {
		$this->folder->newFile('exists');

		$result = $this->simpleFolder->getFile('exists');
		$this->assertInstanceOf(ISimpleFile::class, $result);

		$this->expectException(NotFoundException::class);
		$this->simpleFolder->getFile('not-exists');
	}

	public function testNewFile(): void {
		$result = $this->simpleFolder->newFile('file');
		$this->assertInstanceOf(ISimpleFile::class, $result);
		$this->assertFalse($this->folder->nodeExists('file'));
		$result->putContent('bar');

		$this->assertTrue($this->folder->nodeExists('file'));
		$this->assertEquals('bar', $result->getContent());
	}

	public function testGetDirectoryListing(): void {
		$this->folder->newFile('file1');
		$this->folder->newFile('file2');

		$result = $this->simpleFolder->getDirectoryListing();
		$this->assertCount(2, $result);
		$this->assertInstanceOf(ISimpleFile::class, $result[0]);
		$this->assertInstanceOf(ISimpleFile::class, $result[1]);
	}

	public function testGetFolder(): void {
		$this->folder->newFolder('exists');

		$result = $this->simpleFolder->getFolder('exists');
		$this->assertInstanceOf(ISimpleFolder::class, $result);

		$this->expectException(NotFoundException::class);
		$this->simpleFolder->getFolder('not-exists');
	}

	public function testNewFolder(): void {
		$result = $this->simpleFolder->newFolder('folder');
		$this->assertInstanceOf(ISimpleFolder::class, $result);
		$result->newFile('file');

		$this->assertTrue($this->folder->nodeExists('folder'));
	}
}
