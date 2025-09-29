<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\IImage;

class Krita extends Bundled {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/application\/x-krita/';
	}


	/**
	 * @inheritDoc
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$image = $this->extractThumbnail($file, 'mergedimage.png');
		if (($image !== null) && $image->valid()) {
			return $image;
		}
		$image = $this->extractThumbnail($file, 'preview.png');
		if (($image !== null) && $image->valid()) {
			return $image;
		}
		return null;
	}
}
