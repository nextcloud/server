<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\SimpleFS;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

/**
 * Interface for representing and manipulating root of a simple folder structure in Nextcloud's virtual filesystem.
 *
 * Provides methods for listing, creating, and retrieving folders within the root.
 *
 * @since 11.0.0
 * @api
 */
interface ISimpleRoot {
	/**
	 * Get all folders at the root of the simple filesystem.
	 *
	 * @return ISimpleFolder[] Array of ISimpleFolder instances representing each folder.
	 * @throws NotFoundException If no folders are found.
	 * @throws \RuntimeException For general runtime errors.
	 * @since 11.0.0
	 */
	public function getDirectoryListing(): array;

	/**
	 * Get the folder named $name from the root of the simple filesystem.
	 *
	 * @param string $name The name of the folder to retrieve.
	 * @return ISimpleFolder The folder instance corresponding to the provided name.
	 * @throws NotFoundException If the folder with the given name does not exist.
	 * @throws \RuntimeException For general runtime errors.
	 * @since 11.0.0
	 */
	public function getFolder(string $name): ISimpleFolder;

	/**
	 * Creates a new folder named $name at the root of the simple filesystem.
	 *
	 * @param string $name The name of the new folder to create.
	 * @return ISimpleFolder The newly created folder instance.
	 * @throws NotPermittedException If folder creation is not permitted.
	 * @throws \RuntimeException For general runtime errors.
	 * @since 11.0.0
	 */
	public function newFolder(string $name): ISimpleFolder;
}
