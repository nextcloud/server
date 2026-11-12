<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use Icewind\Streams\CallbackWrapper;
use OC\Files\Node\File as FileNode;
use OC\Preview\FFmpegOverIpClient;
use OC\Preview\Movie;
use OC\Preview\MovieRemote;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\Lock\ILockingProvider;
use OCP\Server;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class MovieRemoteTest extends Provider {
	protected string $fileName = 'testimage.mp4';
	protected int $width = 1680;
	protected int $height = 1050;

	/** @var list<array{0: int, 1: list<string>, 2: array}> */
	private array $calls = [];
	/** @var list<array{exitCode: int, stdout: string, stderr: string}> */
	private array $results = [];
	/** @var ?callable called with the files passed to each run */
	private $onRun = null;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->overwriteSystemConfig('preview_ffmpeg_address', 'ffmpeg-over-ip:5050');
		$this->overwriteSystemConfig('preview_ffmpeg_key', 'secret');
		$this->imgPath = $this->prepareTestFile($this->fileName, \OC::$SERVERROOT . '/tests/data/' . $this->fileName);

		$client = $this->createMock(FFmpegOverIpClient::class);
		$client->method('run')->willReturnCallback(function (int $program, array $args, array $files): array {
			$this->calls[] = [$program, $args, $files];
			if ($this->onRun !== null) {
				($this->onRun)($files);
			}
			return array_shift($this->results) ?? $this->getResult($program === FFmpegOverIpClient::PROGRAM_FFPROBE ? "bt709\n" : $this->getJpeg());
		});

		$this->provider = new class($client) extends MovieRemote {
			public array $credentials = [];

			public function __construct(
				private FFmpegOverIpClient $client,
			) {
				parent::__construct();
			}

			#[\Override]
			protected function getClient(string $address, string $secret, int $timeout, ?int $readLimit): FFmpegOverIpClient {
				$this->credentials = [$address, $secret, $timeout, $readLimit];
				return $this->client;
			}
		};
	}

	private function getResult(string $stdout, int $exitCode = 0, string $stderr = ''): array {
		return ['exitCode' => $exitCode, 'stdout' => $stdout, 'stderr' => $stderr];
	}

	private function getJpeg(): string {
		return file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.jpg');
	}

	private function getThumbnail(?File $file = null): ?\OCP\IImage {
		$file ??= new FileNode(Server::get(IRootFolder::class), $this->rootView, $this->imgPath);
		return $this->provider->getThumbnail($file, 256, 256);
	}

	public function testRunsFfprobeAndFfmpeg(): void {
		$preview = $this->getThumbnail();

		$this->assertNotNull($preview);
		$this->assertSame(256, $preview->width());
		$this->assertSame(160, $preview->height());
		$this->assertSame(['ffmpeg-over-ip:5050', 'secret', 120, null], $this->provider->credentials);

		$this->assertCount(2, $this->calls);
		$this->assertSame(FFmpegOverIpClient::PROGRAM_FFPROBE, $this->calls[0][0]);
		$this->assertSame([...Movie::HDR_PROBE_ARGS, '/input.mp4'], $this->calls[0][1]);
		$this->assertSame(FFmpegOverIpClient::PROGRAM_FFMPEG, $this->calls[1][0]);
		$this->assertSame(['-y', '-ss', '5', '-i', '/input.mp4', '-f', 'mjpeg', '-vframes', '1', 'pipe:1'], $this->calls[1][1]);
		$this->assertSame(['/input.mp4'], array_keys($this->calls[1][2]));
		$this->assertSame(filesize(\OC::$SERVERROOT . '/tests/data/' . $this->fileName), $this->calls[1][2]['/input.mp4']['size']);
	}

	public function testHdr(): void {
		$this->results[] = $this->getResult("smpte2084\n", 0, 'configuration: --enable-libzimg');

		$this->assertNotNull($this->getThumbnail());
		$this->assertSame(['-y', '-ss', '5', '-i', '/input.mp4', '-f', 'mjpeg', '-vframes', '1', '-vf', Movie::HDR_FILTER, 'pipe:1'], $this->calls[1][1]);
	}

	public function testHdrWithoutZscale(): void {
		$this->results[] = $this->getResult("smpte2084\n");

		$this->assertNotNull($this->getThumbnail());
		$this->assertNotContains('-vf', $this->calls[1][1]);
	}

	public function testProbeFails(): void {
		$this->onRun = function (): void {
			if (count($this->calls) === 1) {
				throw new \RuntimeException('ffprobe not found');
			}
		};

		$this->assertNotNull($this->getThumbnail());
		$this->assertCount(2, $this->calls);
		$this->assertNotContains('-vf', $this->calls[1][1]);
	}

	public function testTriesNextTimestamp(): void {
		$this->results = [$this->getResult(''), $this->getResult('', 1), $this->getResult('not an image')];

		$this->assertNotNull($this->getThumbnail());
		$this->assertSame(['5', '1', '0'], array_map(fn (array $call): string => $call[1][2], array_slice($this->calls, 1)));
	}

	public function testAllAttemptsFail(): void {
		$this->results = [$this->getResult(''), $this->getResult('', 1), $this->getResult('', 1), $this->getResult('', 1)];

		$this->assertNull($this->getThumbnail());
		$this->assertCount(4, $this->calls);
	}

	public function testServerUnreachable(): void {
		$this->onRun = function (): void {
			throw new \RuntimeException('Could not connect', FFmpegOverIpClient::ERROR_CONNECT);
		};

		$this->assertNull($this->getThumbnail());
		$this->assertCount(1, $this->calls);
	}

	public function testStorageError(): void {
		$this->onRun = function (): void {
			throw new \Exception('Connection reset');
		};

		$this->assertNull($this->getThumbnail());
		$this->assertCount(2, $this->calls);
	}

	public function testFileIsNotLocked(): void {
		$this->onRun = function (): void {
			$this->rootView->lockFile($this->imgPath, ILockingProvider::LOCK_EXCLUSIVE);
			$this->rootView->unlockFile($this->imgPath, ILockingProvider::LOCK_EXCLUSIVE);
		};

		$this->assertNotNull($this->getThumbnail());
	}

	public static function timeoutProvider(): array {
		return [[30, 30], [0, 120], [-5, 120]];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('timeoutProvider')]
	public function testTimeoutConfig(int $config, int $timeout): void {
		$this->overwriteSystemConfig('preview_ffmpeg_timeout', $config);

		$this->assertNotNull($this->getThumbnail());
		$this->assertSame($timeout, $this->provider->credentials[2]);
	}

	public function testNoUrlConfigured(): void {
		$this->overwriteSystemConfig('preview_ffmpeg_address', '');

		$this->assertFalse($this->provider->isAvailable($this->createMock(File::class)));
		$this->assertNull($this->getThumbnail());
		$this->assertSame([], $this->calls);
	}

	public static function streamProvider(): array {
		return [
			'seekable stream wrapper (S3, SMB, SFTP, encryption)' => [
				fn (string $content) => CallbackWrapper::wrap(Utils::tryFopen('data://text/plain,' . $content, 'r'), fn () => null),
				false,
			],
			'stream wrapper that cannot seek (WebDAV)' => [
				fn (string $content) => StreamWrapper::getResource(new NoSeekStream(Utils::streamFor($content))),
				true,
			],
			'native stream that cannot seek (FTP)' => [
				function (string $content) {
					[$read, $write] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
					fwrite($write, $content);
					fclose($write);
					return $read;
				},
				true,
			],
		];
	}

	private function getStorageFile(callable $fopen, string $content, int $cachedSize, array &$opened): File {
		$open = function () use ($fopen, $content, &$opened) {
			return $opened[] = $fopen($content);
		};
		$storage = $this->createMock(IStorage::class);
		$storage->method('isLocal')->willReturn(false);
		$storage->method('fopen')->with('files/video.mp4', 'r')->willReturnCallback($open);
		$file = $this->createMock(File::class);
		$file->method('fopen')->willReturnCallback($open);
		$file->method('getInternalPath')->willReturn('files/video.mp4');
		$file->method('getSize')->willReturn($cachedSize);
		$file->method('getStorage')->willReturn($storage);
		$file->method('getExtension')->willReturn('MP4');
		$file->method('getPath')->willReturn('/user/files/video.mp4');
		return $file;
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('streamProvider')]
	public function testStorageStreams(callable $fopen, bool $copied): void {
		$content = 'video content';
		$opened = [];
		// The cached size is outdated
		$file = $this->getStorageFile($fopen, $content, 999, $opened);

		$this->onRun = function (array $files) use ($content, $copied, &$opened): void {
			$stream = $files['/input.mp4']['stream'];
			$this->assertSame($copied, !in_array($stream, $opened, true));
			$this->assertSame(strlen($content), $files['/input.mp4']['size']);
			fseek($stream, 6);
			$this->assertSame(substr($content, 6), stream_get_contents($stream));
			fseek($stream, 0);
			$this->assertSame($content, stream_get_contents($stream));
		};

		$this->assertNotNull($this->getThumbnail($file));
		$this->assertCount(2, $this->calls);
		$this->assertSame(20 * 1024 * 1024, $this->provider->credentials[3]);
	}

	public function testNonSeekableStreamIsDownloadedPartially(): void {
		$content = str_repeat('a', 11 * 1024 * 1024);
		$opened = [];
		$file = $this->getStorageFile(fn (string $content) => StreamWrapper::getResource(new NoSeekStream(Utils::streamFor($content))), $content, strlen($content), $opened);

		$this->onRun = function (array $files): void {
			$this->assertSame(10 * 1024 * 1024, $files['/input.mp4']['size']);
			$this->assertSame(10 * 1024 * 1024, strlen(stream_get_contents($files['/input.mp4']['stream'], -1, 0)));
		};

		$this->assertNotNull($this->getThumbnail($file));
	}
}
