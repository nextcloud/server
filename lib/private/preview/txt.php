<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

class TXT extends Provider {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/text\/plain/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function isAvailable($file) {
		return $file->getSize() > 5;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$content = $fileview->fopen($path, 'r');
		$content = stream_get_contents($content,3000);

		//don't create previews of empty text files
		if(trim($content) === '') {
			return false;
		}

		$lines = preg_split("/\r\n|\n|\r/", $content);

		$fontSize = 5; //5px
		$lineSize = ceil($fontSize * 1.25);

		$image = imagecreate($maxX, $maxY);
		imagecolorallocate($image, 255, 255, 255);
		$textColor = imagecolorallocate($image, 0, 0, 0);

		$fontFile  = __DIR__;
		$fontFile .= '/../../../core';
		$fontFile .= '/fonts/OpenSans-Regular.ttf';

		$canUseTTF = function_exists('imagettftext');

		foreach($lines as $index => $line) {
			$index = $index + 1;

			$x = (int) 1;
			$y = (int) ($index * $lineSize);

			if ($canUseTTF === true) {
				imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $line);
			} else {
				$y -= $fontSize;
				imagestring($image, 1, $x, $y, $line, $textColor);
			}

			if(($index * $lineSize) >= $maxY) {
				break;
			}
		}

		$image = new \OC_Image($image);

		return $image->valid() ? $image : false;
	}
}
