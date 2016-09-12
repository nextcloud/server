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

use OC\SystemConfig;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class MoveAvatars implements IRepairStep {

	/** @var IUserManager */
	private $userManager;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IAppData */
	private $appData;

	/** @var SystemConfig */
	private $systemConfig;

	/**
	 * MoveAvatars constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param IAppData $appData
	 * @param SystemConfig $systemConfig
	 */
	public function __construct(IUserManager $userManager,
								IRootFolder $rootFolder,
								IAppData $appData,
								SystemConfig $systemConfig) {
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->appData = $appData;
		$this->systemConfig = $systemConfig;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Move avatars to AppData folder';
	}

	public function run(IOutput $output) {
		if ($this->systemConfig->getValue('enable_avatars', true) === false) {
			$output->info('Avatars are disabled');
		} else {
			$output->startProgress($this->userCount());
			$this->moveAvatar($output);
			$output->finishProgress();
		}
	}

	private function moveAvatar(IOutput $output) {
		$this->userManager->callForAllUsers(function (IUser $user) use ($output) {
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
			$output->advance();
		});
	}

	/**
	 * @return int
	 */
	private function userCount() {
		$backends = $this->userManager->countUsers();
		$count = 0;

		foreach ($backends as $backend => $amount) {
			$count += $amount;
		}

		return $count;
	}
}
