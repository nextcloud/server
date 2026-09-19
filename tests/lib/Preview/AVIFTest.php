<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\AVIF;

/**
 * Class AVIFTest
 *
 * @package Test\Preview
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class AVIFTest extends Provider {
	use AvifPreviewTrait;

	#[\Override]
	protected function setUp(): void {
		$fileName = 'testimage.avif';
		$sourcePath = \OC::$SERVERROOT . '/tests/data/' . $fileName;

		// Reading an AVIF takes more than a libgd built against libavif.
		// OC\Image picks its decoder from exif_imagetype(), and guards it
		// with getimagesize(), so a build where either does not know the
		// format never reaches imagecreatefromavif() however capable libgd
		// is. Ask for the whole path rather than for one part of it, and
		// say which part was missing when it is not there.
		$probe = new \OCP\Image();
		$probe->loadFromFile($sourcePath);
		if (!$probe->valid()) {
			$this->markTestSkipped(sprintf(
				'libgd cannot read AVIF here (IMG_AVIF=%s, exif_imagetype=%s, getimagesize type=%s, imagecreatefromavif=%s). Skipping tests',
				(imagetypes() & IMG_AVIF) ? 'yes' : 'no',
				var_export(@exif_imagetype($sourcePath), true),
				var_export(@getimagesize($sourcePath)[2] ?? false, true),
				@imagecreatefromavif($sourcePath) === false ? 'failed' : 'ok',
			));
		}

		parent::setUp();

		$this->imgPath = $this->prepareTestFile($fileName, $sourcePath);
		$this->width = 1680;
		$this->height = 1050;
		$this->provider = new AVIF();
	}

	public function testPreviewCarriesThePicture(): void {
		$this->assertPreviewShowsThePicture();
	}
}
