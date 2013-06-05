<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

if(!is_null(shell_exec('ffmpeg -version'))) {

	class Movie extends Provider {

		public function getMimeType() {
			return '/video\/.*/';
		}

		public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
			//get fileinfo
			$fileinfo = $fileview->getFileInfo($path);

			$abspath = $fileview->toTmpFile($path);
			$tmppath = \OC_Helper::tmpFile();

			//$cmd = 'ffmpeg -y  -i ' . escapeshellarg($abspath) . ' -f mjpeg -vframes 1 -ss 1 -s ' . escapeshellarg($maxX) . 'x' . escapeshellarg($maxY) . ' ' . $tmppath;
			$cmd = 'ffmpeg -y  -i ' . escapeshellarg($abspath) . ' -f mjpeg -vframes 1 -ss 1 ' . escapeshellarg($tmppath);
			shell_exec($cmd);

			unlink($abspath);

			$image = new \OC_Image($tmppath);
			if (!$image->valid()) return false;

			unlink($tmppath);

			return $image;
		}
	}

	\OC\Preview::registerProvider('OC\Preview\Movie');
}