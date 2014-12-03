<?php
/**
 * Copyright (c) 2013 Thomas Müller thomas.mueller@tmit.eu
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */
namespace OC;

use OCP\image;
use OCP\IPreview;

class PreviewManager implements IPreview {
	/**
	 * return a preview of a file
	 *
	 * @param string $file The path to the file where you want a thumbnail from
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param boolean $scaleUp Scale smaller images up to the thumbnail size or not. Might look ugly
	 * @return \OCP\Image
	 */
	function createPreview($file, $maxX = 100, $maxY = 75, $scaleUp = false) {
		$preview = new \OC\Preview('', '/', $file, $maxX, $maxY, $scaleUp);
		return $preview->getPreview();
	}

	/**
	 * returns true if the passed mime type is supported
	 *
	 * @param string $mimeType
	 * @return boolean
	 */
	function isMimeSupported($mimeType = '*') {
		return \OC\Preview::isMimeSupported($mimeType);
	}

	/**
	 * Check if a preview can be generated for a file
	 *
	 * @param \OC\Files\FileInfo $file
	 * @return bool
	 */
	function isAvailable($file) {
		return \OC\Preview::isAvailable($file);
	}
}
