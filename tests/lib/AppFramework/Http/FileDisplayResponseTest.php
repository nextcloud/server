<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\IOutput;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;
use PHPUnit\Framework\MockObject\MockObject;

class FileDisplayResponseTest extends \Test\TestCase {
	private File&MockObject $file;
	private FileDisplayResponse $response;

	protected function setUp(): void {
		parent::setUp();

		$this->file = $this->createMock(File::class);
		$this->file->expects($this->once())
			->method('getETag')
			->willReturn('myETag');
		$this->file->expects($this->once())
			->method('getName')
			->willReturn('myFileName');
		$this->file->expects($this->once())
			->method('getMTime')
			->willReturn(1464825600);

		$this->response = new FileDisplayResponse($this->file);
	}

	public function testHeader(): void {
		$headers = $this->response->getHeaders();
		$this->assertArrayHasKey('Content-Disposition', $headers);
		$this->assertSame('inline; filename="myFileName"', $headers['Content-Disposition']);
	}

	public function testETag(): void {
		$this->assertSame('myETag', $this->response->getETag());
	}

	public function testLastModified(): void {
		$lastModified = $this->response->getLastModified();
		$this->assertNotNull($lastModified);
		$this->assertSame(1464825600, $lastModified->getTimestamp());
	}

	public function test304(): void {
		$output = $this->getMockBuilder(IOutput::class)
			->disableOriginalConstructor()
			->getMock();

		$output->expects($this->any())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_NOT_MODIFIED);
		$output->expects($this->never())
			->method('setOutput');
		$this->file->expects($this->never())
			->method('getContent');

		$this->response->callback($output);
	}


	public function testNon304(): void {
		$resource = fopen('php://memory', 'w+b');
		fwrite($resource, 'my data');
		rewind($resource);

		$this->file->expects($this->once())
			->method('fopen')
			->willReturn($resource);
		$this->file->expects($this->any())
			->method('getSize')
			->willReturn(7);

		$output = $this->getMockBuilder(IOutput::class)
			->disableOriginalConstructor()
			->getMock();
		$output->expects($this->once())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_OK);
		$output->expects($this->once())
			->method('setReadFile')
			->with($this->equalTo($resource));
		$output->expects($this->once())
			->method('setHeader')
			->with($this->equalTo('Content-Length: 7'));

		$this->response->callback($output);
	}

	public function testFileNotFound(): void {
		$this->file->expects($this->once())
			->method('fopen')
			->willReturn(false);

		$output = $this->getMockBuilder(IOutput::class)
			->disableOriginalConstructor()
			->getMock();
		$output->expects($this->once())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_OK);
		$output->expects($this->once())
			->method('setHttpResponseCode')
			->with($this->equalTo(Http::STATUS_NOT_FOUND));
		$output->expects($this->once())
			->method('setOutput')
			->with($this->equalTo(''));

		$this->response->callback($output);
	}

	public function testSimpleFileNotFound(): void {
		$file = $this->createMock(ISimpleFile::class);
		$file->expects($this->once())
			->method('getETag')
			->willReturn('myETag');
		$file->expects($this->once())
			->method('getName')
			->willReturn('myFileName');
		$file->expects($this->once())
			->method('getMTime')
			->willReturn(1464825600);
		$file->expects($this->once())
			->method('read')
			->willReturn(false);

		$response = new FileDisplayResponse($file);

		$output = $this->getMockBuilder(IOutput::class)
			->disableOriginalConstructor()
			->getMock();
		$output->expects($this->once())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_OK);
		$output->expects($this->once())
			->method('setHttpResponseCode')
			->with($this->equalTo(Http::STATUS_NOT_FOUND));
		$output->expects($this->once())
			->method('setOutput')
			->with($this->equalTo(''));

		$response->callback($output);
	}

	public function testSimpleFile(): void {
		$file = $this->createMock(ISimpleFile::class);
		$file->expects($this->once())
			->method('getETag')
			->willReturn('myETag');
		$file->expects($this->once())
			->method('getName')
			->willReturn('myFileName');
		$file->expects($this->once())
			->method('getMTime')
			->willReturn(1464825600);

		$resource = fopen('php://memory', 'w+b');
		fwrite($resource, 'my data');
		rewind($resource);

		$file->expects($this->once())
			->method('read')
			->willReturn($resource);
		$file->expects($this->any())
			->method('getSize')
			->willReturn(7);

		$response = new FileDisplayResponse($file);

		$output = $this->getMockBuilder(IOutput::class)
			->disableOriginalConstructor()
			->getMock();
		$output->expects($this->once())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_OK);
		$output->expects($this->once())
			->method('setReadFile')
			->with($this->equalTo($resource));
		$output->expects($this->once())
			->method('setHeader')
			->with($this->equalTo('Content-Length: 7'));

		$response->callback($output);
	}
}
