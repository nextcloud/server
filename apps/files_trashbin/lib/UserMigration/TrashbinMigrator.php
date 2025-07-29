<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\UserMigration;

use OCA\Files_Trashbin\AppInfo\Application;
use OCA\Files_Trashbin\Trashbin;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\ISizeEstimationMigrator;
use OCP\UserMigration\TMigratorBasicVersionHandling;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TrashbinMigrator implements IMigrator, ISizeEstimationMigrator {

	use TMigratorBasicVersionHandling;

	protected const PATH_FILES_FOLDER = Application::APP_ID . '/files';
	protected const PATH_LOCATIONS_FILE = Application::APP_ID . '/locations.json';

	public function __construct(
		protected IRootFolder $root,
		protected IDBConnection $dbc,
		protected IL10N $l10n,
	) {
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEstimatedExportSize(IUser $user): int|float {
		$uid = $user->getUID();

		try {
			$trashbinFolder = $this->root->get('/' . $uid . '/files_trashbin');
			if (!$trashbinFolder instanceof Folder) {
				return 0;
			}
			return ceil($trashbinFolder->getSize() / 1024);
		} catch (\Throwable $e) {
			return 0;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln('Exporting trashbin into ' . Application::APP_ID . '…');

		$uid = $user->getUID();

		try {
			$trashbinFolder = $this->root->get('/' . $uid . '/files_trashbin');
			if (!$trashbinFolder instanceof Folder) {
				throw new UserMigrationException('/' . $uid . '/files_trashbin is not a folder');
			}
			$output->writeln('Exporting trashbin files…');
			$exportDestination->copyFolder($trashbinFolder, static::PATH_FILES_FOLDER);
			$originalLocations = [];
			// TODO Export all extra data and bump migrator to v2
			foreach (Trashbin::getExtraData($uid) as $filename => $extraData) {
				$locationData = [];
				foreach ($extraData as $timestamp => ['location' => $location]) {
					$locationData[$timestamp] = $location;
				}
				$originalLocations[$filename] = $locationData;
			}
			$exportDestination->addFileContents(static::PATH_LOCATIONS_FILE, json_encode($originalLocations));
		} catch (NotFoundException $e) {
			$output->writeln('No trashbin to export…');
		} catch (\Throwable $e) {
			throw new UserMigrationException('Could not export trashbin: ' . $e->getMessage(), 0, $e);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		if ($importSource->getMigratorVersion($this->getId()) === null) {
			$output->writeln('No version for ' . static::class . ', skipping import…');
			return;
		}

		$output->writeln('Importing trashbin from ' . Application::APP_ID . '…');

		$uid = $user->getUID();

		if ($importSource->pathExists(static::PATH_FILES_FOLDER)) {
			try {
				$trashbinFolder = $this->root->get('/' . $uid . '/files_trashbin');
				if (!$trashbinFolder instanceof Folder) {
					throw new UserMigrationException('Could not import trashbin, /' . $uid . '/files_trashbin is not a folder');
				}
			} catch (NotFoundException $e) {
				$trashbinFolder = $this->root->newFolder('/' . $uid . '/files_trashbin');
			}
			$output->writeln('Importing trashbin files…');
			try {
				$importSource->copyToFolder($trashbinFolder, static::PATH_FILES_FOLDER);
			} catch (\Throwable $e) {
				throw new UserMigrationException('Could not import trashbin.', 0, $e);
			}
			$locations = json_decode($importSource->getFileContents(static::PATH_LOCATIONS_FILE), true, 512, JSON_THROW_ON_ERROR);
			$qb = $this->dbc->getQueryBuilder();
			$qb->insert('files_trash')
				->values([
					'id' => $qb->createParameter('id'),
					'timestamp' => $qb->createParameter('timestamp'),
					'location' => $qb->createParameter('location'),
					'user' => $qb->createNamedParameter($uid),
				]);
			foreach ($locations as $id => $fileLocations) {
				foreach ($fileLocations as $timestamp => $location) {
					$qb
						->setParameter('id', $id)
						->setParameter('timestamp', $timestamp)
						->setParameter('location', $location)
					;

					$qb->executeStatement();
				}
			}
		} else {
			$output->writeln('No trashbin to import…');
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getId(): string {
		return 'trashbin';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Deleted files');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDescription(): string {
		return $this->l10n->t('Deleted files and folders in the trash bin (may expire during export if you are low on storage space)');
	}
}
