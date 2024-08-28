<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Preview;

/**
 * Interface IProvider
 *
 * @since 8.1.0
 * @deprecated 17.0.0 use IProviderV2 instead
 */
interface IProvider {
	/**
	 * @return string Regex with the mimetypes that are supported by this provider
	 * @since 8.1.0
	 */
	public function getMimeType();

	/**
	 * Check if a preview can be generated for $path
	 *
	 * @param \OCP\Files\FileInfo $file
	 * @return bool
	 * @since 8.1.0
	 */
	public function isAvailable(\OCP\Files\FileInfo $file);

	/**
	 * get thumbnail for file at path $path
	 *
	 * @param string $path Path of file
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param bool $scalingup Disable/Enable upscaling of previews
	 * @param \OC\Files\View $fileview fileview object of user folder
	 * @return bool|\OCP\IImage false if no preview was generated
	 * @since 8.1.0
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview);
}
