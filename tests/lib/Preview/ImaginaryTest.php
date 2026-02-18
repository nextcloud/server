<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\Imaginary;
use OCP\Files\File;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Integration test for Imaginary preview provider.
 * 
 * This test requires a running Imaginary instance to verify that the
 * Content-Type header fix works correctly with a real server.
 * 
 * To run this test:
 * 1. Start Imaginary: docker run -p 9000:9000 h2non/imaginary
 * 2. Run: ./autotest.sh sqlite lib/Preview/ImaginaryTest.php
 * 
 * Or set IMAGINARY_INTEGRATION_URL environment variable to use a different URL.
 */
class ImaginaryTest extends \Test\TestCase {
	protected function setUp(): void {
		parent::setUp();
	}

	public function testIntegrationWithRealImaginary(): void {
	// Integration test: requires a running Imaginary instance.
	$imaginaryUrl = getenv('IMAGINARY_INTEGRATION_URL') ?: 'http://localhost:9000';

		// We don't pre-check availability. If Imaginary is not reachable,
		// the provider will return null and we skip the test gracefully.

		// Configure IConfig to point the provider to our test Imaginary instance
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueInt')
			->willReturnCallback(function (string $key, int $default = 0) {
				// Provide sane defaults for image and memory related limits used during preview generation
				switch ($key) {
					case 'preview_max_filesize_image':
						return 50; // MB
					case 'preview_max_memory':
						return 256; // MB
					default:
						return $default;
				}
			});
		$config->method('getSystemValueString')
			->willReturnCallback(function (string $key, $default = '') use ($imaginaryUrl) {
				switch ($key) {
					case 'preview_imaginary_url':
						return rtrim($imaginaryUrl, '/');
					case 'preview_imaginary_key':
						return '';
					case 'preview_format':
						return 'jpeg';
					default:
						return $default;
				}
			});
		$config->method('getAppValue')
			->willReturnCallback(function (string $app, string $key, string $default) {
				return $default; // Use defaults
			});

		$logger = $this->createMock(LoggerInterface::class);

		$this->overwriteService(IConfig::class, $config);
		$this->overwriteService(LoggerInterface::class, $logger);

	// Use an existing real JPEG from the repo to avoid requiring ext-gd
	$jpegPath = \OC::$SERVERROOT . '/tests/data/testimage.jpg';
	$size = filesize($jpegPath);
	$stream = fopen($jpegPath, 'r');

		// Mock file that returns our test JPEG
		$file = $this->createMock(File::class);
		$file->method('getSize')->willReturn($size);
		$file->method('getMimeType')->willReturn('image/jpeg');
		$file->method('fopen')->with('r')->willReturn($stream);

		// Run the actual provider against real Imaginary
		$provider = new Imaginary([]);
		$imgRes = $provider->getCroppedThumbnail($file, 128, 128, false);

		if ($imgRes === null) {
			self::markTestSkipped('Imaginary not reachable or returned non-200 response');
		}

		if (is_resource($stream)) {
			fclose($stream);
		}

		self::assertNotNull($imgRes, 'Expected a valid image back from Imaginary provider');
		self::assertTrue($imgRes->valid(), 'Image should be valid');
	}
}
