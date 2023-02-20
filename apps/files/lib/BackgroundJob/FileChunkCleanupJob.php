<?php
declare(strict_types=1);

/**
 * @copyright 2023 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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
namespace OCA\Files\BackgroundJob;

use OC\Cache\File;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\Job;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class FileChunkCleanupJob extends TimedJob {
	private IUserManager $userManager;
	private IRootFolder $rootFolder;
	private LoggerInterface $logger;

	public function __construct(IUserManager $userManager, IRootFolder $rootFolder, LoggerInterface $logger, ITimeFactory $timeFactory) {
		parent::__construct($timeFactory);
		$this->setInterval(3600*24);
		$this->setTimeSensitivity(Job::TIME_INSENSITIVE);
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->logger = $logger;
	}

	/**
	 * This job cleans up all backups except the latest 3 from the updaters backup directory
	 */
	public function run($argument): void {
		$this->userManager->callForSeenUsers(function (IUser $user): void {
			$this->logger->debug('Running chunk cleanup job for user '. $user->getUID());
			$fileCache = new File();
			$fileCache->setUpStorage($user->getUID());
			$fileCache->gc();
			$this->logger->debug('Finished running chunk cleanup job for user '. $user->getUID());
		});
	}
}
