<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyrigjt (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class OC_Preview_Unknown extends OC_Preview_Provider{

	public function getMimeType(){
		return '/.*/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup,$fileview) {	
		// check if GD is installed
		if(!extension_loaded('gd') || !function_exists('gd_info')) {
			OC_Log::write('preview', __METHOD__.'(): GD module not installed', OC_Log::ERROR);
			return false;
		}
	
		// create a white image
		$image = imagecreatetruecolor($maxX, $maxY);
		$color = imagecolorallocate($image, 255, 255, 255);
		imagefill($image, 0, 0, $color);
	
		// output the image
		imagepng($image);
		imagedestroy($image);
	}

}

OC_Preview::registerProvider('OC_Preview_Unknown');