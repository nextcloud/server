<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

if (extension_loaded('imagick')) {

	class PDF extends Provider{

		public function getMimeType() {
			return '/application\/pdf/';
		}

		public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {	
			$tmppath = $fileview->toTmpFile($path);

			//create imagick object from pdf
			$pdf = new \imagick($tmppath . '[0]');
			$pdf->setImageFormat('jpg');

			unlink($tmppath);

			//new image object
			$image = new \OC_Image($pdf);
			//check if image object is valid
			if (!$image->valid()) return false;

			return $image;
		}
	}

	\OC\Preview::registerProvider('OC\Preview\PDF');
}
