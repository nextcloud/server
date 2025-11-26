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
	public function getThumbnail(IProviderV2 $provider, File $file, int $maxWidth, int $maxHeight, bool $crop = false): IImage|false {
		if ($provider instanceof Imaginary) {
			return $provider->getCroppedThumbnail($file, $maxWidth, $maxHeight, $crop) ?? false;
		}
		return $provider->getThumbnail($file, $maxWidth, $maxHeight) ?? false;
	}

	public function getImage(ISimpleFile $maxPreview): IImage {
		$image = new OCPImage();
		$image->loadFromData($maxPreview->getContent());
		return $image;
	}

	/**
	 * @param \Closure|string $providerClosure (string is only authorized in unit tests)
	 */
	public function getProvider(\Closure|string $providerClosure): IProviderV2|false {
		return $providerClosure();
	}
}
