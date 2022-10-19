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
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IDBConnection;

class MigrateBackgroundImages extends QueuedJob {
	public const TIME_SENSITIVE = 0;

	protected const STAGE_PREPARE = 'prepare';
	protected const STAGE_EXECUTE = 'execute';

	private IConfig $config;
	private IAppManager $appManager;
	private IAppDataFactory $appDataFactory;
	private IJobList $jobList;
	private IDBConnection $dbc;

	public function __construct(
		ITimeFactory $time,
		IAppDataFactory $appDataFactory,
		IConfig $config,
		IAppManager $appManager,
		IJobList $jobList,
		IDBConnection $dbc
	) {
		parent::__construct($time);
		$this->config = $config;
		$this->appManager = $appManager;
		$this->appDataFactory = $appDataFactory;
		$this->jobList = $jobList;
		$this->dbc = $dbc;
	}

	protected function run($argument): void {
		if (!isset($argument['stage'])) {
			// not executed in 25.0.0?!
			$argument['stage'] = 'prepare';
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
				->andWhere($selector->expr()->eq('configvalue', $selector->createNamedParameter('custom')))
				->executeQuery();

			$userIds = $result->fetchAll(\PDO::FETCH_COLUMN);
			$this->storeUserIdsToProcess($userIds);
		} catch (\Throwable $t) {
			$this->jobList->add(self::class, self::STAGE_PREPARE);
			throw $t;
		}
		$this->jobList->add(self::class, self::STAGE_EXECUTE);
	}

	protected function runMigration(): void {
		$storedUserIds = $this->config->getAppValue('theming', '25_background_image_migration');
		if ($storedUserIds === '') {
			return;
		}
		$allUserIds = \json_decode(\gzuncompress($storedUserIds), true);
		$notSoFastMode = isset($allUserIds[5000]);
		$dashboardData = $this->appDataFactory->get('dashboard');

		$userIds = $notSoFastMode ? array_slice($allUserIds, 0, 5000) : $allUserIds;
		foreach ($userIds as $userId) {
			try {
				// precondition
				if (!$this->appManager->isEnabledForUser('dashboard', $userId)) {
					continue;
				}

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
			$this->config->deleteAppValue('theming', '25_background_image_migration');
		}
	}

	protected function storeUserIdsToProcess(array $userIds): void {
		$storableUserIds = \gzcompress(\json_encode($userIds), 9);
		$this->config->setAppValue('theming', '25_background_image_migration', $storableUserIds);
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
