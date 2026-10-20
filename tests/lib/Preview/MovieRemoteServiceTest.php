<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Files\Node\File;
use OC\Preview\MovieRemote;
use OCP\Files\IRootFolder;
use OCP\Server;

/**
 * Runs against the service from build/ffmpeg-preview-service, whose url and key
 * are passed in the FFMPEG_PREVIEW_URL and FFMPEG_PREVIEW_KEY environment variables
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
#[\PHPUnit\Framework\Attributes\Group('FFmpegPreviewService')]
class MovieRemoteServiceTest extends Provider {
	protected string $fileName = 'testimage.mp4';
	protected int $width = 560;
	protected int $height = 320;

	#[\Override]
	protected function setUp(): void {
		$url = getenv('FFMPEG_PREVIEW_URL');
		if (!is_string($url) || $url === '') {
			$this->markTestSkipped('FFMPEG_PREVIEW_URL is not set');
		}

		parent::setUp();

		$this->overwriteSystemConfig('preview_ffmpeg_url', $url);
		$this->overwriteSystemConfig('preview_ffmpeg_key', (string)getenv('FFMPEG_PREVIEW_KEY'));
		$this->imgPath = $this->prepareTestFile($this->fileName, \OC::$SERVERROOT . '/tests/data/' . $this->fileName);
		$this->provider = new MovieRemote();
	}

	public function testWrongKey(): void {
		$this->overwriteSystemConfig('preview_ffmpeg_key', 'wrong-key');
		$file = new File(Server::get(IRootFolder::class), $this->rootView, $this->imgPath);

		$this->assertNull($this->provider->getThumbnail($file, 256, 256));
	}

	public function testNoFrame(): void {
		$path = $this->prepareTestFile('not-a-video.mp4', \OC::$SERVERROOT . '/tests/data/lorem.txt');
		$file = new File(Server::get(IRootFolder::class), $this->rootView, $path);

		$this->assertNull($this->provider->getThumbnail($file, 256, 256));
	}

	public function testNoUpscaling(): void {
		$file = new File(Server::get(IRootFolder::class), $this->rootView, $this->imgPath);
		$preview = $this->provider->getThumbnail($file, 4096, 4096);

		$this->assertNotNull($preview);
		$this->assertSame($this->width, $preview->width());
		$this->assertSame($this->height, $preview->height());
	}
}
