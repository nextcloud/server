<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

function findBinaryPath($program) {
	exec('which ' . escapeshellarg($program) . ' 2> /dev/null', $output, $returnCode);
	if ($returnCode === 0 && count($output) > 0) {
		return escapeshellcmd($output[0]);
	}
	return null;
}

// movie preview is currently not supported on Windows
if (!\OC_Util::runningOnWindows()) {
	$isExecEnabled = !in_array('exec', explode(', ', ini_get('disable_functions')));
	$ffmpegBinary = null;
	$avconvBinary = null;

	if ($isExecEnabled) {
		$avconvBinary = findBinaryPath('avconv');
		if (!$avconvBinary) {
			$ffmpegBinary = findBinaryPath('ffmpeg');
		}
	}

	if($isExecEnabled && ( $avconvBinary || $ffmpegBinary )) {

		class Movie extends Provider {
			public static $avconvBinary;
			public static $ffmpegBinary;

			public function getMimeType() {
				return '/video\/.*/';
			}

			public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
				// TODO: use proc_open() and stream the source file ?
				$absPath = \OC_Helper::tmpFile();
				$tmpPath = \OC_Helper::tmpFile();

				$handle = $fileview->fopen($path, 'rb');

				$firstmb = stream_get_contents($handle, 1048576); //1024 * 1024 = 1048576
				file_put_contents($absPath, $firstmb);

				if (self::$avconvBinary) {
					$cmd = self::$avconvBinary . ' -an -y -ss 1'.
						' -i ' . escapeshellarg($absPath) .
						' -f mjpeg -vframes 1 ' . escapeshellarg($tmpPath) .
						' > /dev/null 2>&1';
				}
				else {
					$cmd = self::$ffmpegBinary . ' -y -ss 1' .
						' -i ' . escapeshellarg($absPath) .
						' -f mjpeg -vframes 1' .
						' -s ' . escapeshellarg($maxX) . 'x' . escapeshellarg($maxY) .
						' ' . escapeshellarg($tmpPath) .
						' > /dev/null 2>&1';
				}

				exec($cmd, $output, $returnCode);

				unlink($absPath);

				if ($returnCode === 0) {
					$image = new \OC_Image();
					$image->loadFromFile($tmpPath);
					unlink($tmpPath);
					return $image->valid() ? $image : false;
				}
				return false;
			}
		}

		// a bit hacky but didn't want to use subclasses
		Movie::$avconvBinary = $avconvBinary;
		Movie::$ffmpegBinary = $ffmpegBinary;

		\OC\Preview::registerProvider('OC\Preview\Movie');
	}
}

