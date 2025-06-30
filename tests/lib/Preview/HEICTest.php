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
 * @group DB
 *
 * @package Test\Preview
 */
class HEICTest extends Provider {
	protected function setUp(): void {
		if (!in_array('HEIC', \Imagick::queryFormats('HEI*'))) {
			$this->markTestSkipped('ImageMagick is not HEIC aware. Skipping tests');
		} else {
			parent::setUp();

			$fileName = 'testimage.heic';
			$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
			$this->width = 1680;
			$this->height = 1050;
			$this->provider = new HEIC;
		}
	}
}
