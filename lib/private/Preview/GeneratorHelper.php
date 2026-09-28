<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IImage;
use OCP\Image as OCPImage;
use OCP\Preview\IProvider;
use OCP\Preview\IProviderV2;

/**
 * Very small wrapper class to make the generator fully unit testable
 */
class GeneratorHelper {
	/** @var IRootFolder */
	private $rootFolder;

	/** @var IConfig */
	private $config;

	public function __construct(IRootFolder $rootFolder, IConfig $config) {
		$this->rootFolder = $rootFolder;
		$this->config = $config;
	}

	/**
	 * @param IProviderV2 $provider
	 * @param File $file
	 * @param int $maxWidth
	 * @param int $maxHeight
	 *
	 * @return bool|IImage
	 */
	public function getThumbnail(IProviderV2 $provider, File $file, $maxWidth, $maxHeight, bool $crop = false) {
		if ($provider instanceof Imaginary) {
			return $provider->getCroppedThumbnail($file, $maxWidth, $maxHeight, $crop) ?? false;
		}
		return $provider->getThumbnail($file, $maxWidth, $maxHeight) ?? false;
	}

	/**
	 * @param ISimpleFile $maxPreview
	 * @return IImage
	 */
	public function getImage(ISimpleFile $maxPreview) {
		$image = new OCPImage();
		$image->loadFromData($maxPreview->getContent());
		return $image;
	}

	/**
	 * @param callable $providerClosure
	 * @return IProviderV2
	 */
	public function getProvider($providerClosure) {
		$provider = $providerClosure();
		if ($provider instanceof IProvider) {
			$provider = new ProviderV1Adapter($provider);
		}
		return $provider;
	}
}
