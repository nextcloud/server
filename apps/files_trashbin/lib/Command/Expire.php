<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin\Command;

use OC\Command\FileAccess;
use OC\Files\SetupManager;
use OCA\Encryption\Users\Setup;
use OCA\Files_Trashbin\Service\ExpireService;
use OCP\Command\ICommand;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class Expire implements ICommand {
	use FileAccess;

	public function __construct(
		readonly private string $user,
	) {
	}

	public function handle(): void {
		try {
			$user = Server::get(IUserManager::class)->get($this->user);
			if (!$user) {
				return;
			}
			Server::get(ExpireService::class)->expireTrashForUser($user);
			Server::get(SetupManager::class)->execute();
		} catch (\Throwable $e) {
			Server::get(LoggerInterface::class)->error('Error while expiring trashbin for user ' . $this->user, ['exception' => $e]);
		}
	}
}
