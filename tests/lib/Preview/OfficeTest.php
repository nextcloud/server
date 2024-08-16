<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

/**
 * Class OfficeTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class OfficeTest extends Provider {
	protected function setUp(): void {
		$libreofficeBinary = \OC_Helper::findBinaryPath('libreoffice');
		$openofficeBinary = ($libreofficeBinary) ? null : \OC_Helper::findBinaryPath('openoffice');

		if ($libreofficeBinary || $openofficeBinary) {
			parent::setUp();

			$fileName = 'testimage.odt';
			$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
			$this->width = 595;
			$this->height = 842;
			$this->provider = new \OC\Preview\OpenDocument;
		} else {
			$this->markTestSkipped('No Office provider present');
		}
	}
}
