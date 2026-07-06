<?php
/**
 * @copyright Copyright (c) 2018, Sebastian Steinmetz (me@sebastiansteinmetz.ch)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
		if (!in_array("HEIC", \Imagick::queryFormats("HEI*"))) {
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
