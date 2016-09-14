<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Repair\NC11;

use OC\BackgroundJob\QueuedJob;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;

class MoveAvatarsBackgroundJob extends QueuedJob {

	/** @var IUserManager */
	private $userManager;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IAppData */
	private $appData;

	/** @var ILogger */
	private $logger;

	/**
	 * MoveAvatars constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param ILogger $logger
	 */
	public function __construct(IUserManager $userManager,
								IRootFolder $rootFolder,
								ILogger $logger) {
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->logger = $logger;
		$this->appData = \OC::$server->getAppDataDir('avatar');
	}

	public function run($arguments) {
		$this->logger->info('Started migrating avatars to AppData folder');
		$this->moveAvatars();
		$this->logger->info('All avatars migrated to AppData folder');
	}

	private function moveAvatars() {
		$counter = 0;
		$this->userManager->callForAllUsers(function (IUser $user) use ($counter) {
			if ($user->getLastLogin() !== 0) {
				$uid = $user->getUID();

				\OC\Files\Filesystem::initMountPoints($uid);
				/** @var Folder $userFolder */
				$userFolder = $this->rootFolder->get($uid);

				try {
					$userData = $this->appData->getFolder($uid);
				} catch (NotFoundException $e) {
					$userData = $this->appData->newFolder($uid);
				}


				$regex = '/^avatar\.([0-9]+\.)?(jpg|png)$/';
				$avatars = $userFolder->getDirectoryListing();

				foreach ($avatars as $avatar) {
					/** @var File $avatar */
					if (preg_match($regex, $avatar->getName())) {
						/*
						 * This is not the most effective but it is the most abstract way
						 * to handle this. Avatars should be small anyways.
						 */
						$newAvatar = $userData->newFile($avatar->getName());
						$newAvatar->putContent($avatar->getContent());
						$avatar->delete();
					}
				}
			}
			$counter++;
			if ($counter % 100) {
				$this->logger->info('{amount} avatars migrated', ['amount' => $counter]);
			}
		});
	}
}
