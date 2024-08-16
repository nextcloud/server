<?php
/**
 * SPDX-FileCopyrightText: 2021-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin;

class Hooks {

	/**
	 * clean up user specific settings if user gets deleted
	 * @param array $params array with uid
	 *
	 * This function is connected to the pre_deleteUser signal of OC_Users
	 * to remove the used space for the trash bin stored in the database
	 */
	public static function deleteUser_hook($params) {
		$uid = $params['uid'];
		Trashbin::deleteUser($uid);
	}

	public static function post_write_hook($params) {
		$user = \OC_User::getUser();
		if (!empty($user)) {
			Trashbin::resizeTrash($user);
		}
	}
}
