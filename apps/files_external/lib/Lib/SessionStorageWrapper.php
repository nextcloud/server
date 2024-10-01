<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

use OC\Files\Storage\Wrapper\PermissionsMask;
use OCP\Constants;

/**
 * Wrap Storage in PermissionsMask for session ephemeral use
 */
class SessionStorageWrapper extends PermissionsMask {

	/**
	 * @param array $arguments ['storage' => $storage]
	 */
	public function __construct($arguments) {
		// disable sharing permission
		$arguments['mask'] = Constants::PERMISSION_ALL & ~Constants::PERMISSION_SHARE;
		parent::__construct($arguments);
	}
}
