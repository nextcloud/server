<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\SimpleFS;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

/**
 * Interface ISimpleFolder
 *
 * @since 11.0.0
 */
interface ISimpleFolder {
	/**
	 * Get all the files in a folder
	 *
	 * @return ISimpleFile[]
	 * @since 11.0.0
	 */
	public function getDirectoryListing(): array;

	/**
	 * Check if a file with $name exists
	 *
	 * @param string $name
	 * @return bool
	 * @since 11.0.0
	 */
	public function fileExists(string $name): bool;

	/**
	 * Get the file named $name from the folder
	 *
	 * @throws NotFoundException
	 * @since 11.0.0
	 */
	public function getFile(string $name): ISimpleFile;

	/**
	 * Creates a new file with $name in the folder
	 *
	 * @param string|resource|null $content @since 19.0.0
	 * @throws NotPermittedException
	 * @since 11.0.0
	 */
	public function newFile(string $name, $content = null): ISimpleFile;

	/**
	 * Remove the folder and all the files in it
	 *
	 * @throws NotPermittedException
	 * @since 11.0.0
	 */
	public function delete(): void;

	/**
	 * Get the folder name
	 *
	 * @since 11.0.0
	 */
	public function getName(): string;

	/**
	 * Get the folder named $name from the current folder
	 *
	 * @throws NotFoundException
	 * @since 25.0.0
	 */
	public function getFolder(string $name): ISimpleFolder;

	/**
	 * Creates a new folder with $name in the current folder
	 *
	 * @param string|resource|null $content @since 19.0.0
	 * @throws NotPermittedException
	 * @since 25.0.0
	 */
	public function newFolder(string $path): ISimpleFolder;
}
