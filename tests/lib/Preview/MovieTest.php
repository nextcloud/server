<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

/**
 * Class MovieTest
 *
 * @group DB
 *
 * @package Test\Preview
 */
class MovieTest extends Provider {
	protected string $fileName = 'testimage.mp4';
	protected int $width = 560;
	protected int $height = 320;

	protected function setUp(): void {
		$avconvBinary = \OC_Helper::findBinaryPath('avconv');
		$ffmpegBinary = ($avconvBinary) ? null : \OC_Helper::findBinaryPath('ffmpeg');

		if ($avconvBinary || $ffmpegBinary) {
			parent::setUp();

			\OC\Preview\Movie::$avconvBinary = $avconvBinary;
			\OC\Preview\Movie::$ffmpegBinary = $ffmpegBinary;

			$this->imgPath = $this->prepareTestFile($this->fileName, \OC::$SERVERROOT . '/tests/data/' . $this->fileName);
			$this->provider = new \OC\Preview\Movie;
		} else {
			$this->markTestSkipped('No Movie provider present');
		}
	}
}
