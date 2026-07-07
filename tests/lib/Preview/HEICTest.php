<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\HEIC;

/**
 * Class BitmapTest
 *
 *
 * @package Test\Preview
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class HEICTest extends Provider {
	#[\Override]
	protected function setUp(): void {
		if (!in_array('HEIC', \Imagick::queryFormats('HEI*'))) {
			$this->markTestSkipped('ImageMagick is not HEIC aware. Skipping tests');
		}

		$fileName = 'testimage.heic';
		$sourcePath = \OC::$SERVERROOT . '/tests/data/' . $fileName;

		// queryFormats() only reports that the HEIC coder is registered, not that
		// ImageMagick can actually decode a HEIC file: the libheif delegate may be
		// missing or the coder may be disabled by ImageMagick's policy.xml. In that
		// case decoding throws, the provider returns null and the tests fail instead
		// of being skipped. Verify a real decode before running the tests.
		try {
			(new \Imagick())->readImage($sourcePath . '[0]');
		} catch (\ImagickException $e) {
			$this->markTestSkipped('ImageMagick cannot decode HEIC in this environment: ' . $e->getMessage() . '. Skipping tests');
		}

		parent::setUp();

		$this->imgPath = $this->prepareTestFile($fileName, $sourcePath);
		$this->width = 1680;
		$this->height = 1050;
		$this->provider = new HEIC;
	}
}
