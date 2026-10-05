<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IImage;
use OCP\Image as OCPImage;
use OCP\IPreview;
use OCP\Preview\IProviderV2;

/**
 * Very small wrapper class to make the generator fully unit testable
 * @psalm-import-type ProviderClosure from IPreview
 */
class GeneratorHelper {
	public function __construct(
		private readonly IMagickSupport $imagickSupport,
	) {
	}

	public function getThumbnail(IProviderV2 $provider, File $file, int $maxWidth, int $maxHeight, bool $crop = false): IImage|false {
		if ($provider instanceof Imaginary) {
			return $provider->getCroppedThumbnail($file, $maxWidth, $maxHeight, $crop) ?? false;
		}
		return $provider->getThumbnail($file, $maxWidth, $maxHeight) ?? false;
	}

	/**
	 * Whether small previews are faster to generate from the original than from the max preview
	 */
	public function resizesEfficiently(IProviderV2 $provider): bool {
		return $provider instanceof Imaginary;
	}

	public function getImage(ISimpleFile $maxPreview): IImage {
		$image = new OCPImage();
		$image->loadFromData($maxPreview->getContent());
		return $image;
	}

	/**
	 * Decode a JPEG preview at a reduced size of at least $minWidth x $minHeight,
	 * much faster than a full decode. Requires Imagick.
	 */
	public function getScaledImage(ISimpleFile $preview, int $minWidth, int $minHeight): ?IImage {
		if ($preview->getMimeType() !== 'image/jpeg' || !$this->imagickSupport->hasExtension()) {
			return null;
		}

		try {
			$imagick = new \Imagick();
			// libjpeg scales by multiples of 1/8, twice the size keeps enough detail
			$imagick->setOption('jpeg:size', ($minWidth * 2) . 'x' . ($minHeight * 2));
			$imagick->readImageBlob($preview->getContent());
			if ($imagick->getImageWidth() < $minWidth || $imagick->getImageHeight() < $minHeight) {
				return null;
			}
			// Re-encoded as JPEG, so derived previews keep the mimetype
			$imagick->setImageFormat('jpeg');
			$imagick->setImageCompressionQuality(95);
			$data = $imagick->getImageBlob();
			$imagick->clear();
		} catch (\ImagickException) {
			return null;
		}

		$image = new OCPImage();
		$image->loadFromData($data);
		return $image->valid() ? $image : null;
	}

	/**
	 * @param \Closure|string $providerClosure (string is only authorized in unit tests)
	 */
	public function getProvider(\Closure|string $providerClosure): IProviderV2|false {
		return $providerClosure();
	}
}
