<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Versions\Command;

use OC\Command\FileAccess;
use OCA\Files_Versions\Storage;
use OCP\Command\ICommand;
use OCP\Files\StorageNotAvailableException;
use OCP\IUserManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

class Expire implements ICommand {
	use FileAccess;

	public function __construct(
		private string $user,
		private string $fileName,
	) {
	}

	public function handle(): void {
		/** @var IUserManager $userManager */
		$userManager = Server::get(IUserManager::class);
		if (!$userManager->userExists($this->user)) {
			// User has been deleted already
			return;
		}

		try {
			Storage::expire($this->fileName, $this->user);
		} catch (StorageNotAvailableException $e) {
			// In case of external storage and session credentials, the expiration
			// fails because the command does not have those credentials

			$logger = Server::get(LoggerInterface::class);
			$logger->warning($e->getMessage(), [
				'exception' => $e,
				'uid' => $this->user,
				'fileName' => $this->fileName,
			]);
		}
	}
}
