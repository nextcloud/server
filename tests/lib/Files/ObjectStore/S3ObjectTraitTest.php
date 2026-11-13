<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use Icewind\Streams\Wrapper;
use OC\Files\ObjectStore\S3;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class S3ObjectTraitTest extends TestCase {

	/** @return array{S3, MockHandler} */
	private function createObjectStore(): array {
		$handler = new MockHandler([new Result()]);
		$client = new S3Client([
			'version' => '2006-03-01',
			'region' => 'us-east-1',
			'credentials' => false,
			'handler' => $handler,
			'retries' => 0,
			'request_checksum_calculation' => 'when_required',
		]);
		$store = $this->getMockBuilder(S3::class)
			->setConstructorArgs([['bucket' => 'test-bucket']])
			->onlyMethods(['getConnection'])
			->getMock();
		$store->method('getConnection')->willReturn($client);
		return [$store, $handler];
	}

	/** @return resource */
	private function createInput(int $actualSize, bool $unknownSize = false, bool $signalEof = true) {
		$source = fopen('php://temp', 'w+');
		fwrite($source, str_repeat('a', $actualSize));
		rewind($source);
		return S3BufferedUploadInput::wrap($source, $unknownSize, $signalEof);
	}

	public static function incompleteUploadSizes(): array {
		return [
			'aligned cutoff' => [532001, 393216],
			'unaligned cutoff' => [532001, 393217],
			'large declared size ending below multipart threshold' => [104857601, 8192],
			'empty interrupted body' => [532001, 0],
			'empty read without EOF' => [532001, 393216, false],
			'body larger than declared size' => [8192, 16384],
			'nonempty body declared empty' => [0, 8192],
		];
	}

	#[DataProvider('incompleteUploadSizes')]
	public function testIncompleteBufferedUploadDoesNotReachS3(int $expectedSize, int $actualSize, bool $signalEof = true): void {
		[$store, $handler] = $this->createObjectStore();
		$input = $this->createInput($actualSize, false, $signalEof);

		try {
			$this->expectException(\RuntimeException::class);
			$this->expectExceptionMessage("Incomplete upload, expected $expectedSize bytes, buffered $actualSize");
			$store->writeObjectWithMetaData('urn:oid:test', $input, ['size' => $expectedSize]);
		} finally {
			// No request may publish a new object or replace an existing object.
			$this->assertNull($handler->getLastCommand()?->getName());
			$this->assertCount(1, $handler);
			if (is_resource($input)) {
				fclose($input);
			}
		}
	}

	public static function completeUploadSizes(): array {
		return [
			'known length' => [50000, 50000, false],
			'empty known length' => [0, 0, false],
			'unknown length' => [null, 50000, true],
			'empty unknown length' => [null, 0, true],
		];
	}

	#[DataProvider('completeUploadSizes')]
	public function testCompleteBufferedUploadReachesS3(?int $expectedSize, int $actualSize, bool $unknownSize): void {
		[$store, $handler] = $this->createObjectStore();
		$input = $this->createInput($actualSize, $unknownSize);
		$metadata = $expectedSize === null ? [] : ['size' => $expectedSize];

		try {
			$store->writeObjectWithMetaData('urn:oid:test', $input, $metadata);
			$command = $handler->getLastCommand();
			$this->assertNotNull($command);
			$this->assertSame('PutObject', $command->getName());
			$this->assertSame('urn:oid:test', $command['Key']);
			$this->assertSame(str_repeat('a', $actualSize), (string)$command['Body']);
			if ($actualSize > 0) {
				$this->assertSame($actualSize, $command['ContentLength']);
			}
			$this->assertCount(0, $handler);
		} finally {
			if (is_resource($input)) {
				fclose($input);
			}
		}
	}
}

/**
 * Models a request body that cannot be rewound. The unknown-size variant also
 * models wrappers reporting zero through fstat despite containing data.
 */
class S3BufferedUploadInput extends Wrapper {
	private bool $unknownSize = false;
	private bool $signalEof = true;

	public static function wrap($source, bool $unknownSize = false, bool $signalEof = true) {
		return self::wrapSource($source, ['unknownSize' => $unknownSize, 'signalEof' => $signalEof]);
	}

	#[\Override]
	public function dir_opendir($path, $options) {
		return false;
	}

	#[\Override]
	public function stream_open($path, $mode, $options, &$opened_path) {
		$options = $this->loadContext();
		$this->unknownSize = $options['unknownSize'];
		$this->signalEof = $options['signalEof'];
		return true;
	}

	#[\Override]
	public function stream_eof() {
		return $this->signalEof && parent::stream_eof();
	}

	#[\Override]
	public function stream_seek($offset, $whence = SEEK_SET) {
		return false;
	}

	#[\Override]
	public function stream_stat() {
		return $this->unknownSize ? ['size' => 0] : parent::stream_stat();
	}
}
