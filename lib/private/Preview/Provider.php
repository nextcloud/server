<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Preview;

use OCP\Preview\IProvider;

abstract class Provider implements IProvider {
	private $options;

	/**
	 * Constructor
	 *
	 * @param array $options
	 */
	public function __construct(array $options = []) {
		$this->options = $options;
	}

	/**
	 * @return string Regex with the mimetypes that are supported by this provider
	 */
	abstract public function getMimeType();

	/**
	 * Check if a preview can be generated for $path
	 *
	 * @param \OCP\Files\FileInfo $file
	 * @return bool
	 */
	public function isAvailable(\OCP\Files\FileInfo $file) {
		return true;
	}

	/**
	 * Generates thumbnail which fits in $maxX and $maxY and keeps the aspect ratio, for file at path $path
	 *
	 * @param string $path Path of file
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param bool $scalingup Disable/Enable upscaling of previews
	 * @param \OC\Files\View $fileview fileview object of user folder
	 * @return bool|\OCP\IImage false if no preview was generated
	 */
	abstract public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview);
}
