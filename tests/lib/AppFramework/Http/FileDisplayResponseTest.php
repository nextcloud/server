<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\Files\File;

class FileDisplayResponseTest extends \Test\TestCase {
	/** @var File|\PHPUnit\Framework\MockObject\MockObject */
	private $file;

	/** @var FileDisplayResponse */
	private $response;

	protected function setUp(): void {
		$this->file = $this->getMockBuilder('OCP\Files\File')
			->getMock();

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
		$output = $this->getMockBuilder('OCP\AppFramework\Http\IOutput')
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
		$output = $this->getMockBuilder('OCP\AppFramework\Http\IOutput')
			->disableOriginalConstructor()
			->getMock();

		$output->expects($this->any())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_OK);
		$output->expects($this->once())
			->method('setOutput')
			->with($this->equalTo('my data'));
		$output->expects($this->once())
			->method('setHeader')
			->with($this->equalTo('Content-Length: 42'));
		$this->file->expects($this->once())
			->method('getContent')
			->willReturn('my data');
		$this->file->expects($this->any())
			->method('getSize')
			->willReturn(42);

		$this->response->callback($output);
	}
}
