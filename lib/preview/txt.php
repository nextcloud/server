<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

class TXT extends Provider {

	public function getMimeType() {
		return '/text\/.*/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$content = $fileview->fopen($path, 'r');
		$content = stream_get_contents($content);

		$lines = preg_split("/\r\n|\n|\r/", $content);
		$numoflines = count($lines);

		$fontsize = 5; //5px
		$linesize = ceil($fontsize * 1.25);

		$image = imagecreate($maxX, $maxY);
		$imagecolor = imagecolorallocate($image, 255, 255, 255);
		$textcolor = imagecolorallocate($image, 0, 0, 0);

		foreach($lines as $index => $line) {
			$index = $index + 1;

			$x = (int) 1;
			$y = (int) ($index * $linesize) - $fontsize;

			imagestring($image, 1, $x, $y, $line, $textcolor);

			if(($index * $linesize) >= $maxY) {
				break;
			}
		}

		$image = new \OC_Image($image);

		return $image->valid() ? $image : false;
	}
}

\OC\PreviewManager::registerProvider('OC\Preview\TXT');

class PHP extends TXT {

	public function getMimeType() {
		return '/application\/x-php/';
	}

}

\OC\PreviewManager::registerProvider('OC\Preview\PHP');

class JavaScript extends TXT {

	public function getMimeType() {
		return '/application\/javascript/';
	}

}

\OC\PreviewManager::registerProvider('OC\Preview\JavaScript');