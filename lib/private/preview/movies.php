<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

$isShellExecEnabled = !in_array('shell_exec', explode(', ', ini_get('disable_functions')));
$whichAVCONV = shell_exec('which avconv');
$isAVCONVAvailable = !empty($whichAVCONV);

if($isShellExecEnabled && $isAVCONVAvailable) {

	class Movie extends Provider {

		public function getMimeType() {
			return '/video\/.*/';
		}

		public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
			$absPath = \OC_Helper::tmpFile();
			$tmpPath = \OC_Helper::tmpFile();

			$handle = $fileview->fopen($path, 'rb');

			$firstmb = stream_get_contents($handle, 1048576); //1024 * 1024 = 1048576
			file_put_contents($absPath, $firstmb);

			//$cmd = 'ffmpeg -y  -i ' . escapeshellarg($absPath) . ' -f mjpeg -vframes 1 -ss 1 -s ' . escapeshellarg($maxX) . 'x' . escapeshellarg($maxY) . ' ' . $tmpPath;
			$cmd = 'avconv -an -y -ss 1 -i ' . escapeshellarg($absPath) . ' -f mjpeg -vframes 1 ' . escapeshellarg($tmpPath);
			
			shell_exec($cmd);

			$image = new \OC_Image($tmpPath);

			unlink($absPath);
			unlink($tmpPath);

			return $image->valid() ? $image : false;
		}
	}

	\OC\Preview::registerProvider('OC\Preview\Movie');
}