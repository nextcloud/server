<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files;

/**
 * Interface IMimeTypeLoader
 * @since 8.2.0
 *
 * Interface to load mimetypes
 **/
interface IMimeTypeLoader {
	/**
	 * Get a mimetype from its ID
	 *
	 * @param int $id
	 * @return string|null
	 * @since 8.2.0
	 */
	public function getMimetypeById(int $id): ?string;

	/**
	 * Get a mimetype ID, adding the mimetype to the DB if it does not exist
	 *
	 * @param string $mimetype
	 * @return int
	 * @since 8.2.0
	 */
	public function getId(string $mimetype): int;

	/**
	 * Test if a mimetype exists in the database
	 *
	 * @param string $mimetype
	 * @return bool
	 * @since 8.2.0
	 */
	public function exists(string $mimetype): bool;

	/**
	 * Clear all loaded mimetypes, allow for re-loading
	 *
	 * @since 8.2.0
	 */
	public function reset(): void;
}
