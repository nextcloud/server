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
			$abspath = \OC_Helper::tmpFile();
			$tmppath = \OC_Helper::tmpFile();

			$handle = $fileview->fopen($path, 'rb');

			$firstmb = stream_get_contents($handle, 1048576); //1024 * 1024 = 1048576
			file_put_contents($abspath, $firstmb);

			//$cmd = 'ffmpeg -y  -i ' . escapeshellarg($abspath) . ' -f mjpeg -vframes 1 -ss 1 -s ' . escapeshellarg($maxX) . 'x' . escapeshellarg($maxY) . ' ' . $tmppath;
			$cmd = 'ffmpeg -an -y  -i ' . escapeshellarg($abspath) . ' -f mjpeg -vframes 1 -ss 1 ' . escapeshellarg($tmppath);
			
			shell_exec($cmd);

			$image = new \OC_Image($tmppath);

			unlink($abspath);
			unlink($tmppath);

			return $image->valid() ? $image : false;
		}
	}

	\OC\PreviewManager::registerProvider('OC\Preview\Movie');
}