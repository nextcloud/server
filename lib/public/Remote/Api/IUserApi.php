<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Remote\Api;

use OCP\Remote\IUser;

/**
 * @since 13.0.0
 * @deprecated 23.0.0
 */
interface IUserApi {
	/**
	 * @param string $userId
	 * @return IUser
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getUser($userId);
}
