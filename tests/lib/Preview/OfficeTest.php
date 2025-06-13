<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\Preview\OpenDocument;
use OCP\IBinaryFinder;
use OCP\Server;

/**
 * Class OfficeTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class OfficeTest extends Provider {
	protected function setUp(): void {
		$binaryFinder = Server::get(IBinaryFinder::class);
		$libreofficeBinary = $binaryFinder->findBinaryPath('libreoffice');
		$openofficeBinary = $libreofficeBinary === false ? $binaryFinder->findBinaryPath('openoffice') : false;

		if ($libreofficeBinary !== false || $openofficeBinary !== false) {
			parent::setUp();

			$fileName = 'testimage.odt';
			$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
			$this->width = 595;
			$this->height = 842;
			$this->provider = new OpenDocument;
		} else {
			$this->markTestSkipped('No Office provider present');
		}
	}
}
