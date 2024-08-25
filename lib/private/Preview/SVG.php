<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\IImage;
use Psr\Log\LoggerInterface;

class SVG extends ProviderV2 {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return '/image\/svg\+xml/';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		try {
			$content = stream_get_contents($file->fopen('r'));
			if (substr($content, 0, 5) !== '<?xml') {
				$content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . $content;
			}

			// Do not parse SVG files with references
			if (preg_match('/["\s](xlink:)?href\s*=/i', $content)) {
				return null;
			}

			$svg = new \Imagick();

			$svg->pingImageBlob($content);
			$mimeType = $svg->getImageMimeType();
			if (!preg_match($this->getMimeType(), $mimeType)) {
				throw new \Exception('File mime type does not match the preview provider: ' . $mimeType);
			}

			$svg->setBackgroundColor(new \ImagickPixel('transparent'));
			$svg->readImageBlob($content);
			$svg->setImageFormat('png32');
		} catch (\Exception $e) {
			\OC::$server->get(LoggerInterface::class)->error(
				'File: ' . $file->getPath() . ' Imagick says:',
				[
					'exception' => $e,
					'app' => 'core',
				]
			);
			return null;
		}

		//new image object
		$image = new \OCP\Image();
		$image->loadFromData((string)$svg);
		//check if image object is valid
		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);

			return $image;
		}
		return null;
	}
}
