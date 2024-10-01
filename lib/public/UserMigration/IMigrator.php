<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		OutputInterface $output,
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
		OutputInterface $output,
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
		IImportSource $importSource,
	): bool;
}
