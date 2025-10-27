<?php

/**
 * SPDX-FileCopyrightText: 2019-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Preview;

use OC\Files\Node\File;
use OC\Files\Storage\Storage;
use OC\Preview\Movie;
use OCP\Files\IRootFolder;
use OCP\IBinaryFinder;
use OCP\Server;

/**
 * Class MovieTestRemoteFile
 *
 *
 * @package Test\Preview
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class MovieTestRemoteFile extends Provider {
	public function __construct() {
		parent::__construct(static::class);
	}
	// 1080p (1920x1080) 30 FPS HEVC/H264, 10 secs, avg. bitrate: ~10 Mbps
	protected string $fileName = 'testvideo-remote-file.mp4';
	protected int $width = 1920;
	protected int $height = 1080;

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

	#[\PHPUnit\Framework\Attributes\DataProvider('dimensionsDataProvider')]
	public function testGetThumbnail($widthAdjustment, $heightAdjustment): void {
		$ratio = round($this->width / $this->height, 2);
		$this->maxWidth = $this->width - $widthAdjustment;
		$this->maxHeight = $this->height - $heightAdjustment;
		$file = new File(Server::get(IRootFolder::class), $this->rootView, $this->imgPath);
		// Create mock remote file to be passed
		$remoteStorage = $this->createMock(Storage::class);
		$remoteStorage->method('isLocal')
			->willReturn(false);
		$mockRemoteVideo = $this->createMock(File::class);
		$mockRemoteVideo->method('getStorage')
			->willReturn($remoteStorage);
		$mockRemoteVideo->method('getSize')
			->willReturn($file->getSize());
		$mockRemoteVideo->method('fopen')
			->with('r')
			->willreturn($file->fopen('r'));
		$remotePreview = $this->provider->getThumbnail($mockRemoteVideo, $this->maxWidth, $this->maxHeight, $this->scalingUp);
		$localPreview = $this->provider->getThumbnail($file, $this->maxWidth, $this->maxHeight, $this->scalingUp);
		$this->assertNotFalse($remotePreview);
		$this->assertTrue($remotePreview->valid());
		$this->assertEquals($remotePreview->data(), $localPreview->data());
	}
}
