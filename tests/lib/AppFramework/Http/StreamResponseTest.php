<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\IOutput;
use OCP\AppFramework\Http\StreamResponse;

class StreamResponseTest extends \Test\TestCase {
	/** @var IOutput */
	private $output;

	protected function setUp(): void {
		parent::setUp();
		$this->output = $this->getMockBuilder('OCP\\AppFramework\\Http\\IOutput')
			->disableOriginalConstructor()
			->getMock();
	}

	public function testOutputNotModified(): void {
		$path = __FILE__;
		$this->output->expects($this->once())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_NOT_MODIFIED);
		$this->output->expects($this->never())
			->method('setReadfile');
		$response = new StreamResponse($path);

		$response->callback($this->output);
	}

	public function testOutputOk(): void {
		$path = __FILE__;
		$this->output->expects($this->once())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_OK);
		$this->output->expects($this->once())
			->method('setReadfile')
			->with($this->equalTo($path))
			->willReturn(true);
		$response = new StreamResponse($path);

		$response->callback($this->output);
	}

	public function testOutputNotFound(): void {
		$path = __FILE__ . 'test';
		$this->output->expects($this->once())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_OK);
		$this->output->expects($this->never())
			->method('setReadfile');
		$this->output->expects($this->once())
			->method('setHttpResponseCode')
			->with($this->equalTo(Http::STATUS_NOT_FOUND));
		$response = new StreamResponse($path);

		$response->callback($this->output);
	}

	public function testOutputReadFileError(): void {
		$path = __FILE__;
		$this->output->expects($this->once())
			->method('getHttpResponseCode')
			->willReturn(Http::STATUS_OK);
		$this->output->expects($this->once())
			->method('setReadfile')
			->willReturn(false);
		$this->output->expects($this->once())
			->method('setHttpResponseCode')
			->with($this->equalTo(Http::STATUS_BAD_REQUEST));
		$response = new StreamResponse($path);

		$response->callback($this->output);
	}
}
