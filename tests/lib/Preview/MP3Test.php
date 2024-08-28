<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

/**
 * Class MP3Test
 *
 * @group DB
 *
 * @package Test\Preview
 */
class MP3Test extends Provider {
	protected function setUp(): void {
		parent::setUp();

		$fileName = 'testimage.mp3';
		$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
		$this->width = 200;
		$this->height = 200;
		$this->provider = new \OC\Preview\MP3;
	}
}
