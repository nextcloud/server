<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Image;

class TXT extends ProviderV2 {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/text\/plain/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isAvailable(FileInfo $file): bool {
		return $file->getSize() > 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		if (!$this->isAvailable($file)) {
			return null;
		}

		$content = $file->fopen('r');

		if ($content === false) {
			return null;
		}

		$content = stream_get_contents($content, 3000);

		//don't create previews of empty text files
		if (trim($content) === '') {
			return null;
		}

		$lines = preg_split("/\r\n|\n|\r/", $content);

		// Define text size of text file preview
		$fontSize = $maxX ? (int)((1 / 32) * $maxX) : 5; //5px
		$lineSize = ceil($fontSize * 1.5);

		$image = imagecreate($maxX, $maxY);
		imagecolorallocate($image, 255, 255, 255);
		$textColor = imagecolorallocate($image, 0, 0, 0);

		$fontFile = __DIR__;
		$fontFile .= '/../../../core';
		$fontFile .= '/fonts/NotoSans-Regular.ttf';

		$canUseTTF = function_exists('imagettftext');

		foreach ($lines as $index => $line) {
			$index = $index + 1;

			$x = 1;
			$y = (int)($index * $lineSize);

			if ($canUseTTF === true) {
				imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $line);
			} else {
				$y -= $fontSize;
				imagestring($image, 1, $x, $y, $line, $textColor);
			}

			if (($index * $lineSize) >= $maxY) {
				break;
			}
		}

		$imageObject = new Image();
		$imageObject->setResource($image);

		return $imageObject->valid() ? $imageObject : null;
	}
}
