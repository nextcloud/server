<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\SFTP;
use phpseclib3\Net\SFTP as SFTPClient;
use PHPUnit\Framework\Attributes\DataProvider;

class SFTPCopyTest extends \Test\TestCase {
	private function storage(SFTPClient $client): SFTP {
		$storage = $this->getMockBuilder(SFTP::class)
			->disableOriginalConstructor()
			->onlyMethods(['getConnection', 'is_dir'])
			->getMock();
		$storage->method('getConnection')->willReturn($client);
		$storage->method('is_dir')->willReturn(false);
		(new \ReflectionProperty(SFTP::class, 'root'))->setValue($storage, '/storage/');
		return $storage;
	}

	#[DataProvider('chunkSizes')]
	public function testCopyChunkBoundaries(int $size, array $offsets): void {
		$source = str_repeat('a', $size - 1) . 'z';
		$target = str_repeat('b', $size + 10);
		$reads = [];
		$writes = [];
		$client = $this->createMock(SFTPClient::class);
		$client->method('filesize')->with('/storage//source')->willReturn($size);
		$client->method('get')->willReturnCallback(
			function ($path, $local, $offset, $length) use (&$source, &$reads) {
				$reads[] = [$path, $local, $offset, $length];
				return substr($source, $offset, $length);
			}
		);
		$client->method('put')->willReturnCallback(
			function ($path, $chunk, $mode, $offset) use (&$target, &$writes) {
				$writes[] = [$path, $mode, $offset, strlen($chunk)];
				$target = $offset === -1 ? $chunk : substr_replace($target, $chunk, $offset, strlen($chunk));
				return true;
			}
		);

		$result = $this->storage($client)->copy('source', 'target');
		$sourceHash = hash('sha256', $source);
		$targetSize = strlen($target);
		$targetHash = hash('sha256', $target);
		$source = $target = '';
		$this->assertTrue($result);
		$this->assertSame($size, $targetSize);
		$this->assertSame($sourceHash, $targetHash);
		$this->assertSame(array_map(
			fn (int $offset) => ['/storage//source', false, $offset, SFTP::COPY_CHUNK_SIZE],
			$offsets
		), $reads);
		$this->assertSame(array_map(
			fn (int $offset) => ['/storage//target', SFTPClient::SOURCE_STRING, $offset === 0 ? -1 : $offset, min(SFTP::COPY_CHUNK_SIZE, $size - $offset)],
			$offsets
		), $writes);
	}

	public static function chunkSizes(): array {
		$chunk = SFTP::COPY_CHUNK_SIZE;
		return [
			'just below a chunk' => [$chunk - 1, [0]],
			'exactly one chunk' => [$chunk, [0]],
			'just above a chunk' => [$chunk + 1, [0, $chunk]],
			'more than two chunks' => [2 * $chunk + 1, [0, $chunk, 2 * $chunk]],
		];
	}

	public function testCopyFailsWhenSourceSizeIsUnavailable(): void {
		$client = $this->createMock(SFTPClient::class);
		$client->expects($this->once())->method('filesize')->with('/storage//source')->willReturn(false);
		$client->expects($this->never())->method('get');
		$client->expects($this->never())->method('put');

		$this->assertFalse($this->storage($client)->copy('source', 'target'));
	}

	#[DataProvider('transferFailures')]
	public function testCopyStopsOnTransferFailure(string $operation, int $failureOffset, array $expectedReads, array $expectedWrites): void {
		$reads = [];
		$writes = [];
		$client = $this->createMock(SFTPClient::class);
		$client->method('filesize')->willReturn(2 * SFTP::COPY_CHUNK_SIZE + 1);
		$client->method('get')->willReturnCallback(
			function ($path, $local, $offset, $length) use ($operation, $failureOffset, &$reads) {
				$reads[] = $offset;
				return $operation === 'read' && $offset === $failureOffset
					? false
					: str_repeat('a', min($length, 2 * SFTP::COPY_CHUNK_SIZE + 1 - $offset));
			}
		);
		$client->method('put')->willReturnCallback(
			function ($path, $data, $mode, $offset) use ($operation, $failureOffset, &$writes) {
				$writes[] = $offset;
				return $operation !== 'write' || $offset !== $failureOffset;
			}
		);

		$this->assertFalse($this->storage($client)->copy('source', 'target'));
		$this->assertSame($expectedReads, $reads);
		$this->assertSame($expectedWrites, $writes);
	}

	public static function transferFailures(): array {
		$chunk = SFTP::COPY_CHUNK_SIZE;
		return [
			'first read' => ['read', 0, [0], []],
			'later read' => ['read', $chunk, [0, $chunk], [-1]],
			'first write' => ['write', -1, [0], [-1]],
			'later write' => ['write', $chunk, [0, $chunk], [-1, $chunk]],
		];
	}

	public function testCopyEmptyFileReportsWriteFailure(): void {
		$client = $this->createMock(SFTPClient::class);
		$client->method('filesize')->willReturn(0);
		$client->expects($this->never())->method('get');
		$client->expects($this->once())->method('put')->with('/storage//target', '')->willReturn(false);

		$this->assertFalse($this->storage($client)->copy('source', 'target'));
	}
}
