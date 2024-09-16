<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\File\SimpleFS;

use OC\Files\SimpleFS\SimpleFile;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;

class SimpleFileTest extends \Test\TestCase {
	/** @var File|\PHPUnit\Framework\MockObject\MockObject */
	private $file;

	/** @var SimpleFile */
	private $simpleFile;

	protected function setUp(): void {
		parent::setUp();

		$this->file = $this->createMock(File::class);
		$this->simpleFile = new SimpleFile($this->file);
	}

	public function testGetName(): void {
		$this->file->expects($this->once())
			->method('getName')
			->willReturn('myname');

		$this->assertEquals('myname', $this->simpleFile->getName());
	}

	public function testGetSize(): void {
		$this->file->expects($this->once())
			->method('getSize')
			->willReturn(42);

		$this->assertEquals(42, $this->simpleFile->getSize());
	}

	public function testGetETag(): void {
		$this->file->expects($this->once())
			->method('getETag')
			->willReturn('etag');

		$this->assertEquals('etag', $this->simpleFile->getETag());
	}

	public function testGetMTime(): void {
		$this->file->expects($this->once())
			->method('getMTime')
			->willReturn(101);

		$this->assertEquals(101, $this->simpleFile->getMTime());
	}

	public function testGetContent(): void {
		$this->file->expects($this->once())
			->method('getContent')
			->willReturn('foo');

		$this->assertEquals('foo', $this->simpleFile->getContent());
	}

	public function testPutContent(): void {
		$this->file->expects($this->once())
			->method('putContent')
			->with($this->equalTo('bar'));

		$this->simpleFile->putContent('bar');
	}

	public function testDelete(): void {
		$this->file->expects($this->once())
			->method('delete');

		$this->simpleFile->delete();
	}

	public function testGetMimeType(): void {
		$this->file->expects($this->once())
			->method('getMimeType')
			->willReturn('app/awesome');

		$this->assertEquals('app/awesome', $this->simpleFile->getMimeType());
	}

	public function testGetContentInvalidAppData(): void {
		$this->file->method('getContent')
			->willReturn(false);
		$this->file->method('stat')->willReturn(false);

		$parent = $this->createMock(Folder::class);
		$parent->method('stat')->willReturn(false);

		$root = $this->createMock(Folder::class);
		$root->method('stat')->willReturn([]);

		$this->file->method('getParent')->willReturn($parent);
		$parent->method('getParent')->willReturn($root);

		$this->expectException(NotFoundException::class);

		$this->simpleFile->getContent();
	}

	public function testRead(): void {
		$this->file->expects($this->once())
			->method('fopen')
			->with('r');

		$this->simpleFile->read();
	}

	public function testWrite(): void {
		$this->file->expects($this->once())
			->method('fopen')
			->with('w');

		$this->simpleFile->write();
	}
}
