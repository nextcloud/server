<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\Preview\TXT;

/**
 * Class TXTTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class TXTTest extends Provider {
	protected function setUp(): void {
		parent::setUp();

		$fileName = 'lorem-big.txt';
		$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
		// Arbitrary width and length which won't be used to calculate the ratio
		$this->width = 500;
		$this->height = 200;
		$this->provider = new TXT;
	}
}
