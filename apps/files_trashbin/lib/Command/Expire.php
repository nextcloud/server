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
use OCP\IUserManager;
use OCP\Server;
use Override;
use Psr\Log\LoggerInterface;

class Expire implements ICommand {
	use FileAccess;

	public function __construct(
		private readonly string $userId,
	) {
	}

	#[Override]
	public function handle(): void {
		// can't use DI because Expire needs to be serializable
		$userManager = Server::get(IUserManager::class);
		$user = $userManager->get($this->userId);
		if (!$user) {
			// User has been deleted already
			return;
		}

		try {
			$setupManager = Server::get(SetupManager::class);
			$setupManager->tearDown();
			$setupManager->setupForUser($user);

			$trashRoot = Server::get(IRootFolder::class)->getUserFolder($user->getUID())->getParent()->get('files_trashbin');
			if (!$trashRoot instanceof Folder) {
				throw new \LogicException("Didn't expect files_trashbin to be a file instead of a folder");
			}
			Trashbin::expire($trashRoot, $user);
		} catch (\Exception $e) {
			Server::get(LoggerInterface::class)->error('Error while expiring trashbin for user ' . $user->getUID(), ['exception' => $e]);
		} finally {
			$setupManager->tearDown();
		}
	}
}
