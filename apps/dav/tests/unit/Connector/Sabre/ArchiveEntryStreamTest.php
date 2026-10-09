<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use Icewind\Streams\CallbackWrapper;
use OCA\DAV\Connector\Sabre\ArchiveEntryStream;
use ownCloud\TarStreamer\TarStreamer;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;
use ZipStreamer\ZipStreamer;

class ArchiveEntryStreamTest extends TestCase {
	public static function dataRead(): array {
		$content = str_repeat('a', 10000);
		return [
			'complete read' => [
				'content' => $content,
				'size' => 10000,
				'fixedSize' => false,
				'throwOnRead' => null,
				'expected' => $content,
				'expectedBytesRead' => 10000,
				'expectedFailed' => false,
				'expectedTruncated' => false,
			],
			'source longer than size is read completely' => [
				'content' => $content,
				'size' => 4000,
				'fixedSize' => false,
				'throwOnRead' => null,
				'expected' => $content,
				'expectedBytesRead' => 10000,
				'expectedFailed' => false,
				'expectedTruncated' => false,
			],
			'exception ends the stream' => [
				'content' => $content,
				'size' => 10000,
				'fixedSize' => false,
				'throwOnRead' => 2,
				'expected' => str_repeat('a', 8192),
				'expectedBytesRead' => 8192,
				'expectedFailed' => true,
				'expectedTruncated' => false,
			],
			'fixed size, complete read' => [
				'content' => $content,
				'size' => 10000,
				'fixedSize' => true,
				'throwOnRead' => null,
				'expected' => $content,
				'expectedBytesRead' => 10000,
				'expectedFailed' => false,
				'expectedTruncated' => false,
			],
			'fixed size, short source, short stream is padded' => [
				'content' => 'abc',
				'size' => 10,
				'fixedSize' => true,
				'throwOnRead' => null,
				'expected' => "abc\0\0\0\0\0\0\0",
				'expectedBytesRead' => 3,
				'expectedFailed' => false,
				'expectedTruncated' => false,
			],
			'fixed size, with exception, short stream is padded' => [
				'content' => $content,
				'size' => 10000,
				'fixedSize' => true,
				'throwOnRead' => 2,
				'expected' => str_repeat('a', 8192) . str_repeat("\0", 1808),
				'expectedBytesRead' => 8192,
				'expectedFailed' => true,
				'expectedTruncated' => false,
			],
			'fixed size, longer source is truncated' => [
				'content' => $content,
				'size' => 4000,
				'fixedSize' => true,
				'throwOnRead' => null,
				'expected' => str_repeat('a', 4000),
				'expectedBytesRead' => 4000,
				'expectedFailed' => false,
				'expectedTruncated' => true,
			],
		];
	}

	/**
	 * @param ?int $throwOnRead number of the source read that throws, null to never throw
	 */
	#[DataProvider(methodName: 'dataRead')]
	public function testRead(
		string $content,
		int $size,
		bool $fixedSize,
		?int $throwOnRead,
		string $expected,
		int $expectedBytesRead,
		bool $expectedFailed,
		bool $expectedTruncated,
	): void {
		$source = $this->createSource($content, $throwOnRead);
		$entry = null;
		$stream = ArchiveEntryStream::wrap($source, $size, $fixedSize, function (ArchiveEntryStream $closed) use (&$entry): void {
			$entry = $closed;
		});

		/** Same read loop as in {@see ZipStreamer::streamFileData()} */
		$actual = '';
		while (!feof($stream) && ($data = fread($stream, 8192)) !== false) {
			$actual .= $data;
		}
		fclose($stream);

		$this->assertSame($expected, $actual);
		$this->assertInstanceOf(ArchiveEntryStream::class, $entry);
		$this->assertSame($expectedBytesRead, $entry->getBytesRead());
		$this->assertSame($expectedFailed, $entry->hasFailed());
		$this->assertSame($expectedTruncated, $entry->isTruncated());
		if ($throwOnRead !== null) {
			$this->assertInstanceOf(\RuntimeException::class, $entry->getThrowable());
		} else {
			$this->assertNull($entry->getThrowable());
		}
	}

	public static function dataArchiveStaysValid(): array {
		return [
			'zip, failing read' => ['tar' => false, 'brokenContent' => str_repeat('b', 10000), 'brokenSize' => 10000, 'throwOnRead' => 2, 'expected' => str_repeat('b', 8192)],
			'tar, failing read' => ['tar' => true, 'brokenContent' => str_repeat('b', 10000), 'brokenSize' => 10000, 'throwOnRead' => 2, 'expected' => str_repeat('b', 8192) . str_repeat("\0", 1808)],
			'tar, short read' => ['tar' => true, 'brokenContent' => 'bbb', 'brokenSize' => 1000, 'throwOnRead' => null, 'expected' => 'bbb' . str_repeat("\0", 997)],
			'tar, long read' => ['tar' => true, 'brokenContent' => str_repeat('b', 1000), 'brokenSize' => 600, 'throwOnRead' => null, 'expected' => str_repeat('b', 600)],
		];
	}

	/**
	 * Tests that an archive containing a broken entry can still be read,
	 * including the entries written after it.
	 */
	#[DataProvider(methodName: 'dataArchiveStaysValid')]
	public function testArchiveStaysValid(bool $tar, string $brokenContent, int $brokenSize, ?int $throwOnRead, string $expected): void {
		$output = fopen('php://temp', 'w+');
		$streamer = $tar ? new TarStreamer(['outstream' => $output]) : new ZipStreamer(['outstream' => $output, 'zip64' => false]);
		$add = static function (string $name, $source, int $size) use ($streamer, $tar): void {
			$tar ? $streamer->addFileFromStream($source, $name, $size) : $streamer->addFileFromStream($source, $name);
		};

		$add('first.txt', $this->createSource('first', null), 5);
		$broken = ArchiveEntryStream::wrap($this->createSource($brokenContent, $throwOnRead), $brokenSize, $tar, static function (): void {
		});
		$add('broken.bin', $broken, $brokenSize);
		fclose($broken);
		$add('last.txt', $this->createSource('last', null), 4);
		$streamer->finalize();

		$archivePath = tempnam(sys_get_temp_dir(), 'archiveentrystream') . ($tar ? '.tar' : '.zip');
		rewind($output);
		file_put_contents($archivePath, stream_get_contents($output));
		try {
			$entries = $this->readArchive($archivePath);
			ksort($entries);
			$this->assertSame(['broken.bin' => $expected, 'first.txt' => 'first', 'last.txt' => 'last'], $entries);
		} finally {
			unlink($archivePath);
		}
	}

	/**
	 * @return array<string, string> file contents by name
	 */
	private function readArchive(string $path): array {
		$entries = [];
		if (str_ends_with($path, '.tar')) {
			foreach (new \PharData($path) as $file) {
				$entries[$file->getFilename()] = $file->getContent();
			}
			return $entries;
		}

		$zip = new \ZipArchive();
		$this->assertTrue($zip->open($path), 'Archive is not a valid zip file');
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$entries[$zip->getNameIndex($i)] = $zip->getFromIndex($i);
		}
		$zip->close();
		return $entries;
	}

	/**
	 * @return resource
	 */
	private function createSource(string $content, ?int $throwOnRead) {
		$source = fopen('php://temp', 'r+');
		fwrite($source, $content);
		rewind($source);
		if ($throwOnRead === null) {
			return $source;
		}

		$reads = 0;
		return CallbackWrapper::wrap($source, function () use (&$reads, $throwOnRead): void {
			if (++$reads === $throwOnRead) {
				throw new \RuntimeException('storage read failed');
			}
		});
	}
}
