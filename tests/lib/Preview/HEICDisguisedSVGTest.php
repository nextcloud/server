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
		# HEIC->getThumbnail will always return null if there is an exception, be we want to check why getResizedPreview fails
		$reflection = new \ReflectionClass($this->provider);
		$method = $reflection->getMethod('getResizedPreview');
		$method->setAccessible(true);

		$absolutePath = \OC::$SERVERROOT . '/tests/data/' . 'testimage-disguised-svg.heic';

		try {
			$method->invoke(
				$this->provider,
				$absolutePath,
				$this->width,
				$this->height
			);
			$this->fail('Expected ImagickException was not thrown.');
		} catch (\ImagickException $e) {
			$this->assertStringStartsWith('ImageTypeNotSupported', $e->getMessage());
		}
	}
}
