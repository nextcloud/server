<?php
/**
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
			$absPath = \OC::$server->getTempManager()->getTemporaryFile();

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
	 * @return bool|\OCP\IImage
	 */
	private function generateThumbNail($maxX, $maxY, $absPath, $second) {
		$tmpPath = \OC::$server->getTempManager()->getTemporaryFile();

		if (self::$avconvBinary) {
			$cmd = self::$avconvBinary . ' -y -ss ' . escapeshellarg($second) .
				' -i ' . escapeshellarg($absPath) .
				' -an -f mjpeg -vframes 1 -vsync 1 ' . escapeshellarg($tmpPath) .
				' > /dev/null 2>&1';
		} else {
			$cmd = self::$ffmpegBinary . ' -y -ss ' . escapeshellarg($second) .
				' -i ' . escapeshellarg($absPath) .
				' -f mjpeg -vframes 1' .
				' ' . escapeshellarg($tmpPath) .
				' > /dev/null 2>&1';
		}

		exec($cmd, $output, $returnCode);

		if ($returnCode === 0) {
			$image = new \OC_Image();
			$image->loadFromFile($tmpPath);
			unlink($tmpPath);
			if ($image->valid()) {
				$image->scaleDownToFit($maxX, $maxY);

				return $image;
			}
		}
		unlink($tmpPath);
		return false;
	}
}
