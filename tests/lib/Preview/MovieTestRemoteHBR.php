<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
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
 * Class MovieTestRemoteHBR
 *
 * @group DB
 *
 * @package Test\Preview
 */
class MovieTestRemoteHBR extends Provider {
	// 54 MB modern video file used for movie preview generation testing
	// 4K (3840x21600) 60 FPS HEVC/H265, 10 secs, avg. bitrate: ~41 Mbps
	protected string $fileName = 'testvideo-high-bitrate.mp4';
	protected int $width = 3840;
	protected int $height = 2160;

	protected function setUp(): void {
		$binaryFinder = Server::get(IBinaryFinder::class);
		$movieBinary = $binaryFinder->findBinaryPath('avconv');
		if (!is_string($movieBinary)) {
			$movieBinary = $binaryFinder->findBinaryPath('ffmpeg');
		}
		if (is_string($movieBinary)) {
			parent::setUp();
			$this->imgPath = $this->prepareTestFile($this->fileName, \OC::$SERVERROOT . '/tests/data/' . $this->fileName);
			$this->provider = new Movie(['movieBinary' => $movieBinary]);
		} else {
			$this->markTestSkipped('No Movie provider present');
		}
	}

	/**
	 * Launches all the tests we have
	 *
	 * @requires extension imagick
	 *
	 * @param int $widthAdjustment
	 * @param int $heightAdjustment
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dimensionsDataProvider')]
	public function testGetThumbnail($widthAdjustment, $heightAdjustment): void {
		$ratio = round($this->width / $this->height, 2);
		$this->maxWidth = $this->width - $widthAdjustment;
		$this->maxHeight = $this->height - $heightAdjustment;
		$preview = $this->getPreviewWithRemoteVideo($this->provider);
	}

	private function getPreviewWithRemoteVideo($provider) {
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
		$mockRemoteVideo->method('getMetaData')
			->willreturn(['istest' => true]);
		$remotePreview = $provider->getThumbnail($mockRemoteVideo, $this->maxWidth, $this->maxHeight, $this->scalingUp);

		// Create mock local file to be passed
		$localStorage = $this->createMock(Storage::class);
		$localStorage->method('isLocal')
			->willReturn(true);
		$localStorage->method('getLocalFile')
			->with($file->getInternalPath())
			->willReturn($file->getStorage()->getLocalFile($file->getInternalPath()));
		$mockLocalVideo = $this->createMock(File::class);
		$mockLocalVideo->method('getStorage')
			->willReturn($localStorage);
		$mockLocalVideo->method('getInternalPath')
			->willReturn($file->getInternalPath());
		$mockLocalVideo->method('getSize')
			->willReturn($file->getSize());
		$mockLocalVideo->method('fopen')
			->with('r')
			->willreturn($file->fopen('r'));
		$mockLocalVideo->method('getMetaData')
			->willreturn(['istest' => true]);
		$localPreview = $provider->getThumbnail($mockLocalVideo, $this->maxWidth, $this->maxHeight, $this->scalingUp);
		$this->assertNotEquals(false, $remotePreview);
		$this->assertEquals(true, $remotePreview->valid());
		$this->assertEquals($remotePreview->data(), $localPreview->data());
		return $remotePreview;
	}
}
