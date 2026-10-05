<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\JP2;

/**
 * Class JP2Test
 *
 * @package Test\Preview
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class JP2Test extends Provider {
	use PreviewPixelsTrait;

	#[\Override]
	protected function setUp(): void {
		if (!extension_loaded('imagick')) {
			$this->markTestSkipped('ImageMagick is not installed. Skipping tests');
		}
		if (!in_array('JP2', \Imagick::queryFormats('JP2'), true)) {
			$this->markTestSkipped('ImageMagick was built without JPEG 2000. Skipping tests');
		}

		$fileName = 'testimage.jp2';
		$sourcePath = \OC::$SERVERROOT . '/tests/data/' . $fileName;

		// Reporting the coder is not the same as being able to use it: the
		// OpenJPEG delegate may be missing, or policy.xml may have disabled
		// it, in which case decoding throws and the tests would fail rather
		// than skip. Decode once for real before committing to them.
		try {
			(new \Imagick())->readImage('jp2:' . $sourcePath . '[0]');
		} catch (\ImagickException $e) {
			$this->markTestSkipped('ImageMagick cannot decode JPEG 2000 here: ' . $e->getMessage() . '. Skipping tests');
		}

		parent::setUp();

		$this->imgPath = $this->prepareTestFile($fileName, $sourcePath);
		$this->width = 1680;
		$this->height = 1050;
		$this->provider = new JP2();
	}

	public function testPreviewCarriesThePicture(): void {
		$this->assertPreviewShowsThePicture();
	}
}
