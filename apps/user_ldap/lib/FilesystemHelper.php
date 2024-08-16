<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP;

/**
 * @brief wraps around static Nextcloud core methods
 */
class FilesystemHelper {

	/**
	 * @brief states whether the filesystem was loaded
	 * @return bool
	 */
	public function isLoaded() {
		return \OC\Files\Filesystem::$loaded;
	}

	/**
	 * @brief initializes the filesystem for the given user
	 * @param string $uid the Nextcloud username of the user
	 */
	public function setup($uid) {
		\OC_Util::setupFS($uid);
	}
}
