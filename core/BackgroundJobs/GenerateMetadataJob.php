<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IConfig;
use OCP\IUserManager;

class GenerateMetadataJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
		private IRootFolder $rootFolder,
		private IUserManager $userManager,
		private IFilesMetadataManager $filesMetadataManager,
		private IJobList $jobList,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_INSENSITIVE);
		$this->setInterval(24 * 3600);
	}

	protected function run(mixed $argument): void {
		$users = $this->userManager->search('');
		$lastMappedUser = $this->config->getAppValue('core', 'metadataGenerationLastHandledUser', '');

		if ($lastMappedUser === '') {
			$user = array_key_first($users);
			if ($user === null) {
				return;
			}

			$lastMappedUser = $users[$user]->getUID();
		}

		$startTime = null;
		foreach ($users as $user) {
			if ($startTime === null) {
				// Skip all user before lastMappedUser.
				if ($lastMappedUser !== $user->getUID()) {
					continue;
				}

				$startTime = time();
			}

			// Stop if execution time is more than one hour.
			if (time() - $startTime > 60 * 60) {
				return;
			}

			$this->scanFilesForUser($user->getUID());
			$this->config->setAppValue('core', 'metadataGenerationLastHandledUser', $user->getUID());
		}

		$this->jobList->remove(GenerateMetadataJob::class);
	}

	private function scanFilesForUser(string $userId): void {
		$userFolder = $this->rootFolder->getUserFolder($userId);
		$this->scanFolder($userFolder);
	}

	private function scanFolder(Folder $folder): void {
		// Do not scan share and other moveable mounts.
		if ($folder->getMountPoint() instanceof \OC\Files\Mount\MoveableMount) {
			return;
		}

		foreach ($folder->getDirectoryListing() as $node) {
			if ($node instanceof Folder) {
				$this->scanFolder($node);
				continue;
			}

			$this->filesMetadataManager->refreshMetadata(
				$node,
				IFilesMetadataManager::PROCESS_LIVE | IFilesMetadataManager::PROCESS_BACKGROUND
			);
		}
	}
}
