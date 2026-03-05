<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\IConfig;
use OCP\IImage;
use OCP\Server;
use Psr\Log\LoggerInterface;

abstract class Image extends ProviderV2 {
	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$maxSizeForImages = Server::get(IConfig::class)->getSystemValueInt('preview_max_filesize_image', 50);
		$size = $file->getSize();

		if ($maxSizeForImages !== -1 && $size > ($maxSizeForImages * 1024 * 1024)) {
			return null;
		}

		$image = new \OCP\Image();

		$fileName = $this->getLocalFile($file);
		if ($fileName === false) {
			Server::get(LoggerInterface::class)->error(
				'Failed to get local file to generate thumbnail for: ' . $file->getPath(),
				['app' => 'core']
			);
			return null;
		}

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
