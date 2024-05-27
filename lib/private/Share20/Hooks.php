<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share20;

use OCP\Share\IManager as IShareManager;

class Hooks {
	public static function post_deleteUser($arguments) {
		\OC::$server->get(IShareManager::class)->userDeleted($arguments['uid']);
	}

	public static function post_deleteGroup($arguments) {
		\OC::$server->get(IShareManager::class)->groupDeleted($arguments['gid']);
	}
}
