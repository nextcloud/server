<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Backend;

/**
 * @since 24.0.7
 */
interface ICountMappedUsersBackend {
	/**
	 * @since 24.0.7
	 *
	 * @return int The number of users already mapped to a Nextcloud account
	 */
	public function countMappedUsers(): int;
}
