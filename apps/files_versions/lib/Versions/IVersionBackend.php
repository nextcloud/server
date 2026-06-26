<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Versions;

use OC\Files\Node\Node;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IUser;

/**
 * @since 15.0.0
 */
interface IVersionBackend {
	/**
	 * Whether or not this version backend should be used for a storage
	 *
	 * If false is returned then the next applicable backend will be used
	 *
	 * @param IStorage $storage
	 * @return bool
	 * @since 17.0.0
	 */
	public function useBackendForStorage(IStorage $storage): bool;

	/**
	 * Get all versions for a file
	 *
	 * @param IUser $user
	 * @param FileInfo $file
	 * @return IVersion[]
	 * @since 15.0.0
	 */
	public function getVersionsForFile(IUser $user, FileInfo $file): array;

	/**
	 * Create a new version for a file
	 *
	 * @param IUser $user
	 * @param FileInfo $file
	 * @since 15.0.0
	 */
	public function createVersion(IUser $user, FileInfo $file);

	/**
	 * Restore this version
	 *
	 * @param IVersion $version
	 * @since 15.0.0
	 */
	public function rollback(IVersion $version);

	/**
	 * Open the file for reading
	 *
	 * @param IVersion $version
	 * @return resource|false
	 * @throws NotFoundException
	 * @since 15.0.0
	 */
	public function read(IVersion $version);

	/**
	 * Get the preview for a specific version of a file
	 *
	 * @param IUser $user
	 * @param FileInfo $sourceFile
	 * @param int|string $revision
	 *
	 * @return File
	 *
	 * @since 15.0.0
	 */
	public function getVersionFile(IUser $user, FileInfo $sourceFile, $revision): File;

	/**
	 * Get the revision for a node
	 *
	 * @since 32.0.0
	 */
	public function getRevision(Node $node): int;
}
