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
		// libgd is built against libavif only where the distribution chose to,
		// so a build without it reports no AVIF and cannot decode the fixture
		if (!(imagetypes() & IMG_AVIF)) {
			$this->markTestSkipped('libgd has no AVIF support. Skipping tests');
		}

		parent::setUp();

		$fileName = 'testimage.avif';
		$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
		$this->width = 1680;
		$this->height = 1050;
		$this->provider = new AVIF();
	}

	public function testPreviewCarriesThePicture(): void {
		$this->assertPreviewShowsThePicture();
	}
}
