<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\BinaryFinder;
use OC\Preview\Postscript;

/**
 * Class BitmapTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class BitmapTest extends Provider {
	protected function setUp(): void {
		if (\Imagick::queryFormats('EPS') === false || \Imagick::queryFormats('PS') === false) {
			$this->markTestSkipped('Imagick does not support postscript.');
		}
		if (\OCP\Server::get(BinaryFinder::class)->findBinaryPath('gs') === false) {
			// Imagick forwards postscript rendering to Ghostscript but does not report this in queryFormats
			$this->markTestSkipped('Ghostscript is not installed.');
		}

		parent::setUp();

		$fileName = 'testimage.eps';
		$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
		$this->width = 2400;
		$this->height = 1707;
		$this->provider = new Postscript;
	}
}
