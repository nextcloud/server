<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

use Imagick;

if (extension_loaded('imagick')) {

	$checkImagick = new Imagick();

	if(count($checkImagick->queryFormats('PDF')) === 1) {

		class PDF extends Provider {

			public function getMimeType() {
				return '/application\/pdf/';
			}

			public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
				$tmpPath = $fileview->toTmpFile($path);

				//create imagick object from pdf
				try{
					$pdf = new Imagick($tmpPath . '[0]');
					$pdf->setImageFormat('jpg');
				} catch (\Exception $e) {
					\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
					return false;
				}

				unlink($tmpPath);

				//new image object
				$image = new \OC_Image($pdf);
				//check if image object is valid
				return $image->valid() ? $image : false;
			}

		}

		\OC\Preview::registerProvider('OC\Preview\PDF');
	}
}
