<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files;

use OCP\Files\SimpleFS\ISimpleRoot;

/**
 * Interface for accessing app-specific data storage in Nextcloud.
 *
 * Implementations of this interface provide a virtual filesystem abstraction
 * for storing application data that is isolated from user files. Each Nextcloud
 * application can store, retrieve, and manage its own data within a dedicated
 * subfolder of the instance-wide appdata directory.
 *
 * This interface extends {@see OCP\Files\SimpleFS\ISimpleRoot}, allowing
 * applications to work with files and folders using simplified filesystem
 * operations.
 *
 * Typical use cases include caching, storing previews, thumbnails, configuration,
 * or other non-user-specific data for an app.
 *
 * @since 11.0.0
 */
interface IAppData extends ISimpleRoot {

	/**
	 * Returns a list of subfolders in the app-specific data folder.
	 *
	 * Unlike ISimpleRoot, this method only lists folders within the current application's
	 * data storage area, not user directories or files. The returned folders are isolated
	 * from other applications. Files within the appdata folder are not included.
	 *
	 * @return ISimpleFolder[] List of subfolders in the appdata directory.
	 */
	public function getDirectoryListing(): array;

	/**
	 * Retrieves a named subfolder from the app-specific data storage.
	 *
	 * The folder is always relative to the application's own appdata directory, and is
	 * isolated from other apps and user files. If the folder does not exist, an exception
	 * is thrown.
	 *
	 * @param string $name Name of the subfolder to retrieve.
	 * @return ISimpleFolder The requested folder.
	 * @throws \OCP\Files\NotFoundException If the folder does not exist.
	 */
	public function getFolder(string $name): ISimpleFolder;

	/**
	 * Creates a new subfolder within the app-specific data directory.
	 *
	 * The folder is created inside the application's appdata storage and is not visible
	 * to other apps or users. If a folder with the given name already exists, an exception
	 * may be thrown.
	 *
	 * @param string $name Name of the folder to create.
	 * @return ISimpleFolder The created folder.
	 * @throws \OCP\Files\NotPermittedException If the folder cannot be created.
	 */
	public function newFolder(string $name): ISimpleFolder;
}
