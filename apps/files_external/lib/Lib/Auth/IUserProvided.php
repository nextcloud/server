<?php
/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth;

use OCP\IUser;

/**
 * For auth mechanisms where the user needs to provide credentials
 */
interface IUserProvided {
	/**
	 * @param IUser $user the user for which to save the user provided options
	 * @param int $mountId the mount id to save the options for
	 * @param array $options the user provided options
	 */
	public function saveBackendOptions(IUser $user, $mountId, array $options);
}
