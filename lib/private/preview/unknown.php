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

		if (extension_loaded('imagick')) {
				$path = substr_replace($path, 'png', -3);
				$content = file_get_contents($path);

				$svg = new \Imagick();
				$svg->setBackgroundColor(new \ImagickPixel('transparent'));
				$svg->readImageBlob($content);
				$svg->setImageFormat('png32');

				$image = new \OC_Image();
				$image->loadFromData($svg);
		} else {
			$image = new \OC_Image($path);
		}

		return $image;
	}
}

\OC\Preview::registerProvider('OC\Preview\Unknown');