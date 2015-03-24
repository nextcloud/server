<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Command;

use OCP\IUser;

trait FileAccess {
	protected function setupFS(IUser $user){
		\OC_Util::setupFS($user->getUID());
	}

	protected function getUserFolder(IUser $user) {
		$this->setupFS($user);
		return \OC::$server->getUserFolder($user->getUID());
	}
}
