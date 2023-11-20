<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Theming\Jobs;

use OCA\Theming\AppInfo\Application;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class MigrateBackgroundImages extends QueuedJob {
	public const TIME_SENSITIVE = 0;

	public const STAGE_PREPARE = 'prepare';
	public const STAGE_EXECUTE = 'execute';
	// will be saved in appdata/theming/global/
	protected const STATE_FILE_NAME = '25_dashboard_to_theming_migration_users.json';

	private IAppDataFactory $appDataFactory;
	private IJobList $jobList;
	private IDBConnection $dbc;
	private IAppData $appData;
	private LoggerInterface $logger;

	public function __construct(
		ITimeFactory $time,
		IAppDataFactory $appDataFactory,
		IJobList $jobList,
		IDBConnection $dbc,
		IAppData $appData,
		LoggerInterface $logger
	) {
		parent::__construct($time);
		$this->appDataFactory = $appDataFactory;
		$this->jobList = $jobList;
		$this->dbc = $dbc;
		$this->appData = $appData;
		$this->logger = $logger;
	}

	protected function run(mixed $argument): void {
		if (!is_array($argument) || !isset($argument['stage'])) {
			throw new \Exception('Job '.self::class.' called with wrong argument');
		}

		switch ($argument['stage']) {
			case self::STAGE_PREPARE:
				$this->runPreparation();
				break;
			case self::STAGE_EXECUTE:
				$this->runMigration();
				break;
			default:
				break;
		}
	}

	protected function runPreparation(): void {
		try {
			$selector = $this->dbc->getQueryBuilder();
			$result = $selector->select('userid')
				->from('preferences')
				->where($selector->expr()->eq('appid', $selector->createNamedParameter('theming')))
				->andWhere($selector->expr()->eq('configkey', $selector->createNamedParameter('background')))
				->andWhere($selector->expr()->eq('configvalue', $selector->createNamedParameter('custom', IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR))
				->executeQuery();

			$userIds = $result->fetchAll(\PDO::FETCH_COLUMN);
			$this->storeUserIdsToProcess($userIds);
		} catch (\Throwable $t) {
			$this->jobList->add(self::class, ['stage' => self::STAGE_PREPARE]);
			throw $t;
		}
		$this->jobList->add(self::class, ['stage' => self::STAGE_EXECUTE]);
	}

	/**
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	protected function runMigration(): void {
		$allUserIds = $this->readUserIdsToProcess();
		$notSoFastMode = count($allUserIds) > 5000;
		$dashboardData = $this->appDataFactory->get('dashboard');

		$userIds = $notSoFastMode ? array_slice($allUserIds, 0, 5000) : $allUserIds;
		foreach ($userIds as $userId) {
			try {
				// migration
				$file = $dashboardData->getFolder($userId)->getFile('background.jpg');
				$targetDir = $this->getUserFolder($userId);

				if (!$targetDir->fileExists('background.jpg')) {
					$targetDir->newFile('background.jpg', $file->getContent());
				}
				$file->delete();
			} catch (NotFoundException|NotPermittedException $e) {
			}
		}

		if ($notSoFastMode) {
			$remainingUserIds = array_slice($allUserIds, 5000);
			$this->storeUserIdsToProcess($remainingUserIds);
			$this->jobList->add(self::class, ['stage' => self::STAGE_EXECUTE]);
		} else {
			$this->deleteStateFile();
		}
	}

	/**
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	protected function readUserIdsToProcess(): array {
		$globalFolder = $this->appData->getFolder('global');
		if ($globalFolder->fileExists(self::STATE_FILE_NAME)) {
			$file = $globalFolder->getFile(self::STATE_FILE_NAME);
			try {
				$userIds = \json_decode($file->getContent(), true);
			} catch (NotFoundException $e) {
				$userIds = [];
			}
			if ($userIds === null) {
				$userIds = [];
			}
		} else {
			$userIds = [];
		}
		return $userIds;
	}

	/**
	 * @throws NotFoundException
	 */
	protected function storeUserIdsToProcess(array $userIds): void {
		$storableUserIds = \json_encode($userIds);
		$globalFolder = $this->appData->getFolder('global');
		try {
			if ($globalFolder->fileExists(self::STATE_FILE_NAME)) {
				$file = $globalFolder->getFile(self::STATE_FILE_NAME);
			} else {
				$file = $globalFolder->newFile(self::STATE_FILE_NAME);
			}
			$file->putContent($storableUserIds);
		} catch (NotFoundException $e) {
		} catch (NotPermittedException $e) {
			$this->logger->warning('Lacking permissions to create {file}',
				[
					'app' => 'theming',
					'file' => self::STATE_FILE_NAME,
					'exception' => $e,
				]
			);
		}
	}

	/**
	 * @throws NotFoundException
	 */
	protected function deleteStateFile(): void {
		$globalFolder = $this->appData->getFolder('global');
		if ($globalFolder->fileExists(self::STATE_FILE_NAME)) {
			$file = $globalFolder->getFile(self::STATE_FILE_NAME);
			try {
				$file->delete();
			} catch (NotPermittedException $e) {
				$this->logger->info('Could not delete {file} due to permissions. It is safe to delete manually inside data -> appdata -> theming -> global.',
					[
						'app' => 'theming',
						'file' => $file->getName(),
						'exception' => $e,
					]
				);
			}
		}
	}

	/**
	 * Get the root location for users theming data
	 */
	protected function getUserFolder(string $userId): ISimpleFolder {
		$themingData = $this->appDataFactory->get(Application::APP_ID);

		try {
			$rootFolder = $themingData->getFolder('users');
		} catch (NotFoundException $e) {
			$rootFolder = $themingData->newFolder('users');
		}

		try {
			return $rootFolder->getFolder($userId);
		} catch (NotFoundException $e) {
			return $rootFolder->newFolder($userId);
		}
	}
}
