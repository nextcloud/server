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

namespace OCA\Files_Trashbin\UserMigration;

use OCA\Files_Trashbin\AppInfo\Application;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use OCP\UserMigration\TMigratorBasicVersionHandling;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;
use OCP\IDBConnection;

class TrashbinMigrator implements IMigrator {

	use TMigratorBasicVersionHandling;

	protected const PATH_FILES_FOLDER = Application::APP_ID.'/files';
	protected const PATH_LOCATIONS_FILE = Application::APP_ID.'/locations.json';

	protected IRootFolder $root;

	protected IDBConnection $dbc;

	public function __construct(
		IRootFolder $rootFolder,
		IDBConnection $dbc
	) {
		$this->root = $rootFolder;
		$this->dbc = $dbc;
	}

	/**
	 * {@inheritDoc}
	 */
	public function export(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln('Exporting trashbin into ' . Application::APP_ID . '…');

		$uid = $user->getUID();

		try {
			$trashbinFolder = $this->root->get('/'.$uid.'/files_trashbin');
			$output->writeln("Exporting trashbin…");
			if ($exportDestination->copyFolder($trashbinFolder, static::PATH_FILES_FOLDER) === false) {
				throw new UserMigrationException("Could not export trashbin.");
			}
			$originalLocations = \OCA\Files_Trashbin\Trashbin::getLocations($uid);
			if ($exportDestination->addFileContents(static::PATH_LOCATIONS_FILE, json_encode($originalLocations)) === false) {
				throw new UserMigrationException("Could not export trashbin.");
			}
		} catch (NotFoundException $e) {
			$output->writeln("No trashbin to export…");
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function import(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		if ($importSource->getMigratorVersion(static::class) === null) {
			$output->writeln('No version for ' . static::class . ', skipping import…');
			return;
		}

		$output->writeln('Importing trashbin from ' . Application::APP_ID . '…');

		$uid = $user->getUID();

		if ($importSource->pathExists(static::PATH_FILES_FOLDER)) {
			try {
				$trashbinFolder = $this->root->get('/'.$uid.'/files_trashbin');
			} catch (NotFoundException $e) {
				$trashbinFolder = $this->root->newFolder('/'.$uid.'/files_trashbin');
			}
			$output->writeln("Importing trashbin files…");
			if ($importSource->copyToFolder($trashbinFolder, static::PATH_FILES_FOLDER) === false) {
				throw new UserMigrationException("Could not import trashbin.");
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
			$output->writeln("No trashbin to import…");
		}
	}
}
