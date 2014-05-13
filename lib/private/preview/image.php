<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

class Image extends Provider {

	public function getMimeType() {
		return '/image\/.*/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		//get fileinfo
		$fileInfo = $fileview->getFileInfo($path);
		if(!$fileInfo) {
			return false;
		}

		$image = new \OC_Image();

		if($fileInfo['encrypted'] === true) {
			$fileName = $fileview->toTmpFile($path);
		} else {
			$fileName = $fileview->getLocalFile($path);
		}
		$image->loadFromFile($fileName);

		return $image->valid() ? $image : false;
	}

}

\OC\Preview::registerProvider('OC\Preview\Image');
