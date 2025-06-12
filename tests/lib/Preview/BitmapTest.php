<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

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
		parent::setUp();

		$fileName = 'testimage.eps';
		$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
		$this->width = 2400;
		$this->height = 1707;
		$this->provider = new Postscript;
	}
}
