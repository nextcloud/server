<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\AVIFImagick;

/**
 * Class AVIFImagickTest
 *
 * @package Test\Preview
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class AVIFImagickTest extends Provider {
	use AvifPreviewTrait;

	#[\Override]
	protected function setUp(): void {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('ImageMagick is not installed. Skipping tests');
		}
		if (!in_array('AVIF', \Imagick::queryFormats('AVIF'), true)) {
			$this->markTestSkipped('ImageMagick was built without AVIF. Skipping tests');
		}

		$fileName = 'testimage.avif';
		$sourcePath = \OC::$SERVERROOT . '/tests/data/' . $fileName;

		// Reporting the coder is not the same as being able to use it: the
		// libheif delegate may be missing, or policy.xml may have disabled
		// it, in which case decoding throws and the tests would fail rather
		// than skip. Decode once for real before committing to them.
		try {
			(new \Imagick())->readImage('avif:' . $sourcePath . '[0]');
		} catch (\ImagickException $e) {
			$this->markTestSkipped('ImageMagick cannot decode AVIF here: ' . $e->getMessage() . '. Skipping tests');
		}

		parent::setUp();

		$this->imgPath = $this->prepareTestFile($fileName, $sourcePath);
		$this->width = 1680;
		$this->height = 1050;
		$this->provider = new AVIFImagick();
	}

	public function testPreviewCarriesThePicture(): void {
		$this->assertPreviewShowsThePicture();
	}
}
