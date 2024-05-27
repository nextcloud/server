<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

//.odt, .ott, .oth, .odm, .odg, .otg, .odp, .otp, .ods, .ots, .odc, .odf, .odb, .odi, .oxt
use OCP\Files\File;
use OCP\IImage;

class OpenDocument extends Bundled {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/application\/vnd.oasis.opendocument.*/';
	}


	/**
	 * @inheritDoc
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$image = $this->extractThumbnail($file, 'Thumbnails/thumbnail.png');
		if (($image !== null) && $image->valid()) {
			return $image;
		}
		return null;
	}
}
