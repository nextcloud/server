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
 * Public interface for preview providers.
 *
 * A preview provider generates a thumbnail image for supported files. That image
 * may be stored and reused by the preview system.
 *
 * @since 17.0.0
 */
interface IProviderV2 {
	/**
	 * Returns a regex matching the MIME types supported by this provider.
	 *
	 * @since 17.0.0
	 */
	public function getMimeType(): string;

	/**
	 * Returns whether this provider can currently generate a thumbnail for the given file.
	 *
	 * @since 17.0.0
	 */
	public function isAvailable(FileInfo $file): bool;

	/**
	 * Generates a thumbnail image for the given file.
	 *
	 * @param File $file
	 * @param int $maxX Maximum thumbnail width; the returned image may be smaller depending on its aspect ratio
	 * @param int $maxY Maximum thumbnail height; the returned image may be smaller depending on its aspect ratio
	 * @return IImage|null Null if no thumbnail could be generated
	 * @since 17.0.0
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage;
}
