<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class OC_Preview_Unknown extends OC_Preview_Provider{

	public function getMimeType(){
		return '/.*/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup,$fileview) {


		$mimetype = $this->fileview->getMimeType($file);
		return new \OC_Image();
	}
}

OC_Preview::registerProvider('OC_Preview_Unknown');