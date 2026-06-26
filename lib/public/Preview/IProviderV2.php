<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;

/**
 * @since 17.0.0
 */
interface IProviderV2 {
	/**
	 * @return string Regex with the mimetypes that are supported by this provider
	 * @since 17.0.0
	 */
	public function getMimeType(): string;

	/**
	 * Check if a preview can be generated for $path
	 *
	 * @param FileInfo $file
	 * @return bool
	 * @since 17.0.0
	 */
	public function isAvailable(FileInfo $file): bool;

	/**
	 * get thumbnail for file at path $path
	 *
	 * @param File $file
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @return null|\OCP\IImage null if no preview was generated
	 * @since 17.0.0
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage;
}
