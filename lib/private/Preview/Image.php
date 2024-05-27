<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\IImage;

abstract class Image extends ProviderV2 {
	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$maxSizeForImages = \OC::$server->getConfig()->getSystemValueInt('preview_max_filesize_image', 50);
		$size = $file->getSize();

		if ($maxSizeForImages !== -1 && $size > ($maxSizeForImages * 1024 * 1024)) {
			return null;
		}

		$image = new \OCP\Image();

		$fileName = $this->getLocalFile($file);

		$image->loadFromFile($fileName);
		$image->fixOrientation();

		$this->cleanTmpFiles();

		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);

			return $image;
		}
		return null;
	}
}
