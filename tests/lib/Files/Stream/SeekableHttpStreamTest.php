<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Stream;

use OC\Files\Stream\SeekableHttpStream;
use OCP\Http\Client\IResponse;
use Test\TestCase;

class SeekableHttpStreamTest extends TestCase {
	private const DATA = 'Lorem ipsum dolor sit amet';

	/**
	 * Serve a range of DATA as streamed response, like IClient does with the stream option
	 */
	private function respond(string $range): IResponse {
		$start = (int)explode('-', $range)[0];
		$body = fopen('php://memory', 'w+');
		fwrite($body, substr(self::DATA, $start));
		rewind($body);

		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn($body);
		$response->method('getHeader')->willReturnMap([
			['Content-Range', 'bytes ' . $start . '-' . (strlen(self::DATA) - 1) . '/' . strlen(self::DATA)],
		]);
		return $response;
	}

	public function testReadAndSeekWithResponses(): void {
		$ranges = [];
		$handle = SeekableHttpStream::open(function (string $range) use (&$ranges) {
			$ranges[] = $range;
			return $this->respond($range);
		});

		$this->assertIsResource($handle);
		$this->assertSame('Lorem', fread($handle, 5));

		fseek($handle, 0, SEEK_END);
		$this->assertSame(strlen(self::DATA), ftell($handle));

		fseek($handle, 6);
		$this->assertSame('ipsum', fread($handle, 5));
		$this->assertSame(['0-', '6-'], $ranges);
		fclose($handle);
	}

	public function testFailsWithoutContentRange(): void {
		$response = $this->createMock(IResponse::class);
		$response->method('getBody')->willReturn(fopen('php://memory', 'r'));
		$response->method('getHeader')->willReturn('');

		$this->assertFalse(@SeekableHttpStream::open(fn () => $response));
	}

	public function testFailsWhenCallbackFails(): void {
		$this->assertFalse(@SeekableHttpStream::open(fn () => false));
	}
}
