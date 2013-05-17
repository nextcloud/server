<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('getid3/getid3.php');

class OC_Preview_MP3 extends OC_Preview_Provider{

	public function getMimeType(){
		return '/audio\/mpeg/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$getID3 = new getID3(); 
		//Todo - add stream support
		$tags = $getID3->analyze($fileview->getLocalFile($path)); 
		getid3_lib::CopyTagsToComments($tags); 
		$picture = @$tags['id3v2']['APIC'][0]['data'];
		
		$image = new \OC_Image($picture);
		if (!$image->valid()) return $this->getNoCoverThumbnail($maxX, $maxY);
		
		return $image;
	}

	public function getNoCoverThumbnail($maxX, $maxY){
		$image = new \OC_Image();
		return $image;
	}

}

OC_Preview::registerProvider('OC_Preview_MP3');