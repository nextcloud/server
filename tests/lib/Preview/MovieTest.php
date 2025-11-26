<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\Preview\Movie;
use OCP\IBinaryFinder;
use OCP\Server;

/**
 * Class MovieTest
 *
 *
 * @package Test\Preview
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class MovieTest extends Provider {
	protected string $fileName = 'testimage.mp4';
	protected int $width = 560;
	protected int $height = 320;

	protected function setUp(): void {
		$binaryFinder = Server::get(IBinaryFinder::class);
		$movieBinary = $binaryFinder->findBinaryPath('ffmpeg');

		if (is_string($movieBinary)) {
			parent::setUp();

			$this->imgPath = $this->prepareTestFile($this->fileName, \OC::$SERVERROOT . '/tests/data/' . $this->fileName);
			$this->provider = new Movie(['movieBinary' => $movieBinary]);
		} else {
			$this->markTestSkipped('No Movie provider present');
		}
	}
}
