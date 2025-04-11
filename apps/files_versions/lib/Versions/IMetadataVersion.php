<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

/**
 * This interface allows for just direct accessing of the metadata column JSON
 * @since 29.0.0
 */
interface IMetadataVersion {
	/**
	 * retrieves the all the metadata
	 *
	 * @return string[]
	 * @since 29.0.0
	 */
	public function getMetadata(): array;

	/**
	 * retrieves the metadata value from our $key param
	 *
	 * @param string $key the key for the json value of the metadata column
	 * @since 29.0.0
	 */
	public function getMetadataValue(string $key): ?string;
}
