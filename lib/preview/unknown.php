<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

class Unknown extends Provider {

	public function getMimeType() {
		return '/.*/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		/*$mimetype = $fileview->getMimeType($path);
		$info = $fileview->getFileInfo($path);
		$name = array_key_exists('name', $info) ? $info['name'] : '';
		$size = array_key_exists('size', $info) ? $info['size'] : 0; 
		$isencrypted = array_key_exists('encrypted', $info) ? $info['encrypted'] : false;*/ // show little lock
		return new \OC_Image();
	}
}

\OC\Preview::registerProvider('OC\Preview\Unknown');
