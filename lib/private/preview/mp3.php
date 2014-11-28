<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
			return $image->valid() ? $image : $this->getNoCoverThumbnail();
		}

		return $this->getNoCoverThumbnail();
	}

	/**
	 * Generates a default image when the file has no cover
	 *
	 * @return false|\OC_Image	False if the default image is missing or invalid,
	 *							otherwise the image is returned as \OC_Image
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
