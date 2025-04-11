<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OCP\IBinaryFinder;
use OCP\Server;

/**
 * Class MovieTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class MovieTest extends Provider {
	protected function setUp(): void {
		$binaryFinder = Server::get(IBinaryFinder::class);
		$movieBinary = $binaryFinder->findBinaryPath('avconv');
		if (!is_string($movieBinary)) {
			$movieBinary = $binaryFinder->findBinaryPath('ffmpeg');
		}

		if (is_string($movieBinary)) {
			parent::setUp();

			$fileName = 'testimage.mp4';
			$this->imgPath = $this->prepareTestFile($fileName, \OC::$SERVERROOT . '/tests/data/' . $fileName);
			$this->width = 560;
			$this->height = 320;
			$this->provider = new \OC\Preview\Movie(['movieBinary' => $movieBinary]);
		} else {
			$this->markTestSkipped('No Movie provider present');
		}
	}
}
