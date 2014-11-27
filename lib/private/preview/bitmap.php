<?php
/**
 * Copyright (c) 2013-2014 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

use Imagick;

	class Bitmap extends Provider {

		public function getMimeType() {
			return null;
		}

		public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
			$tmpPath = $fileview->toTmpFile($path);

			//create imagick object from bitmap or vector file
			try {
				// Layer 0 contains either the bitmap or
				// a flat representation of all vector layers
				$bp = new Imagick($tmpPath . '[0]');

				$bp->setImageFormat('png');
			} catch (\Exception $e) {
				\OC_Log::write('core', $e->getmessage(), \OC_Log::ERROR);
				return false;
			}

			unlink($tmpPath);

			//new bitmap image object
			$image = new \OC_Image($bp);
			//check if image object is valid
			return $image->valid() ? $image : false;
		}
	}

		//.pdf
		class PDF extends Bitmap {

			public function getMimeType() {
				return '/application\/pdf/';
			}

		}

		//.tiff
		class TIFF extends Bitmap {

			public function getMimeType() {
				return '/image\/tiff/';
			}

		}

		//.ai
		class Illustrator extends Bitmap {

			public function getMimeType() {
				return '/application\/illustrator/';
			}

		}

		//.eps
		class Postscript extends Bitmap {

			public function getMimeType() {
				return '/application\/postscript/';
			}

		}

		//.psd
		class Photoshop extends Bitmap {

			public function getMimeType() {
				return '/application\/x-photoshop/';
			}

		}
