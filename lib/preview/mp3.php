<?php
/**
 * Copyrigjt (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class OC_Preview_MP3 extends OC_Preview_Provider{

	public function getMimeType(){
		return '/audio\/mpeg/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {	
		
	}

}

OC_Preview::registerProvider('OC_Preview_MP3');