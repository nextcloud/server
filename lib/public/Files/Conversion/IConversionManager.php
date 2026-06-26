<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Conversion;

use OCP\Files\File;

/**
 * @since 31.0.0
 */
interface IConversionManager {
	/**
	 * Determines whether or not conversion providers are available
	 *
	 * @since 31.0.0
	 */
	public function hasProviders(): bool;

	/**
	 * Gets all supported MIME type conversions
	 *
	 * @return list<ConversionMimeProvider>
	 *
	 * @since 31.0.0
	 */
	public function getProviders(): array;

	/**
	 * Convert a file to a given MIME type
	 *
	 * @param File $file The file to be converted
	 * @param string $targetMimeType The MIME type to convert the file to
	 * @param ?string $destination The destination to save the converted file
	 *
	 * @return string Path to the converted file
	 *
	 * @since 31.0.0
	 */
	public function convert(File $file, string $targetMimeType, ?string $destination = null): string;
}
