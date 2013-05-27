<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class OC_Preview_SVG extends OC_Preview_Provider{

	public function getMimeType(){
		return '/image\/svg\+xml/';
	}

	public function getThumbnail($path,$maxX,$maxY,$scalingup,$fileview) {
		$svg = new Imagick();
		$svg->setResolution($maxX, $maxY);
		$svg->readImageBlob('<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . $fileview->file_get_contents($path));
		$svg->setImageFormat('jpg');

		//new image object
		$image = new \OC_Image($svg);
		//check if image object is valid
		if (!$image->valid()) return false;

		return $image;
	}
}

OC_Preview::registerProvider('OC_Preview_SVG');