<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Trashbin\BackgroundJob;

use OC\Files\SetupManager;
use OC\Files\View;
use OCA\Files_Trashbin\Expiration;
use OCA\Files_Trashbin\Helper;
use OCA\Files_Trashbin\Trashbin;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class ExpireTrash extends TimedJob {
	private const THIRTY_MINUTES = 30 * 60;

	public function __construct(
		private IAppConfig $appConfig,
		private IUserManager $userManager,
		private Expiration $expiration,
		private LoggerInterface $logger,
		private SetupManager $setupManager,
		ITimeFactory $time
	) {
		parent::__construct($time);
		$this->setInterval(self::THIRTY_MINUTES);
	}

	protected function run($argument) {
		$backgroundJob = $this->appConfig->getValueString('files_trashbin', 'background_job_expire_trash', 'yes');
		if ($backgroundJob === 'no') {
			return;
		}

		$maxAge = $this->expiration->getMaxAgeAsTimestamp();
		if (!$maxAge) {
			return;
		}

		$stopTime = time() + self::THIRTY_MINUTES;

		do {
			$this->appConfig->clearCache();
			$offset = $this->appConfig->getValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
			$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', $offset + 10);

			$users = $this->userManager->getSeenUsers($offset, 10);
			$count = 0;

			foreach ($users as $user) {
				$uid = $user->getUID();
				$count++;

				try {
					if ($this->setupFS($user)) {
						$dirContent = Helper::getTrashFiles('/', $uid, 'mtime');
						Trashbin::deleteExpiredFiles($dirContent, $uid);
					}
				} catch (\Throwable $e) {
					$this->logger->error('Error while expiring trashbin for user ' . $uid, ['exception' => $e]);
				} finally {
					$this->setupManager->tearDown();
				}
			}

		} while (time() < $stopTime && $count === 10);

		if ($count < 10) {
			$this->appConfig->setValueInt('files_trashbin', 'background_job_expire_trash_offset', 0);
		}
	}

	/**
	 * Act on behalf on trash item owner
	 */
	protected function setupFS(IUser $user): bool {
		$this->setupManager->setupForUser($user);

		// Check if this user has a trashbin directory
		$view = new View('/' . $user->getUID());
		if (!$view->is_dir('/files_trashbin/files')) {
			return false;
		}

		return true;
	}
}
