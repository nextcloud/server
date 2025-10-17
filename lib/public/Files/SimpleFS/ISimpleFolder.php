<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\SimpleFS;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

/**
 * Interface for representing and manipulating simple folders in Nextcloud's virtual filesystem.
 *
 * Provides methods for listing, creating, retrieving, and deleting folders.
 *
 * @since 11.0.0
 * @api
 */
interface ISimpleFolder {
	/**
	 * Get all the files in this folder.
	 *
	 * @return ISimpleFile[] Array of files contained in the folder.
	 * @since 11.0.0
	 */
	public function getDirectoryListing(): array;

    /**
     * Check if a file with the given name exists in this folder.
     *
     * @param string $name Name of the file to check.
     * @return bool True if the file exists, false otherwise.
     * @since 11.0.0
     */
	public function fileExists(string $name): bool;

    /**
     * Get the file named $name from this folder.
	 *
     * @param string $name Name of the file to retrieve.
     * @return ISimpleFile The file object.
     * @throws NotFoundException If the file does not exist.
	 * @throws NotPermittedException If access to the file is not permitted. 
     * @since 11.0.0
     */
	public function getFile(string $name): ISimpleFile;

    /**
     * Creates a new file with the given name in this folder.
     *
     * @param string $name Name of the new file.
     * @param string|resource|null $content Initial content for the file (optional).
     * @return ISimpleFile The newly created file object.
     * @throws NotPermittedException If file creation is not permitted.
     * @since 11.0.0
     */
	public function newFile(string $name, $content = null): ISimpleFile;

    /**
     * Remove this folder and all its contents.
     *
     * @return void
     * @throws NotPermittedException If deletion is not permitted.
     * @since 11.0.0
     */
	public function delete(): void;

    /**
     * Get the name of this folder.
     *
     * @return string The folder name.
     * @since 11.0.0
     */
	public function getName(): string;

    /**
     * Get the subfolder named $name from this folder.
     *
     * @param string $name Name of the subfolder to retrieve.
     * @return ISimpleFolder The subfolder object.
     * @throws NotFoundException If the subfolder does not exist.
     * @since 25.0.0
     */
	public function getFolder(string $name): ISimpleFolder;

    /**
     * Creates a new subfolder with the given path in this folder.
     *
     * @param string $path Path (name) of the new subfolder.
     * @return ISimpleFolder The newly created subfolder object.
     * @throws NotPermittedException If folder creation is not permitted.
     * @since 25.0.0
     */
	// TODO: rename $path -> $name for consistency (already the case in parallel interfaces such as ISimpleRoot).
	// Alternatively/related, clarify whether nested paths/names are officially accepted here (versus for getFolder() where they're not).
	// Same technically applies to some other methods with different behavior, such as fileExists().
	public function newFolder(string $path): ISimpleFolder;
}
