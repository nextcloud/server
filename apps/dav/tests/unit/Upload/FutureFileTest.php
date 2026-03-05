<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Upload\FutureFile;

class FutureFileTest extends \Test\TestCase {
	public function testGetContentType(): void {
		$f = $this->mockFutureFile();
		$this->assertEquals('application/octet-stream', $f->getContentType());
	}

	public function testGetETag(): void {
		$f = $this->mockFutureFile();
		$this->assertEquals('1234567890', $f->getETag());
	}

	public function testGetName(): void {
		$f = $this->mockFutureFile();
		$this->assertEquals('foo.txt', $f->getName());
	}

	public function testGetLastModified(): void {
		$f = $this->mockFutureFile();
		$this->assertEquals(12121212, $f->getLastModified());
	}

	public function testGetSize(): void {
		$f = $this->mockFutureFile();
		$this->assertEquals(0, $f->getSize());
	}

	public function testGet(): void {
		$f = $this->mockFutureFile();
		$stream = $f->get();
		$this->assertTrue(is_resource($stream));
	}

	public function testDelete(): void {
		$d = $this->getMockBuilder(Directory::class)
			->disableOriginalConstructor()
			->onlyMethods(['delete'])
			->getMock();

		$d->expects($this->once())
			->method('delete');

		$f = new FutureFile($d, 'foo.txt');
		$f->delete();
	}


	public function testPut(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$f = $this->mockFutureFile();
		$f->put('');
	}


	public function testSetName(): void {
		$this->expectException(\Sabre\DAV\Exception\Forbidden::class);

		$f = $this->mockFutureFile();
		$f->setName('');
	}

	private function mockFutureFile(): FutureFile {
		$d = $this->getMockBuilder(Directory::class)
			->disableOriginalConstructor()
			->onlyMethods(['getETag', 'getLastModified', 'getChildren'])
			->getMock();

		$d->expects($this->any())
			->method('getETag')
			->willReturn('1234567890');

		$d->expects($this->any())
			->method('getLastModified')
			->willReturn(12121212);

		$d->expects($this->any())
			->method('getChildren')
			->willReturn([]);

		return new FutureFile($d, 'foo.txt');
	}
}
