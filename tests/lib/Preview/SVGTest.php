<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

/**
 * Class SVGTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class SVGTest extends Provider {
	protected function setUp(): void {
		$checkImagick = new \Imagick();
		if (count($checkImagick->queryFormats('SVG')) === 1) {
			parent::setUp();

			$fileName = 'testimagelarge.svg';
			$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
			$this->width = 3000;
			$this->height = 2000;
			$this->provider = new \OC\Preview\SVG;
		} else {
			$this->markTestSkipped('No SVG provider present');
		}
	}
}
