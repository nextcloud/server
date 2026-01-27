<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Image;
use OCP\Files\File;
use OCP\Preview\IProviderV2;

trait TraitSvgSanitizing {

	protected IProviderV2 $provider;

	public static function dataTestSanitizingSVG(): array {
		$pathToRealFile = __DIR__ . '../../data/testimage.jpg';
		$maliciousSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><image href="'
			. $pathToRealFile
			. '" width="400" height="400"></image></svg>';
			
		return ['SVG with embedded local file link' => [$maliciousSvg]];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestSanitizingSVG')]
	public function testSanitizingSVGInput(string $maliciousSvg): void {
		$stream = fopen('php://memory', 'rb+');
		fwrite($stream, $maliciousSvg);
		rewind($stream);
		$file = $this->createMock(File::class);
		$file->method('isEncrypted')
			// force reading content of file instead of trying local file access
			->willReturn(true);
		$file->method('fopen')
			->willReturn($stream);

		$actualResult = $this->provider->getThumbnail($file, 32, 32, false);
		if ($actualResult !== null) {
			$this->assertImage(__DIR__ . '/../../data/empty.png', $actualResult);
			return;
		}
		self::assertNull($actualResult);
	}

	/**
	 * Compare an image with a given expected image.
	 *
	 * @param string $expected - Path to expected image
	 * @param Image $actual - Actual image
	 */
	public static function assertImage(string $expected, Image $actual, string $message = 'Images are not equal'): void {
		$EPSILON = 0.0001;
		imagepng($actual->resource());
		$actualImageBlob = ob_get_clean();

		# Compare images
		$imageExpected = new \Imagick($expected);
		$imageActual = new \Imagick();
		$imageActual->readImageBlob($actualImageBlob);

		[, $result] = $imageExpected->compareImages(
			$imageActual,
			\Imagick::METRIC_MEANSQUAREERROR,
		);

		self::assertLessThan($EPSILON, $result, $message);
	}
}
