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

		//check if file is encrypted
		if($fileInfo['encrypted'] === true) {
			$image = new \OC_Image(stream_get_contents($fileview->fopen($path, 'r')));
		}else{
			$image = new \OC_Image();
			$image->loadFromFile($fileview->getLocalFile($path));
		}

		return $image->valid() ? $image : false;
	}
}

\OC\Preview::registerProvider('OC\Preview\Image');