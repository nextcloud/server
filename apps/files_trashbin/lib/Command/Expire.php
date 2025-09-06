<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Command;

use OC\Command\FileAccess;
use OC\Files\SetupManager;
use OCA\Files_Trashbin\Trashbin;
use OCP\Command\ICommand;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;

class Expire implements ICommand {
	use FileAccess;

	/**
	 * @param string $user
	 */
	public function __construct(
		private $user,
	) {
	}

	public function handle() {
		$userManager = Server::get(IUserManager::class);
		$userObject = $userManager->get($this->user);
		if (!$userObject) {
			// User has been deleted already
			return;
		}

		$rootFolder = $this->getTrashRoot($userObject);
		if (!$rootFolder) {
			return;
		}

		Trashbin::expire($rootFolder, $userObject);
		$setupManager = Server::get(SetupManager::class);
		$setupManager->tearDown();
	}

	protected function getTrashRoot(IUser $user): ?Folder {
		$setupManager = Server::get(SetupManager::class);
		$rootFolder = Server::get(IRootFolder::class);
		$setupManager->tearDown();
		$setupManager->setupForUser($user);

		try {
			/** @var Folder $folder */
			$folder = $rootFolder->getUserFolder($user->getUID())->getParent()->get('files_trashbin');
			return $folder;
		} catch (NotFoundException|NotPermittedException) {
			$setupManager->tearDown();
			return null;
		}
	}
}
