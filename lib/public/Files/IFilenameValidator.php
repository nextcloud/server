<?php
declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files;

/**
 * @since 30.0.0
 */
interface IFilenameValidator {

	/**
	 * It is recommended to use `\OCP\Files\Storage\IStorage::isFileValid` instead as this
	 * only checks if the filename is valid in general but not for a specific storage
	 * which might have additional naming rules.
	 * 
	 * @param string $filename The filename to check for validity
	 * @return bool
	 * @since 30.0.0
	 */
	public function isFilenameValid(string $filename): bool;

	/**
	 * Get a list of reserved filenames that must not be used
	 * This list should be checked case-insensitive, all names are returned lowercase.
	 * @return list<string>
	 * @since 30.0.0
	 */
	public function getForbiddenFilenames(): array;

	/**
	 * Get a list of characters forbidden in filenames
	 *
	 * Note: Characters in the range [0-31] are always forbidden,
	 * even if not inside this list (see OCP\Files\Storage\IStorage::verifyPath).
	 *
	 * @return list<string>
	 * @since 30.0.0
	 */
	public function getForbiddenCharacters(): array;

	/**
	 * Get a list of forbidden filename extensions that must not be used
	 * This list should be checked case-insensitive, all names are returned lowercase.
	 * @return list<string>
	 * @since 30.0.0
	 */
	public function getForbiddenExtensions(): array;
}
