<?php

declare(strict_types=1);

/**
 * @copyright 2020 Morris Jobke <hey@morrisjobke.de>
 *
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OC\Core\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;

class CheckForUserCertificates extends QueuedJob {
	public function __construct(
		protected IConfig $config,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
		ITimeFactory $time,
	) {
		parent::__construct($time);
	}

	/**
	 * Checks all user directories for old user uploaded certificates
	 */
	public function run($arguments): void {
		$uploadList = [];
		$this->userManager->callForSeenUsers(function (IUser $user) use (&$uploadList) {
			$userId = $user->getUID();
			try {
				\OC_Util::setupFS($userId);
				$filesExternalUploadsFolder = $this->rootFolder->get($userId . '/files_external/uploads');
			} catch (NotFoundException $e) {
				\OC_Util::tearDownFS();
				return;
			}
			if ($filesExternalUploadsFolder instanceof Folder) {
				$files = $filesExternalUploadsFolder->getDirectoryListing();
				foreach ($files as $file) {
					$filename = $file->getName();
					$uploadList[] = "$userId/files_external/uploads/$filename";
				}
			}
			\OC_Util::tearDownFS();
		});

		if (empty($uploadList)) {
			$this->config->deleteAppValue('files_external', 'user_certificate_scan');
		} else {
			$this->config->setAppValue('files_external', 'user_certificate_scan', json_encode($uploadList));
		}
	}
}
