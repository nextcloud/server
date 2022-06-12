<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\UserMigration;

use OCP\Files\Folder;

/**
 * @since 24.0.0
 */
interface IImportSource {
	public const PATH_USER = 'user.json';

	/**
	 * Reads a file from the export
	 *
	 * @param string $path Full path to the file in the export archive.
	 * @return string The full content of the file.
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function getFileContents(string $path): string;

	/**
	 * Reads a file from the export as a stream
	 *
	 * @param string $path Full path to the file in the export archive.
	 * @return resource A stream resource to read from to get the file content.
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function getFileAsStream(string $path);

	/**
	 * List the files of a folder
	 *
	 * @param string $path Full path to the folder in the export archive.
	 * @return array The list of files.
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function getFolderListing(string $path): array;

	/**
	 * Test if a path exists, which may be a file or a folder
	 *
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function pathExists(string $path): bool;

	/**
	 * Copy files from the export to a Folder
	 *
	 * Folder $destination folder to copy into
	 * string $sourcePath path in the export archive
	 *
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function copyToFolder(Folder $destination, string $sourcePath): void;

	/**
	 * @return array<string,int> Migrators and their versions from the export archive.
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function getMigratorVersions(): array;

	/**
	 * @return ?int Version for this migrator from the export archive. Null means migrator missing.
	 * @throws UserMigrationException
	 * @param string $migrator Migrator id (as returned by IMigrator::getId)
	 *
	 * @since 24.0.0
	 */
	public function getMigratorVersion(string $migrator): ?int;

	/**
	 * Get original uid of the imported account
	 *
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function getOriginalUid(): string;

	/**
	 * Called after import is complete
	 *
	 * @throws UserMigrationException
	 *
	 * @since 24.0.0
	 */
	public function close(): void;
}
