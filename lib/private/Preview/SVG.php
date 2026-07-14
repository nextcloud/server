<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Olivier Paroz <github@oparoz.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
		$image->loadFromData((string) $svg);
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
