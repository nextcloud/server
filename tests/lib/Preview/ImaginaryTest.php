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
use PHPUnit\Framework\Attributes\TestWith;
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

	private function getJpeg(?int $orientation): string {
		$image = imagecreatetruecolor(40, 30);
		ob_start();
		imagejpeg($image);
		$jpeg = ob_get_clean();
		if ($orientation === null) {
			return $jpeg;
		}

		// EXIF segment with only the orientation
		$tiff = "II*\0" . pack('V', 8) . pack('v', 1) . pack('vvVvv', 0x0112, 3, 1, $orientation, 0) . pack('V', 0);
		$app1 = "\xFF\xE1" . pack('n', 2 + 6 + strlen($tiff)) . "Exif\0\0" . $tiff;
		return substr($jpeg, 0, 2) . $app1 . substr($jpeg, 2);
	}

	#[TestWith([null, false])]
	#[TestWith([1, false])]
	#[TestWith([6, true])]
	public function testAutorotateOnlyWhenNeeded(?int $orientation, bool $expectAutorotate): void {
		$jpeg = $this->getJpeg($orientation);
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $jpeg);
		rewind($stream);

		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn(strlen($jpeg));
		$file->method('getMimeType')->willReturn('image/jpeg');
		$file->method('fopen')->willReturn($stream);

		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn(200);
		$response->method('getHeader')->willReturn('');
		$responseBody = fopen('php://memory', 'r+');
		fwrite($responseBody, $this->getJpeg(null));
		rewind($responseBody);
		$response->method('getBody')->willReturn($responseBody);

		$this->client->expects($this->once())
			->method('post')
			->willReturnCallback(function (string $url, array $options) use ($jpeg, $expectAutorotate, $response): IResponse {
				$operations = array_column(json_decode($options['query']['operations'], true), 'operation');
				$this->assertSame($expectAutorotate ? ['autorotate', 'fit'] : ['fit'], $operations);

				// The whole file is sent, including the head read for the orientation
				$body = $options['body'];
				$this->assertSame($jpeg, $body instanceof StreamInterface ? $body->getContents() : stream_get_contents($body));
				return $response;
			});

		$preview = (new Imaginary([]))->getThumbnail($file, 256, 256);
		$this->assertNotNull($preview);
	}
}
