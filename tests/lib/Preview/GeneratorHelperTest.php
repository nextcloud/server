<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\GeneratorHelper;
use OC\Preview\IMagickSupport;
use OC\Preview\Imaginary;
use OC\Preview\JPEG;
use OCP\Files\SimpleFS\ISimpleFile;
use Test\TestCase;

class GeneratorHelperTest extends TestCase {
	private function getPreviewFile(string $mimeType, int $width, int $height): ISimpleFile {
		$image = imagecreatetruecolor($width, $height);
		ob_start();
		if ($mimeType === 'image/jpeg') {
			imagejpeg($image);
		} else {
			imagepng($image);
		}
		$data = ob_get_clean();

		$file = $this->createMock(ISimpleFile::class);
		$file->method('getMimeType')->willReturn($mimeType);
		$file->method('getContent')->willReturn($data);
		return $file;
	}

	private function getHelper(bool $hasImagick): GeneratorHelper {
		$imagickSupport = $this->createMock(IMagickSupport::class);
		$imagickSupport->method('hasExtension')->willReturn($hasImagick);
		return new GeneratorHelper($imagickSupport);
	}

	public function testGetScaledImage(): void {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('Imagick is required to decode JPEG images at a reduced size');
		}

		$image = $this->getHelper(true)->getScaledImage($this->getPreviewFile('image/jpeg', 4000, 3000), 256, 192);

		$this->assertNotNull($image);
		$this->assertSame('image/jpeg', $image->dataMimeType());
		$this->assertGreaterThanOrEqual(256, $image->width());
		$this->assertGreaterThanOrEqual(192, $image->height());
		$this->assertLessThan(4000, $image->width());
		$this->assertEqualsWithDelta(4 / 3, $image->width() / $image->height(), 0.01);
	}

	public function testGetScaledImageWithoutImagick(): void {
		$this->assertNull($this->getHelper(false)->getScaledImage($this->getPreviewFile('image/jpeg', 800, 600), 64, 48));
	}

	public function testResizesEfficiently(): void {
		$helper = $this->getHelper(false);
		$this->assertTrue($helper->resizesEfficiently(new Imaginary([])));
		$this->assertFalse($helper->resizesEfficiently(new JPEG()));
	}

	public function testGetScaledImageOfPng(): void {
		$this->assertNull($this->getHelper(true)->getScaledImage($this->getPreviewFile('image/png', 800, 600), 64, 48));
	}
}
