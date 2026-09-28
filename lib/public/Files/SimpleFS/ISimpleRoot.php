<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\SimpleFS;

use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;

/**
 * Interface ISimpleRoot
 *
 * @since 11.0.0
 */
interface ISimpleRoot {
	/**
	 * Get the folder with name $name
	 *
	 * @throws NotFoundException
	 * @throws \RuntimeException
	 * @since 11.0.0
	 */
	public function getFolder(string $name): ISimpleFolder;

	/**
	 * Get all the Folders
	 *
	 * @return ISimpleFolder[]
	 * @throws NotFoundException
	 * @throws \RuntimeException
	 * @since 11.0.0
	 */
	public function getDirectoryListing(): array;

	/**
	 * Create a new folder named $name
	 *
	 * @throws NotPermittedException
	 * @throws \RuntimeException
	 * @since 11.0.0
	 */
	public function newFolder(string $name): ISimpleFolder;
}
