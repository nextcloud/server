<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Command;

use OCP\IUser;

trait FileAccess {
	protected function setupFS(IUser $user) {
		\OC_Util::setupFS($user->getUID());
	}

	protected function getUserFolder(IUser $user) {
		$this->setupFS($user);
		return \OC::$server->getUserFolder($user->getUID());
	}
}
