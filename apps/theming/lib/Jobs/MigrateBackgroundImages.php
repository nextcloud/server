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

class MigrateBackgroundImages extends QueuedJob {
	public const TIME_SENSITIVE = 0;

	private IConfig $config;
	private IAppManager $appManager;
	private IAppDataFactory $appDataFactory;
	private IJobList $jobList;

	public function __construct(ITimeFactory $time, IAppDataFactory $appDataFactory, IConfig $config, IAppManager $appManager, IJobList $jobList) {
		parent::__construct($time);
		$this->config = $config;
		$this->appManager = $appManager;
		$this->appDataFactory = $appDataFactory;
		$this->jobList = $jobList;
	}

	protected function run($argument): void {
		if (!$this->appManager->isEnabledForUser('dashboard')) {
			return;
		}

		$dashboardData = $this->appDataFactory->get('dashboard');

		$userIds = $this->config->getUsersForUserValue('theming', 'background', 'custom');

		$notSoFastMode = \count($userIds) > 5000;
		$reTrigger = false;
		$processed = 0;

		foreach ($userIds as $userId) {
			try {
				// precondition
				if ($notSoFastMode) {
					if ($this->config->getUserValue($userId, 'theming', 'background-migrated', '0') === '1') {
						// already migrated
						continue;
					}
					$reTrigger = true;
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
			// capture state
			if ($notSoFastMode) {
				$this->config->setUserValue($userId, 'theming', 'background-migrated', '1');
				$processed++;
			}
			if ($processed > 4999) {
				break;
			}
		}

		if ($reTrigger) {
			$this->jobList->add(self::class);
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
