<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Image;

class CDR extends ProviderV2 {
	#[\Override]
	public function getMimeType(): string {
		return '/application\/coreldraw/';
	}
	#[\Override]
	public function isAvailable(FileInfo $file): bool {
		return $file->getSize() > 0;
	}
	#[\Override]
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		if (!$this->isAvailable($file)) {
			return null;
		}

		$localFile = $this->getLocalFile($file);

		if ($localFile === false) {
			return null;
		}

		$data = $this->extractThumbnail($localFile);
		if ($data === null) {
			return null;
		}

		$image = new Image();
		$image->loadFromData($data);

		if (!$image->valid()) {
			return null;
		}

		$image->scaleDownToFit($maxX, $maxY);

		return $image;
	}
	/** * Extract ONLY thumbnail (no pages) */
	private function extractThumbnail(string $file): ?string {
		$zip = new \ZipArchive();

		if ($zip->open($file) !== true) {
			return null;
		}

		/**
		 * Newer CorelDRAW files store the embedded preview in previews/thumbnail.png.
		 *
		 * Older CorelDRAW files store the embedded BMP thumbnail in
		 * metadata/thumbnails/thumbnail.bmp.
		 */
		foreach ([
			'previews/thumbnail.png',
			'metadata/thumbnails/thumbnail.bmp',
		] as $thumbnail) {
			$idx = $zip->locateName($thumbnail);

			if ($idx === false) {
				continue;
			}

			$data = $zip->getFromIndex($idx);
			$zip->close();

			return $data === false ? null : $data;
		}

		$zip->close();

		return null;
	}
}
