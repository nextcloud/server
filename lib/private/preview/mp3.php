<?php
/**
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

class MP3 extends Provider {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/audio\/mpeg/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$getID3 = new \getID3();

		$tmpPath = $fileview->toTmpFile($path);

		$tags = $getID3->analyze($tmpPath);
		\getid3_lib::CopyTagsToComments($tags);
		if(isset($tags['id3v2']['APIC'][0]['data'])) {
			$picture = @$tags['id3v2']['APIC'][0]['data'];
			unlink($tmpPath);
			$image = new \OC_Image();
			$image->loadFromData($picture);

			if ($image->valid()) {
				$image->scaleDownToFit($maxX, $maxY);

				return $image;
			}
		}

		return $this->getNoCoverThumbnail();
	}

	/**
	 * Generates a default image when the file has no cover
	 *
	 * @return bool|\OCP\IImage false if the default image is missing or invalid
	 */
	private function getNoCoverThumbnail() {
		$icon = \OC::$SERVERROOT . '/core/img/filetypes/audio.png';

		if(!file_exists($icon)) {
			return false;
		}

		$image = new \OC_Image();
		$image->loadFromFile($icon);
		return $image->valid() ? $image : false;
	}

}
