<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Preview;

use OCP\Files\File;
use OCP\IImage;
use OCP\Image;
use OCP\Server;
use Psr\Log\LoggerInterface;

class SVG extends ProviderV2 {
	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getMimeType(): string {
		return '/image\/svg\+xml/';
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		try {
			$content = stream_get_contents($file->fopen('r'));
			if ($content === false) {
				return null;
			}
			// check if the file can be processed by this provider
			if (!$this->canBeProcessed($content)) {
				return null;
			}

			$content = ltrim($content);
			if (substr($content, 0, 5) !== '<?xml') {
				$content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . $content;
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
			Server::get(LoggerInterface::class)->error(
				'File: ' . $file->getPath() . ' Imagick says:',
				[
					'exception' => $e,
					'app' => 'core',
				]
			);
			return null;
		}

		//new image object
		$image = new Image();
		$image->loadFromData((string)$svg);
		//check if image object is valid
		if ($image->valid()) {
			$image->scaleDownToFit($maxX, $maxY);

			return $image;
		}
		return null;
	}

	/**
	 * Check if the file can be processed by this provider,
	 * meaning the SVG is safe to be processed and does not contain any external references.
	 */
	protected function canBeProcessed(string $content): bool {
		// check for allowed encodings and convert if necessary
		$encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-2022-JP', 'ISO-8859-1'], true);
		if ($encoding === false) {
			return false;
		} elseif ($encoding !== 'UTF-8') {
			$content = mb_convert_encoding($content, 'UTF-8', $encoding);
		}

		// Strip all non-printable/control characters except newlines/tabs
		$content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
		if ($content === null) {
			return false;
		}

		// check for any potential external reference (include custom namespace prefix)
		if (preg_match('/["\s\']([a-z_][a-z0-9_.-]*:)?href\s*=/i', $content)) {
			return false;
		}
		return true;
	}
}
