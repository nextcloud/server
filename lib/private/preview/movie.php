<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

class Movie extends Provider {
	public static $avconvBinary;
	public static $ffmpegBinary;

	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/video\/.*/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		// TODO: use proc_open() and stream the source file ?

		$fileInfo = $fileview->getFileInfo($path);
		$useFileDirectly = (!$fileInfo->isEncrypted() && !$fileInfo->isMounted());

		if ($useFileDirectly) {
			$absPath = $fileview->getLocalFile($path);
		} else {
			$absPath = \OC_Helper::tmpFile();

			$handle = $fileview->fopen($path, 'rb');

			// we better use 5MB (1024 * 1024 * 5 = 5242880) instead of 1MB.
			// in some cases 1MB was no enough to generate thumbnail
			$firstmb = stream_get_contents($handle, 5242880);
			file_put_contents($absPath, $firstmb);
		}

		$result = $this->generateThumbNail($maxX, $maxY, $absPath, 5);
		if ($result === false) {
			$result = $this->generateThumbNail($maxX, $maxY, $absPath, 1);
			if ($result === false) {
				$result = $this->generateThumbNail($maxX, $maxY, $absPath, 0);
			}
		}

		if (!$useFileDirectly) {
			unlink($absPath);
		}

		return $result;
	}

	/**
	 * @param int $maxX
	 * @param int $maxY
	 * @param string $absPath
	 * @param int $second
	 * @return bool|\OC_Image
	 */
	private function generateThumbNail($maxX, $maxY, $absPath, $second) {
		$tmpPath = \OC_Helper::tmpFile();

		if (self::$avconvBinary) {
			$cmd = self::$avconvBinary . ' -an -y -ss ' . escapeshellarg($second) .
				' -i ' . escapeshellarg($absPath) .
				' -f mjpeg -vframes 1 -vsync 1 ' . escapeshellarg($tmpPath) .
				' > /dev/null 2>&1';
		} else {
			$cmd = self::$ffmpegBinary . ' -y -ss ' . escapeshellarg($second) .
				' -i ' . escapeshellarg($absPath) .
				' -f mjpeg -vframes 1' .
				' -s ' . escapeshellarg($maxX) . 'x' . escapeshellarg($maxY) .
				' ' . escapeshellarg($tmpPath) .
				' > /dev/null 2>&1';
		}

		exec($cmd, $output, $returnCode);

		if ($returnCode === 0) {
			$image = new \OC_Image();
			$image->loadFromFile($tmpPath);
			unlink($tmpPath);
			return $image->valid() ? $image : false;
		}
		unlink($tmpPath);
		return false;
	}
}
