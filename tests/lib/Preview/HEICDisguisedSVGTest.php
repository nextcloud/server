<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\HEIC;

/**
 * Class HEICDisguisedSVGTest
 *
 *
 * @package Test\Preview
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class HEICDisguisedSVGTest extends Provider {
	protected function setUp(): void {
		if (!in_array('HEIC', \Imagick::queryFormats('HEI*'))) {
			$this->markTestSkipped('ImageMagick is not HEIC aware. Skipping tests');
		} else {
			parent::setUp();

			$fileName = 'testimage-disguised-svg.heic';
			$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
			$this->width = 1680;
			$this->height = 1050;
			$this->provider = new HEIC;
		}
	}

	/**
	 * Launches all the tests we have
	 *
	 *
	 * @param int $widthAdjustment
	 * @param int $heightAdjustment
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dimensionsDataProvider')]
	#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('imagick')]
	public function testGetThumbnail($widthAdjustment, $heightAdjustment): void {
		try {
			parent::testGetThumbnail($widthAdjustment, $heightAdjustment);
			$this->fail('Expected ImagickException was not thrown.');
		} catch (\ImagickException $e) {
			$this->assertStringStartsWith('ImageTypeNotSupported', $e->getMessage());
		}
	}
}
