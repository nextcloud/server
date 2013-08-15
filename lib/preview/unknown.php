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
		$mimetype = $fileview->getMimeType($path);

		$path = \OC_Helper::mimetypeIcon($mimetype);
		$path = \OC::$SERVERROOT . substr($path, strlen(\OC::$WEBROOT));

		return new \OC_Image($path);
	}
}

\OC\Preview::registerProvider('OC\Preview\Unknown');