<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class OC_Preview_PDF extends OC_Preview_Provider{

	public function getMimeType(){
		return '/application\/pdf/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup,$fileview) {	
		//create imagick object from pdf
		$pdf = new imagick($fileview->getLocalFile($path) . '[0]');
		$pdf->setImageFormat('jpg');

		//new image object
		$image = new \OC_Image($pdf);
		//check if image object is valid
		if (!$image->valid()) return false;

		return $image;
	}
}

OC_Preview::registerProvider('OC_Preview_PDF');