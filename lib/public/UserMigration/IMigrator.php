<?php

declare(strict_types=1);

/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

use OCP\IUser;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @since 24.0.0
 */
interface IMigrator {
	/**
	 * Export user data
	 *
	 * @throws UserMigrationException
	 * @since 24.0.0
	 */
	public function export(
		IUser $user,
		IExportDestination $exportDestination,
		OutputInterface $output
	): void;

	/**
	 * Import user data
	 *
	 * @throws UserMigrationException
	 * @since 24.0.0
	 */
	public function import(
		IUser $user,
		IImportSource $importSource,
		OutputInterface $output
	): void;

	/**
	 * Returns the unique ID
	 *
	 * @since 24.0.0
	 */
	public function getId(): string;

	/**
	 * Returns the display name
	 *
	 * @since 24.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * Returns the description
	 *
	 * @since 24.0.0
	 */
	public function getDescription(): string;

	/**
	 * Returns the version of the export format for this migrator
	 *
	 * @since 24.0.0
	 */
	public function getVersion(): int;

	/**
	 * Checks whether it is able to import a version of the export format for this migrator
	 * Use $importSource->getMigratorVersion($this->getId()) to get the version from the archive
	 *
	 * @since 24.0.0
	 */
	public function canImport(
		IImportSource $importSource
	): bool;
}
