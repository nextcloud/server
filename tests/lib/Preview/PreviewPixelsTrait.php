<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Files\Node\File;
use OCP\Files\IRootFolder;
use OCP\Server;

/**
 * Checks that a provider gave back the picture, and not merely an image.
 *
 * The shared provider test asserts the size and the aspect ratio of what
 * came back, which a blank canvas of the right shape would satisfy just as
 * well as a decoded photo. These read the pixels.
 */
trait PreviewPixelsTrait {
	/** How far the average may drift, over encoding and scaling */
	private int $tolerance = 12;

	/**
	 * The average colour of an encoded image, sampled over a grid.
	 *
	 * @param string $bytes an encoded image
	 * @return array{float, float, float} the mean red, green and blue
	 */
	private function meanColour(string $bytes): array {
		$image = imagecreatefromstring($bytes);
		$this->assertNotFalse($image, 'the preview is not a readable image');

		$width = imagesx($image);
		$height = imagesy($image);
		$stepX = max(1, intdiv($width, 16));
		$stepY = max(1, intdiv($height, 16));

		$red = $green = $blue = 0;
		$samples = 0;
		for ($y = 0; $y < $height; $y += $stepY) {
			for ($x = 0; $x < $width; $x += $stepX) {
				$colour = imagecolorat($image, $x, $y);
				$red += ($colour >> 16) & 0xFF;
				$green += ($colour >> 8) & 0xFF;
				$blue += $colour & 0xFF;
				$samples++;
			}
		}
		imagedestroy($image);

		return [$red / $samples, $green / $samples, $blue / $samples];
	}

	/**
	 * Generate a preview and assert it carries the picture the file holds.
	 */
	protected function assertPreviewShowsThePicture(): void {
		$file = new File(Server::get(IRootFolder::class), $this->rootView, $this->imgPath);
		$preview = $this->provider->getThumbnail($file, 256, 256);

		$this->assertNotNull($preview, 'no preview was produced');
		$this->assertTrue($preview->valid());
		// Smaller than it was, so something actually resized it
		$this->assertLessThanOrEqual(256, $preview->width());
		$this->assertLessThanOrEqual(256, $preview->height());

		// Every fixture using this is a re-encode of testimage.jpg, so they
		// hold the same picture: a strong magenta whose average survives
		// the encoding and the scaling. A blank canvas misses by ~250.
		$expected = $this->meanColour(file_get_contents(\OC::$SERVERROOT . '/tests/data/testimage.jpg'));
		$actual = $this->meanColour($preview->data());

		foreach ([0 => 'red', 1 => 'green', 2 => 'blue'] as $channel => $name) {
			$this->assertEqualsWithDelta(
				$expected[$channel],
				$actual[$channel],
				$this->tolerance,
				"the preview's average $name is not the picture's",
			);
		}
	}
}
