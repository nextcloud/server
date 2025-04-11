<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Conversion;

use OCP\Files\File;

/**
 * This interface is implemented by apps that provide
 * a file conversion provider
 *
 * @since 31.0.0
 */
interface IConversionProvider {
	/**
	 * Get an array of MIME type tuples this conversion provider supports
	 *
	 * @return list<ConversionMimeProvider>
	 *
	 * @since 31.0.0
	 */
	public function getSupportedMimeTypes(): array;

	/**
	 * Convert a file to a given MIME type
	 *
	 * @param File $file The file to be converted
	 * @param string $targetMimeType The MIME type to convert the file to
	 *
	 * @return resource|string Resource or string content of the file
	 *
	 * @since 31.0.0
	 */
	public function convertFile(File $file, string $targetMimeType): mixed;
}
