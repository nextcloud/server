<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

class SVG extends Provider {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/image\/svg\+xml/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		try{
			$svg = new \Imagick();
			$svg->setBackgroundColor(new \ImagickPixel('transparent'));

			$content = stream_get_contents($fileview->fopen($path, 'r'));
			if(substr($content, 0, 5) !== '<?xml') {
				$content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . $content;
			}

			// Do not parse SVG files with references
			if(stripos($content, 'xlink:href') !== false) {
				return false;
			}

			$svg->readImageBlob($content);
			$svg->setImageFormat('png32');
		} catch (\Exception $e) {
			\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
			return false;
		}

		//new image object
		$image = new \OC_Image();
		$image->loadFromData($svg);
		//check if image object is valid
		return $image->valid() ? $image : false;
	}
}
