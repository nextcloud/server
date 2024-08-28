<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\UserMigration;

use OCP\Files\Folder;

/**
 * @since 24.0.0
 */
interface IExportDestination {
	/**
	 * Adds a file to the export
	 *
	 * @param string $path Full path to the file in the export archive. Parent directories will be created if needed.
	 * @param string $content The full content of the file.
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function addFileContents(string $path, string $content): void;

	/**
	 * Adds a file to the export as a stream
	 *
	 * @param string $path Full path to the file in the export archive. Parent directories will be created if needed.
	 * @param resource $stream A stream resource to read from to get the file content.
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function addFileAsStream(string $path, $stream): void;

	/**
	 * Copy a folder to the export
	 *
	 * @param Folder $folder folder to copy to the export archive.
	 * @param string $destinationPath Full path to the folder in the export archive. Parent directories will be created if needed.
	 * @param ?callable(\OCP\Files\Node):bool $nodeFilter Callback to filter nodes to copy
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function copyFolder(Folder $folder, string $destinationPath, ?callable $nodeFilter = null): void;

	/**
	 * @param array<string,int> $versions Migrators and their versions.
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function setMigratorVersions(array $versions): void;

	/**
	 * Called after export is complete
	 *
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function close(): void;
}
