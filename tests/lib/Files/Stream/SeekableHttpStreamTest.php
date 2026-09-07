<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Stream;

use OC\Files\Stream\SeekableHttpStream;
use Test\TestCase;

class SeekableHttpStreamTest extends TestCase {
	public function testReadDoesNotReadBeyondRemoteResourceSize(): void {
		$stream = new SeekableHttpStream();

		$current = fopen('php://temp', 'r+');
		fwrite($current, '0123456789');
		fseek($current, 8);

		$this->setPrivateProperty($stream, 'current', $current);
		$this->setPrivateProperty($stream, 'offset', 8);
		$this->setPrivateProperty($stream, 'totalSize', 10);

		$this->assertSame('89', $stream->stream_read(100));
		$this->assertSame(10, $stream->stream_tell());
		$this->assertTrue($stream->stream_eof());

		fclose($current);
	}

	public function testReadAtLogicalEndReturnsEmptyString(): void {
		$stream = new SeekableHttpStream();

		$this->setPrivateProperty($stream, 'offset', 10);
		$this->setPrivateProperty($stream, 'totalSize', 10);

		$this->assertSame('', $stream->stream_read(100));
		$this->assertTrue($stream->stream_eof());
	}

	public function testEofAtLogicalEndDoesNotReconnect(): void {
		$stream = new SeekableHttpStream();

		$this->setPrivateProperty($stream, 'offset', 10);
		$this->setPrivateProperty($stream, 'totalSize', 10);
		$this->setPrivateProperty($stream, 'needReconnect', true);

		/*
		 * If stream_eof() attempted to reconnect before checking the logical
		 * end, this would try to invoke the unset callback.
		 */
		$this->assertTrue($stream->stream_eof());
	}

	public function testParsesContentRange(): void {
		$stream = new SeekableHttpStream();

		$result = $this->invokePrivate(
			$stream,
			'parseContentRange',
			[[
				'HTTP/1.1 206 Partial Content',
				'Content-Range: bytes 10-19/100',
			]]
		);

		$this->assertSame([
			'begin' => 10,
			'end' => 19,
			'totalSize' => 100,
		], $result);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('invalidContentRangeProvider')]
	public function testRejectsInvalidContentRange(string $contentRange): void {
		$stream = new SeekableHttpStream();

		$result = $this->invokePrivate(
			$stream,
			'parseContentRange',
			[[$contentRange]]
		);

		$this->assertNull($result);
	}

	public static function invalidContentRangeProvider(): array {
		return [
			'missing header' => ['HTTP/1.1 200 OK'],
			'missing total size' => ['Content-Range: bytes 0-9/*'],
			'descending range' => ['Content-Range: bytes 9-0/10'],
			'total smaller than end' => ['Content-Range: bytes 0-9/9'],
			'not bytes' => ['Content-Range: items 0-9/10'],
			'malformed range' => ['Content-Range: bytes 0-9'],
		];
	}

	/**
	 * @param mixed $value
	 */
	private function setPrivateProperty(
		SeekableHttpStream $stream,
		string $property,
		mixed $value,
	): void {
		$reflection = new \ReflectionClass($stream);
		$reflection->getProperty($property)->setValue($stream, $value);
	}
}
