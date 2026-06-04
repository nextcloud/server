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
	 * It is recommended to use `\OCP\Files\Storage\IStorage::isFileValid` instead as this
	 * only checks if the filename is valid in general but not for a specific storage
	 * which might have additional naming rules.
	 *
	 * This will validate a filename and throw an exception with details on error.
	 *
	 * @param string $filename The filename to check for validity
	 * @throws \OCP\Files\InvalidPathException or one of its child classes in case of an error
	 * @since 30.0.0
	 */
	public function validateFilename(string $filename): void;

	/**
	 * Sanitize a give filename to comply with admin setup naming constrains.
	 *
	 * If no sanitizing is needed the same name is returned.
	 *
	 * @param string $name The filename to sanitize
	 * @param null|string $charReplacement Character to use for replacing forbidden ones - by default underscore, dash or space is used if allowed.
	 * @throws \InvalidArgumentException if no character replacement was given (and the default could not be applied) or the replacement is not valid.
	 * @since 32.0.0
	 */
	public function sanitizeFilename(string $name, ?string $charReplacement = null): string;

}
