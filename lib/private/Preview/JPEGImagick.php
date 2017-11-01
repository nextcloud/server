<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Jonathan Smith <jonsmith@mail.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Preview;

use Imagick;

class JPEGImagick extends Provider{

	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/image\/jpeg/';
	}


	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		\OCP\Util::writeLog('core', 'JPEGImagick->getThumbnail() called ' , \OCP\Util::DEBUG);

		$tmpPath = $fileview->toTmpFile($path);
		if (!$tmpPath) {
			return false;
		}

		try {
			$im_img = new Imagick();

			$im_img->readImage($tmpPath . '[0]');

			list($previewWidth, $previewHeight) = array_values($im_img->getImageGeometry());

			if ($previewWidth > $maxX || $previewHeight > $maxY) {
				$im_img->resizeImage($maxX, $maxY, imagick::FILTER_LANCZOS, 1, true);
			}

			$exif = $this->getExif($tmpPath);
			$orientation = $this->getOrientation($exif);
			$this->fixOrientation($im_img, $orientation);

		} catch (\Exception $e) {
			\OCP\Util::writeLog('core', 'ImageMagick says: ' . $e->getmessage(), \OCP\Util::ERROR);
			return false;
		}

		unlink($tmpPath);

		$gd_img = new \OC_Image();

		$gd_img->loadFromData($im_img);
		return $gd_img->valid() ? $gd_img : false;
	}


	private function getExif($filePath) {
		if (!is_callable('exif_read_data')) {
			\OCP\Util::writeLog('core', 'JPEGImagick->getExif() Exif module not enabled.', \OCP\Util::ERROR);
			return false;
		}
		if (is_null($filePath) || !is_readable($filePath)) {
			\OCP\Util::writeLog('core', 'JPEGImagick->getExif() No readable file path set. '. $filePath, \OCP\Util::ERROR);
			return false;
		}
		return @exif_read_data($filePath, 'IFD0');
	}

	private function getOrientation($exif) {
		if (!$exif) {
			return -1;
		}
		if (!isset($exif['Orientation'])) {
			return -1;
		}
		return $exif['Orientation'];
	}

	private function fixOrientation(Imagick $img, $o) {
		\OCP\Util::writeLog('core', 'JPEGImagick->fixOrientation() Orientation: ' . $o, \OCP\Util::DEBUG);
		$rotate = 0;
		$flip = false;
		switch ($o) {
			case -1:
				return false; //Nothing to fix
			case 1:
				$rotate = 0;
				break;
			case 2:
				$rotate = 0;
				$flip = true;
				break;
			case 3:
				$rotate = 180;
				break;
			case 4:
				$rotate = 180;
				$flip = true;
				break;
			case 5:
				$rotate = 270;
				$flip = true;
				break;
			case 6:
				$rotate = 90;
				break;
			case 7:
				$rotate = 90;
				$flip = true;
				break;
			case 8:
				$rotate = 270;
				break;
		}

		if($flip) {
			$img->flipImage();
		}
		if ($rotate) {
			if(  $img->rotateImage('#00000000', $rotate) ) {
				return true;
			} else {
				\OCP\Util::writeLog('core', 'JPEGImagick->fixOrientation() Error during orientation fixing', \OCP\Util::ERROR);
				return false;
			}
		}
		return false;
	}

}