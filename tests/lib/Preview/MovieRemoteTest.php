<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\MovieRemote;
use OCP\Files\File;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use Test\TestCase;

class MovieRemoteTest extends TestCase {
	private IClient $client;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->client = $this->createMock(IClient::class);
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($this->client);
		$this->overwriteService(IClientService::class, $clientService);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->restoreService(IClientService::class);
		parent::tearDown();
	}

	private function getFile(string $content): File {
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $content);
		rewind($stream);

		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn(strlen($content));
		$file->method('getMimeType')->willReturn('video/mp4');
		$file->method('fopen')->willReturn($stream);
		return $file;
	}

	private function getResponse(int $status, string $body): IResponse {
		$responseBody = fopen('php://memory', 'r+');
		fwrite($responseBody, $body);
		rewind($responseBody);

		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn($status);
		$response->method('getBody')->willReturn($responseBody);
		return $response;
	}

	private function getJpeg(int $width, int $height): string {
		$image = imagecreatetruecolor($width, $height);
		ob_start();
		imagejpeg($image);
		return ob_get_clean();
	}

	public function testNoUrlConfigured(): void {
		$this->overwriteSystemConfig('preview_ffmpeg_url', '');
		$this->client->expects($this->never())->method('post');

		$this->assertNull((new MovieRemote())->getThumbnail($this->getFile('video'), 256, 256));
	}

	public function testThumbnail(): void {
		$this->overwriteSystemConfig('preview_ffmpeg_url', 'http://ffmpeg:8080/');
		$this->overwriteSystemConfig('preview_ffmpeg_key', 'secret');
		$response = $this->getResponse(200, $this->getJpeg(400, 200));

		$this->client->expects($this->once())
			->method('post')
			->willReturnCallback(function (string $url, array $options) use ($response): IResponse {
				$this->assertSame('http://ffmpeg:8080/thumbnail', $url);
				$this->assertSame(['width' => 256, 'height' => 256, 'time' => '5,1,0'], $options['query']);
				$this->assertSame('Bearer secret', $options['headers']['Authorization']);
				$this->assertSame('video/mp4', $options['headers']['Content-Type']);
				$this->assertSame('5', $options['headers']['Content-Length']);
				$this->assertSame('video', stream_get_contents($options['body']));
				return $response;
			});

		$preview = (new MovieRemote())->getThumbnail($this->getFile('video'), 256, 256);
		$this->assertNotNull($preview);
		$this->assertSame(256, $preview->width());
		$this->assertSame(128, $preview->height());
	}

	public function testNoKeyConfigured(): void {
		$this->overwriteSystemConfig('preview_ffmpeg_url', 'http://ffmpeg:8080');
		$this->overwriteSystemConfig('preview_ffmpeg_key', '');
		$response = $this->getResponse(200, $this->getJpeg(40, 30));

		$this->client->expects($this->once())
			->method('post')
			->willReturnCallback(function (string $url, array $options) use ($response): IResponse {
				$this->assertSame('http://ffmpeg:8080/thumbnail', $url);
				$this->assertArrayNotHasKey('Authorization', $options['headers']);
				return $response;
			});

		$this->assertNotNull((new MovieRemote())->getThumbnail($this->getFile('video'), 256, 256));
	}

	public function testServiceError(): void {
		$this->overwriteSystemConfig('preview_ffmpeg_url', 'http://ffmpeg:8080');
		$this->client->method('post')->willReturn($this->getResponse(422, 'no frame'));

		$this->assertNull((new MovieRemote())->getThumbnail($this->getFile('video'), 256, 256));
	}

	public function testServiceUnreachable(): void {
		$this->overwriteSystemConfig('preview_ffmpeg_url', 'http://ffmpeg:8080');
		$this->client->method('post')->willThrowException(new \Exception('Connection refused'));

		$this->assertNull((new MovieRemote())->getThumbnail($this->getFile('video'), 256, 256));
	}
}
