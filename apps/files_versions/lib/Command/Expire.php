<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OCA\Files_Versions\Command;

use OC\Command\FileAccess;
use OCA\Files_Versions\Storage;
use OCP\Command\ICommand;
use OCP\Files\StorageNotAvailableException;
use OCP\IUserManager;
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
		$userManager = \OC::$server->get(IUserManager::class);
		if (!$userManager->userExists($this->user)) {
			// User has been deleted already
			return;
		}

		try {
			Storage::expire($this->fileName, $this->user);
		} catch (StorageNotAvailableException $e) {
			// In case of external storage and session credentials, the expiration
			// fails because the command does not have those credentials

			$logger = \OC::$server->get(LoggerInterface::class);
			$logger->warning($e->getMessage(), [
				'exception' => $e,
				'uid' => $this->user,
				'fileName' => $this->fileName,
			]);
		}
	}
}
