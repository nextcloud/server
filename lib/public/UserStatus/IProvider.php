<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\UserStatus;

/**
 * Interface IProvider
 *
 * @since 20.0.0
 */
interface IProvider {
	/**
	 * Gets the statuses for all users in $users
	 *
	 * @param string[] $userIds
	 * @return array<string, IUserStatus> array key being the userid, users without a status will not be in the returned array
	 * @since 20.0.0
	 */
	public function getUserStatuses(array $userIds):array;
}
