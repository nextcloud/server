<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Command;

use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\IUser;
use OCP\Server;

trait FileAccess {
	protected function setupFS(IUser $user): void {
		Server::get(ISetupManager::class)->setupForUser($user);
	}

	protected function getUserFolder(IUser $user): void {
		$this->setupFS($user);
		Server::get(IRootFolder::class)->getUserFolder($user->getUID());
	}
}
