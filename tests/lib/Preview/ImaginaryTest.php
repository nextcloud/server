<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\Imaginary;
use OCP\Files\File;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\StreamInterface;
use Test\TestCase;

class ImaginaryTest extends TestCase {
	private IClient $client;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->overwriteSystemConfig('preview_imaginary_url', 'http://imaginary');
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

	private function getJpeg(): string {
		$image = imagecreatetruecolor(40, 30);
		ob_start();
		imagejpeg($image);
		return ob_get_clean();
	}

	/**
	 * Requests a preview of a file of the given MIME type and returns the operations sent
	 * to Imaginary.
	 */
	private function getOperations(string $mimeType, int $maxX, int $maxY): array {
		$content = $this->getJpeg();
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $content);
		rewind($stream);

		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn(strlen($content));
		$file->method('getMimeType')->willReturn($mimeType);
		$file->method('fopen')->willReturn($stream);

		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn(200);
		$response->method('getHeader')->willReturn('');
		$responseBody = fopen('php://memory', 'r+');
		fwrite($responseBody, $this->getJpeg());
		rewind($responseBody);
		$response->method('getBody')->willReturn($responseBody);

		$operations = null;
		$this->client->expects($this->once())
			->method('post')
			->willReturnCallback(function (string $url, array $options) use ($content, $response, &$operations): IResponse {
				$operations = json_decode($options['query']['operations'], true);

				// The whole file is sent
				$body = $options['body'];
				$this->assertSame($content, $body instanceof StreamInterface ? $body->getContents() : stream_get_contents($body));
				return $response;
			});

		$preview = (new Imaginary([]))->getThumbnail($file, $maxX, $maxY);
		$this->assertNotNull($preview);
		return $operations;
	}

	public static function dataShrinkBeforeRotate(): array {
		return [
			'jpeg' => ['image/jpeg', 'jpeg'],
			'heic' => ['image/heic', 'jpeg'],
			'tiff' => ['image/tiff', 'jpeg'],
			'webp' => ['image/webp', 'jpeg'],
			'png' => ['image/png', 'png'],
			'gif' => ['image/gif', 'png'],
		];
	}

	#[DataProvider('dataShrinkBeforeRotate')]
	public function testShrinkBeforeRotate(string $mimeType, string $type): void {
		$this->assertSame([
			[
				'operation' => 'fit',
				'params' => [
					'width' => 4096,
					'height' => 4096,
					'stripmeta' => 'false',
					'type' => $type,
					'norotation' => 'true',
					'quality' => '100',
				],
			],
			[
				'operation' => 'autorotate',
			],
			[
				'operation' => 'fit',
				'params' => [
					'width' => 4096,
					'height' => 4096,
					'stripmeta' => 'true',
					'type' => $type,
					'norotation' => 'true',
					'quality' => '80',
				],
			],
		], $this->getOperations($mimeType, 4096, 4096));
	}

	public function testNonSquareBox(): void {
		$operations = $this->getOperations('image/jpeg', 4096, 2048);

		$this->assertSame(['fit', 'autorotate', 'fit'], array_column($operations, 'operation'));
		$this->assertSame([4096, 4096], [$operations[0]['params']['width'], $operations[0]['params']['height']]);
		$this->assertSame([4096, 2048], [$operations[2]['params']['width'], $operations[2]['params']['height']]);
	}

	public function testPreviewFormat(): void {
		$this->overwriteSystemConfig('preview_format', 'webp');

		$operations = $this->getOperations('image/jpeg', 1024, 1024);

		$this->assertSame('webp', $operations[0]['params']['type']);
		$this->assertSame('webp', $operations[2]['params']['type']);
	}

	public static function dataConvert(): array {
		return [
			'pdf' => ['application/pdf'],
			'svg' => ['image/svg+xml'],
			'illustrator' => ['application/illustrator'],
		];
	}

	#[DataProvider('dataConvert')]
	public function testConvertWithoutRotation(string $mimeType): void {
		$this->assertSame([
			[
				'operation' => 'convert',
				'params' => [
					'type' => 'png',
				],
			],
			[
				'operation' => 'fit',
				'params' => [
					'width' => 1024,
					'height' => 1024,
					'stripmeta' => 'true',
					'type' => 'png',
					'norotation' => 'true',
					'quality' => '80',
				],
			],
		], $this->getOperations($mimeType, 1024, 1024));
	}
}
