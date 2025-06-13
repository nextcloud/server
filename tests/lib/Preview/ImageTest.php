<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\Preview\JPEG;

/**
 * Class ImageTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class ImageTest extends Provider {
	protected function setUp(): void {
		parent::setUp();

		$fileName = 'testimage.jpg';
		$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
		$this->width = 1680;
		$this->height = 1050;
		$this->provider = new JPEG();
	}
}
