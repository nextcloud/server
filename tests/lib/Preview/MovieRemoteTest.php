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

		$this->overwriteSystemConfig('preview_ffmpeg_url', 'ffmpeg-over-ip:5050');
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
			protected function getClient(string $address, string $secret): FFmpegOverIpClient {
				$this->credentials = [$address, $secret];
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
		$this->assertSame(['ffmpeg-over-ip:5050', 'secret'], $this->provider->credentials);

		$this->assertCount(2, $this->calls);
		$this->assertSame(FFmpegOverIpClient::PROGRAM_FFPROBE, $this->calls[0][0]);
		$this->assertSame([...Movie::HDR_PROBE_ARGS, '/input.mp4'], $this->calls[0][1]);
		$this->assertSame(FFmpegOverIpClient::PROGRAM_FFMPEG, $this->calls[1][0]);
		$this->assertSame(['-ss', '5', '-i', '/input.mp4', '-f', 'mjpeg', '-vframes', '1', 'pipe:1'], $this->calls[1][1]);
		$this->assertSame(['/input.mp4'], array_keys($this->calls[1][2]));
		$this->assertSame(filesize(\OC::$SERVERROOT . '/tests/data/' . $this->fileName), $this->calls[1][2]['/input.mp4']['size']);
	}

	public function testHdr(): void {
		$this->results[] = $this->getResult("smpte2084\n", 0, 'configuration: --enable-libzimg');

		$this->assertNotNull($this->getThumbnail());
		$this->assertSame(['-ss', '5', '-i', '/input.mp4', '-f', 'mjpeg', '-vframes', '1', '-vf', Movie::HDR_FILTER, 'pipe:1'], $this->calls[1][1]);
	}

	public function testHdrWithoutZscale(): void {
		$this->results[] = $this->getResult("smpte2084\n");

		$this->assertNotNull($this->getThumbnail());
		$this->assertNotContains('-vf', $this->calls[1][1]);
	}

	public function testTriesNextTimestamp(): void {
		$this->results = [$this->getResult(''), $this->getResult('', 1), $this->getResult('not an image')];

		$this->assertNotNull($this->getThumbnail());
		$this->assertSame(['5', '1', '0'], array_map(fn (array $call): string => $call[1][1], array_slice($this->calls, 1)));
	}

	public function testAllAttemptsFail(): void {
		$this->results = [$this->getResult(''), $this->getResult('', 1), $this->getResult('', 1), $this->getResult('', 1)];

		$this->assertNull($this->getThumbnail());
		$this->assertCount(4, $this->calls);
	}

	public function testServerFails(): void {
		$this->onRun = function (): void {
			throw new \RuntimeException('Could not connect');
		};

		$this->assertNull($this->getThumbnail());
		$this->assertCount(1, $this->calls);
	}

	public function testNoUrlConfigured(): void {
		$this->overwriteSystemConfig('preview_ffmpeg_url', '');

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

	#[\PHPUnit\Framework\Attributes\DataProvider('streamProvider')]
	public function testStorageStreams(callable $fopen, bool $copied): void {
		$content = 'video content';
		$opened = [];
		$file = $this->createMock(File::class);
		$file->method('fopen')->willReturnCallback(function () use ($fopen, $content, &$opened) {
			return $opened[] = $fopen($content);
		});
		$file->method('getSize')->willReturn(strlen($content));
		$file->method('getExtension')->willReturn('MP4');
		$file->method('getPath')->willReturn('/user/files/video.mp4');

		$this->onRun = function (array $files) use ($content, $copied, &$opened): void {
			$stream = $files['/input.mp4']['stream'];
			$this->assertSame($copied, !in_array($stream, $opened, true));
			fseek($stream, 6);
			$this->assertSame(substr($content, 6), stream_get_contents($stream));
			fseek($stream, 0);
			$this->assertSame($content, stream_get_contents($stream));
		};

		$this->assertNotNull($this->getThumbnail($file));
		$this->assertCount(2, $this->calls);
	}
}
